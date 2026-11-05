<?php

namespace App\Libraries;

use App\Models\AtendimentoModel;
use App\Models\HistoricoMulherModel;
use App\Models\MulherModel;

/**
 * Contexto de leitura de uma mulher, para exibir junto ao formulário de
 * criação de qualquer ação.
 *
 * É SOMENTE LEITURA. Nenhum registro antigo é copiado para o registro novo,
 * nada é duplicado no banco e nenhum registro existente é alterado. O que
 * aparece aqui serve para a profissional decidir com o histórico à vista.
 *
 * Em especial, NENHUMA decisão é preenchida a partir do histórico: prioridade,
 * situação, sigilo, prazo, número judicial, descrição e classificação
 * continuam sempre sendo informadas por ela.
 */
class ContextoMulher
{
    /**
     * Quantos eventos recentes aparecem no painel. O total de cada módulo vem
     * das mesmas consultas, sem custo adicional.
     */
    public const EVENTOS_RECENTES = 5;

    private const ZONAS = ['urbana' => 'Urbana', 'rural' => 'Rural'];

    public function __construct(private int $limiteEventos = self::EVENTOS_RECENTES)
    {
    }

    /**
     * Devolve null quando não há mulher informada ou ela não existe mais —
     * assim o formulário simplesmente não exibe o painel, sem quebrar.
     */
    public function montar(?int $mulherId): ?array
    {
        if ($mulherId === null || $mulherId <= 0) {
            return null;
        }

        $mulher = (new MulherModel())->find($mulherId);
        if ($mulher === null) {
            return null;
        }

        $linha = (new HistoricoMulherModel())->timeline($mulherId);

        return [
            'mulher'       => $this->identidade($mulher),
            'atendimento'  => $this->atendimentoEmAberto($mulherId),
            'eventos'      => array_slice($linha['eventos'], 0, $this->limiteEventos),
            'totalEventos' => $linha['total'],
            'contagem'     => $linha['contagem'],
            'urlHistorico' => 'mulheres/historico/' . $mulherId,
        ];
    }

    /**
     * Identificação exibida como contexto. Nada daqui é gravado no registro
     * que está sendo criado.
     */
    private function identidade(array $m): array
    {
        $nome = trim((string) ($m['nome_completo'] ?? ''));
        $nomeSocial = trim((string) ($m['nome_social'] ?? ''));

        return [
            'id'            => (int) $m['id'],
            'nome'          => $nome,
            // Nome social só entra como complemento, sem substituir o civil.
            'nomeSocial'    => $nomeSocial !== '' && $nomeSocial !== $nome ? $nomeSocial : '',
            'cpf'           => trim((string) ($m['cpf'] ?? '')),
            'idade'         => MulherModel::calcularIdade($m['data_nascimento'] ?? null),
            'bairro'        => trim((string) ($m['bairro'] ?? '')),
            'municipio'     => trim((string) ($m['municipio'] ?? '')),
            'zona'          => self::ZONAS[(string) ($m['zona'] ?? '')] ?? (string) ($m['zona'] ?? ''),
            'vulneravel'    => ! empty($m['situacao_vulnerabilidade']),
        ];
    }

    /**
     * Atendimento em aberto exibido como contexto.
     *
     * Usa a MESMA regra de pré-seleção do formulário: só aparece quando
     * existe exatamente um em aberto. Havendo zero ou mais de um, o bloco é
     * omitido — mostrar apenas um deles seria enganoso, e os dois aparecem de
     * qualquer forma na lista de eventos recentes logo abaixo.
     *
     * Não grava nada: é apenas o contexto exibido ao lado do formulário.
     */
    private function atendimentoEmAberto(int $mulherId): ?array
    {
        $atendimento = (new AtendimentoModel())->unicoAbertoPorMulher($mulherId);

        if ($atendimento === null) {
            return null;
        }

        return [
            'id'        => (int) $atendimento['id'],
            'protocolo' => (string) ($atendimento['protocolo'] ?? ''),
            'data'      => ! empty($atendimento['data_hora'])
                ? date('d/m/Y H:i', strtotime((string) $atendimento['data_hora']))
                : '',
            'situacao'  => (string) ($atendimento['situacao'] ?? ''),
            'url'       => 'atendimentos/' . (int) $atendimento['id'],
        ];
    }
}
