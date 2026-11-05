<?php

namespace App\Models;

/**
 * Leitura unificada da trajetória da mulher no sistema.
 *
 * Esta classe NÃO é uma tabela: ela monta, somente para leitura, a lista de
 * eventos que a mulher acumulou nos módulos vinculados a `dados_mulher`.
 * Nenhuma tabela ou campo é criado ou alterado aqui.
 *
 * Três decisões que sustentam o conjunto:
 *
 * 1. Um módulo é consultado UMA VEZ, sempre pelo vínculo mais curto e mais
 *    direto. Isso elimina duplicação por construção. `documentos`, por
 *    exemplo, também aponta para `processo_id`, `medida_protetiva_id`,
 *    `encaminhamento_id`, `atendimento_id`, `manifestacao_id` e `caso_id` —
 *    se o histórico andasse por mais de um caminho, o mesmo documento
 *    apareceria repetido. Aqui ele entra só por `mulher_id`.
 *
 * 2. A ordem é montada em PHP, não por `UNION` de SQL. Cada módulo tem
 *    colunas diferentes, e o PHP permite tratar a data de cada um do seu
 *    jeito e ainda deduplicar com segurança.
 *
 * 3. A data do evento é a data específica do módulo; quando ela não existe,
 *    cai para `created_at`. Sem isso, registros sem data simplesmente
 *    desapareceriam do histórico.
 *
 * Acolhimentos e avaliações de risco não têm `mulher_id` na tabela, por isso
 * chegam pela cadeia `acolhimento → atendimento → mulher`. Encaminhamentos
 * e descumprimentos de medida também são resolvidos pela cadeia quando o
 * vínculo direto não está preenchido.
 */
class HistoricoMulherModel
{
    /** Valor do filtro que significa "todos os módulos". */
    public const TODOS = '';

    /**
     * Módulos da timeline.
     *
     * `chip`   rótulo do contador/filtro ("Pedidos SOS").
     * `rotulo` rótulo do evento na linha do tempo ("Pedido SOS").
     * `icone`  ícone do Lucide usado no evento.
     *
     * @var array<string, array{chip: string, rotulo: string, icone: string}>
     */
    public const MODULOS = [
        'caso'           => ['chip' => 'Casos', 'rotulo' => 'Caso', 'icone' => 'briefcase'],
        'atendimento'    => ['chip' => 'Atendimentos', 'rotulo' => 'Atendimento', 'icone' => 'clipboard-list'],
        'sos'            => ['chip' => 'Pedidos SOS', 'rotulo' => 'Pedido SOS', 'icone' => 'shield-alert'],
        'acolhimento'    => ['chip' => 'Acolhimentos', 'rotulo' => 'Acolhimento', 'icone' => 'life-buoy'],
        'avaliacao'      => ['chip' => 'Avaliações de risco', 'rotulo' => 'Avaliação de risco', 'icone' => 'triangle-alert'],
        'encaminhamento' => ['chip' => 'Encaminhamentos', 'rotulo' => 'Encaminhamento', 'icone' => 'send'],
        'medida'         => ['chip' => 'Medidas protetivas', 'rotulo' => 'Medida protetiva', 'icone' => 'shield'],
        'descumprimento' => ['chip' => 'Descumprimentos', 'rotulo' => 'Descumprimento de medida', 'icone' => 'shield-off'],
        'processo'       => ['chip' => 'Processos', 'rotulo' => 'Processo', 'icone' => 'gavel'],
        'documento'      => ['chip' => 'Documentos', 'rotulo' => 'Documento', 'icone' => 'file-text'],
        'compromisso'    => ['chip' => 'Compromissos', 'rotulo' => 'Compromisso', 'icone' => 'calendar-clock'],
        'tarefa'         => ['chip' => 'Tarefas', 'rotulo' => 'Tarefa', 'icone' => 'check-square'],
        'manifestacao'   => ['chip' => 'Manifestações', 'rotulo' => 'Manifestação', 'icone' => 'message-square'],
    ];

