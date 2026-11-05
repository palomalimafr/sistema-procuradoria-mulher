<?php

namespace App\Controllers;

use App\Libraries\ContextoMulher;
use App\Models\CasoHistoricoModel;
use App\Models\CasoModel;
use App\Models\AtendimentoModel;
use App\Models\ConfiguracaoListaModel;
use App\Models\MulherModel;
use App\Models\UserModel;

class Casos extends BaseController
{
    private CasoModel $casos;
    private CasoHistoricoModel $historico;

    public function __construct()
    {
        $this->casos = new CasoModel();
        $this->historico = new CasoHistoricoModel();
    }

    private function somenteEquipe()
    {
        if (! in_array((string) session()->get('perfil'), ['admin', 'funcionario'], true)) {
            return redirect()->to('/dashboard')->with('erro', 'Acesso restrito à equipe da Procuradoria.');
        }
        return null;
    }

    public function ativos() { return $this->listar('ativos'); }
    public function urgentes() { return $this->listar('urgentes'); }
    public function atrasados() { return $this->listar('atrasados'); }
    public function encerrados() { return $this->listar('encerrados'); }

    private function listar(string $visao)
    {
        if ($r = $this->somenteEquipe()) return $r;
        $f = ['q' => trim((string) $this->request->getGet('q')), 'prioridade' => (string) $this->request->getGet('prioridade'), 'situacao' => (string) $this->request->getGet('situacao'), 'responsavel' => (int) $this->request->getGet('responsavel')];
        $m = $this->casos->comDados();
        if ($visao === 'ativos') $m->whereNotIn('casos.situacao', ['encerrado', 'arquivado']);
        if ($visao === 'encerrados') $m->whereIn('casos.situacao', ['encerrado', 'arquivado']);
        if ($visao === 'urgentes') $m->groupStart()->whereIn('casos.prioridade', ['emergencia', 'alta'])->orWhere('EXISTS (SELECT 1 FROM atendimentos au JOIN acolhimentos ac ON ac.atendimento_id = au.id JOIN avaliacoes_risco ar ON ar.acolhimento_id = ac.id WHERE au.caso_id = casos.id AND ar.classificacao IN (\'alto\', \'alto_risco\', \'emergencia\'))', null, false)->groupEnd();
        if ($visao === 'atrasados') $m->where("(EXISTS (SELECT 1 FROM tarefas t WHERE t.caso_id = casos.id AND t.status IN ('pendente','em_andamento') AND t.prazo IS NOT NULL AND t.prazo < '" . date('Y-m-d') . "') OR EXISTS (SELECT 1 FROM encaminhamentos e WHERE e.caso_id = casos.id AND e.status IN ('pendente','encaminhado','em_atendimento','aguardando_retorno') AND e.prazo IS NOT NULL AND e.prazo < '" . date('Y-m-d') . "'))", null, false);
        if ($f['q'] !== '') $m->groupStart()->like('casos.protocolo', $f['q'])->orLike('dados_mulher.nome_completo', $f['q'])->orLike('dados_mulher.cpf', $f['q'])->groupEnd();
        if (array_key_exists($f['prioridade'], ConfiguracaoListaModel::todasCategoria('prioridade'))) $m->where('casos.prioridade', $f['prioridade']);
        if (array_key_exists($f['situacao'], CasoModel::SITUACOES)) $m->where('casos.situacao', $f['situacao']);
        if ($f['responsavel'] > 0) $m->where('casos.responsavel_id', $f['responsavel']);
        $casos = $m->orderBy('casos.data_abertura', 'DESC')->findAll();
        foreach ($casos as &$caso) $caso['atrasado'] = $this->estaAtrasado((int) $caso['id']);
        return view('casos/lista', ['titulo' => 'Casos', 'casos' => $casos, 'visao' => $visao, 'filtros' => $f, 'situacoes' => CasoModel::SITUACOES, 'prioridades' => ConfiguracaoListaModel::todasCategoria('prioridade'), 'equipe' => $this->equipe()]);
    }

