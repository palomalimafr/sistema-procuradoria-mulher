<?php

namespace App\Controllers;

use App\Models\ConfiguracaoListaModel;

/**
 * LISTAS CONFIGURÁVEIS (SISTEMA > Configurações > Listas).
 *
 * Acesso restrito ao perfil administrador (filtro 'admin' nas rotas).
 * Administra o catálogo `sistema_configuracoes_itens` — as opções exibidas
 * nos formulários e filtros dos módulos (tipos, origens, prioridades,
 * status/situações, motivos de encerramento, órgãos e tipos de documento).
 *
 * A inativação é LÓGICA (`ativo = 0`): o item deixa de aparecer em novos
 * formulários/filtros, mas continua válido no histórico e na consulta de
 * rótulos dos registros antigos. A chave nunca é alterada.
 */
class ConfiguracoesListas extends BaseController
{
    protected $listaModel;

    public function __construct()
    {
        $this->listaModel = new ConfiguracaoListaModel();
    }

    public function index()
    {
        $grupos = ConfiguracaoListaModel::GRUPOS;

        $porGrupo = [];
        foreach ($grupos as $chaveGrupo => $rotuloGrupo) {
            $porGrupo[$chaveGrupo] = [
                'rotulo'     => $rotuloGrupo,
                'categorias' => [],
            ];
        }

        foreach (ConfiguracaoListaModel::CATEGORIAS as $categoria => $info) {
            $porGrupo[$info['grupo']]['categorias'][] = [
                'categoria' => $categoria,
                'rotulo'    => $info['rotulo'],
                'descricao' => $info['descricao'],
                'total'     => count($this->listaModel->itens($categoria)),
            ];
        }

        $data = [
            'titulo' => 'Listas configuráveis',
            'grupos' => $porGrupo,
        ];

        return view('configuracoes/listas', $data);
    }

    public function categoria(string $categoria)
    {
        if (! $this->listaModel->categoriaValida($categoria)) {
            return redirect()->to('/sistema/configuracoes/listas')
                ->with('erro', 'Categoria não encontrada.');
        }

        $data = [
            'titulo'    => ConfiguracaoListaModel::CATEGORIAS[$categoria]['rotulo'],
            'categoria' => $categoria,
            'info'      => ConfiguracaoListaModel::CATEGORIAS[$categoria],
            'itens'     => $this->listaModel->itens($categoria),
        ];

        return view('configuracoes/listas_categoria', $data);
    }

    public function criarItem(string $categoria)
    {
        if (! $this->listaModel->categoriaValida($categoria)) {
            return redirect()->to('/sistema/configuracoes/listas')
                ->with('erro', 'Categoria não encontrada.');
        }

        $chave  = (string) $this->request->getPost('chave');
        $rotulo = (string) $this->request->getPost('rotulo');

        if ($chave === '' || $rotulo === '') {
            return redirect()->to('/sistema/configuracoes/listas/' . $categoria)
                ->withInput()
                ->with('erros', ['rotulo' => 'Informe a chave e o rótulo do item.']);
        }

        if (preg_match('/^[a-z0-9_]+$/', $chave) !== 1) {
            return redirect()->to('/sistema/configuracoes/listas/' . $categoria)
                ->withInput()
                ->with('erros', ['chave' => 'A chave deve conter apenas letras minúsculas, números e sublinhado.']);
        }

        if ($this->listaModel->chaveCadastrada($categoria, $chave)) {
            return redirect()->to('/sistema/configuracoes/listas/' . $categoria)
                ->withInput()
                ->with('erros', ['chave' => 'Já existe um item com esta chave nesta categoria.']);
        }

        if ($this->listaModel->criarItem($categoria, $chave, $rotulo) === false) {
            return redirect()->to('/sistema/configuracoes/listas/' . $categoria)
                ->with('erro', 'Não foi possível cadastrar o item.');
        }

        return redirect()->to('/sistema/configuracoes/listas/' . $categoria)
            ->with('sucesso', 'Item cadastrado com sucesso.');
    }

    public function renomearItem(string $categoria, int $id)
    {
        if (! $this->listaModel->categoriaValida($categoria)) {
            return redirect()->to('/sistema/configuracoes/listas')
                ->with('erro', 'Categoria não encontrada.');
        }

        $item = $this->listaModel->find($id);
        if ($item === null || $item['categoria'] !== $categoria) {
            return redirect()->to('/sistema/configuracoes/listas/' . $categoria)
                ->with('erro', 'Item não encontrado.');
        }

        $rotulo = (string) $this->request->getPost('rotulo');

        if (trim($rotulo) === '') {
            return redirect()->to('/sistema/configuracoes/listas/' . $categoria)
                ->with('erro', 'O rótulo é obrigatório.');
        }

        if (! $this->listaModel->renomear($id, $rotulo)) {
            return redirect()->to('/sistema/configuracoes/listas/' . $categoria)
                ->with('erro', 'Não foi possível atualizar o item.');
        }

        return redirect()->to('/sistema/configuracoes/listas/' . $categoria)
            ->with('sucesso', 'Rótulo atualizado com sucesso.');
    }

    public function alternarAtivo(string $categoria, int $id)
    {
        if (! $this->listaModel->categoriaValida($categoria)) {
            return redirect()->to('/sistema/configuracoes/listas')
                ->with('erro', 'Categoria não encontrada.');
        }

        $item = $this->listaModel->find($id);
        if ($item === null || $item['categoria'] !== $categoria) {
            return redirect()->to('/sistema/configuracoes/listas/' . $categoria)
                ->with('erro', 'Item não encontrado.');
        }

        if (! $this->listaModel->alternarAtivo($id)) {
            return redirect()->to('/sistema/configuracoes/listas/' . $categoria)
                ->with('erro', 'Não foi possível atualizar o item.');
        }

        $mensagem = (int) $item['ativo']
            ? 'Item inativado. Registros antigos continuam exibindo este rótulo.'
            : 'Item reativado com sucesso.';

        return redirect()->to('/sistema/configuracoes/listas/' . $categoria)
            ->with('sucesso', $mensagem);
    }
}