<?php

namespace App\Controllers;

use App\Models\CasoModel;
use App\Models\EncaminhamentoModel;
use App\Models\MedidaProtetivaModel;
use App\Models\PedidoSosModel;
use App\Models\TarefaModel;
use CodeIgniter\Database\BaseBuilder;

/**
 * Painel operacional da Procuradoria da Mulher.
 *
 * REGRA CENTRAL: os cartões são um SNAPSHOT da situação atual e NÃO dependem
 * de período. A lógica de status é sempre a que já existe nos models
 * (constantes SITUACOES/STATUS e suas variantes "ativas/pendentes"), nunca
 * uma regra nova criada aqui.
 *
 * Cada indicador é resolvido com um único COUNT. Nada de consulta por registro.
 */
class Dashboard extends BaseController
{
    /**
     * Status de compromisso que ainda pode acontecer: mesma lista usada por
     * Relatorios::totalCompromissos().
     */
    private const COMPROMISSOS_ABERTOS = ['agendado', 'confirmado'];

    public function index()
    {
        $admin = session()->get('perfil') === 'admin';

        $cartoes = $this->cartoes($admin);

        return view('dashboard/index', [
            'titulo'      => 'Dashboard',
            'cartoes'     => $cartoes,
            'pendencias'  => $this->pendencias($admin),
            'sosTotal'    => $admin ? (int) $cartoes['sos']['valor'] : null,
            'sosVisivel'  => $admin,
        ]);
    }

    /**
     * Indicadores do snapshot, agrupados em duas linhas de quatro.
     *
     * @return array<string, array<string, mixed>>
     */
    private function cartoes(bool $admin): array
    {
        $db = db_connect();

        $mulheres = (int) $db->table('dados_mulher')->countAllResults();

        $sos = $admin
            ? (new PedidoSosModel())->totalPendentes()
            : null;

        $atendimentosAbertos = (int) $this->contar(
            $db->table('atendimentos')->whereIn('situacao', ['novo', 'aguardando_atendimento', 'em_andamento'])
        );

        $acolhimentos = (int) $this->contar(
            $db->table('acolhimentos')->where('status', 'em_andamento')
        );

        $encaminhamentos = (int) $this->contar(
            $db->table('encaminhamentos')->whereIn('status', EncaminhamentoModel::STATUS_PENDENTES)
        );

        $casos = (int) $this->contar(
            $db->table('casos')->whereIn('situacao', CasoModel::SITUACOES_ATIVAS)
        );

        $medidas = (int) $this->contar(
            $db->table('medidas_protetivas')->whereIn('situacao', MedidaProtetivaModel::SITUACOES_ATIVAS)
        );

        $tarefas = (int) $this->contar(
            $db->table('tarefas')->whereIn('status', TarefaModel::STATUS_ATIVOS)
        );

        return [
            'sos' => [
                'rotulo'    => 'Pedidos SOS sem atendimento',
                'valor'     => $sos,
                'icone'     => 'siren',
                'rota'      => 'pedidos-sos',
                'mostrar'   => $admin,
                'destaque'  => 'alerta',
            ],
            'mulheres' => [
                'rotulo'   => 'Mulheres cadastradas',
                'valor'    => $mulheres,
                'icone'    => 'users',
                'rota'     => 'mulheres/pesquisa',
                'destaque' => null,
            ],
            'atendimentos' => [
                'rotulo'   => 'Atendimentos em aberto',
                'valor'    => $atendimentosAbertos,
                'icone'    => 'clipboard-list',
                'rota'     => 'atendimentos/fila',
                'destaque' => null,
            ],
            'acolhimentos' => [
                'rotulo'   => 'Acolhimentos em andamento',
                'valor'    => $acolhimentos,
                'icone'    => 'heart-handshake',
                'rota'     => 'acolhimentos?status=em_andamento',
                'destaque' => null,
            ],
            'encaminhamentos' => [
                'rotulo'   => 'Encaminhamentos pendentes',
                'valor'    => $encaminhamentos,
                'icone'    => 'send',
                'rota'     => 'encaminhamentos/pendentes',
                'destaque' => null,
            ],
            'casos' => [
                'rotulo'   => 'Casos ativos',
                'valor'    => $casos,
                'icone'    => 'folder-plus',
                'rota'     => 'casos/ativos',
                'destaque' => null,
            ],
            'medidas' => [
                'rotulo'   => 'Medidas protetivas ativas',
                'valor'    => $medidas,
                'icone'    => 'shield-check',
                'rota'     => 'medidas-protetivas',
                'destaque' => null,
            ],
            'tarefas' => [
                'rotulo'   => 'Tarefas ativas',
                'valor'    => $tarefas,
                'icone'    => 'list-checks',
                'rota'     => 'tarefas',
                'destaque' => null,
            ],
        ];
    }

    /**
     * Pendências operacionais: apenas situações reais e com regra de status
     * já existente. Itens zerados não são exibidos; se tudo for zero, a view
     * mostra estado vazio em vez de uma lista de "0".
     *
     * @return list<array<string, mixed>>
     */
    private function pendencias(bool $admin): array
    {
        $db = db_connect();
        $hoje = date('Y-m-d');

        $itens = [];

        if ($admin) {
            $sos = (int) $this->contar(
                $db->table('pedidos_sos')->where('status', 'pendente')
            );
            if ($sos > 0) {
                $itens[] = [
                    'rotulo' => 'Pedidos SOS aguardando atendimento',
                    'valor'  => $sos,
                    'icone'  => 'siren',
                    'tom'    => 'perigo',
                    'rota'   => 'pedidos-sos',
                    'nota'   => 'Nenhum atendimento foi aberto para estes pedidos.',
                ];
            }
        }

        $encaminhamentos = (int) $this->contar(
            $db->table('encaminhamentos')->whereIn('status', EncaminhamentoModel::STATUS_PENDENTES)
        );
        if ($encaminhamentos > 0) {
            $itens[] = [
                'rotulo' => 'Encaminhamentos aguardando desfecho',
                'valor'  => $encaminhamentos,
                'icone'  => 'send',
                'tom'    => 'neutro',
                'rota'   => 'encaminhamentos/pendentes',
                'nota'   => 'Pendente, encaminhado, em atendimento ou aguardando retorno.',
            ];
        }

        $tarefas = (int) $this->contar(
            $db->table('tarefas')->whereIn('status', TarefaModel::STATUS_ATIVOS)
        );
        if ($tarefas > 0) {
            $itens[] = [
                'rotulo' => 'Tarefas em aberto',
                'valor'  => $tarefas,
                'icone'  => 'list-checks',
                'tom'    => 'neutro',
                'rota'   => 'tarefas',
                'nota'   => 'Pendentes ou em andamento.',
            ];
        }

        $compromissosVencidos = (int) $this->contar(
            $db->table('compromissos')
                ->whereIn('status', self::COMPROMISSOS_ABERTOS)
                ->where('data <', $hoje)
        );
        if ($compromissosVencidos > 0) {
            $itens[] = [
                'rotulo' => 'Compromissos vencidos sem desfecho',
                'valor'  => $compromissosVencidos,
                'icone'  => 'calendar-clock',
                'tom'    => 'alerta',
                'rota'   => 'agenda',
                'nota'   => 'Continuam agendados, mas a data já passou.',
            ];
        }

        return $itens;
    }

    /**
     * COUNT direto no builder: uma consulta por indicador, sem hidratação de
     * registros e sem N+1.
     */
    private function contar(BaseBuilder $builder): int
    {
        return (int) $builder->countAllResults();
    }
}