    public function novo(int $atendimentoId = 0)
    {
        if ($r = $this->somenteEquipe()) return $r;

        if ($atendimentoId <= 0) {
            $atendimentoId = (int) $this->request->getGet('atendimento_id');
        }

        $mulherContexto = 0;

        if ($atendimentoId <= 0) {
            $mulherContexto = (int) $this->request->getGet('mulher_id');
            $emAberto = $mulherContexto > 0 ? (new AtendimentoModel())->unicoAbertoPorMulher($mulherContexto) : null;
            if ($emAberto === null) {
                return redirect()->to('/atendimentos/fila')->with('erro', $mulherContexto > 0
                    ? 'Selecione o atendimento de origem para abrir o caso.'
                    : 'Informe a mulher para abrir o caso.');
            }
            $atendimentoId = (int) $emAberto['id'];
        }

        $atendimento = (new AtendimentoModel())->comDados()->where('atendimentos.id', $atendimentoId)->first();
        if (! $atendimento) return redirect()->to('/atendimentos/fila')->with('erro', 'Atendimento não encontrado.');
        if (! empty($atendimento['caso_id'])) return redirect()->to('/casos/' . $atendimento['caso_id'])->with('erro', 'Este atendimento já está vinculado a um caso.');

        $mulherContexto = (int) ($atendimento['mulher_id'] ?? 0);

        $casosEmAberto = (new CasoModel())
            ->where('mulher_id', $mulherContexto)
            ->whereNotIn('situacao', ['encerrado', 'cancelado'])
            ->orderBy('data_abertura', 'DESC')
            ->findAll();

        return view('casos/form', [
            'titulo'        => 'Abrir Caso',
            'atendimento'   => $atendimento,
            'equipe'        => $this->equipe(),
            'prioridades'   => ConfiguracaoListaModel::todasCategoria('prioridade'),
            'casosEmAberto' => $casosEmAberto,
            'contexto'      => (new ContextoMulher())->montar($mulherContexto),
        ]);
    }

    public function criar()
    {
        if ($r = $this->somenteEquipe()) return $r;
        $atendimento = (new AtendimentoModel())->find((int) $this->request->getPost('atendimento_id'));
        if (! $atendimento || ! empty($atendimento['caso_id'])) return redirect()->to('/atendimentos/fila')->with('erro', 'Atendimento inválido ou já vinculado a um caso.');
        $dados = ['protocolo' => CasoModel::gerarProtocolo(), 'mulher_id' => (int) $atendimento['mulher_id'], 'responsavel_id' => (int) $this->request->getPost('responsavel_id') ?: null, 'prioridade' => (string) $this->request->getPost('prioridade'), 'situacao' => 'novo', 'data_abertura' => date('Y-m-d H:i:s'), 'resumo' => trim((string) $this->request->getPost('resumo')) ?: null];
        if (! $this->casos->insert($dados)) return redirect()->back()->withInput()->with('erros', $this->casos->errors());
        $id = (int) $this->casos->getInsertID();
        (new AtendimentoModel())->update((int) $atendimento['id'], ['caso_id' => $id]);
        $this->evento($id, 'abertura', 'Caso aberto a partir do atendimento ' . $atendimento['protocolo'] . '.');
        $this->evento($id, 'associacao_atendimento', 'Atendimento ' . $atendimento['protocolo'] . ' associado ao caso.');
        return redirect()->to('/casos/' . $id)->with('sucesso', 'Caso aberto e atendimento vinculado.');
    }

