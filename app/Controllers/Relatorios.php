<?php

namespace App\Controllers;

use App\Models\AtendimentoModel;
use App\Models\AvaliacaoRiscoModel;
use App\Models\CompromissoModel;
use App\Models\ConfiguracaoListaModel;
use App\Models\ConfiguracaoModel;
use App\Models\EncaminhamentoModel;
use App\Models\InstituicaoModel;
use App\Models\ManifestacaoModel;
use App\Models\MedidaProtetivaModel;
use App\Libraries\PdfServico;
use App\Models\MulherModel;
use App\Models\ProcessoModel;
use App\Models\UserModel;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;
use DateTime;

/**
 * Módulo RELATÓRIOS — central de análise com dados REAIS do banco.
 *
 * Todos os valores provêm de consultas agregadas (COUNT/GROUP BY/SUM) sobre as
 * tabelas existentes — sem dados fictícios e sem tabelas novas. Os gráficos
 * são renderizados em HTML/CSS puro (sem biblioteca externa), a exportação
 * gera um PDF institucional via Dompdf e a exportação CSV legada continua
 * disponível em /relatorios/exportar.
 *
 * Os filtros são aplicados por domínio: filtros de atendimento (situação,
 * prioridade, forma, origem, município), filtros de encaminhamento (órgão,
 * instituição), responsável/profissional, período e faixa etária. Filtros que
 * não fazem sentido para um bloco específico são ignorados por ele (a interface
 * indica quais filtros cada seção considera).
 */
class Relatorios extends BaseController
{
    protected BaseConnection $db;

    /**
     * Classificações de risco reutilizadas (acolhimentos e avaliações).
     *
     * @var array<string, string>
     */
    public const RISCOS = [
        'baixo'   => 'Baixo',
        'medio'   => 'Médio',
        'alto'    => 'Alto',
        'extremo' => 'Extremo',
    ];

    /**
     * Fatores das avaliações de risco (violência) — fonte: PerguntasRisco.php.
     *
     * @var array<string, string>
     */
    public const FATORES_VIOLENCIA = [
        'ameaca_morte'           => 'Ameaça de morte',
        'agressao_fisica'        => 'Agressão física',
        'estrangulamento'        => 'Estrangulamento',
        'agressor_arma'          => 'Agressor com arma',
        'ameaca_com_arma'        => 'Ameaça com arma',
        'violencia_aumentando'   => 'Violência em aumento',
        'tentou_separar'         => 'Tentativa de separação',
        'controlador'            => 'Controle/ciúmes',
        'perseguicao'            => 'Perseguição (stalking)',
        'criancas_envolvidas'    => 'Crianças envolvidas',
        'dependencia_financeira' => 'Dependência financeira',
        'local_seguro'           => 'Sem local seguro',
        'descumpriu_medida'      => 'Descumprimento de medida',
    ];

    /**
     * Faixas etárias (idade em anos completos). [mínimo, máximo|null, rótulo]
     *
     * @var array<string, array{int, int|null, string}>
     */
    public const FAIXAS_ETARIAS = [
        'menor_18' => [0, 17, 'Até 17 anos'],
        '18_29'    => [18, 29, '18 a 29 anos'],
        '30_39'    => [30, 39, '30 a 39 anos'],
        '40_49'    => [40, 49, '40 a 49 anos'],
        '50_59'    => [50, 59, '50 a 59 anos'],
        '60_mais'  => [60, null, '60 anos ou mais'],
    ];

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Garante acesso apenas à equipe da Procuradoria (admin/funcionário).
     */
    private function somenteEquipe()
    {
        if (! in_array((string) session()->get('perfil'), ['admin', 'funcionario'], true)) {
            return redirect()->to('/dashboard')->with('erro', 'Acesso restrito à equipe da Procuradoria.');
        }

        return null;
    }

    private function equipeAtiva(): array
    {
        return (new UserModel())
            ->whereIn('perfil', ['admin', 'funcionario'])
            ->where('ativo', 1)
            ->orderBy('nome', 'ASC')
            ->findAll();
    }

    /**
     * Página principal dos relatórios.
     */
    public function index()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $filtros = $this->filtros();

        $dados = [
            'titulo'                => 'Relatórios',
            'filtros'               => $filtros,
            'equipe'                => $this->equipeAtiva(),
            'instituicoes'          => $this->instituicoesAtivas(),
            'municipios'            => $this->municipiosDisponiveis(),
            'prioridades'           => ConfiguracaoListaModel::todasCategoria('prioridade'),
            'situacoesAtend'        => ConfiguracaoListaModel::todasCategoria('status_atendimento'),
            'formas'                => ConfiguracaoListaModel::todasCategoria('tipo_atendimento'),
            'origensAtend'          => ConfiguracaoListaModel::todasCategoria('origem_atendimento'),
            'orgaos'                => ConfiguracaoListaModel::todasCategoria('orgao'),
            'statusEnc'             => ConfiguracaoListaModel::todasCategoria('status_encaminhamento'),
            'riscos'                => self::RISCOS,
            'fatores'               => self::FATORES_VIOLENCIA,
            'faixas'                => self::FAIXAS_ETARIAS,
            'situacoesMedida'       => ConfiguracaoListaModel::todasCategoria('situacao_medida'),
            'situacoesProcesso'     => ConfiguracaoListaModel::todasCategoria('situacao_processo'),
            'situacoesManifest'     => ConfiguracaoListaModel::todasCategoria('situacao_manifestacao'),
            'tiposManifest'         => ManifestacaoModel::TIPOS,
        ];

