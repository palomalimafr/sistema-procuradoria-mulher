<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Cadastro da Rede de Proteção: instituições/órgãos e os serviços que cada
 * uma oferece. As instituições recebem encaminhamentos (relação construída
 * pela integração mínima com o módulo de Encaminhamentos).
 *
 * Não há exclusão física: uma instituição desativada (ativo = 0) preserva o
 * histórico e deixa de aparecer como opção para NOVOS encaminhamentos.
 */
class CreateRedeProtecaoTables extends Migration
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
            'nome' => [
                'type'       => 'VARCHAR',
                'constraint' => '120',
            ],
            'tipo' => [
                'type'       => 'VARCHAR',
                'constraint' => '40',
            ],
            'endereco' => [
                'type'       => 'VARCHAR',
                'constraint' => '200',
                'null'       => true,
            ],
            'municipio' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            'telefone' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'null'       => true,
            ],
            'whatsapp' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'null'       => true,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => '120',
                'null'       => true,
            ],
            'responsavel' => [
                'type'       => 'VARCHAR',
                'constraint' => '120',
                'null'       => true,
            ],
            'horario_atendimento' => [
                'type'       => 'VARCHAR',
                'constraint' => '120',
                'null'       => true,
            ],
            'observacoes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'ativo' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'default'    => 1,
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
        $this->forge->addKey('nome');
        $this->forge->addKey('tipo');
        $this->forge->addKey('municipio');
        $this->forge->addKey('ativo');
        $this->forge->createTable('rede_instituicoes');

        $this->forge->addField([
            'id' => [
                'type'           => 'INTEGER',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'instituicao_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'nome' => [
                'type'       => 'VARCHAR',
                'constraint' => '120',
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
        $this->forge->addKey('instituicao_id');
        $this->forge->addForeignKey('instituicao_id', 'rede_instituicoes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('rede_servicos');
    }

    public function down()
    {
        $this->forge->dropTable('rede_servicos');
        $this->forge->dropTable('rede_instituicoes');
    }
}