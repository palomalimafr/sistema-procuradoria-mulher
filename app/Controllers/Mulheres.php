<?php

namespace App\Controllers;

use App\Libraries\EmailServico;
use App\Libraries\PdfServico;
use App\Models\AtendimentoModel;
use App\Models\ConfiguracaoModel;
use App\Models\ConviteMulherModel;
use App\Models\HistoricoMulherModel;
use App\Models\MulherModel;
use App\Models\UserModel;

class Mulheres extends BaseController
{
    protected $mulherModel;

    public function __construct()
    {
        $this->mulherModel = new MulherModel();
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

    /**
     * Listas usadas no formulário e na ficha da mulher.
     */
    private function listasFormulario(): array
    {
        return [
            'sexos'         => ['F' => 'Feminino', 'M' => 'Masculino', 'O' => 'Outro'],
            'estadosCivis'  => [
                'solteira'     => 'Solteira',
                'casada'       => 'Casada',
                'divorciada'   => 'Divorciada',
                'viuva'        => 'Viúva',
                'uniao_estavel' => 'União estável',
                'outra'        => 'Outro',
            ],
            'escolaridades' => [
                'sem_escolaridade' => 'Sem escolaridade',
                'fundamental_incompleto' => 'Fundamental incompleto',
                'fundamental'      => 'Fundamental completo',
                'medio_incompleto' => 'Médio incompleto',
                'medio'            => 'Médio completo',
                'superior_incompleto' => 'Superior incompleto',
                'superior'         => 'Superior completo',
                'pos_graduacao'    => 'Pós-graduação',
            ],
            'zonas'         => ['urbana' => 'Urbana', 'rural' => 'Rural'],
            'ufs'           => [
                'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG',
                'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
            ],
            'faixasEtarias' => [
                '0_17'    => '0 a 17 anos',
                '18_29'   => '18 a 29 anos',
                '30_44'   => '30 a 44 anos',
                '45_59'   => '45 a 59 anos',
                '60_mais' => '60 anos ou mais',
            ],
        ];
    }

    public function cadastro()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        return view('mulheres/cadastro', array_merge([
            'titulo' => 'Cadastro de Mulher',
            'mulher' => [],
        ], $this->listasFormulario()));
    }