    public function detalhes(int $id)
    {
        if ($r = $this->somenteEquipe()) return $r;
        $caso = $this->casos->comDados()->where('casos.id', $id)->first();
        if (! $caso) return redirect()->to('/casos/ativos')->with('erro', 'Caso não encontrado.');
        $db = db_connect();
        $dados = ['atendimentos' => $db->table('atendimentos')->where('caso_id', $id)->get()->getResultArray(), 'encaminhamentos' => $db->table('encaminhamentos')->where('caso_id', $id)->get()->getResultArray(), 'tarefas' => $db->table('tarefas')->where('caso_id', $id)->get()->getResultArray(), 'medidas' => $db->table('medidas_protetivas')->where('caso_id', $id)->get()->getResultArray(), 'processos' => $db->table('processos')->where('caso_id', $id)->get()->getResultArray(), 'documentos' => $db->table('documentos')->where('caso_id', $id)->where('excluido_em', null)->get()->getResultArray()];
        $acolhimentos = $db->table('acolhimentos')->select('acolhimentos.*, avaliacoes_risco.classificacao')->join('atendimentos', 'atendimentos.id = acolhimentos.atendimento_id')->join('avaliacoes_risco', 'avaliacoes_risco.acolhimento_id = acolhimentos.id', 'left')->where('atendimentos.caso_id', $id)->get()->getResultArray();
        $vinculaveis = [];
        foreach (['atendimentos' => 'Atendimento', 'encaminhamentos' => 'Encaminhamento', 'tarefas' => 'Tarefa', 'medidas_protetivas' => 'Medida protetiva', 'processos' => 'Processo', 'documentos' => 'Documento'] as $tabela => $rotulo) {
            $vinculaveis[$tabela] = $db->table($tabela)->select('id')->where('mulher_id', $caso['mulher_id'])->where('caso_id', null)->limit(50)->get()->getResultArray();
        }
        $historico = $this->historico->comUsuario()->where('caso_historico.caso_id', $id)->orderBy('caso_historico.created_at', 'DESC')->findAll();
        $timeline = array_map(static fn ($h) => ['data' => $h['created_at'], 'descricao' => $h['descricao'], 'usuario' => $h['usuario_nome'] ?? null], $historico);
        foreach (['atendimentos' => 'Atendimento registrado', 'encaminhamentos' => 'Encaminhamento registrado', 'tarefas' => 'Tarefa registrada', 'medidas' => 'Medida protetiva registrada', 'processos' => 'Processo registrado', 'documentos' => 'Documento registrado'] as $chave => $rotulo) foreach ($dados[$chave] as $registro) $timeline[] = ['data' => $registro['created_at'] ?? null, 'descricao' => $rotulo . ' (#' . $registro['id'] . ').', 'usuario' => null];
        foreach ($acolhimentos as $registro) $timeline[] = ['data' => $registro['created_at'] ?? $registro['data_hora'], 'descricao' => 'Acolhimento registrado' . (! empty($registro['classificacao']) ? ' · risco: ' . $registro['classificacao'] : '') . '.', 'usuario' => null];
        usort($timeline, static fn ($a, $b) => strcmp((string) $b['data'], (string) $a['data']));
        return view('casos/detalhes', array_merge(['titulo' => 'Caso ' . $caso['protocolo'], 'caso' => $caso, 'situacoes' => CasoModel::SITUACOES, 'motivos' => ConfiguracaoListaModel::todasCategoria('motivo_encerramento'), 'historico' => $historico, 'timeline' => $timeline, 'atrasado' => $this->estaAtrasado($id), 'acolhimentos' => $acolhimentos, 'vinculaveis' => $vinculaveis], $dados));
    }

    public function status(int $id)
    {
        if ($r = $this->somenteEquipe()) return $r;
        $caso = $this->casos->find($id); $novo = (string) $this->request->getPost('situacao');
        if (! $caso || ! array_key_exists($novo, CasoModel::SITUACOES) || in_array($novo, ['encerrado', 'arquivado'], true)) return redirect()->back()->with('erro', 'Situação inválida.');
        $this->casos->update($id, ['situacao' => $novo]);
        $this->evento($id, 'mudanca_situacao', 'Situação alterada de ' . (CasoModel::SITUACOES[$caso['situacao']] ?? $caso['situacao']) . ' para ' . CasoModel::SITUACOES[$novo] . '.');
        return redirect()->to('/casos/' . $id)->with('sucesso', 'Situação do caso atualizada.');
    }

