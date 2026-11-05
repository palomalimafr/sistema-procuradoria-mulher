<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Agenda integrada de compromissos da Procuradoria da Mulher.
 *
 * Um compromisso pode estar vinculado (quando aplicável) a uma mulher, a um
 * atendimento, a um encaminhamento e, futuramente, a retornos do acolhimento
 * (coluna preparada sem alterar o módulo de acolhimento).
 *
 * reagendado_para_id mantém o rastreio de reagendamentos: ao reagendar, o
 * registro original passa a "Reagendado" e aponta para o novo compromisso,
 * sem apagar o histórico.
 */
class CreateCompromissosTable extends Migration
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
            // Preparado para retornos decorrentes do acolhimento.
            'acolhimento_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'tipo' => [
                'type'       => 'VARCHAR',
                'constraint' => '40',
            ],
            'data' => [
                'type' => 'DATE',
            ],
            'hora_inicio' => [
                'type'       => 'VARCHAR',
                'constraint' => '8',
            ],
            'hora_fim' => [
                'type'       => 'VARCHAR',
                'constraint' => '8',
                'null'       => true,
            ],
            'profissional_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'observacoes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'default'    => 'agendado',
            ],
            // Novo compromisso criado no reagendamento.
            'reagendado_para_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
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
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('mulher_id');
        $this->forge->addKey('atendimento_id');
        $this->forge->addKey('encaminhamento_id');
        $this->forge->addKey('acolhimento_id');
        $this->forge->addKey('profissional_id');
        $this->forge->addKey('data');
        $this->forge->addKey('status');
        $this->forge->addKey('reagendado_para_id');
        $this->forge->addForeignKey('mulher_id', 'dados_mulher', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('atendimento_id', 'atendimentos', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('encaminhamento_id', 'encaminhamentos', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('acolhimento_id', 'acolhimentos', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('profissional_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('reagendado_para_id', 'compromissos', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('compromissos');
    }

    public function down()
    {
        $this->forge->dropTable('compromissos');
    }
}