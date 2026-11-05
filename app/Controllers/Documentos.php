<?php

namespace App\Controllers;

use App\Libraries\ContextoMulher;
use App\Models\AtendimentoModel;
use App\Models\ConfiguracaoListaModel;
use App\Models\DocumentoHistoricoModel;
use App\Models\DocumentoModel;
use App\Models\EncaminhamentoModel;
use App\Models\MedidaProtetivaModel;
use App\Models\ManifestacaoModel;
use App\Models\MulherModel;
use App\Models\ProcessoModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Módulo PROCESSOS > Documentos.
 *
 * Repositório seguro de documentos: o arquivo físico fica em
 * writable/uploads/documentos (FORA do docroot) e somente é acessado pelos
 * endpoints de visualização/download deste controller, com registro no
 * histórico. A mulher é obrigatória; atendimento/processo/medida/
 * dependências e encaminhamento são vínculos opcionais da mesma mulher. A exclusão é
 * LÓGICA (preserva arquivo e histórico). O responsável é SEMPRE o usuário
 * autenticado.
 *
 * Módulo OUVIDORIA: anexos de manifestação usam o mesmo repositório
 * (manifestacao_id opcional, vínculo com a MESMA mulher, ou __qualquer__ mulher
 * quando a manifestação é anônima — manifestações anônimas não possuem mulher).
 */
class Documentos extends BaseController
{
    protected $documentoModel;

    public function __construct()
    {
        $this->documentoModel = new DocumentoModel();
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
     * Listagem de documentos com filtros compactos (padrão de Tarefas).
     */
    public function index()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $filtros = [
            'mulher'        => trim((string) $this->request->getGet('mulher')),
            'tipo'          => (string) $this->request->getGet('tipo'),
            'sigilo'        => (string) $this->request->getGet('sigilo'),
            'atendimento'   => trim((string) $this->request->getGet('atendimento')),
            'processo'      => trim((string) $this->request->getGet('processo')),
            'medida'        => trim((string) $this->request->getGet('medida')),
            'encaminhamento' => trim((string) $this->request->getGet('encaminhamento')),
            'responsavel'   => max(0, (int) $this->request->getGet('responsavel')),
            'data_de'       => trim((string) $this->request->getGet('data_de')),
            'data_ate'      => trim((string) $this->request->getGet('data_ate')),
        ];

        $model = $this->documentoModel->comDados();

        if ($filtros['mulher'] !== '') {
            $termo = $filtros['mulher'];
            $model->groupStart()
                ->like('dados_mulher.nome_completo', $termo)
                ->orLike('dados_mulher.nome_social', $termo)
                ->orLike('dados_mulher.cpf', $termo)
                ->groupEnd();
        }
        if ($filtros['tipo'] !== '' && array_key_exists($filtros['tipo'], ConfiguracaoListaModel::todasCategoria('tipo_documento'))) {
            $model->where('documentos.tipo', $filtros['tipo']);
        }
        if ($filtros['sigilo'] !== '' && array_key_exists($filtros['sigilo'], DocumentoModel::SIGILOS)) {
            $model->where('documentos.sigilo', $filtros['sigilo']);
        }
        if ($filtros['atendimento'] !== '') {
            $model->like('atendimentos.protocolo', $filtros['atendimento']);
        }
        if ($filtros['processo'] !== '') {
            $model->like('processos.numero_processo', $filtros['processo']);
        }
        if ($filtros['medida'] !== '') {
            $model->like('medidas_protetivas.numero', $filtros['medida']);
        }
        if ($filtros['encaminhamento'] !== '') {
            $model->like('encaminhamentos.id', $filtros['encaminhamento']);
        }
        if ($filtros['responsavel'] > 0) {
            $model->where('documentos.usuario_id', $filtros['responsavel']);
        }
        if ($this->dataValida($filtros['data_de'])) {
            $model->where('documentos.created_at >=', $filtros['data_de'] . ' 00:00:00');
        }
        if ($this->dataValida($filtros['data_ate'])) {
            $model->where('documentos.created_at <=', $filtros['data_ate'] . ' 23:59:59');
        }

        $documentos = $model->orderBy('documentos.created_at', 'DESC')
            ->orderBy('documentos.id', 'DESC')
            ->paginate(15);

        return view('documentos/lista', [
            'titulo'      => 'Documentos',
            'documentos'  => $documentos,
            'pager'       => $this->documentoModel->pager,
            'filtros'     => $filtros,
            'tipos'       => ConfiguracaoListaModel::todasCategoria('tipo_documento'),
            'sigilos'     => DocumentoModel::SIGILOS,
            'sigiloCores' => DocumentoModel::SIGILO_APARENCIA,
            'tamanhoMax'  => DocumentoModel::MAX_TAMANHO_BYTES,
            'equipe'      => $this->equipeAtiva(),
        ]);
    }