    public function salvar()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        return $this->processar(null);
    }

    public function editar(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $mulher = $this->buscarMulher($id);
        if ($mulher === null) {
            return redirect()->to('/mulheres/pesquisa')->with('erro', 'Mulher não encontrada.');
        }

        return view('mulheres/cadastro', array_merge([
            'titulo' => 'Editar Mulher',
            'mulher' => $mulher,
        ], $this->listasFormulario()));
    }

    public function atualizar(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        if ($this->buscarMulher($id) === null) {
            return redirect()->to('/mulheres/pesquisa')->with('erro', 'Mulher não encontrada.');
        }

        return $this->processar($id);
    }

    /**
     * MULHERES > Pesquisa
     */
    public function pesquisa()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $filtros = $this->request->getGet();

        $model = new MulherModel();
        $model->pesquisar($filtros);
        $mulheres = $model->orderBy('nome_completo', 'ASC')->paginate(10);
        $pager    = $model->pager;

        $ids    = array_column($mulheres, 'id');
        $status = (new AtendimentoModel())->ultimosStatusPorMulheres($ids);

        return view('mulheres/pesquisa', array_merge($this->listasFormulario(), [
            'mulheres' => $mulheres,
            'pager'    => $pager,
            'status'   => $status,
            'filtros'  => $filtros,
        ]));
    }

    /**
     * MULHERES > Visualizar (ficha da mulher)
     */
    /**
     * MULHERES > Reenviar convite de primeiro acesso
     *
     * Só faz sentido para mulher ainda sem conta. Um novo envio invalida o
     * convite anterior, para que exista um único link válido por vez.
     */
    public function reenviarConvite(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $mulher = $this->buscarMulher($id);
        if ($mulher === null) {
            return redirect()->to('/mulheres/pesquisa')->with('erro', 'Mulher não encontrada.');
        }

        if (! empty($mulher['user_id'])) {
            return redirect()->to('/mulheres/detalhes/' . $id)
                ->with('erro', 'Esta mulher já possui conta de acesso. Não há convite a reenviar.');
        }

        $email = trim((string) ($mulher['email'] ?? ''));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->to('/mulheres/detalhes/' . $id)
                ->with('erro', 'Cadastre um e-mail válido para a mulher antes de enviar o convite.');
        }

        $resultado = $this->enviarConvitePrimeiroAcesso($id, (string) $mulher['nome_completo'], $email);

        if ($resultado['enviado'] !== true) {
            return redirect()->to('/mulheres/detalhes/' . $id)
                ->with('erro', 'Não foi possível enviar o convite: ' . $resultado['erro']);
        }

        $this->auditar('CONVITE_REENVIADO', 'mulheres', $id, 'mulher', (string) $mulher['nome_completo']);

        return redirect()->to('/mulheres/detalhes/' . $id)
            ->with('sucesso', 'Convite de primeiro acesso reenviado para o e-mail cadastrado.');
    }

    public function detalhes(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $mulher = $this->buscarMulher($id);
        if ($mulher === null) {
            return redirect()->to('/mulheres/pesquisa')->with('erro', 'Mulher não encontrada.');
        }

        $conta = ! empty($mulher['user_id'])
            ? (new UserModel())->find((int) $mulher['user_id'])
            : null;

        $conviteModel = new ConviteMulherModel();
        $convite      = $conviteModel->ultimo($id);

        return view('mulheres/detalhes', array_merge($this->listasFormulario(), [
            'mulher' => $mulher,
            'conta'  => $conta,
            'statusAcesso' => $this->statusAcesso($mulher, $convite),
        ]));
    }

    /**
     * Situação do acesso da mulher, para o selo da ficha:
     * 'criada' (conta vinculada), 'enviado' (convite aguardando o primeiro
     * acesso) ou 'sem_conta' (sem conta e sem convite em aberto).
     * O token nunca chega à view — nem em texto puro, nem em hash.
     *
     * @return array{estado: string, rotulo: string, descricao: string, convite: array|null}
     */
    private function statusAcesso(array $mulher, ?array $convite): array
    {
        if (! empty($mulher['user_id'])) {
            return [
                'estado'     => 'criada',
                'rotulo'     => 'Conta criada',
                'descricao'  => 'A mulher já criou a senha e consegue acessar o sistema.',
                'convite'    => $convite,
            ];
        }

        $pendente = $convite !== null && (new ConviteMulherModel())->statusDe($convite) === ConviteMulherModel::STATUS_VALIDO
            ? $convite
            : null;

        if ($pendente !== null) {
            return [
                'estado'    => 'enviado',
                'rotulo'    => 'Convite enviado',
                'descricao' => 'Aguards a mulher criar a senha pelo link recebido por e-mail.',
                'convite'   => $pendente,
            ];
        }

        return [
            'estado'    => 'sem_conta',
            'rotulo'    => 'Sem conta',
            'descricao' => 'Aguardando o envio do convite de primeiro acesso.',
            'convite'   => $convite,
        ];
    }

    /**
     * MULHERES > Histórico
     *
     * Visão única da trajetória da mulher: um evento por registro, montado por
     * `HistoricoMulherModel` e ordenado do mais recente para o mais antigo.
     * O filtro de módulo é opcional e não muda o total exibido nos contadores.
     */
    public function historico(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $mulher = $this->buscarMulher($id);
        if ($mulher === null) {
            return redirect()->to('/mulheres/pesquisa')->with('erro', 'Mulher não encontrada.');
        }

        $modulo  = (string) $this->request->getGet('modulo');
        $historico = (new HistoricoMulherModel())->timeline($id, $modulo);

        return view('mulheres/historico', array_merge($this->listasFormulario(), [
            'mulher'     => $mulher,
            'eventos'    => $historico['eventos'],
            'contagem'   => $historico['contagem'],
            'total'      => $historico['total'],
            'modulo'     => $historico['modulo'],
            'modulos'    => HistoricoMulherModel::MODULOS,
        ]));
    }

    /**
     * MULHERES > Relatório individual (PDF)
     *
     * Gera o dossiê completo da mulher: ficha cadastral (dados reais de
     * `dados_mulher`), resumo dos registros e o histórico de todos os módulos
     * vinculados, organizado por seção em ordem cronológica. Reutiliza o mesmo
     * mecanismo do relatório geral (PdfServico/Dompdf) e o `HistoricoMulherModel`
     * como fonte consolidada dos vínculos — sem inventar tabelas ou campos.
     */
    public function relatorioPdf(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $mulher = $this->buscarMulher($id);
        if ($mulher === null) {
            return redirect()->to('/mulheres/pesquisa')->with('erro', 'Mulher não encontrada.');
        }

        $config    = (new ConfiguracaoModel())->obterTodos();
        $servico   = new PdfServico();
        $historico = (new HistoricoMulherModel())->timeline($id);
        $eventos   = $historico['eventos'];

        $ficha  = $this->fichaParaPdf($mulher, $this->listasFormulario());
        $resumo = $this->resumoDoHistorico($eventos, $historico['contagem']);

        $variaveis = [
            'titulo'         => 'Relatório Individual',
            'mulher'         => $mulher,
            'ficha'          => $ficha,
            'resumo'         => $resumo,
            'secoes'         => $this->secoesDoHistorico($eventos),
            'totalRegistros' => $historico['total'],
            'semRegistros'   => $historico['total'] === 0,
            'config'         => $config,
            'logo'           => $servico->logoParaPdf(),
            'usuarioNome'    => (string) (session()->get('usuario_nome') ?? ''),
            'usuarioPerfil'  => (string) (session()->get('perfil') ?? ''),
            'geradoEm'       => date('d/m/Y \à\s H:i'),
        ];

        try {
            $html = view('mulheres/relatorio_pdf', $variaveis);
            $pdf  = $servico->renderizar($html, $config);

            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', 'inline; filename="mulher_' . $id . '_' . date('Ymd_His') . '.pdf"')
                ->setBody($pdf);
        } catch (\Throwable $e) {
            log_message('error', 'Falha ao gerar relatório individual da mulher #' . $id . ': ' . $e->getMessage());

            return redirect()->to('/mulheres/pesquisa')
                ->with('erro', 'Não foi possível gerar o relatório individual em PDF. Tente novamente.');
        }
    }

    /**
     * Ficha cadastral da mulher organizada em blocos para exibição no PDF.
     * Valores vazios/ausentes são tratados pela view como "Não informado".
     *
     * @return array<int, array{titulo: string, itens: array<int, array{rotulo: string, valor: string}>}>
     */
    private function fichaParaPdf(array $m, array $listas): array
    {
        $idade     = MulherModel::calcularIdade($m['data_nascimento'] ?? null);
        $nascimento = trim((string) ($m['data_nascimento'] ?? ''));
        $nascimento = $nascimento !== '' ? $this->dataBr($nascimento) . ($idade !== null ? ' (' . $idade . ' anos)' : '') : '';

        $logradouro = trim((string) ($m['logradouro'] ?? ''));
        $numero     = trim((string) ($m['numero'] ?? ''));
        $endereco   = $logradouro . ($numero !== '' ? ', ' . $numero : '');

        $municipio = trim((string) ($m['municipio'] ?? ''));
        $estado    = trim((string) ($m['estado'] ?? ''));
        $cidadeUf  = $municipio . ($estado !== '' ? '/' . $estado : '');

        return [
            [
                'titulo' => 'Dados pessoais',
                'itens'  => [
                    ['rotulo' => 'Nome completo',       'valor' => (string) ($m['nome_completo'] ?? '')],
                    ['rotulo' => 'Nome social',         'valor' => (string) ($m['nome_social'] ?? '')],
                    ['rotulo' => 'CPF',                 'valor' => (string) ($m['cpf'] ?? '')],
                    ['rotulo' => 'RG',                  'valor' => (string) ($m['rg'] ?? '')],
                    ['rotulo' => 'Nascimento',          'valor' => $nascimento],
                    ['rotulo' => 'Sexo',                'valor' => $listas['sexos'][$m['sexo'] ?? ''] ?? (string) ($m['sexo'] ?? '')],
                    ['rotulo' => 'Estado civil',        'valor' => $listas['estadosCivis'][$m['estado_civil'] ?? ''] ?? (string) ($m['estado_civil'] ?? '')],
                    ['rotulo' => 'Escolaridade',        'valor' => $listas['escolaridades'][$m['escolaridade'] ?? ''] ?? (string) ($m['escolaridade'] ?? '')],
                    ['rotulo' => 'Profissão',           'valor' => (string) ($m['profissao'] ?? '')],
                    ['rotulo' => 'Ocupação',            'valor' => (string) ($m['ocupacao'] ?? '')],
                ],
            ],
            [
                'titulo' => 'Contato',
                'itens'  => [
                    ['rotulo' => 'Telefone',            'valor' => (string) ($m['telefone'] ?? '')],
                    ['rotulo' => 'WhatsApp',            'valor' => (string) ($m['whatsapp'] ?? '')],
                    ['rotulo' => 'E-mail',              'valor' => (string) ($m['email'] ?? '')],
                ],
            ],
            [
                'titulo' => 'Endereço e localização',
                'itens'  => [
                    ['rotulo' => 'Logradouro',          'valor' => $endereco],
                    ['rotulo' => 'Complemento',         'valor' => (string) ($m['complemento'] ?? '')],
                    ['rotulo' => 'Bairro',              'valor' => (string) ($m['bairro'] ?? '')],
                    ['rotulo' => 'Município / UF',      'valor' => $cidadeUf],
                    ['rotulo' => 'CEP',                 'valor' => (string) ($m['cep'] ?? '')],
                    ['rotulo' => 'Ponto de referência', 'valor' => (string) ($m['ponto_referencia'] ?? '')],
                    ['rotulo' => 'Zona',                'valor' => $listas['zonas'][$m['zona'] ?? ''] ?? (string) ($m['zona'] ?? '')],
                ],
            ],
            [
                'titulo' => 'Informações socioeconômicas e familiares',
                'itens'  => [
                    ['rotulo' => 'Possui filhos',             'valor' => ! empty($m['possui_filhos']) ? 'Sim' : 'Não'],
                    ['rotulo' => 'Quantidade de filhos',      'valor' => $this->campoNumero($m['quantidade_filhos'] ?? null)],
                    ['rotulo' => 'Quantidade de dependentes', 'valor' => $this->campoNumero($m['quantidade_dependentes'] ?? null)],
                    ['rotulo' => 'Gestante',                  'valor' => ! empty($m['esta_gravida']) ? 'Sim' : 'Não'],
                    ['rotulo' => 'Possui deficiência',        'valor' => ! empty($m['possui_deficiencia']) ? 'Sim' : 'Não'],
                    ['rotulo' => 'Idosa',                     'valor' => ! empty($m['e_idosa']) ? 'Sim' : 'Não'],
                    ['rotulo' => 'Situação de vulnerabilidade', 'valor' => (string) ($m['situacao_vulnerabilidade'] ?? '')],
                ],
            ],
        ];
    }

    /**
     * Valor numérico de contagem para exibição no PDF (vazio vira "Não informado").
     */
    private function campoNumero($valor): string
    {
        return $valor === null || $valor === '' ? '' : (string) (int) $valor;
    }

    /**
     * Resumo dos registros: total por módulo e classificação de risco mais
     * recente (se houver), sempre a partir de dados reais do histórico.
     *
     * @return array<string, mixed>
     */
    private function resumoDoHistorico(array $eventos, array $contagem): array
    {
        $itens = [];
        foreach (HistoricoMulherModel::MODULOS as $chave => $info) {
            $itens[] = [
                'modulo' => $chave,
                'rotulo' => (string) $info['chip'],
                'total'  => (int) ($contagem[$chave] ?? 0),
            ];
        }

        $ultimaAvaliacao = null;
        foreach ($eventos as $ev) {
            if (($ev['modulo'] ?? '') === 'avaliacao') {
                $ultimaAvaliacao = $ev;
                break;
            }
        }

        return [
            'itens'         => $itens,
            'total'         => array_sum(array_column($itens, 'total')),
            'ultimaAvaliacao' => $ultimaAvaliacao !== null ? [
                'classificacao' => (string) ($ultimaAvaliacao['situacaoRotulo'] ?? ''),
                'data'          => $this->dataBr((string) ($ultimaAvaliacao['data'] ?? '')),
            ] : null,
        ];
    }

    /**
     * Histórico agregado por módulo, na ordem fixa dos módulos e do mais recente
     * para o mais antigo, com as informações já formatadas para o PDF.
     *
     * @return array<int, array{modulo: string, rotulo: string, registros: array<int, array<string, mixed>>}>
     */
    private function secoesDoHistorico(array $eventos): array
    {
        $agrupados = [];
        foreach ($eventos as $ev) {
            $agrupados[$ev['modulo']][] = $ev;
        }

        $secoes = [];
        foreach (HistoricoMulherModel::MODULOS as $chave => $info) {
            if (empty($agrupados[$chave])) {
                continue;
            }

            $secoes[] = [
                'modulo'    => $chave,
                'rotulo'    => (string) $info['chip'],
                'registros' => array_map(function (array $ev): array {
                    return [
                        'data'     => $this->dataBr((string) ($ev['data'] ?? '')),
                        'titulo'   => (string) ($ev['titulo'] ?? ''),
                        'situacao' => (string) ($ev['situacaoRotulo'] ?? ''),
                        'detalhes' => $ev['detalhes'] ?? [],
                    ];
                }, $agrupados[$chave]),
            ];
        }

        return $secoes;
    }

    /**
     * Formata data/hora para o padrão brasileiro.
     */
    private function dataBr(string $data): string
    {
        $texto = trim($data);
        if ($texto === '') {
            return '';
        }

        $ts = strtotime($texto);

        return $ts === false ? $texto : date(strlen($texto) <= 10 ? 'd/m/Y' : 'd/m/Y H:i', $ts);
    }

    /**
     * Valida e persiste (cria ou atualiza) a mulher.
     *
     * Na criação, o e-mail é obrigatório: é por ele que a mulher recebe o
     * convite de primeiro acesso. A conta em `users` NÃO é criada aqui — a
     * mulher define a própria senha no link recebido.
     */
    private function processar(?int $id = null)
    {
        $erros = $this->validarPost($id === null);
        if ($erros !== null) {
            return redirect()->back()->withInput()->with('erros', $erros);
        }

        $dados = $this->dadosDoPost();

        if ($id === null) {
            $errosCadastro = $this->validarDuplicidadeCpf();
            if ($errosCadastro !== null) {
                return redirect()->back()->with('erros', $errosCadastro);
            }

            return $this->criar($dados);
        }

        $atual = $this->mulherModel->find($id);
        if ($atual === null) {
            return redirect()->to('/mulheres/pesquisa')->with('erro', 'Mulher não encontrada.');
        }

        // Na edição mantém o vínculo com a conta (não recria nem remove).
        $dados['id']      = $id;
        $dados['user_id'] = ! empty($atual['user_id']) ? (int) $atual['user_id'] : null;

        // E-mail em branco na edição NÃO apaga o endereço já cadastrado: a
        // ficha é atualizada sem obrigar a equipe a redigitar o e-mail.
        if (trim((string) $dados['email']) === '') {
            $dados['email'] = $atual['email'] ?? null;
        }

        $emailAlterou = mb_strtolower(trim((string) $dados['email'])) !== mb_strtolower(trim((string) ($atual['email'] ?? '')));

        if ($emailAlterou) {
            $conflito = $this->conflitoDeEmail((string) $dados['email'], $id);
            if ($conflito !== null) {
                return redirect()->back()->withInput()->with('erros', ['email' => $conflito]);
            }
        }

        $this->mulherModel->save($dados);

        // Um convite em aberto apontaria para o endereço anterior: invalidado
        // para que o link velho não crie a conta com o e-mail que ficou para trás.
        if ($emailAlterou && empty($atual['user_id'])) {
            (new ConviteMulherModel())->invalidarPendentes($id);
        }

        $this->auditar('UPDATE', 'mulheres', $id, 'mulher', (string) $dados['nome_completo']);

        return redirect()->to('/mulheres/detalhes/' . $id)
            ->with('sucesso', 'Dados da mulher atualizados.');
    }

    /**
     * Cadastro administrativo de uma nova mulher.
     *
     * Tudo em transação: se a geração ou o envio do convite falhar, a mulher
     * não fica gravada. Assim nunca existe ficha sem caminho de acesso — e
     * nunca é exibido "acesso enviado" quando o e-mail não saiu.
     */
    private function criar(array $dados)
    {
        $email = trim((string) $dados['email']);

        $existente = $this->mulherModel->buscarPorCpf((string) ($dados['cpf'] ?? ''));
        $vinculo   = $this->mulherModel->findUserIdMulherPorEmail($email);

        // O próprio CPF pode gerar conflito de e-mail contra si mesmo, então a
        // verificação acontece depois de localizar a ficha e a ignora.
        $conflito = $this->conflitoDeEmail($email, $existente === null ? null : (int) $existente['id']);
        if ($conflito !== null) {
            return redirect()->back()->withInput()->with('erros', ['email' => $conflito]);
        }

        if ($existente !== null) {
            // Evita duplicidade: reaproveita a mulher já cadastrada. O id fixo
            // faz o save() virar update, então o id já é conhecido.
            $mulherId        = (int) $existente['id'];
            $dados['id']      = $mulherId;
            $dados['user_id'] = ! empty($existente['user_id']) ? (int) $existente['user_id'] : $vinculo;
        } else {
            $mulherId        = 0;
            $dados['user_id'] = $vinculo;
        }

        // Já existe conta vinculada (vinculação por e-mail ou cadastro
        // anterior): nada de segundo convite, a mulher entra com a senha que tem.
        $precisaConvite = empty($dados['user_id']);

        $db = \Config\Database::connect();
        $db->transException(false);
        $db->transBegin();

        try {
            $salvo = $this->mulherModel->save($dados);

            if ($salvo === false) {
                $db->transRollback();

                return redirect()->back()->withInput()->with('erros', [
                    'geral' => 'Não foi possível salvar a mulher: ' . implode(' ', $this->mulherModel->errors()),
                ]);
            }

            // Em update o insertId é zero; o id da ficha já é conhecido.
            if ($mulherId <= 0) {
                $mulherId = (int) $this->mulherModel->getInsertID();
            }

            if ($mulherId <= 0) {
                $db->transRollback();

                return redirect()->back()->withInput()->with('erros', ['geral' => 'Não foi possível salvar a mulher.']);
            }

            $conviteEnviado = false;

            if ($precisaConvite) {
                $resultado = $this->enviarConvitePrimeiroAcesso($mulherId, (string) $dados['nome_completo'], $email);

                if ($resultado['enviado'] !== true) {
                    $db->transRollback();

                    return redirect()->back()->withInput()->with('erros', [
                        'geral' => 'A mulher não foi salva: ' . $resultado['erro'] . ' Verifique o e-mail do sistema em SISTEMA > Configurações.',
                    ]);
                }

                $conviteEnviado = true;
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();

            return redirect()->back()->withInput()->with('erros', ['geral' => 'Não foi possível concluir o cadastro. Tente novamente.']);
        }

        $this->auditar(
            'CREATE',
            'mulheres',
            $mulherId,
            'mulher',
            (string) $dados['nome_completo'],
            ['convite_enviado' => $conviteEnviado]
        );

        if ($conviteEnviado) {
            $this->auditar('CONVITE_ENVIADO', 'mulheres', $mulherId, 'mulher', (string) $dados['nome_completo']);

            return redirect()->to('/mulheres/cadastro')->with(
                'sucesso',
                'Mulher cadastrada com sucesso. O convite de primeiro acesso foi enviado para o e-mail informado.'
            );
        }

        if (! empty($dados['user_id'])) {
            return redirect()->to('/mulheres/cadastro')->with(
                'sucesso',
                'Mulher já possuía conta de acesso — cadastro atualizado sem gerar duplicidade e sem novo convite.'
            );
        }

        return redirect()->to('/mulheres/cadastro')->with('sucesso', 'Mulher cadastrada com sucesso.');
    }

    /**
     * Gera o convite de primeiro acesso e envia o e-mail com o link.
     *
     * O token em texto puro existe somente aqui, em memória, para compor a
     * URL do e-mail. Nem o painel nem o log veem esse valor.
     *
     * @return array{enviado: bool, erro: string}
     */
    private function enviarConvitePrimeiroAcesso(int $mulherId, string $nome, string $email): array
    {
        $emailServico = new EmailServico();

        if (! $emailServico->configurado()) {
            return [
                'enviado' => false,
                'erro'    => 'não há remetente de e-mail configurado em SISTEMA > Configurações.',
            ];
        }

        $conviteModel = new ConviteMulherModel();

        // Convite anterior aberto é consumido para que só o link mais recente valha.
        $conviteModel->invalidarPendentes($mulherId);

        $convite = $conviteModel->gerar($mulherId, $emailServico->validadeConviteHoras());
        $link    = base_url('primeiro-acesso/' . $convite['token']);

        $enviado = $emailServico->enviar(
            $email,
            'Primeiro acesso ao sistema — ' . $this->nomeInstituicao(),
            $this->corpoEmailConvite($nome, $link, $convite['expira_em'])
        );

        if (! $enviado) {
            // O motivo técnico (host, porta, credencial) fica no log. A tela
            // mostra só o que o operador precisa fazer, sem vazar segredo.
            log_message('error', 'Falha ao enviar convite de primeiro acesso para #' . $mulherId . ': ' . (string) $emailServico->ultimoErro());

            return [
                'enviado' => false,
                'erro'    => 'o envio do e-mail falhou. Verifique o servidor SMTP em SISTEMA > Configurações.',
            ];
        }

        return ['enviado' => true, 'erro' => ''];
    }

    /**
     * Corpo do e-mail de primeiro acesso. Deixa explícito que o link serve para
     * criar a senha, e que ele é de uso único.
     */
    private function corpoEmailConvite(string $nome, string $link, string $expiraEm): string
    {
        $primeiroNome = trim(explode(' ', $nome)[0] ?? $nome);
        $validade    = date('d/m/Y \à\s H:i', strtotime($expiraEm));

        return '<p>Olá, ' . esc($primeiroNome) . '!</p>'
            . '<p>A ' . esc($this->nomeInstituicao()) . ' cadastrou o seu acesso ao sistema.</p>'
            . '<p>Para entrar pela <strong>primeira vez</strong>, crie a sua senha:</p>'
            . '<p><a href="' . esc($link) . '" style="display:inline-block;padding:10px 18px;background:#a179c3;color:#fff;text-decoration:none;border-radius:6px;">Criar minha senha</a></p>'
            . '<p>Ou copie o endereço abaixo no seu navegador:<br>' . esc($link) . '</p>'
            . '<p><strong>Importante:</strong> este link é de uso único e vale até ' . esc($validade) . '. '
            . 'A senha será criada por você e usada em todos os acessos futuros. '
            . 'Se você não solicitou este cadastro, entre em contato com o órgão.</p>';
    }

    private function nomeInstituicao(): string
    {
        return (new \App\Models\ConfiguracaoModel())->obter('nome_procuradoria') ?? 'Procuradoria da Mulher';
    }

    /**
     * E-mail que criaria uma conta conflitante. Comparação sem diferenciar caixa.
     *
     * Duas situações não são conflito, e sim o caso normal de vínculo:
     *  - o e-mail já tem conta de perfil `mulher` (a própria mulher se
     *    cadastrou pelo dispositivo): o fluxo apenas passa a apontar para ela;
     *  - o e-mail pertence à própria ficha que está sendo editada.
     *
     * @return string|null mensagem de erro, ou null quando o e-mail está livre
     */
    private function conflitoDeEmail(string $email, ?int $mulherId): ?string
    {
        $email = trim($email);
        if ($email === '') {
            return 'Informe o e-mail da mulher — é por ele que o acesso ao sistema será enviado.';
        }

        // Consulta direta: usar o model aqui contaminaria o builder e quebraria
        // os find() seguintes.
        $db = db_connect();

        $usuario = $db
            ->table('users')
            ->where('LOWER(email)', mb_strtolower($email))
            ->get()
            ->getRowArray();

        if ($usuario !== null && (string) $usuario['perfil'] !== 'mulher') {
            return 'Este e-mail já pertence a um usuário do sistema. Informe outro endereço.';
        }

        // Conta da própria mulher: a ficha é vinculada a ela, sem novo convite.
        if ($usuario !== null) {
            return null;
        }

        $query = $db->table('dados_mulher')
            ->where('email IS NOT NULL')
            ->where('LOWER(email)', mb_strtolower($email));

        if ($mulherId !== null) {
            $query->where('id !=', $mulherId);
        }

        if ($query->get()->getRowArray() !== null) {
            return 'Já existe uma mulher cadastrada com este e-mail e sem conta de acesso. '
                . 'Defina um e-mail diferente para esta mulher.';
        }

        return null;
    }

    /**
     * @param bool $exigirEmail obrigatório apenas no cadastro: na edição a
     *                          ficha pode já existir sem e-mail (registros
     *                          antigos) e apagá-lo seria perda de dados.
     */
    private function validarPost(bool $exigirEmail = false): ?array
    {
        $rules = [
            'nome_completo'          => 'required|max_length[200]',
            'nome_social'            => 'permit_empty|max_length[200]',
            'cpf'                    => 'permit_empty|max_length[14]',
            'rg'                     => 'permit_empty|max_length[20]',
            'data_nascimento'        => 'permit_empty|valid_date',
            'sexo'                   => 'permit_empty|in_list[M,F,O]',
            'estado_civil'           => 'permit_empty|max_length[20]',
            'escolaridade'           => 'permit_empty|max_length[50]',
            'profissao'              => 'permit_empty|max_length[100]',
            'ocupacao'               => 'permit_empty|max_length[100]',
            'telefone'               => 'permit_empty|max_length[20]',
            'whatsapp'               => 'permit_empty|max_length[20]',
            // Obrigatório no cadastro: sem e-mail não há como enviar o convite
            // de acesso. Na edição continua opcional.
            'email'                  => ($exigirEmail ? 'required|' : 'permit_empty|') . 'valid_email|max_length[150]',
            'cep'                    => 'permit_empty|max_length[10]',
            'logradouro'             => 'permit_empty|max_length[200]',
            'numero'                 => 'permit_empty|max_length[20]',
            'complemento'            => 'permit_empty|max_length[100]',
            'bairro'                 => 'permit_empty|max_length[100]',
            'municipio'              => 'permit_empty|max_length[100]',
            'estado'                 => 'permit_empty|exact_length[2]',
            'ponto_referencia'       => 'permit_empty|max_length[200]',
            'zona'                   => 'permit_empty|in_list[urbana,rural]',
            'quantidade_filhos'      => 'permit_empty|is_natural',
            'quantidade_dependentes' => 'permit_empty|is_natural',
            'observacoes'            => 'permit_empty',
        ];

        if (! $this->validate($rules)) {
            return $this->validator->getErrors();
        }

        return null;
    }

    private function validarDuplicidadeCpf(): ?array
    {
        $cpfLimpo = $this->normalizarCpf((string) $this->request->getPost('cpf'));

        if ($cpfLimpo !== '' && strlen($cpfLimpo) !== 11) {
            return ['cpf' => 'O CPF informado é inválido.'];
        }

        $dataNascimento = trim((string) $this->request->getPost('data_nascimento'));
        if ($dataNascimento !== '' && $dataNascimento > date('Y-m-d')) {
            return ['data_nascimento' => 'A data de nascimento não pode ser futura.'];
        }

        return null;
    }

    private function dadosDoPost(): array
    {
        $possuiFilhos = $this->request->getPost('possui_filhos') ? 1 : 0;
        $cpfLimpo     = $this->normalizarCpf((string) $this->request->getPost('cpf'));
        $email        = trim((string) $this->request->getPost('email'));

        return [
            'nome_completo'            => trim((string) $this->request->getPost('nome_completo')),
            'nome_social'              => trim((string) $this->request->getPost('nome_social')),
            'cpf'                      => $cpfLimpo !== '' ? $cpfLimpo : null,
            'rg'                       => trim((string) $this->request->getPost('rg')),
            'data_nascimento'          => trim((string) $this->request->getPost('data_nascimento')) !== '' ? trim((string) $this->request->getPost('data_nascimento')) : null,
            'sexo'                     => trim((string) $this->request->getPost('sexo')),
            'estado_civil'             => trim((string) $this->request->getPost('estado_civil')),
            'escolaridade'             => trim((string) $this->request->getPost('escolaridade')),
            'profissao'                => trim((string) $this->request->getPost('profissao')),
            'ocupacao'                 => trim((string) $this->request->getPost('ocupacao')),
            'telefone'                 => trim((string) $this->request->getPost('telefone')),
            'whatsapp'                 => trim((string) $this->request->getPost('whatsapp')),
            'email'                    => $email !== '' ? $email : null,
            'cep'                      => trim((string) $this->request->getPost('cep')),
            'logradouro'               => trim((string) $this->request->getPost('logradouro')),
            'numero'                   => trim((string) $this->request->getPost('numero')),
            'complemento'              => trim((string) $this->request->getPost('complemento')),
            'bairro'                   => trim((string) $this->request->getPost('bairro')),
            'municipio'                => trim((string) $this->request->getPost('municipio')),
            'estado'                   => strtoupper(trim((string) $this->request->getPost('estado'))),
            'ponto_referencia'         => trim((string) $this->request->getPost('ponto_referencia')),
            'zona'                     => trim((string) $this->request->getPost('zona')),
            'possui_filhos'            => $possuiFilhos,
            'quantidade_filhos'        => $possuiFilhos && $this->request->getPost('quantidade_filhos') !== '' ? (int) $this->request->getPost('quantidade_filhos') : null,
            'quantidade_dependentes'   => $this->request->getPost('quantidade_dependentes') !== '' ? (int) $this->request->getPost('quantidade_dependentes') : null,
            'esta_gravida'             => $this->request->getPost('esta_gravida') ? 1 : 0,
            'possui_deficiencia'       => $this->request->getPost('possui_deficiencia') ? 1 : 0,
            'e_idosa'                  => $this->request->getPost('e_idosa') ? 1 : 0,
            'situacao_vulnerabilidade' => $this->request->getPost('situacao_vulnerabilidade') ? 1 : 0,
            'observacoes'              => trim((string) $this->request->getPost('observacoes')),
        ];
    }

    /**
     * Localiza a mulher cadastrada (null quando não existe).
     */
    private function buscarMulher(int $id): ?array
    {
        $mulher = $this->mulherModel->find($id);

        return $mulher === null ? null : $mulher;
    }

    private function normalizarCpf(string $cpf): string
    {
        return preg_replace('/\D/', '', $cpf) ?? '';
    }
}