    /**
     * Trajetória completa da mulher, do evento mais recente para o mais
     * antigo.
     *
     * @return array{
     *     eventos: array<int, array<string, mixed>>,
     *     contagem: array<string, int>,
     *     total: int,
     *     modulo: string
     * }
     */
    public function timeline(int $mulherId, string $modulo = self::TODOS): array
    {
        $modulo = $this->normalizarModulo($modulo);

        // A contagem vem da lista INTEIRA, para os chips continuarem mostrando
        // o total real de cada módulo mesmo com um filtro aplicado.
        $todos  = $this->ordenar($this->coletar($mulherId));
        $total  = count($todos);
        $contagem = array_count_values(array_column($todos, 'modulo'));

        foreach (self::MODULOS as $chave => $_) {
            $contagem[$chave] ??= 0;
        }

        $eventos = $modulo === self::TODOS
            ? $todos
            : array_values(array_filter($todos, static fn (array $e): bool => $e['modulo'] === $modulo));

        return [
            'eventos'  => $eventos,
            'contagem' => $contagem,
            'total'    => $total,
            'modulo'   => $modulo,
        ];
    }

    /**
     * Filtro de módulo validado: qualquer valor fora da lista vira "todos",
     * em vez de devolver uma página vazia por erro de digitação na URL.
     */
    private function normalizarModulo(string $modulo): string
    {
        $modulo = trim($modulo);

        return isset(self::MODULOS[$modulo]) ? $modulo : self::TODOS;
    }

    /**
     * Junta os eventos de todos os módulos. Cada bloco devolve linhas no
     * mesmo formato; a montagem é feita aqui para manter a ordem dos módulos
     * estável (o `usort` do PHP é estável, então o desempate é determinístico).
     *
     * @return array<int, array<string, mixed>>
     */
    private function coletar(int $mulherId): array
    {
        $eventos = array_merge(
            $this->casos($mulherId),
            $this->atendimentos($mulherId),
            $this->pedidosSos($mulherId),
            $this->acolhimentos($mulherId),
            $this->avaliacoesRisco($mulherId),
            $this->encaminhamentos($mulherId),
            $this->medidasProtetivas($mulherId),
            $this->descumprimentos($mulherId),
            $this->processos($mulherId),
            $this->documentos($mulherId),
            $this->compromissos($mulherId),
            $this->tarefas($mulherId),
            $this->manifestacoes($mulherId),
        );

        return $eventos;
    }

    /**
     * Mais recente primeiro. Em caso de empate, vale a ordem do módulo e
     * depois o id, para a lista não "tremer" entre duas aberturas da página.
     *
     * @param array<int, array<string, mixed>> $eventos
     *
     * @return array<int, array<string, mixed>>
     */
    private function ordenar(array $eventos): array
    {
        $ordemModulos = array_flip(array_keys(self::MODULOS));

        usort($eventos, static function (array $a, array $b) use ($ordemModulos): int {
            $porData = strcmp((string) $b['data'], (string) $a['data']);

            if ($porData !== 0) {
                return $porData;
            }

            $porModulo = ($ordemModulos[$a['modulo']] ?? 99) <=> ($ordemModulos[$b['modulo']] ?? 99);

            return $porModulo !== 0 ? $porModulo : ((int) $a['id']) <=> ((int) $b['id']);
        });

        return $eventos;
    }

    // --------------------------------------------------------------------
    // Blocos por módulo
    // --------------------------------------------------------------------

