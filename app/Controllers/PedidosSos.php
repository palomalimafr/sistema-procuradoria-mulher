<?php

namespace App\Controllers;

use App\Models\AtendimentoModel;
use App\Models\MulherModel;
use App\Models\PedidoSosModel;

class PedidosSos extends BaseController
{
    public function criar()
    {
        // Defesa em profundidade (o rótulo de rota 'mulher' já restringe o acesso)
        if (! session()->get('logado') || session()->get('perfil') !== 'mulher') {
            return redirect()->to('/dashboard')->with('erro', 'Acesso restrito.');
        }

        $userId = (int) session()->get('usuario_id');

        $mulher = (new MulherModel())->findByUserId($userId);

        if ($mulher === null) {
            return redirect()->to('/mulher/dashboard')->with('erro', 'Dados da mulher não encontrados.');
        }

        $latitude = $this->request->getPost('latitude');
        $longitude = $this->request->getPost('longitude');

        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            return redirect()->to('/mulher/dashboard')->with('erro', 'Para enviar um pedido SOS, é necessário permitir o acesso à sua localização.');
        }

        $latitude = (float) $latitude;
        $longitude = (float) $longitude;

        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return redirect()->to('/mulher/dashboard')->with('erro', 'Localização inválida. Tente novamente.');
        }

        $pedido = (new PedidoSosModel())->insert([
            'mulher_id' => (int) $mulher['id'],
            'data_hora' => date('Y-m-d H:i:s'),
            'status'    => 'pendente',
            'latitude'  => $latitude,
            'longitude' => $longitude,
        ]);

        if ($pedido === false) {
            return redirect()->to('/mulher/dashboard')->with('erro', 'Não foi possível registrar o pedido. Tente novamente.');
        }

        return redirect()->to('/mulher/dashboard')->with('sos_sucesso', true);
    }

    public function index()
    {
        $bloqueado = $this->somenteAdmin();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $pedidoModel = new PedidoSosModel();

        $data = [
            'titulo'  => 'Pedidos SOS',
            'pedidos' => $pedidoModel->listarComMulher(10),
            'pager'   => $pedidoModel->pager,
        ];

        return view('pedidos_sos/index', $data);
    }

    public function detalhes(int $id)
    {
        $bloqueado = $this->somenteAdmin();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $pedidoModel = new PedidoSosModel();

        $pedido = $pedidoModel->comMulher($id);

        if ($pedido === null) {
            return redirect()->to('/pedidos-sos')->with('erro', 'Pedido não encontrado.');
        }

        // A primeira abertura marca o pedido como "visualizado pelo admin".
        if ($pedido['visualizado_em'] === null) {
            $pedidoModel->marcarVisualizado($id);
            $pedido['visualizado_em'] = date('Y-m-d H:i:s');
        }

        $atendimentoModel = new AtendimentoModel();

        $data = [
            'titulo'            => 'Pedido SOS #' . $pedido['id'],
            'pedido'            => $pedido,
            'atendimentoOrigem' => $atendimentoModel->porPedidoSos($id),
            'atendimentoAberto' => $atendimentoModel->emAbertoPorMulher((int) $pedido['mulher_id']),
        ];

        return view('pedidos_sos/detalhes', $data);
    }

    /**
     * Inicia o direcionamento de um pedido SOS para um atendimento.
     *
     * Usa os dados da mulher vinculada ao SOS para pré-preencher o formulário
     * de atendimento (a referência ao SOS é gravada quando o atendimento é
     * salvo). Não cria atendimento duplicado se a mulher já tiver um aberto.
     */
    public function direcionar(int $id)
    {
        $bloqueado = $this->somenteAdmin();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $pedido = (new PedidoSosModel())->find($id);

        if ($pedido === null) {
            return redirect()->to('/pedidos-sos')->with('erro', 'Pedido não encontrado.');
        }

        $atendimentoModel = new AtendimentoModel();

        if ($pedido['status'] !== 'pendente') {
            $atendimento = $atendimentoModel->porPedidoSos($id);

            if ($atendimento !== null) {
                return redirect()->to('/atendimentos/' . (int) $atendimento['id'])
                    ->with('sucesso', 'Este pedido SOS já foi direcionado para o atendimento ' . $atendimento['protocolo'] . '.');
            }

            return redirect()->to('/pedidos-sos/' . $id)
                ->with('erro', 'Este pedido SOS não está mais pendente de direcionamento.');
        }

        $atendimentoAberto = $atendimentoModel->emAbertoPorMulher((int) $pedido['mulher_id']);

        if ($atendimentoAberto !== null) {
            return redirect()->to('/pedidos-sos/' . $id)
                ->with('erro', 'Já existe um atendimento em andamento para esta mulher (protocolo ' . $atendimentoAberto['protocolo'] . ').');
        }

        return redirect()->to('/atendimentos/novo?mulher_id=' . (int) $pedido['mulher_id'] . '&pedido_sos_id=' . $id);
    }

    public function novos()
    {
        $bloqueado = $this->somenteAdmin();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $pedidoModel = new PedidoSosModel();

        $novos = array_map(static function (array $pedido): array {
            return [
                'id'        => (int) $pedido['id'],
                'mulher'    => $pedido['mulher'],
                'data_hora' => $pedido['data_hora'],
                'latitude'  => $pedido['latitude'] !== null ? (float) $pedido['latitude'] : null,
                'longitude' => $pedido['longitude'] !== null ? (float) $pedido['longitude'] : null,
            ];
        }, $pedidoModel->novosParaAdmin());

        return $this->response
            ->setContentType('application/json')
            ->setJSON([
                'novos'     => $novos,
                'pendentes' => $pedidoModel->totalPendentes(),
            ]);
    }

    /**
     * Garante que apenas administradores acessem a área administrativa.
     * Retorna null se liberado; caso contrário, um redirect.
     */
    private function somenteAdmin()
    {
        if (session()->get('perfil') !== 'admin') {
            return redirect()->to('/dashboard')->with('erro', 'Acesso restrito ao administrador.');
        }

        return null;
    }
}