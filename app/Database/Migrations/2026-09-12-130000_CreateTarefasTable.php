<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tarefas internas da Procuradoria da Mulher (subopção de Agenda).
 *
 * Uma tarefa representa uma ação a ser executada por um servidor no
 * acompanhamento de uma mulher, atendimento ou encaminhamento. Não é um
 * compromisso de calendário: Agenda e Tarefas permanecem independentes.
 *
 * A tabela é preparada para vínculos futuros (acolhimento e compromisso),
 * sem automações neste momento. Migração não destrutiva.
 */
class CreateTarefasTable extends Migration
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
            'titulo' => [
                'type'       => 'VARCHAR',
                'constraint' => '200',
            ],
            'descricao' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'mulher_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'atendimento_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'encaminhamento_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            // Preparado para tarefas originadas do acolhimento.
            'acolhimento_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            // Preparado para vínculo futuro com a Agenda (sem automação).
            'compromisso_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'responsavel_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'prazo' => [
                'type'       => 'DATE',
                'null'       => true,
            ],
            'prioridade' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'default'    => 'media',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'default'    => 'pendente',
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
        $this->forge->addKey('mulher_id');
        $this->forge->addKey('atendimento_id');
        $this->forge->addKey('encaminhamento_id');
        $this->forge->addKey('acolhimento_id');
        $this->forge->addKey('compromisso_id');
        $this->forge->addKey('responsavel_id');
        $this->forge->addKey('prazo');
        $this->forge->addKey('prioridade');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('mulher_id', 'dados_mulher', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('atendimento_id', 'atendimentos', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('encaminhamento_id', 'encaminhamentos', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('acolhimento_id', 'acolhimentos', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('compromisso_id', 'compromissos', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('responsavel_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('tarefas');
    }

    public function down()
    {
        $this->forge->dropTable('tarefas');
    }
}