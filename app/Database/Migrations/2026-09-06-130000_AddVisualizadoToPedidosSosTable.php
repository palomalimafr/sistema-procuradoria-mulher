<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVisualizadoToPedidosSosTable extends Migration
{
    public function up()
    {
        $this->forge->addColumn('pedidos_sos', [
            'visualizado_em' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'longitude',
            ],
        ]);

        // Pedidos já existentes não devem ser tratados como "novos" pelo admin.
        $tabela = $this->db->prefixTable('pedidos_sos');
        $this->db->query("UPDATE {$tabela} SET visualizado_em = data_hora");
    }

    public function down()
    {
        $this->forge->dropColumn('pedidos_sos', 'visualizado_em');
    }
}