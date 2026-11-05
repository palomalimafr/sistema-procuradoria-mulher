<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tabela de configurações do sistema (SISTEMA > Configurações).
 *
 * Armazena as informações institucionais e gerais como pares chave -> valor,
 * permitindo que cada configuração seja persistida de forma independente
 * (nome do sistema, instituição, contatos, endereço e logo).
 */
class CreateConfiguracoesTable extends Migration
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
            'chave' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'unique'     => true,
            ],
            'valor' => [
                'type' => 'TEXT',
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
        $this->forge->createTable('sistema_configuracoes');
    }

    public function down()
    {
        $this->forge->dropTable('sistema_configuracoes');
    }
}