    public function encerrar(int $id)
    {
        if ($r = $this->somenteEquipe()) return $r;
        $caso = $this->casos->find($id); $motivo = (string) $this->request->getPost('motivo_encerramento');
        if (! $caso || ! array_key_exists($motivo, ConfiguracaoListaModel::todasCategoria('motivo_encerramento'))) return redirect()->back()->with('erro', 'Informe um motivo válido para o encerramento.');
        $this->casos->update($id, ['situacao' => 'encerrado', 'data_encerramento' => date('Y-m-d H:i:s'), 'motivo_encerramento' => $motivo, 'motivo_encerramento_outra' => $motivo === 'outros' ? trim((string) $this->request->getPost('motivo_encerramento_outra')) : null]);
        $this->evento($id, 'encerramento', 'Caso encerrado: ' . (ConfiguracaoListaModel::todasCategoria('motivo_encerramento')[$motivo] ?? $motivo) . '.');
        return redirect()->to('/casos/' . $id)->with('sucesso', 'Caso encerrado.');
    }

    public function associar(int $id)
    {
        if ($r = $this->somenteEquipe()) return $r;
        $caso = $this->casos->find($id);
        [$tabela, $registroId] = array_pad(explode(':', (string) $this->request->getPost('registro'), 2), 2, '');
        $registroId = (int) $registroId;
        $tipos = ['atendimentos' => 'Atendimento', 'encaminhamentos' => 'Encaminhamento', 'tarefas' => 'Tarefa', 'medidas_protetivas' => 'Medida protetiva', 'processos' => 'Processo', 'documentos' => 'Documento'];
        if (! $caso || ! isset($tipos[$tabela]) || $registroId < 1) return redirect()->back()->with('erro', 'Registro inválido para associação.');
        $registro = db_connect()->table($tabela)->where('id', $registroId)->get()->getRowArray();
        if (! $registro || (int) $registro['mulher_id'] !== (int) $caso['mulher_id']) return redirect()->back()->with('erro', 'O registro pertence a outra mulher e não pode ser associado.');
        if (! empty($registro['caso_id']) && (int) $registro['caso_id'] !== $id) return redirect()->back()->with('erro', 'O registro já pertence a outro caso.');
        db_connect()->table($tabela)->where('id', $registroId)->update(['caso_id' => $id]);
        $this->evento($id, 'associacao_' . rtrim($tabela, 's'), $tipos[$tabela] . ' #' . $registroId . ' associado ao caso.');
        return redirect()->to('/casos/' . $id)->with('sucesso', $tipos[$tabela] . ' associado ao caso.');
    }

    private function estaAtrasado(int $id): bool { $db = db_connect(); return $db->query("SELECT 1 FROM tarefas WHERE caso_id = ? AND status IN ('pendente','em_andamento') AND prazo < ? UNION SELECT 1 FROM encaminhamentos WHERE caso_id = ? AND status IN ('pendente','encaminhado','em_atendimento','aguardando_retorno') AND prazo < ? LIMIT 1", [$id, date('Y-m-d'), $id, date('Y-m-d')])->getRowArray() !== null; }
    private function evento(int $casoId, string $tipo, string $descricao): void { $this->historico->insert(['caso_id' => $casoId, 'usuario_id' => (int) session()->get('usuario_id') ?: null, 'tipo_evento' => $tipo, 'descricao' => $descricao, 'created_at' => date('Y-m-d H:i:s')]); }
    private function equipe(): array { return (new UserModel())->whereIn('perfil', ['admin', 'funcionario'])->where('ativo', 1)->orderBy('nome')->findAll(); }
}