    /**
     * Formulário de novo documento, com vínculos opcionais pré-selecionados.
     */
    public function novo()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $mulherPreselecionada = null;
        $atendimentoPreselecionado = null;
        $processoPreselecionado = null;
        $medidaPreselecionada = null;
        $encaminhamentoPreselecionado = null;
        $manifestacaoPreselecionada = null;

        $atendimentoId = (int) $this->request->getGet('atendimento_id');
        if ($atendimentoId > 0) {
            $a = (new AtendimentoModel())->comDados()
                ->where('atendimentos.id', $atendimentoId)
                ->first();
            if ($a !== null) {
                $atendimentoPreselecionado = $this->linhaAtendimento($a);
                $mulherPreselecionada = $this->mulherPreselecionada((int) $a['mulher_id']);
            }
        }

        $processoId = (int) $this->request->getGet('processo_id');
        if ($processoId > 0) {
            $p = (new ProcessoModel())->comDados()
                ->where('processos.id', $processoId)
                ->first();
            if ($p !== null) {
                $processoPreselecionado = $this->linhaProcesso($p);
                if ($mulherPreselecionada === null) {
                    $mulherPreselecionada = $this->mulherPreselecionada((int) $p['mulher_id']);
                }
            }
        }

        $medidaId = (int) $this->request->getGet('medida_protetiva_id');
        if ($medidaId > 0) {
            $m = (new MedidaProtetivaModel())->comDados()
                ->where('medidas_protetivas.id', $medidaId)
                ->first();
            if ($m !== null) {
                $medidaPreselecionada = $this->linhaMedida($m);
                if ($mulherPreselecionada === null) {
                    $mulherPreselecionada = $this->mulherPreselecionada((int) $m['mulher_id']);
                }
            }
        }

        $encaminhamentoId = (int) $this->request->getGet('encaminhamento_id');
        if ($encaminhamentoId > 0) {
            $e = (new EncaminhamentoModel())->comDados()
                ->where('encaminhamentos.id', $encaminhamentoId)
                ->first();
            if ($e !== null) {
                $encaminhamentoPreselecionado = $this->linhaEncaminhamento($e);
                if ($mulherPreselecionada === null) {
                    $mulherPreselecionada = $this->mulherPreselecionada((int) $e['mulher_id']);
                }
            }
        }

        $manifestacaoId = (int) $this->request->getGet('manifestacao_id');
        if ($manifestacaoId > 0) {
            $ma = (new ManifestacaoModel())->comDados()
                ->where('manifestacoes.id', $manifestacaoId)
                ->first();
            if ($ma !== null) {
                $manifestacaoPreselecionada = $this->linhaManifestacao($ma);
                if ($mulherPreselecionada === null && ! empty($ma['mulher_id'])) {
                    $mulherPreselecionada = $this->mulherPreselecionada((int) $ma['mulher_id']);
                }
            }
        }

        $mulherId = (int) $this->request->getGet('mulher_id');
        if ($mulherId > 0 && $mulherPreselecionada === null) {
            $mulherPreselecionada = $this->mulherPreselecionada($mulherId);
        }

