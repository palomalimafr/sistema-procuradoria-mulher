<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePedidosSosTable extends Migration
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
            ],
            'data_hora' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'default'    => 'pendente',
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
        $this->forge->addForeignKey('mulher_id', 'dados_mulher', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pedidos_sos');
    }

    public function down()
    {
        $this->forge->dropTable('pedidos_sos');
    }
}