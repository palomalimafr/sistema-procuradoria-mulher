<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAtendimentosTable extends Migration
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
            'protocolo' => [
                'type'       => 'VARCHAR',
                'constraint' => '30',
            ],
            'data_hora' => [
                'type' => 'DATETIME',
            ],
            'mulher_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'profissional_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'unidade_setor' => [
                'type'       => 'VARCHAR',
                'constraint' => '120',
                'null'       => true,
            ],
            'forma_atendimento' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'default'    => 'presencial',
            ],
            'origem' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'default'    => 'demanda_espontanea',
            ],
            'origem_outra' => [
                'type'       => 'VARCHAR',
                'constraint' => '150',
                'null'       => true,
            ],
            'motivo_atendimento' => [
                'type'       => 'VARCHAR',
                'constraint' => '200',
                'null'       => true,
            ],
            'classificacao' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            'prioridade' => [
                'type'       => 'VARCHAR',
                'constraint' => '15',
                'default'    => 'media',
            ],
            'situacao' => [
                'type'       => 'VARCHAR',
                'constraint' => '30',
                'default'    => 'novo',
            ],
            'descricao_caso' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'observacoes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'pedido_sos_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'data_encerramento' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'motivo_encerramento' => [
                'type'       => 'VARCHAR',
                'constraint' => '40',
                'null'       => true,
            ],
            'motivo_encerramento_outra' => [
                'type'       => 'VARCHAR',
                'constraint' => '150',
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
        $this->forge->addUniqueKey('protocolo');
        $this->forge->addKey('mulher_id');
        $this->forge->addKey('profissional_id');
        $this->forge->addKey('pedido_sos_id');
        $this->forge->addKey('situacao');
        $this->forge->addKey('data_hora');
        $this->forge->addForeignKey('mulher_id', 'dados_mulher', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('profissional_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('pedido_sos_id', 'pedidos_sos', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('atendimentos');
    }

    public function down()
    {
        $this->forge->dropTable('atendimentos');
    }
}