        $dados = array_merge($dados, $this->dados($filtros));

        return view('relatorios/index', $dados);
    }

    /**
     * Exporta os resumos agregados (mesmos filtros) em CSV nativo.
     */
    public function exportar()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $filtros = $this->filtros();
        $dados   = $this->dados($filtros);

        $out = fopen('php://temp', 'w');

        fputcsv($out, ['Relatórios — Procuradoria da Mulher; período ' . $dados['periodo']['de'] . ' a ' . $dados['periodo']['ate']], ',', '"', '');

        fputcsv($out, [], ',', '"', '');
        fputcsv($out, ['Indicadores principais'], ',', '"', '');
        fputcsv($out, array_keys($dados['indicadores']), ',', '"', '');
        fputcsv($out, array_map('strval', $dados['indicadores']), ',', '"', '');

        $this->tabelaCsv($out, 'Atendimentos por mês', ['Mês', 'Atendimentos'], $dados['atendimentos_por_mes']);
        $this->tabelaCsv($out, 'Atendimentos por situação', ['Situação', 'Total'], $dados['atendimentos_por_situacao']);
        $this->tabelaCsv($out, 'Atendimentos por prioridade', ['Prioridade', 'Total'], $dados['atendimentos_por_prioridade']);
        $this->tabelaCsv($out, 'Atendimentos por forma', ['Forma', 'Total'], $dados['atendimentos_por_forma']);
        $this->tabelaCsv($out, 'Atendimentos por origem', ['Origem', 'Total'], $dados['atendimentos_por_origem']);
        $this->tabelaCsv($out, 'Atendimentos por município', ['Município', 'Total'], $dados['atendimentos_por_municipio']);
        $this->tabelaCsv($out, 'Faixa etária das mulheres atendidas', ['Faixa', 'Mulheres'], $dados['faixa_etaria']);
        $this->tabelaCsv($out, 'Avaliações de risco por classificação', ['Classificação', 'Total'], $dados['risco_por_classificacao']);
        $this->tabelaCsv($out, 'Fatores de risco identificados', ['Fator', 'Total'], $dados['risco_fatores']);
        $this->tabelaCsv($out, 'Avaliações de risco por mês', ['Mês', 'Avaliações'], $dados['risco_por_mes']);
        $this->tabelaCsv($out, 'Encaminhamentos por órgão', ['Órgão', 'Total'], $dados['encaminhamentos_por_orgao']);
        $this->tabelaCsv($out, 'Encaminhamentos por situação', ['Situação', 'Total'], $dados['encaminhamentos_por_situacao']);
        $this->tabelaCsv($out, 'Encaminhamentos por instituição', ['Instituição', 'Total'], $dados['encaminhamentos_por_instituicao']);
        $this->tabelaCsv($out, 'Medidas protetivas por situação', ['Situação', 'Total'], $dados['medidas_por_situacao']);
        $this->tabelaCsv($out, 'Processos por situação', ['Situação', 'Total'], $dados['processos_por_situacao']);
        $this->tabelaCsv($out, 'Manifestações por situação', ['Situação', 'Total'], $dados['manifestacoes_por_situacao']);
        $this->tabelaCsv($out, 'Manifestações por tipo', ['Tipo', 'Total'], $dados['manifestacoes_por_tipo']);

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="relatorios_' . date('Ymd_His') . '.csv"')
            ->setBody("\xEF\xBB\xBF" . $csv);
    }

    /**
     * Gera o relatório em PDF (mesmos filtros e dados da tela).
     *
     * Produz um documento institucional com cabeçalho da Procuradoria, período
     * consultado, filtros aplicados, indicadores e tabelas/gráficos reais,
     * rodapé paginado com identificação do sistema e bloco de validação. O
     * arquivo é disponibilizado para visualização/impressão (inline).
     */
    public function exportarPdf()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $filtros = $this->filtros();
        $dados   = $this->dados($filtros);

        $configSistema = (new ConfiguracaoModel())->obterTodos();

        $nomesEquipe = [];
        foreach ($this->equipeAtiva() as $u) {
            $nomesEquipe[(int) $u['id']] = (string) $u['nome'];
        }
        $nomesInstituicoes = [];
        foreach ($this->instituicoesAtivas() as $inst) {
            $nomesInstituicoes[(int) $inst['id']] = (string) $inst['nome'];
        }

        $variaveis = [
            'titulo'            => 'Relatório Estatístico',
            'config'            => $configSistema,
            'filtros'           => $filtros,
            'dados'             => $dados,
            'nomesEquipe'       => $nomesEquipe,
            'nomesInstituicoes' => $nomesInstituicoes,
            'mapas'             => [
                'situacao'           => ConfiguracaoListaModel::todasCategoria('status_atendimento'),
                'prioridade'         => ConfiguracaoListaModel::todasCategoria('prioridade'),
                'forma'              => ConfiguracaoListaModel::todasCategoria('tipo_atendimento'),
                'origem'             => ConfiguracaoListaModel::todasCategoria('origem_atendimento'),
                'orgao'              => ConfiguracaoListaModel::todasCategoria('orgao'),
                'statusEnc'          => ConfiguracaoListaModel::todasCategoria('status_encaminhamento'),
                'riscos'             => self::RISCOS,
                'fatores'            => self::FATORES_VIOLENCIA,
                'situacaoMedida'     => ConfiguracaoListaModel::todasCategoria('situacao_medida'),
                'situacaoProcesso'   => ConfiguracaoListaModel::todasCategoria('situacao_processo'),
                'situacaoManifest'   => ConfiguracaoListaModel::todasCategoria('situacao_manifestacao'),
                'tipoManifest'       => ManifestacaoModel::TIPOS,
            ],
            'faixas'            => self::FAIXAS_ETARIAS,
            'fatores'           => self::FATORES_VIOLENCIA,
            'logo'              => (new PdfServico())->logoParaPdf(),
            'usuarioNome'       => (string) (session()->get('usuario_nome') ?? ''),
            'usuarioPerfil'     => (string) (session()->get('perfil') ?? ''),
            'geradoEm'          => date('d/m/Y \à\s H:i'),
        ];

        $html = view('relatorios/pdf', $variaveis);

        try {
            $pdf = (new PdfServico())->renderizar($html, $configSistema);

            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', 'inline; filename="relatorios_' . date('Ymd_His') . '.pdf"')
                ->setBody($pdf);
        } catch (\Throwable $e) {
            log_message('error', 'Falha ao gerar PDF de relatórios: ' . $e->getMessage());

            return redirect()->to('/relatorios')
                ->with('erro', 'Não foi possível gerar o relatório em PDF. Tente novamente.');
        }
    }

    /**
     * Normaliza e valida os filtros recebidos via GET.
     *
     * @return array<string, mixed>
     */
    private function filtros(): array
    {
        $dataDe  = trim((string) $this->request->getGet('data_de'));
        $dataAte = trim((string) $this->request->getGet('data_ate'));
        $rapido  = trim((string) $this->request->getGet('rapido'));

        if ($rapido !== '') {
            $pares = $this->periodoRapido($rapido);
            if ($pares !== null) {
                $dataDe  = $pares['de'];
                $dataAte = $pares['ate'];
            } else {
                $rapido = '';
            }
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataDe) !== 1) {
            $dataDe = '';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataAte) !== 1) {
            $dataAte = '';
        }

        if ($dataDe === '' && $dataAte === '') {
            $dataDe  = date('Y') . '-01-01';
            $dataAte = date('Y') . '-12-31';
        }
        if ($dataDe !== '' && $dataAte !== '' && $dataDe > $dataAte) {
            [$dataDe, $dataAte] = [$dataAte, $dataDe];
        }

        return [
            'data_de'         => $dataDe,
            'data_ate'        => $dataAte,
            'rapido'          => $rapido,
            'situacao'        => $this->texto('situacao', ConfiguracaoListaModel::todasCategoria('status_atendimento')),
            'prioridade'      => $this->texto('prioridade', ConfiguracaoListaModel::todasCategoria('prioridade')),
            'forma'           => $this->texto('forma', ConfiguracaoListaModel::todasCategoria('tipo_atendimento')),
            'origem'          => $this->texto('origem', ConfiguracaoListaModel::todasCategoria('origem_atendimento')),
            'municipio'       => trim((string) $this->request->getGet('municipio')),
            'profissional'    => (int) $this->request->getGet('profissional'),
            'instituicao'     => (int) $this->request->getGet('instituicao'),
            'orgao'           => $this->texto('orgao', ConfiguracaoListaModel::todasCategoria('orgao')),
            'faixa'           => array_key_exists((string) $this->request->getGet('faixa'), self::FAIXAS_ETARIAS)
                ? (string) $this->request->getGet('faixa')
                : '',
            'fator_violencia' => array_key_exists((string) $this->request->getGet('fator_violencia'), self::FATORES_VIOLENCIA)
                ? (string) $this->request->getGet('fator_violencia')
                : '',
        ];
    }

    /**
     * Valida um valor de select dentro das opções disponíveis.
     *
     * @param array<string, string> $opcoes
     */
    private function texto(string $campo, array $opcoes): string
    {
        $valor = (string) $this->request->getGet($campo);

        return array_key_exists($valor, $opcoes) ? $valor : '';
    }

    /**
     * Período de um atalho pré-definido, ou null se desconhecido.
     *
     * @return array{de: string, ate: string}|null
     */
    private function periodoRapido(string $chave): ?array
    {
        $hoje = date('Y-m-d');

        switch ($chave) {
            case 'hoje':
                return ['de' => $hoje, 'ate' => $hoje];

            case '7_dias':
                return ['de' => date('Y-m-d', strtotime('-6 days')), 'ate' => $hoje];

            case '30_dias':
                return ['de' => date('Y-m-d', strtotime('-29 days')), 'ate' => $hoje];

            case 'mes_atual':
                return ['de' => date('Y-m-01'), 'ate' => date('Y-m-t')];

            case 'mes_anterior':
                $ref = new DateTime('first day of last month');

                return ['de' => $ref->format('Y-m-01'), 'ate' => $ref->format('Y-m-t')];

            case 'ano_atual':
                return ['de' => date('Y') . '-01-01', 'ate' => date('Y') . '-12-31'];
        }

        return null;
    }

    /**
     * Computa todos os indicadores/gráficos/tabelas com os filtros aplicados.
     *
     * @param array<string, mixed> $filtros
     *
     * @return array<string, mixed>
     */
    private function dados(array $filtros): array
    {
        $idsFaixa = $this->mulheresIdsFaixa($filtros);

        $agregado = $this->agregadoAtendimentos($filtros, $idsFaixa);

        $indicadores = [
            'mulheres'              => $this->mulheresAtendidas($filtros, $idsFaixa),
            'atendimentos'          => $agregado['total'],
            'novos'                 => $agregado['novos'],
            'em_andamento'          => $agregado['em_andamento'],
            'encerrados'            => $agregado['encerrados'],
            'urgentes'              => $agregado['urgentes'],
            'riscos'                => $this->totalAvaliacoesRisco($filtros),
            'encaminhamentos'       => $this->totalEncaminhamentos($filtros),
            'encaminhamentos_pendentes' => $this->totalEncaminhamentos($filtros, true),
            'retornos'              => $this->totalCompromissos($filtros),
            'medidas'               => $this->totalMedidas($filtros),
            'processos'             => $this->totalGenerico('processos', 'created_at', $filtros),
            'manifestacoes'         => $this->totalGenerico('manifestacoes', 'data_hora', $filtros, function (BaseBuilder $b) use ($filtros): void {
                if ($filtros['prioridade'] !== '') {
                    $b->where('manifestacoes.prioridade', (string) $filtros['prioridade']);
                }
            }),
        ];

        return [
            'periodo'                       => ['de' => $filtros['data_de'], 'ate' => $filtros['data_ate']],
            'indicadores'                   => $indicadores,
            'atendimentos_por_mes'          => $this->serieMensal('atendimentos', 'data_hora', $filtros, $idsFaixa),
            'atendimentos_por_situacao'     => $this->distribuicaoAtendimentos('situacao', $filtros, $idsFaixa),
            'atendimentos_por_prioridade'   => $this->distribuicaoAtendimentos('prioridade', $filtros, $idsFaixa),
            'atendimentos_por_forma'        => $this->distribuicaoAtendimentos('forma_atendimento', $filtros, $idsFaixa),
            'atendimentos_por_origem'       => $this->distribuicaoAtendimentos('origem', $filtros, $idsFaixa),
            'atendimentos_por_municipio'    => $this->atendimentosPorMunicipio($filtros, $idsFaixa),
            'faixa_etaria'                  => $this->faixaEtaria($filtros, $idsFaixa),
            'risco_por_classificacao'       => $this->distribuicao('avaliacoes_risco', 'classificacao', function (BaseBuilder $b) use ($filtros): void {
                $this->filtrosAvaliacaoRisco($b, $filtros);
            }),
            'risco_fatores'                 => $this->riscoFatores($filtros),
            'risco_por_mes'                 => $this->riscoPorMes($filtros),
            'encaminhamentos_por_orgao'     => $this->distribuicao('encaminhamentos', 'orgao', function (BaseBuilder $b) use ($filtros): void {
                $this->filtrosEncaminhamento($b, $filtros);
            }),
            'encaminhamentos_por_situacao'  => $this->distribuicao('encaminhamentos', 'status', function (BaseBuilder $b) use ($filtros): void {
                $this->filtrosEncaminhamento($b, $filtros);
            }),
            'encaminhamentos_por_instituicao' => $this->encaminhamentosPorInstituicao($filtros),
            'medidas_por_situacao'          => $this->distribuicao('medidas_protetivas', 'situacao', function (BaseBuilder $b) use ($filtros): void {
                $this->filtrosMedidas($b, $filtros);
            }),
            'processos_por_situacao'        => $this->distribuicao('processos', 'situacao', function (BaseBuilder $b) use ($filtros): void {
                $this->aplicarPeriodo($b, 'processos.created_at', $filtros);
            }),
            'manifestacoes_por_situacao'    => $this->distribuicao('manifestacoes', 'situacao', function (BaseBuilder $b) use ($filtros): void {
                $this->aplicarPeriodo($b, 'manifestacoes.data_hora', $filtros);
                if ($filtros['prioridade'] !== '') {
                    $b->where('manifestacoes.prioridade', (string) $filtros['prioridade']);
                }
            }),
            'manifestacoes_por_tipo'        => $this->distribuicao('manifestacoes', 'tipo', function (BaseBuilder $b) use ($filtros): void {
                $this->aplicarPeriodo($b, 'manifestacoes.data_hora', $filtros);
                if ($filtros['prioridade'] !== '') {
                    $b->where('manifestacoes.prioridade', (string) $filtros['prioridade']);
                }
            }),
        ];
    }

    /**
     * Agregados de atendimentos em UMA consulta.
     *
     * @param array<string, mixed> $filtros
     * @param int[]|null           $idsFaixa
     *
     * @return array<string, int>
     */
    private function agregadoAtendimentos(array $filtros, ?array $idsFaixa): array
    {
        $b = $this->builder('atendimentos');
        $this->filtrosAtendimento($b, $filtros);
        if ($idsFaixa !== null) {
            $b->whereIn('atendimentos.mulher_id', $idsFaixa);
        }

        $b->select(
            'COUNT(*) AS total, '
            . "SUM(CASE WHEN situacao = 'novo' THEN 1 ELSE 0 END) AS novos, "
            . "SUM(CASE WHEN situacao IN ('aguardando_atendimento','em_andamento') THEN 1 ELSE 0 END) AS em_andamento, "
            . "SUM(CASE WHEN situacao = 'encerrado' THEN 1 ELSE 0 END) AS encerrados, "
            . "SUM(CASE WHEN prioridade IN ('emergencia','alta') THEN 1 ELSE 0 END) AS urgentes",
            false
        );

        $row = $b->get()->getRowArray();

        return [
            'total'            => (int) ($row['total'] ?? 0),
            'novos'            => (int) ($row['novos'] ?? 0),
            'em_andamento'     => (int) ($row['em_andamento'] ?? 0),
            'encerrados'       => (int) ($row['encerrados'] ?? 0),
            'urgentes'         => (int) ($row['urgentes'] ?? 0),
        ];
    }

    /**
     * Contagem de mulheres distintas com atendimento no período/filtros.
     *
     * @param array<string, mixed> $filtros
     * @param int[]|null           $idsFaixa
     */
    private function mulheresAtendidas(array $filtros, ?array $idsFaixa): int
    {
        $b = $this->builder('atendimentos');
        $this->filtrosAtendimento($b, $filtros);
        if ($idsFaixa !== null) {
            $b->whereIn('atendimentos.mulher_id', $idsFaixa);
        }

        $b->select('atendimentos.mulher_id');
        $ids = array_column($b->get()->getResultArray(), 'mulher_id');

        return count(array_unique($ids));
    }

    /**
     * Builder de uma tabela (com tabela prefixada do banco).
     */
    private function builder(string $tabela): BaseBuilder
    {
        return $this->db->table($tabela);
    }

    /**
     * Nome físico da tabela, considerando o prefixo configurado no banco.
     */
    private function tp(string $tabela): string
    {
        return $this->db->getPrefix() . $tabela;
    }

    /**
     * Aplica os filtros comuns do domínio de atendimentos.
     * A junção com dados_mulher é feita quando necessário (filtro de município
     * ou quando o chamador informa $juncaoPermanente).
     *
     * @param array<string, mixed> $filtros
     */
    private function filtrosAtendimento(BaseBuilder $b, array $filtros, bool $juncaoPermanente = false): void
    {
        $this->aplicarPeriodo($b, 'atendimentos.data_hora', $filtros);

        if ($filtros['profissional'] > 0) {
            $b->where('atendimentos.profissional_id', (int) $filtros['profissional']);
        }
        if ($filtros['situacao'] !== '') {
            $b->where('atendimentos.situacao', (string) $filtros['situacao']);
        }
        if ($filtros['prioridade'] !== '') {
            $b->where('atendimentos.prioridade', (string) $filtros['prioridade']);
        }
        if ($filtros['forma'] !== '') {
            $b->where('atendimentos.forma_atendimento', (string) $filtros['forma']);
        }
        if ($filtros['origem'] !== '') {
            $b->where('atendimentos.origem', (string) $filtros['origem']);
        }

        $precisaJuncao = $juncaoPermanente || $filtros['municipio'] !== '';
        if ($precisaJuncao) {
            $b->join('dados_mulher', 'dados_mulher.id = atendimentos.mulher_id');
        }
        if ($filtros['municipio'] !== '') {
            $b->where('dados_mulher.municipio', (string) $filtros['municipio']);
        }
    }

    /**
     * Filtros exclusivos do domínio de encaminhamentos.
     *
     * @param array<string, mixed> $filtros
     */
    private function filtrosEncaminhamento(BaseBuilder $b, array $filtros, bool $apenasPendentes = false): void
    {
        $this->aplicarPeriodo($b, 'encaminhamentos.data_hora', $filtros);

        if ($filtros['profissional'] > 0) {
            $b->where('encaminhamentos.profissional_id', (int) $filtros['profissional']);
        }
        if ($filtros['prioridade'] !== '') {
            $b->where('encaminhamentos.prioridade', (string) $filtros['prioridade']);
        }
        if ($filtros['instituicao'] > 0) {
            $b->where('encaminhamentos.instituicao_id', (int) $filtros['instituicao']);
        }
        if ($filtros['orgao'] !== '') {
            $b->where('encaminhamentos.orgao', (string) $filtros['orgao']);
        }
        if ($apenasPendentes) {
            $b->whereIn('encaminhamentos.status', EncaminhamentoModel::STATUS_PENDENTES);
        }
    }

    /**
     * Filtros do domínio de avaliações de risco (violência).
     *
     * @param array<string, mixed> $filtros
     */
    private function filtrosAvaliacaoRisco(BaseBuilder $b, array $filtros): void
    {
        $this->aplicarPeriodo($b, 'avaliacoes_risco.data_hora', $filtros);

        if ($filtros['profissional'] > 0) {
            $b->where('avaliacoes_risco.profissional_id', (int) $filtros['profissional']);
        }
        $this->filtroFatorViolencia($b, $filtros);
    }

    /**
     * Filtros do domínio de medidas protetivas (período por solicitação/criação).
     *
     * @param array<string, mixed> $filtros
     */
    private function filtrosMedidas(BaseBuilder $b, array $filtros): void
    {
        $this->aplicarPeriodoMesclado($b, 'medidas_protetivas', $filtros);
    }

    /**
     * Aplica período (data/hora em texto) em uma coluna.
     *
     * @param array<string, mixed> $filtros
     */
    private function aplicarPeriodo(BaseBuilder $b, string $coluna, array $filtros): void
    {
        $de  = (string) $filtros['data_de'];
        $ate = (string) $filtros['data_ate'];

        if ($de !== '') {
            $b->where("{$coluna} >=", $de . ' 00:00:00');
        }
        if ($ate !== '') {
            $b->where("{$coluna} <=", $ate . ' 23:59:59');
        }
    }

    /**
     * Aplica período comparando solicitação ou criação (medidas protetivas).
     * Usa COALESCE(data_solicitacao, date(created_at)) — portável entre SQLite
     * e MySQL, pois ambas oferecem DATE().
     *
     * @param array<string, mixed> $filtros
     */
    private function aplicarPeriodoMesclado(BaseBuilder $b, string $tabela, array $filtros): void
    {
        $de  = (string) $filtros['data_de'];
        $ate = (string) $filtros['data_ate'];
        $tabelaFisica = $this->tp($tabela);
        $expr = "COALESCE({$tabelaFisica}.data_solicitacao, date({$tabelaFisica}.created_at))";

        if ($de !== '') {
            $b->where("{$expr} >= '{$de}'", null, false);
        }
        if ($ate !== '') {
            $b->where("{$expr} <= '{$ate}'", null, false);
        }
    }

    /**
     * Aplica período em uma coluna do tipo DATE (sem horário).
     *
     * @param array<string, mixed> $filtros
     */
    private function aplicarPeriodoData(BaseBuilder $b, string $coluna, array $filtros): void
    {
        $de  = (string) $filtros['data_de'];
        $ate = (string) $filtros['data_ate'];

        if ($de !== '') {
            $b->where("{$coluna} >=", $de);
        }
        if ($ate !== '') {
            $b->where("{$coluna} <=", $ate);
        }
    }

    /**
     * Total de encaminhamentos (ou somente pendentes).
     *
     * @param array<string, mixed> $filtros
     */
    private function totalEncaminhamentos(array $filtros, bool $apenasPendentes = false): int
    {
        $b = $this->builder('encaminhamentos');
        $this->filtrosEncaminhamento($b, $filtros, $apenasPendentes);

        return (int) $b->countAllResults();
    }

    /**
     * Total de avaliações de risco (violência) no período/filtros.
     *
     * @param array<string, mixed> $filtros
     */
    private function totalAvaliacoesRisco(array $filtros): int
    {
        $b = $this->builder('avaliacoes_risco');
        $this->filtrosAvaliacaoRisco($b, $filtros);

        return (int) $b->countAllResults();
    }

    /**
     * Total de retornos/agendamentos na agenda (status agendado/confirmado).
     *
     * @param array<string, mixed> $filtros
     */
    private function totalCompromissos(array $filtros): int
    {
        $b = $this->builder('compromissos');
        $this->aplicarPeriodoData($b, 'compromissos.data', $filtros);
        $b->whereIn('compromissos.status', ['agendado', 'confirmado']);

        return (int) $b->countAllResults();
    }

    /**
     * Total de medidas protetivas no período.
     *
     * @param array<string, mixed> $filtros
     */
    private function totalMedidas(array $filtros): int
    {
        $b = $this->builder('medidas_protetivas');
        $this->filtrosMedidas($b, $filtros);

        return (int) $b->countAllResults();
    }

    /**
     * Total genérico de uma tabela com período sobre uma coluna data/hora.
     *
     * @param array<string, mixed>  $filtros
     * @param callable(BaseBuilder): void|null $extra
     */
    private function totalGenerico(string $tabela, string $periodoColuna, array $filtros, ?callable $extra = null): int
    {
        $b = $this->builder($tabela);
        $this->aplicarPeriodo($b, "{$tabela}.{$periodoColuna}", $filtros);
        if ($extra !== null) {
            $extra($b);
        }

        return (int) $b->countAllResults();
    }

    /**
     * Distribuição (GROUP BY) com rótulo e contagem.
     *
     * @param string                           $tabela
     * @param string                           $coluna coluna de agrupamento
     * @param callable(BaseBuilder): void      $filtros aplica período/filtros
     *
     * @return array<int, array{rotulo: ?string, total: int}>
     */
    private function distribuicao(string $tabela, string $coluna, callable $filtros): array
    {
        $b = $this->builder($tabela);
        $filtros($b);
        $b->select("COALESCE({$coluna}, 'Sem informação') AS rotulo, COUNT(*) AS total", false)
            ->groupBy($coluna)
            ->orderBy('total', 'DESC');

        return array_map(static fn (array $r): array => [
            'rotulo' => $r['rotulo'] !== null ? (string) $r['rotulo'] : 'Sem informação',
            'total'  => (int) $r['total'],
        ], $b->get()->getResultArray());
    }

    /**
     * Distribuição de atendimentos agrupada por um campo do atendimento.
     *
     * @param array<string, mixed> $filtros
     * @param int[]|null           $idsFaixa
     *
     * @return array<int, array{rotulo: ?string, total: int}>
     */
    private function distribuicaoAtendimentos(string $coluna, array $filtros, ?array $idsFaixa): array
    {
        return $this->distribuicao('atendimentos', $coluna, function (BaseBuilder $b) use ($filtros, $idsFaixa): void {
            $this->filtrosAtendimento($b, $filtros);
            if ($idsFaixa !== null) {
                $b->whereIn('atendimentos.mulher_id', $idsFaixa);
            }
        });
    }

    /**
     * Atendimentos por município da mulher (junção com dados_mulher).
     *
     * @param array<string, mixed> $filtros
     * @param int[]|null           $idsFaixa
     *
     * @return array<int, array{rotulo: string, total: int}>
     */
    private function atendimentosPorMunicipio(array $filtros, ?array $idsFaixa): array
    {
        $b = $this->builder('atendimentos');
        $this->filtrosAtendimento($b, $filtros, true);
        if ($idsFaixa !== null) {
            $b->whereIn('atendimentos.mulher_id', $idsFaixa);
        }

        $b->select("COALESCE(NULLIF({$this->tp('dados_mulher')}.municipio, ''), 'Sem município') AS rotulo, COUNT(*) AS total", false)
            ->groupBy('dados_mulher.municipio')
            ->orderBy('total', 'DESC')
            ->limit(10);

        return array_map(static fn (array $r): array => [
            'rotulo' => (string) $r['rotulo'],
            'total'  => (int) $r['total'],
        ], $b->get()->getResultArray());
    }

    /**
     * Encaminhamentos por instituição da rede (nome).
     *
     * @param array<string, mixed> $filtros
     *
     * @return array<int, array{rotulo: string, total: int}>
     */
    private function encaminhamentosPorInstituicao(array $filtros): array
    {
        $b = $this->builder('encaminhamentos');
        $this->filtrosEncaminhamento($b, $filtros);
        $b->join('rede_instituicoes', 'rede_instituicoes.id = encaminhamentos.instituicao_id', 'left');

        $b->select("COALESCE({$this->tp('rede_instituicoes')}.nome, 'Sem instituição') AS rotulo, COUNT(*) AS total", false)
            ->groupBy('rede_instituicoes.nome')
            ->orderBy('total', 'DESC')
            ->limit(10);

        return array_map(static fn (array $r): array => [
            'rotulo' => (string) $r['rotulo'],
            'total'  => (int) $r['total'],
        ], $b->get()->getResultArray());
    }

    /**
     * Fatores de risco (violência) mais registrados nas avaliações.
     *
     * @param array<string, mixed> $filtros
     *
     * @return array<int, array{fator: string, rotulo: string, total: int}>
     */
    private function riscoFatores(array $filtros): array
    {
        $b = $this->builder('avaliacoes_risco');
        $this->filtrosAvaliacaoRisco($b, $filtros);

        $expressoes = [];
        foreach (array_keys(self::FATORES_VIOLENCIA) as $fator) {
            $expressoes[] = "SUM(CASE WHEN {$fator} = 1 THEN 1 ELSE 0 END) AS {$fator}";
        }
        $b->select(implode(', ', $expressoes), false);

        $row = $b->get()->getRowArray();
        $items = [];
        foreach (self::FATORES_VIOLENCIA as $fator => $rotulo) {
            $total = isset($row[$fator]) ? (int) $row[$fator] : 0;
            if ($total > 0) {
                $items[] = ['fator' => $fator, 'rotulo' => $rotulo, 'total' => $total];
            }
        }
        usort($items, static fn (array $a, array $b): int => $b['total'] <=> $a['total']);

        return $items;
    }

    /**
     * Filtro por fator de violência específico.
     *
     * @param array<string, mixed> $filtros
     */
    private function filtroFatorViolencia(BaseBuilder $b, array $filtros): void
    {
        if ($filtros['fator_violencia'] !== '') {
            $b->where((string) $filtros['fator_violencia'], 1);
        }
    }

    /**
     * Evolução mensal de avaliações de risco.
     *
     * @param array<string, mixed> $filtros
     *
     * @return array<int, array{mes: string, rotulo: string, total: int}>
     */
    private function riscoPorMes(array $filtros): array
    {
        $b = $this->builder('avaliacoes_risco');
        $this->filtrosAvaliacaoRisco($b, $filtros);

        return $this->serieMensalBuilder($b, 'avaliacoes_risco.data_hora', $filtros);
    }

    /**
     * Expressão SQL de mês/ano por driver (portável SQLite/MySQL).
     */
    private function mesExpr(string $coluna): string
    {
        return strtolower((string) $this->db->DBDriver) === 'mysqli'
            ? "DATE_FORMAT({$coluna}, '%Y-%m')"
            : "strftime('%Y-%m', {$coluna})";
    }

    /**
     * Evolução mensal dos atendimentos.
     *
     * @param array<string, mixed> $filtros
     * @param int[]|null           $idsFaixa
     *
     * @return array<int, array{mes: string, rotulo: string, total: int}>
     */
    private function serieMensal(string $tabela, string $coluna, array $filtros, ?array $idsFaixa = null): array
    {
        $b = $this->builder($tabela);
        if ($tabela === 'atendimentos') {
            $this->filtrosAtendimento($b, $filtros);
        } else {
            $this->aplicarPeriodo($b, "{$tabela}.{$coluna}", $filtros);
        }
        if ($idsFaixa !== null) {
            $b->whereIn('atendimentos.mulher_id', $idsFaixa);
        }

        return $this->serieMensalBuilder($b, "{$tabela}.{$coluna}", $filtros);
    }

    /**
     * Mês/ano com as contagens preenchidas (zeros reais entre o período).
     *
     * @param array<string, mixed> $filtros
     *
     * @return array<int, array{mes: string, rotulo: string, total: int}>
     */
    private function serieMensalBuilder(BaseBuilder $b, string $coluna, array $filtros): array
    {
        $pos = strpos($coluna, '.');
        $colunaSql = $pos === false
            ? $coluna
            : $this->tp(substr($coluna, 0, $pos)) . substr($coluna, $pos);
        $expr = $this->mesExpr($colunaSql);
        $rows = $b->select("{$expr} AS mes, COUNT(*) AS total", false)
            ->groupBy($expr, false)
            ->orderBy('mes', 'ASC')
            ->get()
            ->getResultArray();

        $mapa = [];
        foreach ($rows as $r) {
            $mapa[(string) $r['mes']] = (int) $r['total'];
        }

        $inicio = $this->monthStart((string) $filtros['data_de'], date('Y') . '-01-01');
        $fim    = $this->monthStart((string) $filtros['data_ate'], date('Y') . '-12-01');

        $itens = [];
        $atual = clone $inicio;
        $passos = 0;
        while ($atual <= $fim && $passos < 48) {
            $chave = $atual->format('Y-m');
            $itens[] = [
                'mes'    => $chave,
                'rotulo' => $this->rotuloMes($chave),
                'total'  => $mapa[$chave] ?? 0,
            ];
            $atual->modify('+1 month');
            $passos++;
        }

        return $itens;
    }

    /**
     * Data do primeiro dia do mês de uma data, com fallback.
     */
    private function monthStart(string $data, string $fallback): DateTime
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) === 1) {
            $dt = DateTime::createFromFormat('Y-m-d', $data);
            if ($dt !== false) {
                return $dt->modify('first day of this month');
            }
        }

        return new DateTime($fallback);
    }

    /**
     * Rótulo curto de mês (ex.: "Mai/26").
     */
    private function rotuloMes(string $mes): string
    {
        $dt = DateTime::createFromFormat('Y-m', $mes);

        return $dt !== false ? $dt->format('M/y') : $mes;
    }

    /**
     * Distribuição por faixa etária das mulheres atendidas no período.
     *
     * @param array<string, mixed> $filtros
     * @param int[]|null           $idsFaixa
     *
     * @return array<int, array{faixa: string, total: int}>
     */
    private function faixaEtaria(array $filtros, ?array $idsFaixa): array
    {
        $b = $this->builder('atendimentos');
        $this->filtrosAtendimento($b, $filtros, true);
        if ($idsFaixa !== null) {
            $b->whereIn('dados_mulher.id', $idsFaixa);
        }
        $b->select('dados_mulher.id, dados_mulher.data_nascimento')
            ->groupBy('dados_mulher.id');

        $rows = $b->get()->getResultArray();

        $contagem = [];
        foreach (self::FAIXAS_ETARIAS as $chave => $faixa) {
            $contagem[$chave] = 0;
        }
        $semData = 0;

        foreach ($rows as $m) {
            $idade = $this->idade($m['data_nascimento']);
            if ($idade === null) {
                $semData++;
                continue;
            }
            foreach (self::FAIXAS_ETARIAS as $chave => [$min, $max]) {
                $maximo = $max !== null ? (int) $max : PHP_INT_MAX;
                if ($idade >= (int) $min && $idade <= $maximo) {
                    $contagem[$chave]++;
                    break;
                }
            }
        }

        $items = [];
        foreach (self::FAIXAS_ETARIAS as $chave => $faixa) {
            $items[] = ['faixa' => $faixa[2], 'total' => $contagem[$chave]];
        }
        $items[] = ['faixa' => 'Sem data de nascimento', 'total' => $semData];

        return $items;
    }

    /**
     * Lista de ids de mulheres na faixa etária filtrada (ou null se sem filtro).
     *
     * @param array<string, mixed> $filtros
     *
     * @return int[]|null
     */
    private function mulheresIdsFaixa(array $filtros): ?array
    {
        if ($filtros['faixa'] === '' || ! isset(self::FAIXAS_ETARIAS[$filtros['faixa']])) {
            return null;
        }

        $faixa = self::FAIXAS_ETARIAS[$filtros['faixa']];
        $rows  = (new MulherModel())->select('id, data_nascimento')->findAll();

        $ids = [];
        foreach ($rows as $m) {
            $idade = $this->idade($m['data_nascimento']);
            if ($idade === null) {
                continue;
            }
            $min   = (int) $faixa[0];
            $max   = $faixa[1] !== null ? (int) $faixa[1] : PHP_INT_MAX;
            if ($idade >= $min && $idade <= $max) {
                $ids[] = (int) $m['id'];
            }
        }

        return $ids;
    }

    /**
     * Idade completa (anos) a partir de uma data de nascimento, ou null.
     */
    private function idade(?string $nascimento): ?int
    {
        if (empty($nascimento) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $nascimento) !== 1) {
            return null;
        }

        try {
            $nasc = new DateTime($nascimento);
            $hoje = new DateTime();
            if ($nasc > $hoje) {
                return null;
            }

            return (int) $hoje->diff($nasc)->y;
        } catch (\Throwable) {
            return null;
        }
    }

    private function instituicoesAtivas(): array
    {
        return (new InstituicaoModel())->where('ativo', 1)->orderBy('nome', 'ASC')->findAll();
    }

    /**
     * @return array<int, string>
     */
    private function municipiosDisponiveis(): array
    {
        $b = $this->builder('dados_mulher');
        $rows = $b->select('municipio')
            ->where('municipio IS NOT NULL')
            ->where('municipio !=', '')
            ->groupBy('municipio')
            ->orderBy('municipio', 'ASC')
            ->get()
            ->getResultArray();

        $municipios = array_map(static fn (array $m): string => (string) $m['municipio'], $rows);
        sort($municipios, SORT_STRING);

        return array_values($municipios);
    }

    /**
     * Grava uma seção de CSV.
     *
     * @param resource $out
     * @param array<int, array<string, mixed>> $linhas
     */
    private function tabelaCsv($out, string $titulo, array $cabecalho, array $linhas): void
    {
        fputcsv($out, [], ',', '"', '');
        fputcsv($out, [$titulo], ',', '"', '');
        fputcsv($out, $cabecalho, ',', '"', '');
        foreach ($linhas as $linha) {
            fputcsv($out, array_values($linha), ',', '"', '');
        }
    }
}