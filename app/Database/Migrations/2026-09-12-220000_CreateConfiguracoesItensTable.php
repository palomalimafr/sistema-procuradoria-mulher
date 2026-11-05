<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Catálogo de listas configuráveis do sistema (SISTEMA > Configurações).
 *
 * Converte em configuração os valores que hoje são constantes PHP dos models
 * (formas de atendimento, origens, prioridades, status/situações por módulo,
 * motivos de encerramento, órgãos e tipos de documento). Cada linha representa
 * uma opção de uma categoria; a inativação é LÓGICA (campo `ativo`), nunca
 * exclusão física, para preservar o histórico dos registros antigos.
 *
 * O seed replica exatamente os valores atuais em uso (sem inventar dados).
 * A categoria `tipo_violencia` nasce vazia: a estrutura de registro de
 * violência será implementada em módulo futuro.
 */
class CreateConfiguracoesItensTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INTEGER',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'categoria' => [
                'type'       => 'VARCHAR',
                'constraint' => '40',
            ],
            'chave' => [
                'type'       => 'VARCHAR',
                'constraint' => '60',
            ],
            'rotulo' => [
                'type'       => 'VARCHAR',
                'constraint' => '120',
            ],
            'ativo' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'default'    => 1,
            ],
            'ordem' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['categoria', 'chave']);
        $this->forge->createTable('sistema_configuracoes_itens');

        $this->semear();
    }

    public function down()
    {
        $this->forge->dropTable('sistema_configuracoes_itens');
    }

    /**
     * Seed com os valores reais atualmente em uso nas constantes dos models
     * (a inativação é lógica; nenhum item é excluído fisicamente).
     */
    private function semear(): void
    {
        $agora = date('Y-m-d H:i:s');

        $categorias = [
            'tipo_atendimento'      => [
                'presencial' => 'Presencial',
                'telefone'   => 'Telefone',
                'whatsapp'   => 'WhatsApp',
                'email'      => 'E-mail',
                'online'     => 'Online',
                'visita'     => 'Visita',
                'outro'      => 'Outro',
            ],
            'origem_atendimento'    => [
                'demanda_espontanea' => 'Demanda espontânea',
                'camara_municipal'   => 'Câmara Municipal',
                'prefeitura'         => 'Prefeitura',
                'cras'               => 'CRAS',
                'creas'              => 'CREAS',
                'delegacia'          => 'Delegacia',
                'policia_militar'    => 'Polícia Militar',
                'ministerio_publico' => 'Ministério Público',
                'defensoria_publica' => 'Defensoria Pública',
                'poder_judiciario'   => 'Poder Judiciário',
                'unidade_saude'      => 'Unidade de Saúde',
                'escola'             => 'Escola',
                'conselho_tutelar'   => 'Conselho Tutelar',
                'familiar'           => 'Familiar',
                'denuncia'           => 'Denúncia',
                'outros'             => 'Outros',
            ],
            'prioridade'            => [
                'emergencia' => 'Emergência',
                'alta'       => 'Alta',
                'media'      => 'Média',
                'baixa'      => 'Baixa',
            ],
            'status_atendimento'    => [
                'novo'                   => 'Novo',
                'aguardando_atendimento' => 'Aguardando atendimento',
                'em_andamento'           => 'Em andamento',
                'encerrado'              => 'Encerrado',
            ],
            'motivo_encerramento'   => [
                'atendimento_concluido'      => 'Atendimento concluído',
                'encaminhamento_concluido'   => 'Encaminhamento concluído',
                'caso_resolvido'             => 'Caso resolvido',
                'solicitacao_assistida'      => 'Solicitação da assistida',
                'perda_contato'              => 'Perda de contato',
                'mudanca_municipio'          => 'Mudança de município',
                'encaminhamento_outro_orgao' => 'Encaminhamento para outro órgão',
                'outros'                     => 'Outros',
            ],
            'status_encaminhamento' => [
                'pendente'           => 'Pendente',
                'encaminhado'        => 'Encaminhado',
                'em_atendimento'     => 'Em atendimento',
                'aguardando_retorno' => 'Aguardando retorno',
                'concluido'          => 'Concluído',
                'cancelado'          => 'Cancelado',
            ],
            'status_tarefa'         => [
                'pendente'     => 'Pendente',
                'em_andamento' => 'Em andamento',
                'concluida'    => 'Concluída',
                'cancelada'    => 'Cancelada',
            ],
            'status_acolhimento'    => [
                'em_andamento' => 'Em andamento',
                'concluido'    => 'Concluído',
            ],
            'origem_manifestacao'   => [
                'presencial'   => 'Presencial',
                'telefone'     => 'Telefone',
                'whatsapp'     => 'WhatsApp',
                'email'        => 'E-mail',
                'site_sistema' => 'Site/Sistema',
                'outro'        => 'Outro',
            ],
            'situacao_manifestacao' => [
                'nova'               => 'Nova',
                'em_analise'         => 'Em análise',
                'em_atendimento'     => 'Em atendimento',
                'encaminhada'        => 'Encaminhada',
                'aguardando_retorno' => 'Aguardando retorno',
                'resolvida'          => 'Resolvida',
                'encerrada'          => 'Encerrada',
            ],
            'situacao_processo'     => [
                'em_andamento' => 'Em andamento',
                'suspenso'     => 'Suspenso',
                'encerrado'    => 'Encerrado',
            ],
            'situacao_medida'       => [
                'solicitada' => 'Solicitada',
                'em_analise' => 'Em análise',
                'concedida'  => 'Concedida',
                'negada'     => 'Negada',
                'revogada'   => 'Revogada',
                'encerrada'  => 'Encerrada',
            ],
            'orgao'                 => [
                'cras'                   => 'CRAS',
                'creas'                  => 'CREAS',
                'delegacia'              => 'Delegacia',
                'delegacia_mulher'       => 'Delegacia da Mulher',
                'policia_militar'        => 'Polícia Militar',
                'ministerio_publico'     => 'Ministério Público',
                'defensoria_publica'     => 'Defensoria Pública',
                'poder_judiciario'       => 'Poder Judiciário',
                'secretaria_saude'       => 'Secretaria de Saúde',
                'secretaria_assistencia' => 'Secretaria de Assistência Social',
                'caps'                   => 'CAPS',
                'ubs'                    => 'Unidade Básica de Saúde',
                'hospital'               => 'Hospital',
                'conselho_tutelar'       => 'Conselho Tutelar',
                'abrigo'                 => 'Abrigo',
                'outros'                 => 'Outros',
            ],
            'tipo_documento'        => [
                'documentos_pessoais'  => 'Documentos pessoais',
                'boletim_ocorrencia'   => 'Boletim de ocorrência',
                'medida_protetiva'     => 'Medida protetiva',
                'relatorios'           => 'Relatórios',
                'encaminhamentos'      => 'Encaminhamentos',
                'declaracoes'          => 'Declarações',
                'oficios'              => 'Ofícios',
                'requerimentos'        => 'Requerimentos',
                'comprovantes'         => 'Comprovantes',
                'documentos_judiciais' => 'Documentos judiciais',
                'outros'               => 'Outros',
            ],
        ];

        foreach ($categorias as $categoria => $itens) {
            $ordem = 0;
            $linhas = [];

            foreach ($itens as $chave => $rotulo) {
                $linhas[] = [
                    'categoria' => $categoria,
                    'chave'     => $chave,
                    'rotulo'    => $rotulo,
                    'ativo'     => 1,
                    'ordem'     => $ordem++,
                    'created_at' => $agora,
                    'updated_at' => $agora,
                ];
            }

            if ($linhas !== []) {
                $this->db->table('sistema_configuracoes_itens')->insertBatch($linhas);
            }
        }
    }
}