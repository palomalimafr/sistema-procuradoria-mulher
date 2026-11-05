<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Acolhimento: registro dentro do fluxo do atendimento
 * (atendimento -> acolhimento -> avaliação de risco -> demais etapas futuras).
 *
 * A estrutura fica preparada para os próximos módulos (encaminhamentos,
 * tarefas, registro de violência/agressor), sem duplicar dados da mulher
 * ou do atendimento: o acolhimento passa a referenciar apenas o atendimento.
 */
class CreateAcolhimentoTables extends Migration
{
    public function up()
    {
        // Acolhimento vinculado a um atendimento existente.
        $this->forge->addField([
            'id' => [
                'type'           => 'INTEGER',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'atendimento_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'data_hora' => [
                'type' => 'DATETIME',
            ],
            'profissional_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'default'    => 'em_andamento',
            ],
            'motivo_procura' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'situacao_apresentada' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'necessidades_imediatas' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'necessidade_protecao' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'necessidade_juridica' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'necessidade_psicologica' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'necessidade_social' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'necessidade_saude' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'necessidade_seguranca' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'necessidade_abrigo' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'outras_necessidades' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'risco_identificado' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'null'       => true,
            ],
            'observacoes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'data_conclusao' => [
                'type' => 'DATETIME',
                'null' => true,
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
        $this->forge->addKey('atendimento_id');
        $this->forge->addKey('profissional_id');
        $this->forge->addKey('status');
        $this->forge->addKey('data_hora');
        $this->forge->addForeignKey('atendimento_id', 'atendimentos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('profissional_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('acolhimentos');

        // Formulário específico de avaliação de risco, ligado ao acolhimento.
        $perguntas = require __DIR__ . '/../PerguntasRisco.php';

        $campos = [
            'id' => [
                'type'           => 'INTEGER',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'acolhimento_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'data_hora' => [
                'type' => 'DATETIME',
            ],
            'profissional_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
        ];

        foreach ($perguntas as $campo => $rotulo) {
            $campos[$campo] = [
                'type'    => 'BOOLEAN',
                'default' => false,
            ];
        }

        $campos += [
            'observacoes_profissionais' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            // Classificação final, definida pelo profissional.
            'classificacao' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
            ],
            // Auxílio automático calculado a partir das respostas (não substitui
            // a avaliação do profissional).
            'classificacao_sugerida' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ];

        $this->forge->addField($campos);
        $this->forge->addKey('id', true);
        $this->forge->addKey('acolhimento_id');
        $this->forge->addKey('profissional_id');
        $this->forge->addKey('classificacao');
        $this->forge->addForeignKey('acolhimento_id', 'acolhimentos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('profissional_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('avaliacoes_risco');

        // Histórico das mudanças de status do acolhimento.
        $this->forge->addField([
            'id' => [
                'type'           => 'INTEGER',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'acolhimento_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'status_anterior' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'null'       => true,
            ],
            'status_novo' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
            ],
            'observacao' => [
                'type'       => 'VARCHAR',
                'constraint' => '200',
                'null'       => true,
            ],
            'usuario_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('acolhimento_id');
        $this->forge->addForeignKey('acolhimento_id', 'acolhimentos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('usuario_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('acolhimento_historico');
    }

    public function down()
    {
        $this->forge->dropTable('acolhimento_historico');
        $this->forge->dropTable('avaliacoes_risco');
        $this->forge->dropTable('acolhimentos');
    }
}