    /**
     * @return array<int, array<string, mixed>>
     */
    private function casos(int $mulherId): array
    {
        $linhas = db_connect()
            ->table('casos')
            ->select('casos.id, casos.protocolo, casos.situacao, casos.prioridade, casos.data_abertura, casos.created_at, users.nome AS responsavel_nome')
            ->join('users', 'users.id = casos.responsavel_id', 'left')
            ->where('casos.mulher_id', $mulherId)
            ->get()
            ->getResultArray();

        return array_map(fn (array $c): array => $this->evento(
            modulo:  'caso',
            id:      (int) $c['id'],
            titulo:  'Caso ' . (string) $c['protocolo'],
            // O caso tem data_abertura obrigatória; o created_at é só rede de
            // segurança para registro antigo.
            data:    $c['data_abertura'],
            cadastro: $c['created_at'],
            situacao: (string) $c['situacao'],
            rotulo:  CasoModel::SITUACOES[(string) $c['situacao']] ?? ucfirst((string) $c['situacao']),
            url:     'casos/' . (int) $c['id'],
            detalhes: array_filter([
                ['Prioridade', $this->rotuloLista('prioridade', [], $c['prioridade'])],
                ['Responsável', (string) ($c['responsavel_nome'] ?? '')],
            ]),
        ), $linhas);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function atendimentos(int $mulherId): array
    {
        $linhas = db_connect()
            ->table('atendimentos')
            ->select('atendimentos.*, users.nome AS profissional_nome')
            ->join('users', 'users.id = atendimentos.profissional_id', 'left')
            ->where('atendimentos.mulher_id', $mulherId)
            ->get()
            ->getResultArray();

        return array_map(fn (array $a): array => $this->evento(
            modulo:  'atendimento',
            id:      (int) $a['id'],
            titulo:  'Atendimento ' . (string) $a['protocolo'],
            data:    $a['data_hora'],
            cadastro: $a['created_at'],
            situacao: (string) $a['situacao'],
            // O histórico antigo já mostrava estes rótulos vindos da
            // configuração; manter os dois é o que preserva a aparência atual.
            rotulo:  $this->rotuloLista('status_atendimento', AtendimentoModel::SITUACOES, $a['situacao']),
            url:     'atendimentos/' . (int) $a['id'],
            detalhes: array_filter([
                ['Prioridade', $this->rotuloLista('prioridade', AtendimentoModel::PRIORIDADES, $a['prioridade'])],
                ['Origem', $this->rotuloLista('origem_atendimento', AtendimentoModel::ORIGENS, $a['origem'])],
                ['Profissional', (string) ($a['profissional_nome'] ?? '')],
                ['Unidade / Setor', (string) ($a['unidade_setor'] ?? '')],
                ['Motivo do encerramento', $this->rotuloLista(
                    '',
                    AtendimentoModel::MOTIVOS_ENCERRAMENTO,
                    (string) ($a['motivo_encerramento'] ?? ''),
                )],
                ['Encerrado em', $this->mostrarData($a['data_encerramento'] ?? null)],
            ]),
        ), $linhas);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pedidosSos(int $mulherId): array
    {
        $linhas = db_connect()
            ->table('pedidos_sos')
            ->where('mulher_id', $mulherId)
            ->get()
            ->getResultArray();

        return array_map(fn (array $s): array => $this->evento(
            modulo:  'sos',
            id:      (int) $s['id'],
            titulo:  'Pedido SOS',
            data:    $s['data_hora'],
            cadastro: $s['created_at'],
            situacao: (string) $s['status'],
            rotulo:  PedidoSosModel::STATUS[(string) $s['status']] ?? ucfirst((string) $s['status']),
            url:     'pedidos-sos/' . (int) $s['id'],
            detalhes: array_filter([
                ['Visualizado em', $this->mostrarData($s['visualizado_em'] ?? null)],
            ]),
            // A localização segue disponível, com link para o mapa.
            geo: [
                'lat' => (string) $s['latitude'],
                'lon' => (string) $s['longitude'],
            ],
        ), $linhas);
    }

    /**
     * Acolhimento não tem `mulher_id`: entra pela cadeia
     * `acolhimento → atendimento → mulher`. O join é por chave primária, então
     * um acolhimento gera exatamente uma linha.
     *
     * @return array<int, array<string, mixed>>
     */
    private function acolhimentos(int $mulherId): array
    {
        $linhas = db_connect()
            ->table('acolhimentos')
            ->select('acolhimentos.*, atendimentos.protocolo AS atendimento_protocolo')
            ->join('atendimentos', 'atendimentos.id = acolhimentos.atendimento_id', 'inner')
            ->where('atendimentos.mulher_id', $mulherId)
            ->get()
            ->getResultArray();

        return array_map(fn (array $a): array => $this->evento(
            modulo:  'acolhimento',
            id:      (int) $a['id'],
            titulo:  'Acolhimento' . ($a['atendimento_protocolo'] ? ' do atendimento ' . (string) $a['atendimento_protocolo'] : ''),
            data:    $a['data_hora'],
            cadastro: $a['created_at'],
            situacao: (string) $a['status'],
            rotulo:  AcolhimentoModel::STATUS[(string) $a['status']] ?? ucfirst((string) $a['status']),
            url:     'acolhimentos/' . (int) $a['id'],
            detalhes: array_filter([
                ['Risco identificado', AcolhimentoModel::RISCOS[(string) ($a['risco_identificado'] ?? '')] ?? (string) ($a['risco_identificado'] ?? '')],
                ['Motivo da procura', $this->resumir($a['motivo_procura'] ?? null)],
                ['Concluído em', $this->mostrarData($a['data_conclusao'] ?? null)],
            ]),
        ), $linhas);
    }

    /**
     * Avaliação de risco não tem `mulher_id` nem ligação direta à mulher:
     * chega pelo acolhimento e, dele, pelo atendimento.
     *
     * @return array<int, array<string, mixed>>
     */
    private function avaliacoesRisco(int $mulherId): array
    {
        $sinais = [
            'ameaca_morte'          => 'Ameaça de morte',
            'agressao_fisica'       => 'Agressão física',
            'estrangulamento'       => 'Estrangulamento',
            'agressor_arma'         => 'Agressor com arma',
            'ameaca_com_arma'       => 'Ameaça com arma',
            'violencia_aumentando'  => 'Violência aumentando',
            'tentou_separar'        => 'Tentou separar',
            'controlador'           => 'Controlador',
            'perseguicao'           => 'Perseguição',
            'criancas_envolvidas'   => 'Crianças envolvidas',
            'dependencia_financeira' => 'Dependência financeira',
            'descumpriu_medida'     => 'Descumpriu medida',
        ];

        $linhas = db_connect()
            ->table('avaliacoes_risco')
            ->select('avaliacoes_risco.*')
            ->join('acolhimentos', 'acolhimentos.id = avaliacoes_risco.acolhimento_id', 'inner')
            ->join('atendimentos', 'atendimentos.id = acolhimentos.atendimento_id', 'inner')
            ->where('atendimentos.mulher_id', $mulherId)
            ->get()
            ->getResultArray();

        return array_map(function (array $v) use ($sinais): array {
            $marcados = [];

            foreach ($sinais as $coluna => $rotulo) {
                if (! empty($v[$coluna])) {
                    $marcados[] = $rotulo;
                }
            }

            return $this->evento(
                modulo:  'avaliacao',
                id:      (int) $v['id'],
                titulo:  'Avaliação de risco',
                data:    $v['data_hora'],
                cadastro: $v['created_at'],
                situacao: (string) ($v['classificacao'] ?? ''),
                rotulo:  ucfirst(str_replace('_', ' ', (string) ($v['classificacao'] ?? 'avaliada'))),
                url:     'avaliacoes/' . (int) $v['id'],
                detalhes: array_filter([
                    ['Sinais identificados', $marcados === [] ? '' : implode(', ', $marcados)],
                    ['Classificação sugerida', ucfirst(str_replace('_', ' ', (string) ($v['classificacao_sugerida'] ?? '')))],
                    ['Observações', $this->resumir($v['observacoes_profissionais'] ?? null)],
                ]),
            );
        }, $linhas);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function encaminhamentos(int $mulherId): array
    {
        // O vínculo direto é `mulher_id`. O `atendimento_id` entra só para não
        // perder encaminhamento antigo registrado sem a mulher preenchida —
        // e nunca junto com o primeiro caminho, para não vir duplicata.
        $atendimentos = array_column(
            db_connect()->table('atendimentos')->select('id')->where('mulher_id', $mulherId)->get()->getResultArray(),
            'id'
        );

        $query = db_connect()
            ->table('encaminhamentos')
            ->select('encaminhamentos.*, users.nome AS profissional_nome')
            ->join('users', 'users.id = encaminhamentos.profissional_id', 'left')
            ->groupStart()
                ->where('encaminhamentos.mulher_id', $mulherId);

        if ($atendimentos !== []) {
            $query->orGroupStart()
                ->where('encaminhamentos.mulher_id', null)
                ->whereIn('encaminhamentos.atendimento_id', $atendimentos)
                ->groupEnd();
        }

        $linhas = $query->groupEnd()->get()->getResultArray();

        return array_map(fn (array $e): array => $this->evento(
            modulo:  'encaminhamento',
            id:      (int) $e['id'],
            titulo:  'Encaminhamento para ' . ((string) ($e['orgao'] ?? '')) . ((string) ($e['servico'] ?? '') ? ' — ' . (string) $e['servico'] : ''),
            data:    $e['data_hora'],
            cadastro: $e['created_at'],
            situacao: (string) $e['status'],
            rotulo:  EncaminhamentoModel::STATUS[(string) $e['status']] ?? ucfirst((string) $e['status']),
            url:     'encaminhamentos/' . (int) $e['id'],
            detalhes: array_filter([
                ['Profissional', (string) ($e['profissional_nome'] ?? '')],
                ['Prioridade', $this->rotuloLista('prioridade', [], $e['prioridade'])],
                ['Prazo', $this->mostrarData($e['prazo'] ?? null)],
                ['Motivo', $this->resumir($e['motivo'] ?? null)],
                ['Concluído em', $this->mostrarData($e['data_conclusao'] ?? null)],
            ]),
        ), $linhas);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function medidasProtetivas(int $mulherId): array
    {
        $linhas = db_connect()
            ->table('medidas_protetivas')
            ->where('mulher_id', $mulherId)
            ->get()
            ->getResultArray();

        return array_map(fn (array $m): array => $this->evento(
            modulo:  'medida',
            id:      (int) $m['id'],
            titulo:  'Medida protetiva' . ((string) ($m['numero'] ?? '') ? ' ' . (string) $m['numero'] : ''),
            // A concessão é o fato consumado; sem ela, vale o pedido. O
            // prazo de validade não é data de evento: é só informação.
            data:    $m['data_concessao'] ?: $m['data_solicitacao'],
            cadastro: $m['created_at'],
            situacao: (string) $m['situacao'],
            rotulo:  MedidaProtetivaModel::SITUACOES[(string) $m['situacao']] ?? ucfirst((string) $m['situacao']),
            url:     'medidas-protetivas/' . (int) $m['id'],
            detalhes: array_filter([
                ['Órgão', (string) ($m['orgao'] ?? '')],
                ['Processo', (string) ($m['processo'] ?? '')],
                ['Solicitada em', $this->mostrarData($m['data_solicitacao'] ?? null)],
                ['Concedida em', $this->mostrarData($m['data_concessao'] ?? null)],
                ['Validade', $this->mostrarData($m['data_validade'] ?? null)],
            ]),
        ), $linhas);
    }

    /**
     * Descumprimento não tem `mulher_id`: chega pela medida protetiva.
     * Uma medida pode ter vários descumprimentos, e cada um é um evento
     * distinto — não é duplicação.
     *
     * @return array<int, array<string, mixed>>
     */
    private function descumprimentos(int $mulherId): array
    {
        $linhas = db_connect()
            ->table('medida_descumprimentos')
            ->select('medida_descumprimentos.*, medidas_protetivas.numero AS medida_numero, users.nome AS usuario_nome')
            ->join('medidas_protetivas', 'medidas_protetivas.id = medida_descumprimentos.medida_id', 'inner')
            ->join('users', 'users.id = medida_descumprimentos.usuario_id', 'left')
            ->where('medidas_protetivas.mulher_id', $mulherId)
            ->get()
            ->getResultArray();

        return array_map(fn (array $d): array => $this->evento(
            modulo:  'descumprimento',
            id:      (int) $d['id'],
            titulo:  'Descumprimento de medida' . ((string) ($d['medida_numero'] ?? '') ? ' ' . (string) $d['medida_numero'] : ''),
            data:    $d['data_ocorrencia'],
            cadastro: $d['created_at'],
            situacao: 'registrado',
            rotulo:  'Registrado',
            url:     'medidas-protetivas/' . (int) $d['medida_id'],
            detalhes: array_filter([
                ['Ocorrido em', $this->mostrarData($d['data_ocorrencia'] ?? null)],
                ['Registrado por', (string) ($d['usuario_nome'] ?? '')],
                ['Descrição', $this->resumir($d['descricao'] ?? null)],
                ['Decisão da equipe', $this->resumir($d['historico'] ?? null)],
            ]),
        ), $linhas);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function processos(int $mulherId): array
    {
        $linhas = db_connect()
            ->table('processos')
            ->where('mulher_id', $mulherId)
            ->get()
            ->getResultArray();

        return array_map(fn (array $p): array => $this->evento(
            modulo:  'processo',
            id:      (int) $p['id'],
            titulo:  'Processo' . ((string) ($p['numero_processo'] ?? '') ? ' ' . (string) $p['numero_processo'] : ''),
            data:    $p['data'],
            cadastro: $p['created_at'],
            situacao: (string) $p['situacao'],
            rotulo:  ProcessoModel::SITUACOES[(string) $p['situacao']] ?? ucfirst((string) $p['situacao']),
            url:     'processos/' . (int) $p['id'],
            detalhes: array_filter([
                ['Tipo', ProcessoModel::TIPOS[(string) ($p['tipo'] ?? '')] ?? ucfirst((string) ($p['tipo'] ?? ''))],
                ['Órgão', (string) ($p['orgao'] ?? '')],
                ['Vara', (string) ($p['vara'] ?? '')],
                ['Comarca', (string) ($p['comarca'] ?? '')],
                ['Observações', $this->resumir($p['observacoes'] ?? null)],
            ]),
        ), $linhas);
    }

    /**
     * Documento entra SOMENTE por `mulher_id`. É o módulo com mais caminhos
     * possíveis até ele (processo, medida, encaminhamento, atendimento,
     * manifestação, caso); usar mais de um repetiria o mesmo documento.
     *
     * @return array<int, array<string, mixed>>
     */
    private function documentos(int $mulherId): array
    {
        $linhas = db_connect()
            ->table('documentos')
            ->where('mulher_id', $mulherId)
            // Documento excluído (soft delete) não faz parte da trajetória.
            ->where('excluido_em', null)
            ->get()
            ->getResultArray();

        return array_map(fn (array $d): array => $this->evento(
            modulo:  'documento',
            id:      (int) $d['id'],
            titulo:  'Documento — ' . (DocumentoModel::TIPOS[(string) $d['tipo']] ?? ucfirst((string) $d['tipo'])),
            // Documento não tem data de evento própria: entra quando foi
            // registrado no sistema.
            data:    null,
            cadastro: $d['created_at'],
            situacao: (string) $d['sigilo'],
            rotulo:  DocumentoModel::SIGILOS[(string) $d['sigilo']] ?? ucfirst((string) $d['sigilo']),
            url:     'documentos/' . (int) $d['id'],
            detalhes: array_filter([
                ['Sigilo', DocumentoModel::SIGILOS[(string) $d['sigilo']] ?? ''],
                ['Arquivo', (string) ($d['arquivo_original'] ?? '')],
                ['Observações', $this->resumir($d['observacoes'] ?? null)],
            ]),
        ), $linhas);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function compromissos(int $mulherId): array
    {
        $linhas = db_connect()
            ->table('compromissos')
            ->select('compromissos.*, users.nome AS profissional_nome')
            ->join('users', 'users.id = compromissos.profissional_id', 'left')
            ->where('compromissos.mulher_id', $mulherId)
            ->get()
            ->getResultArray();

        return array_map(fn (array $c): array => $this->evento(
            modulo:  'compromisso',
            id:      (int) $c['id'],
            titulo:  CompromissoModel::TIPOS[(string) $c['tipo']] ?? 'Compromisso',
            // `data` é DATE e `hora_inicio` é hora: juntos formam o instante
            // real, o que evita empates artificiais na ordenação.
            data:    trim((string) ($c['data'] ?? '') . ' ' . (string) ($c['hora_inicio'] ?? '')),
            cadastro: $c['created_at'],
            situacao: (string) $c['status'],
            rotulo:  CompromissoModel::STATUS[(string) $c['status']] ?? ucfirst((string) $c['status']),
            url:     'agenda/' . (int) $c['id'],
            detalhes: array_filter([
                ['Data', $this->mostrarData($c['data'] ?? null)],
                ['Horário', trim((string) ($c['hora_inicio'] ?? '') . ($c['hora_fim'] ? ' às ' . (string) $c['hora_fim'] : ''))],
                ['Profissional', (string) ($c['profissional_nome'] ?? '')],
                ['Observações', $this->resumir($c['observacoes'] ?? null)],
            ]),
        ), $linhas);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function tarefas(int $mulherId): array
    {
        $linhas = db_connect()
            ->table('tarefas')
            ->select('tarefas.*, users.nome AS responsavel_nome')
            ->join('users', 'users.id = tarefas.responsavel_id', 'left')
            ->where('tarefas.mulher_id', $mulherId)
            ->get()
            ->getResultArray();

        return array_map(fn (array $t): array => $this->evento(
            modulo:  'tarefa',
            id:      (int) $t['id'],
            titulo:  'Tarefa — ' . (string) $t['titulo'],
            // `prazo` NÃO é a data do evento: é o vencimento, e usá-lo
            // colocaria uma tarefa futura no topo de um histórico. A tarefa
            // entra quando foi criada; quando concluída, na conclusão.
            data:    $t['data_conclusao'],
            cadastro: $t['created_at'],
            situacao: (string) $t['status'],
            rotulo:  TarefaModel::STATUS[(string) $t['status']] ?? ucfirst((string) $t['status']),
            url:     'tarefas/' . (int) $t['id'],
            detalhes: array_filter([
                ['Responsável', (string) ($t['responsavel_nome'] ?? '')],
                ['Prioridade', $this->rotuloLista('prioridade', [], $t['prioridade'])],
                ['Prazo', $this->mostrarData($t['prazo'] ?? null)],
                ['Concluída em', $this->mostrarData($t['data_conclusao'] ?? null)],
                ['Descrição', $this->resumir($t['descricao'] ?? null)],
            ]),
        ), $linhas);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function manifestacoes(int $mulherId): array
    {
        $linhas = db_connect()
            ->table('manifestacoes')
            ->where('mulher_id', $mulherId)
            ->get()
            ->getResultArray();

        return array_map(fn (array $m): array => $this->evento(
            modulo:  'manifestacao',
            id:      (int) $m['id'],
            titulo:  'Manifestação ' . (string) $m['protocolo'],
            data:    $m['data_hora'],
            cadastro: $m['created_at'],
            situacao: (string) $m['situacao'],
            rotulo:  ManifestacaoModel::SITUACOES[(string) $m['situacao']] ?? ucfirst((string) $m['situacao']),
            url:     'manifestacoes/' . (int) $m['id'],
            detalhes: array_filter([
                ['Tipo', ManifestacaoModel::TIPOS[(string) $m['tipo']] ?? ucfirst((string) $m['tipo'])],
                ['Origem', ManifestacaoModel::ORIGENS[(string) $m['origem']] ?? ucfirst((string) $m['origem'])],
                ['Prioridade', $this->rotuloLista('prioridade', [], $m['prioridade'])],
                ['Descrição', $this->resumir($m['descricao'] ?? null)],
            ]),
        ), $linhas);
    }

    // --------------------------------------------------------------------
    // Normalização
    // --------------------------------------------------------------------

    /**
     * Monta o evento no formato único da timeline.
     *
     * @param array<int, array{0: string, 1: string}> $detalhes
     * @param array{lat: string, lon: string}|null     $geo
     *
     * @return array<string, mixed>
     */
    private function evento(
        string $modulo,
        int $id,
        string $titulo,
        ?string $data,
        ?string $cadastro,
        string $situacao,
        string $rotulo,
        string $url,
        array $detalhes = [],
        ?array $geo = null,
    ): array {
        // Data específica do módulo primeiro; `created_at` como rede de
        // segurança. Se nem isso existir, o evento fica no fim da lista em vez
        // de sumir.
        $principal = $this->normalizarData($data);
        $reserva   = $this->normalizarData($cadastro);
        $usouCadastro = $principal === null && $reserva !== null;

        return [
            'modulo'       => $modulo,
            'rotulo'       => self::MODULOS[$modulo]['rotulo'] ?? $modulo,
            'icone'        => self::MODULOS[$modulo]['icone'] ?? 'circle',
            'id'           => $id,
            'titulo'       => $titulo,
            'data'         => $principal ?? $reserva ?? '',
            'dataCadastro' => $usouCadastro,
            'situacao'     => $situacao,
            'situacaoRotulo' => $rotulo,
            'detalhes'     => $detalhes,
            'geo'          => $geo,
            'url'          => $url,
        ];
    }

    /**
     * Reduz qualquer formato de data do banco para 'Y-m-d H:i:s', para que a
     * ordenação por string funcione mesmo misturando DATE e DATETIME.
     */
    private function normalizarData(?string $valor): ?string
    {
        $texto = trim((string) $valor);

        if ($texto === '' || str_starts_with($texto, '0000')) {
            return null;
        }

        $timestamp = strtotime(str_replace('T', ' ', $texto));

        return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
    }

    /**
     * Rótulo vindo da configuração, com o dicionário do model e um
     * "bonito" como último recurso — assim nenhum evento fica sem rótulo.
     */
    private function rotuloLista(string $categoria, array $dicionario, mixed $chave): string
    {
        $chave = trim((string) $chave);

        if ($chave === '') {
            return '';
        }

        if ($categoria !== '') {
            $configuradas = ConfiguracaoListaModel::todasCategoria($categoria);

            if (isset($configuradas[$chave])) {
                return (string) $configuradas[$chave];
            }
        }

        return (string) ($dicionario[$chave] ?? ucfirst(str_replace('_', ' ', $chave)));
    }

    private function mostrarData(?string $valor): string
    {
        $normalizada = $this->normalizarData($valor);

        // Data sem hora não ganha "00:00" na tela.
        return $normalizada === null
            ? ''
            : (strlen(trim((string) $valor)) <= 10
                ? date('d/m/Y', strtotime($normalizada))
                : date('d/m/Y H:i', strtotime($normalizada)));
    }

    private function resumir(?string $valor, int $limite = 180): string
    {
        $texto = trim(preg_replace('/\s+/', ' ', (string) $valor) ?? '');

        if ($texto === '') {
            return '';
        }

        return mb_strlen($texto) > $limite ? mb_substr($texto, 0, $limite - 1) . '…' : $texto;
    }
}