        return view('documentos/form', [
            'titulo'                     => 'Novo Documento',
            'editando'                   => false,
            'documento'                  => null,
            'mulherPreselecionada'       => $mulherPreselecionada,
            'atendimentoPreselecionado'  => $atendimentoPreselecionado,
            'processoPreselecionado'     => $processoPreselecionado,
            'medidaPreselecionada'       => $medidaPreselecionada,
            'encaminhamentoPreselecionado' => $encaminhamentoPreselecionado,
            'manifestacaoPreselecionada' => $manifestacaoPreselecionada,
            'contexto'                   => (new ContextoMulher())->montar((int) ($mulherPreselecionada['id'] ?? 0)),
            'usuarioNome'                => (string) session()->get('usuario_nome'),
            'tipos'                      => ConfiguracaoListaModel::opcoesCategoria('tipo_documento'),
            'sigilos'                    => DocumentoModel::SIGILOS,
            'tamanhoMax'                 => DocumentoModel::MAX_TAMANHO_BYTES,
        ]);
    }

    /**
     * Registra o documento. A mulher é obrigatória; os demais vínculos são
     * opcionais (sempre da mesma mulher). O arquivo é validado (extensão,
     * MIME e tamanho) e persistido fora do docroot. O responsável é o usuário
     * autenticado.
     */
    public function criar()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        if (! $this->validate($this->regrasArquivo(), $this->mensagensArquivo())) {
            return redirect()->back()->withInput()->with('erros', $this->validator->getErrors());
        }

        [$mulherId, $atendimentoId, $processoId, $medidaId, $encaminhamentoId, $manifestacaoId, $erro] = $this->validarVinculos();
        if ($erro !== null) {
            $campo = (string) key($erro);
            $mensagem = (string) reset($erro);

            return redirect()->back()->withInput()->with('erros', [$campo => $mensagem]);
        }

        $arquivo = $this->request->getFile('arquivo');
        $caminhoRelativo = $this->gravarArquivo($arquivo);
        if ($caminhoRelativo === null) {
            return redirect()->back()->withInput()->with('erros', ['arquivo' => 'Não foi possível salvar o arquivo no servidor.']);
        }

        $dados = $this->dadosDoPost();
        $dados['mulher_id'] = $mulherId;
        $dados['atendimento_id'] = $atendimentoId;
        $dados['processo_id'] = $processoId;
        $dados['medida_protetiva_id'] = $medidaId;
        $dados['encaminhamento_id'] = $encaminhamentoId;
        $dados['manifestacao_id'] = $manifestacaoId;
        $dados['arquivo'] = $caminhoRelativo;
        $dados['arquivo_original'] = trim((string) $arquivo->getClientName(), " \t\n\r\0\x0B") ?: 'arquivo';
        $dados['tamanho_bytes'] = $arquivo->getSize() ?: null;
        $dados['tipo_mime'] = $arquivo->getMimeType() ?: null;
        $dados['usuario_id'] = (int) session()->get('usuario_id');

        if (! $this->documentoModel->save($dados)) {
            $this->removerArquivoFisico($caminhoRelativo);

            return redirect()->back()->withInput()->with('erros', $this->documentoModel->errors());
        }

        $id = (int) $this->documentoModel->getInsertID();

        (new DocumentoHistoricoModel())->registrar(
            $id,
            'upload',
            null,
            $dados['sigilo'],
            (int) session()->get('usuario_id'),
            'Arquivo "' . mb_substr($dados['arquivo_original'], 0, 120) . '" enviado.'
        );

        $this->auditar('UPLOAD', 'documentos', $id, 'documento', (string) ($dados['arquivo_original'] ?? ''), [
            'arquivo' => (string) ($dados['arquivo_original'] ?? ''),
            'sigilo'  => (string) ($dados['sigilo'] ?? ''),
        ]);

        return redirect()->to('/documentos/' . $id)
            ->with('sucesso', 'Documento enviado.');
    }

    /**
     * Ficha do documento: dados, vínculos, arquivo e histórico.
     */
    public function detalhes(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $documento = $this->documentoModel->comDados()
            ->where('documentos.id', $id)
            ->first();

        if ($documento === null) {
            return redirect()->to('/documentos')->with('erro', 'Documento não encontrado.');
        }

        $arquivoExiste = is_file($this->caminhoArquivo((string) $documento['arquivo']));

        return view('documentos/detalhes', [
            'documento'     => $documento,
            'titulo'        => 'Documento #' . $id,
            'tipos'         => ConfiguracaoListaModel::todasCategoria('tipo_documento'),
            'sigilos'       => DocumentoModel::SIGILOS,
            'sigiloCores'   => DocumentoModel::SIGILO_APARENCIA,
            'situacoesMedida' => ConfiguracaoListaModel::todasCategoria('situacao_medida'),
            'situacoesEncaminhamento' => ConfiguracaoListaModel::todasCategoria('status_encaminhamento'),
            'situacoesManifestacao' => ConfiguracaoListaModel::todasCategoria('situacao_manifestacao'),
            'arquivoExiste' => $arquivoExiste,
            'historico'     => (new DocumentoHistoricoModel())->listarPorDocumento($id),
        ]);
    }

    public function editar(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $documento = $this->documentoModel->comDados()
            ->where('documentos.id', $id)
            ->first();

        if ($documento === null) {
            return redirect()->to('/documentos')->with('erro', 'Documento não encontrado.');
        }

        return view('documentos/form', [
            'titulo'                     => 'Editar Documento #' . $id,
            'editando'                   => true,
            'documento'                  => $documento,
            'mulherPreselecionada'       => $this->mulherPreselecionada((int) $documento['mulher_id']),
            'atendimentoPreselecionado'  => ! empty($documento['atendimento_id'])
                ? $this->linhaAtendimento($documento)
                : null,
            'processoPreselecionado'     => ! empty($documento['processo_id'])
                ? $this->linhaProcesso($documento)
                : null,
            'medidaPreselecionada'       => ! empty($documento['medida_protetiva_id'])
                ? $this->linhaMedida($documento)
                : null,
            'encaminhamentoPreselecionado' => ! empty($documento['encaminhamento_id'])
                ? $this->linhaEncaminhamento($documento)
                : null,
            'manifestacaoPreselecionada' => ! empty($documento['manifestacao_id'])
                ? $this->linhaManifestacao($documento)
                : null,
            'usuarioNome'                => (string) session()->get('usuario_nome'),
            'tipos'                      => ConfiguracaoListaModel::opcoesCategoria('tipo_documento'),
            'sigilos'                    => DocumentoModel::SIGILOS,
            'tamanhoMax'                 => DocumentoModel::MAX_TAMANHO_BYTES,
        ]);
    }

    /**
     * Atualiza o MESMO registro (não cria novo documento). Permite substituir
     * o arquivo se um novo for anexado; caso contrário, mantém o atual.
     */
    public function atualizar(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $atual = $this->documentoModel->find($id);
        if ($atual === null) {
            return redirect()->to('/documentos')->with('erro', 'Documento não encontrado.');
        }

        [$mulherId, $atendimentoId, $processoId, $medidaId, $encaminhamentoId, $manifestacaoId, $erro] = $this->validarVinculos();
        if ($erro !== null) {
            $campo = (string) key($erro);
            $mensagem = (string) reset($erro);

            return redirect()->back()->withInput()->with('erros', [$campo => $mensagem]);
        }

        $dados = $this->dadosDoPost();
        $dados['mulher_id'] = $mulherId;
        $dados['atendimento_id'] = $atendimentoId;
        $dados['processo_id'] = $processoId;
        $dados['medida_protetiva_id'] = $medidaId;
        $dados['encaminhamento_id'] = $encaminhamentoId;
        $dados['manifestacao_id'] = $manifestacaoId;

        // Substituição opcional do arquivo (novo anexo preenche os metadados).
        $arquivo = $this->request->getFile('arquivo');
        $temArquivoNovo = $arquivo !== null && $arquivo->getError() !== UPLOAD_ERR_NO_FILE;
        $caminhoNovo = null;
        $caminhoAntigo = (string) ($atual['arquivo'] ?? '');

        if ($temArquivoNovo) {
            if (! $this->validate($this->regrasArquivo(), $this->mensagensArquivo())) {
                return redirect()->back()->withInput()->with('erros', $this->validator->getErrors());
            }

            $caminhoNovo = $this->gravarArquivo($arquivo);
            if ($caminhoNovo === null) {
                return redirect()->back()->withInput()->with('erros', ['arquivo' => 'Não foi possível salvar o novo arquivo no servidor.']);
            }

            $dados['arquivo'] = $caminhoNovo;
            $dados['arquivo_original'] = trim((string) $arquivo->getClientName(), " \t\n\r\0\x0B") ?: 'arquivo';
            $dados['tamanho_bytes'] = $arquivo->getSize() ?: null;
            $dados['tipo_mime'] = $arquivo->getMimeType() ?: null;
        }

        if (! $this->documentoModel->update($id, $dados)) {
            if ($caminhoNovo !== null) {
                $this->removerArquivoFisico($caminhoNovo);
            }

            return redirect()->back()->withInput()->with('erros', $this->documentoModel->errors());
        }

        if ($caminhoNovo !== null && $caminhoAntigo !== '' && $caminhoAntigo !== $caminhoNovo) {
            $this->removerArquivoFisico($caminhoAntigo);
        }

        $historico = new DocumentoHistoricoModel();
        $usuarioId = (int) session()->get('usuario_id');

        $sigiloAnterior = $atual['sigilo'] === null ? null : (string) $atual['sigilo'];
        if ($sigiloAnterior !== $dados['sigilo']) {
            $historico->registrar($id, 'sigilo', $sigiloAnterior, $dados['sigilo'], $usuarioId, 'Classificação de sigilo alterada.');
        }

        $observacaoEdicao = $temArquivoNovo
            ? 'Arquivo substituído por "' . mb_substr((string) ($dados['arquivo_original'] ?? 'arquivo'), 0, 120) . '".'
            : 'Metadados do documento editados.';
        $historico->registrar($id, 'edicao', null, null, $usuarioId, $observacaoEdicao);

        $this->auditar('UPDATE', 'documentos', $id, 'documento', (string) ($dados['arquivo_original'] ?? ''), $temArquivoNovo
            ? ['substituiu_arquivo' => true]
            : []);

        return redirect()->to('/documentos/' . $id)
            ->with('sucesso', 'Documento atualizado.');
    }

    /**
     * Exclusão LÓGICA: marca excluido_em/excluido_por e registra no histórico.
     * O arquivo físico e o histórico são preservados para recuperação.
     */
    public function excluir(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $documento = $this->documentoModel->find($id);
        if ($documento === null) {
            return redirect()->to('/documentos')->with('erro', 'Documento não encontrado.');
        }

        $usuarioId = (int) session()->get('usuario_id');
        $this->documentoModel->update($id, [
            'excluido_em'  => date('Y-m-d H:i:s'),
            'excluido_por' => $usuarioId,
        ]);

        (new DocumentoHistoricoModel())->registrar(
            $id,
            'exclusao',
            null,
            null,
            $usuarioId,
            'Documento excluído logicamente; arquivo preservado para recuperação.'
        );

        $this->auditar('DELETE', 'documentos', $id, 'documento', (string) ($documento['arquivo_original'] ?? ''), [
            'exclusao_logica' => true,
        ]);

        return redirect()->to('/documentos')
            ->with('sucesso', 'Documento excluído. O arquivo e o histórico foram preservados.');
    }

    /**
     * Visualização inline (abre no navegador quando o formato permitir).
     */
    public function visualizar(int $id)
    {
        return $this->enviarArquivo($id, false);
    }

    /**
     * Download controlado (attachment com nome original).
     */
    public function download(int $id)
    {
        return $this->enviarArquivo($id, true);
    }

    /**
     * Localiza processos existentes (AJAX) para o vínculo opcional.
     * Opcionalmente restringe à mulher já selecionada no formulário.
     */
    public function buscarProcessos()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $termo = trim((string) $this->request->getGet('term'));
        $mulherId = (int) $this->request->getGet('mulher_id');

        $model = (new ProcessoModel())->comDados();

        if ($mulherId > 0) {
            $model->where('processos.mulher_id', $mulherId);
        }

        if ($termo !== '') {
            $model->groupStart()
                ->like('processos.numero_processo', $termo)
                ->orLike('dados_mulher.nome_completo', $termo)
                ->orLike('dados_mulher.nome_social', $termo)
                ->orLike('dados_mulher.cpf', $termo)
                ->orLike('processos.id', $termo)
                ->groupEnd();
        }

        $processos = $model->orderBy('processos.id', 'DESC')
            ->limit(15)
            ->findAll();

        $resultado = array_map(
            fn (array $p): array => $this->linhaProcesso($p),
            $processos
        );

        return $this->response
            ->setContentType('application/json')
            ->setJSON(['processos' => $resultado]);
    }

    /**
     * Localiza manifestações existentes (AJAX) para o vínculo opcional.
     * Manutenção da mesma mulher; manifestações anônimas podem ser anexadas a
     * qualquer mulher.
     */
    public function buscarManifestacoes()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $termo = trim((string) $this->request->getGet('term'));

        $model = (new ManifestacaoModel())->comDados();

        if ($termo !== '') {
            $model->groupStart()
                ->like('manifestacoes.protocolo', $termo)
                ->orLike('manifestacoes.pessoa_nome', $termo)
                ->orLike('dados_mulher.nome_completo', $termo)
                ->orLike('dados_mulher.nome_social', $termo)
                ->orLike('dados_mulher.cpf', $termo)
                ->orLike('manifestacoes.id', $termo)
                ->groupEnd();
        }

        $manifestacoes = $model->orderBy('manifestacoes.id', 'DESC')
            ->limit(15)
            ->findAll();

        $resultado = array_map(
            fn (array $ma): array => $this->linhaManifestacao($ma),
            $manifestacoes
        );

        return $this->response
            ->setContentType('application/json')
            ->setJSON(['manifestacoes' => $resultado]);
    }

    /**
     * Envia o arquivo com metadados corretos, registrando o acesso no
     * histórico. Toda a entrega passa por este endpoint — nunca há URL
     * pública do arquivo físico.
     */
    private function enviarArquivo(int $id, bool $anexo)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $documento = $this->documentoModel->find($id);
        if ($documento === null) {
            return redirect()->to('/documentos')->with('erro', 'Documento não encontrado.');
        }

        $caminho = $this->caminhoArquivo((string) ($documento['arquivo'] ?? ''));
        if (! is_file($caminho)) {
            return redirect()->to('/documentos/' . $id)
                ->with('erro', 'Arquivo físico não encontrado no servidor.');
        }

        (new DocumentoHistoricoModel())->registrar(
            $id,
            'download',
            null,
            null,
            (int) session()->get('usuario_id'),
            $anexo ? 'Download realizado.' : 'Visualização no navegador.'
        );

        $nome = (string) ($documento['arquivo_original'] ?? ('documento-' . $id));
        $tipo = (string) ($documento['tipo_mime'] ?? '') ?: 'application/octet-stream';

        return $this->response
            ->setHeader('Content-Type', $tipo)
            ->setHeader('Content-Length', (string) filesize($caminho))
            ->setHeader('Content-Disposition', $this->contentDisposition($nome, $anexo))
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody((string) file_get_contents($caminho));
    }

    /**
     * Valida mulher (obrigatória) e vínculos opcionais (atendimento,
     * processo, medida protetiva, encaminhamento e manifestação — todos da
     * MESMA mulher; manifestação anônima (sem mulher) permite qualquer mulher).
     *
     * @return array{0:int, 1:int|null, 2:int|null, 3:int|null, 4:int|null, 5:int|null, 6:array|null}
     */
    private function validarVinculos(): array
    {
        $mulherId = (int) $this->request->getPost('mulher_id');
        if ($mulherId <= 0) {
            return [0, null, null, null, null, null, ['mulher_id' => 'Localize e selecione a mulher vinculada.']];
        }

        if (! (new MulherModel())->find($mulherId)) {
            return [0, null, null, null, null, null, ['mulher_id' => 'A mulher selecionada não existe mais.']];
        }

        $atendimentoId = (int) $this->request->getPost('atendimento_id');
        if ($atendimentoId > 0) {
            $atendimento = (new AtendimentoModel())->find($atendimentoId);
            if ($atendimento === null) {
                return [$mulherId, null, null, null, null, null, ['atendimento_id' => 'O atendimento selecionado não existe mais.']];
            }
            if ((int) $atendimento['mulher_id'] !== $mulherId) {
                return [$mulherId, null, null, null, null, null, ['atendimento_id' => 'O atendimento selecionado pertence a outra mulher.']];
            }
        }

        $processoId = (int) $this->request->getPost('processo_id');
        if ($processoId > 0) {
            $processo = (new ProcessoModel())->find($processoId);
            if ($processo === null) {
                return [$mulherId, $atendimentoId > 0 ? $atendimentoId : null, null, null, null, null, ['processo_id' => 'O processo selecionado não existe mais.']];
            }
            if ((int) $processo['mulher_id'] !== $mulherId) {
                return [$mulherId, $atendimentoId > 0 ? $atendimentoId : null, null, null, null, null, ['processo_id' => 'O processo selecionado pertence a outra mulher.']];
            }
        }

        $medidaId = (int) $this->request->getPost('medida_protetiva_id');
        if ($medidaId > 0) {
            $medida = (new MedidaProtetivaModel())->find($medidaId);
            if ($medida === null) {
                return [$mulherId, $atendimentoId > 0 ? $atendimentoId : null, $processoId > 0 ? $processoId : null, null, null, null, ['medida_protetiva_id' => 'A medida protetiva selecionada não existe mais.']];
            }
            if ((int) $medida['mulher_id'] !== $mulherId) {
                return [$mulherId, $atendimentoId > 0 ? $atendimentoId : null, $processoId > 0 ? $processoId : null, null, null, null, ['medida_protetiva_id' => 'A medida protetiva selecionada pertence a outra mulher.']];
            }
        }

        $encaminhamentoId = (int) $this->request->getPost('encaminhamento_id');
        if ($encaminhamentoId > 0) {
            $encaminhamento = (new EncaminhamentoModel())->find($encaminhamentoId);
            if ($encaminhamento === null) {
                return [$mulherId, $atendimentoId > 0 ? $atendimentoId : null, $processoId > 0 ? $processoId : null, $medidaId > 0 ? $medidaId : null, null, null, ['encaminhamento_id' => 'O encaminhamento selecionado não existe mais.']];
            }
            if ((int) $encaminhamento['mulher_id'] !== $mulherId) {
                return [$mulherId, $atendimentoId > 0 ? $atendimentoId : null, $processoId > 0 ? $processoId : null, $medidaId > 0 ? $medidaId : null, null, null, ['encaminhamento_id' => 'O encaminhamento selecionado pertence a outra mulher.']];
            }
        }

        $manifestacaoId = (int) $this->request->getPost('manifestacao_id');
        if ($manifestacaoId > 0) {
            $manifestacao = (new ManifestacaoModel())->find($manifestacaoId);
            if ($manifestacao === null) {
                return [$mulherId, $atendimentoId > 0 ? $atendimentoId : null, $processoId > 0 ? $processoId : null, $medidaId > 0 ? $medidaId : null, $encaminhamentoId > 0 ? $encaminhamentoId : null, null, ['manifestacao_id' => 'A manifestação selecionada não existe mais.']];
            }
            $mulherManifestacao = (int) ($manifestacao['mulher_id'] ?? 0);
            if ($mulherManifestacao > 0 && $mulherManifestacao !== $mulherId) {
                return [$mulherId, $atendimentoId > 0 ? $atendimentoId : null, $processoId > 0 ? $processoId : null, $medidaId > 0 ? $medidaId : null, $encaminhamentoId > 0 ? $encaminhamentoId : null, null, ['manifestacao_id' => 'A manifestação selecionada pertence a outra mulher.']];
            }
        }

        return [
            $mulherId,
            $atendimentoId > 0 ? $atendimentoId : null,
            $processoId > 0 ? $processoId : null,
            $medidaId > 0 ? $medidaId : null,
            $encaminhamentoId > 0 ? $encaminhamentoId : null,
            $manifestacaoId > 0 ? $manifestacaoId : null,
            null,
        ];
    }

    /**
     * Regras de validação do arquivo enviado (tamanho, extensão e MIME na
     * allowlist — executáveis e formatos perigosos são rejeitados).
     */
    private function regrasArquivo(): array
    {
        return [
            'arquivo' => 'uploaded[arquivo]'
                . '|max_size[arquivo,10240]'
                . '|ext_in[arquivo,' . DocumentoModel::EXTENSOES_PERMITIDAS . ']'
                . '|mime_in[arquivo,application/pdf,image/png,image/jpeg,image/gif,image/webp,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.oasis.opendocument.text,text/plain,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,application/rtf]',
        ];
    }

    private function mensagensArquivo(): array
    {
        return [
            'arquivo' => [
                'uploaded' => 'Selecione o arquivo para envio.',
                'max_size' => 'O arquivo excede o tamanho máximo permitido de 10 MB.',
                'ext_in'   => 'Formato de arquivo não permitido.',
                'mime_in'  => 'O tipo (MIME) do arquivo não é permitido.',
            ],
        ];
    }

    /**
     * Persiste o upload fora do docroot com nome físico aleatório.
     * Usa move_uploaded_file quando o arquivo vem de um POST real (segurança
     * de produção) e rename quando vem de um teste (move_uploaded_file não
     * aceita arquivos criados em ambiente CLI).
     *
     * @return string|null caminho relativo a WRITEPATH/uploads em caso de sucesso
     */
    private function gravarArquivo(UploadedFile $arquivo): ?string
    {
        $subPasta = date('Y/m');
        $dir = WRITEPATH . 'uploads/documentos/' . $subPasta;

        if (! is_dir($dir) && ! mkdir($dir, 0777, true) && ! is_dir($dir)) {
            return null;
        }

        if (! is_file($dir . '/index.html')) {
            @file_put_contents($dir . '/index.html', '403 Forbidden');
        }

        $extensao = mb_strtolower($arquivo->getExtension());
        $nome = bin2hex(random_bytes(16)) . ($extensao !== '' ? '.' . $extensao : '');
        $destino = $dir . '/' . $nome;

        $temp = $arquivo->getTempName();
        $ok = is_uploaded_file($temp) ? move_uploaded_file($temp, $destino) : rename($temp, $destino);

        if (! $ok) {
            return null;
        }

        return 'documentos/' . $subPasta . '/' . $nome;
    }

    private function caminhoArquivo(string $relativo): string
    {
        return WRITEPATH . 'uploads/' . ltrim($relativo, '/');
    }

    private function removerArquivoFisico(string $relativo): void
    {
        $arquivo = $this->caminhoArquivo($relativo);
        if (is_file($arquivo)) {
            @unlink($arquivo);
        }
    }

    /**
     * Monta os campos editáveis (inclui apenas metadados; vínculos e arquivo
     * são tratados separadamente).
     */
    private function dadosDoPost(): array
    {
        $limpar = static fn ($valor) => $valor !== null && trim((string) $valor) !== '' ? trim((string) $valor) : null;

        $tipo = (string) $this->request->getPost('tipo');
        if (! array_key_exists($tipo, ConfiguracaoListaModel::todasCategoria('tipo_documento'))) {
            $tipo = 'outros';
        }

        $sigilo = (string) $this->request->getPost('sigilo');
        if (! array_key_exists($sigilo, DocumentoModel::SIGILOS)) {
            $sigilo = 'restrito';
        }

        return [
            'tipo'        => $tipo,
            'sigilo'      => $sigilo,
            'observacoes' => $limpar($this->request->getPost('observacoes')),
        ];
    }

    private function dataValida(string $data): bool
    {
        return $data !== '' && (bool) strtotime($data) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) === 1;
    }

    private function contentDisposition(string $nome, bool $anexo): string
    {
        $tipo = $anexo ? 'attachment' : 'inline';
        $ascii = preg_replace('/[^\x20-\x7E]/', '_', $nome) ?? 'documento';
        $ascii = str_replace('"', '', $ascii);
        if (trim($ascii) === '') {
            $ascii = 'documento';
        }

        return $tipo . '; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($nome);
    }

    private function mulherPreselecionada(int $mulherId): ?array
    {
        if ($mulherId <= 0) {
            return null;
        }

        $m = (new MulherModel())->find($mulherId);
        if ($m === null) {
            return null;
        }

        return [
            'id'              => (int) $m['id'],
            'nome'            => (string) $m['nome_completo'],
            'cpf'             => $m['cpf'] ?? '',
            'data_nascimento' => $m['data_nascimento'] ?? '',
            'bairro'          => $m['bairro'] ?? '',
            'municipio'       => $m['municipio'] ?? '',
        ];
    }

    private function linhaAtendimento(array $a): array
    {
        return [
            'id'               => (int) ($a['atendimento_id'] ?? $a['id'] ?? 0),
            'protocolo'        => (string) ($a['atendimento_protocolo'] ?? $a['protocolo'] ?? ''),
            'data_hora'        => (string) ($a['data_hora'] ?? $a['atendimento_data_hora'] ?? ''),
            'mulher_id'        => (int) ($a['mulher_id'] ?? 0),
            'mulher_nome'      => (string) ($a['mulher_nome'] ?? ''),
        ];
    }

    private function linhaProcesso(array $p): array
    {
        $id = (int) ($p['processo_id'] ?? $p['id'] ?? 0);
        $numero = (string) ($p['processo_numero'] ?? $p['numero_processo'] ?? '');
        $numeroExibicao = $numero !== '' ? $numero : 'sem número';
        $comarca = (string) ($p['comarca'] ?? '');

        return [
            'id'             => $id,
            'numero_processo' => $numero,
            'numero'         => $numero,
            'titulo'         => '#' . $id . ' · ' . $numeroExibicao . ($comarca !== '' ? ' · ' . $comarca : ''),
            'comarca'        => $comarca,
            'mulher_id'      => (int) ($p['mulher_id'] ?? 0),
            'mulher_nome'    => (string) ($p['mulher_nome'] ?? ''),
        ];
    }

    private function linhaMedida(array $m): array
    {
        $id = (int) ($m['medida_protetiva_id'] ?? $m['id'] ?? 0);
        $numero = (string) ($m['medida_numero'] ?? $m['numero'] ?? '');

        return [
            'id'          => $id,
            'numero'      => $numero,
            'titulo'      => '#' . $id . ($numero !== '' ? ' · ' . $numero : ''),
            'situacao'    => (string) ($m['medida_situacao'] ?? $m['situacao'] ?? ''),
            'mulher_id'   => (int) ($m['mulher_id'] ?? 0),
            'mulher_nome' => (string) ($m['mulher_nome'] ?? ''),
        ];
    }

    private function linhaEncaminhamento(array $e): array
    {
        $id = (int) ($e['encaminhamento_id'] ?? $e['id'] ?? 0);

        return [
            'id'          => $id,
            'titulo'      => '#' . $id . ' · ' . ($e['mulher_nome'] ?? ''),
            'mulher_id'   => (int) ($e['mulher_id'] ?? 0),
            'mulher_nome' => (string) ($e['mulher_nome'] ?? ''),
            'orgao'       => (string) ($e['orgao'] ?? $e['encaminhamento_orgao'] ?? ''),
            'servico'     => (string) ($e['servico'] ?? $e['encaminhamento_servico'] ?? ''),
            'status'      => (string) ($e['status'] ?? $e['encaminhamento_status'] ?? ''),
        ];
    }

    private function linhaManifestacao(array $ma): array
    {
        $id = (int) ($ma['manifestacao_id'] ?? $ma['id'] ?? 0);
        $protocolo = (string) ($ma['manifestacao_protocolo'] ?? $ma['protocolo'] ?? '');

        return [
            'id'          => $id,
            'protocolo'   => $protocolo,
            'titulo'      => (string) ($ma['manifestacao_titulo'] ?? ($protocolo !== '' ? $protocolo : '#' . $id)),
            'tipo'        => (string) ($ma['tipo'] ?? ''),
            'situacao'    => (string) ($ma['situacao'] ?? ''),
            'anonimo'     => (int) ($ma['anonimo'] ?? 0) === 1,
            'mulher_id'   => (int) ($ma['mulher_id'] ?? 0),
            'mulher_nome' => (string) ($ma['mulher_nome'] ?? ''),
        ];
    }
}