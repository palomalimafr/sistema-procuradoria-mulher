<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLocalizacaoToPedidosSosTable extends Migration
{
    public function up()
    {
        $this->forge->addColumn('pedidos_sos', [
            'latitude' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,7',
                'null'       => true,
                'after'      => 'status',
            ],
            'longitude' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,7',
                'null'       => true,
                'after'      => 'latitude',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('pedidos_sos', ['latitude', 'longitude']);
    }
}