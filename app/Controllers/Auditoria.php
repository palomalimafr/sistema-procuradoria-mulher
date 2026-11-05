<?php

namespace App\Controllers;

use App\Models\AuditoriaModel;
use App\Models\UserModel;

/**
 * Trilha de auditoria do sistema (SISTEMA > Auditoria).
 *
 * Acesso exclusivo do perfil admin (rotas com filtro 'admin'). Histórico
 * append-only: a tela não oferece edição nem exclusão de registros.
 */
class Auditoria extends BaseController
{
    public function index()
    {
        $filtros = [
            'usuario_id' => max(0, (int) $this->request->getGet('usuario_id')),
            'acao'       => trim((string) $this->request->getGet('acao')),
            'modulo'     => trim((string) $this->request->getGet('modulo')),
            'registro'   => trim((string) $this->request->getGet('registro')),
            'ip'         => trim((string) $this->request->getGet('ip')),
            'data_de'    => trim((string) $this->request->getGet('data_de')),
            'data_ate'   => trim((string) $this->request->getGet('data_ate')),
        ];

        foreach (['data_de', 'data_ate'] as $campo) {
            if ($filtros[$campo] !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtros[$campo]) !== 1) {
                $filtros[$campo] = '';
            }
        }

        if (! array_key_exists($filtros['acao'], AuditoriaModel::ACOES)) {
            $filtros['acao'] = '';
        }

        if (! array_key_exists($filtros['modulo'], AuditoriaModel::MODULOS)) {
            $filtros['modulo'] = '';
        }

        $model = new AuditoriaModel();
        $registros = $model->filtrar($filtros);

        foreach ($registros as &$linha) {
            $linha['detalhes_existem'] = ! empty($linha['detalhes']);
        }
        unset($linha);

        return view('auditoria/lista', [
            'titulo'    => 'Auditoria',
            'registros' => $registros,
            'pager'     => $model->pager,
            'filtros'   => $filtros,
            'acoes'     => AuditoriaModel::ACOES,
            'modulos'   => AuditoriaModel::MODULOS,
            'usuarios'  => (new UserModel())->orderBy('nome', 'ASC')->findAll(),
        ]);
    }

    public function detalhes(int $id)
    {
        $model = new AuditoriaModel();
        $registro = $model->select('sistema_auditoria.*, users.nome AS usuario_nome, users.email AS usuario_email')
            ->join('users', 'users.id = sistema_auditoria.usuario_id', 'left')
            ->where('sistema_auditoria.id', $id)
            ->first();

        if ($registro === null) {
            return redirect()->to('/sistema/auditoria')->with('erro', 'Registro de auditoria não encontrado.');
        }

        return view('auditoria/detalhes', [
            'titulo'       => 'Detalhes da auditoria',
            'registro'     => $registro,
            'detalhes'     => AuditoriaModel::detalhesDe($registro),
            'acaoRotulo'   => AuditoriaModel::rotuloAcao((string) ($registro['acao'] ?? '')),
            'moduloRotulo' => AuditoriaModel::rotuloModulo((string) ($registro['modulo'] ?? '')),
        ]);
    }
}