<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Medidas Protetivas (módulo da Procuradoria da Mulher).
 *
 * Uma medida é um registro próprio, vinculado à mulher (dados_mulher) e,
 * quando aplicável, ao atendimento que originou a solicitação. Órgão
 * responsável utiliza a estrutura de órgãos existente (EncaminhamentoModel).
 * O vínculo futuro com processo/situação de violência não é obrigatório.
 *
 * Tabelas criadas:
 *  - medidas_protetivas        (registro da medida + situação)
 *  - medida_descumprimentos    (ocorrências vinculadas à medida)
 *  - medida_historico          (histórico do mesmo padrão do acolhimento)
 */
class CreateMedidasProtetivasTables extends Migration
{
    public function up()
    {
        // Medida protetiva: registro próprio com ciclo de situação.
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
            // Atendimento que originou a solicitação (opcional).
            'atendimento_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            // "Possui medida protetiva?" — quando 0, os campos específicos da
            // medida não são exigidos e ficam vazios.
            'possui_medida' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'numero' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
            ],
            'data_solicitacao' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'data_concessao' => [
                'type' => 'DATE',
                'null' => true,
            ],
            // Órgão responsável pela medida (chaves de EncaminhamentoModel::ORGAOS).
            'orgao' => [
                'type'       => 'VARCHAR',
                'constraint' => '60',
                'null'       => true,
            ],
            // Identificador do processo relacionado (módulo de Processos futuro;
            // neste momento apenas o identificador/informação é registrado).
            'processo' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            'situacao' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'null'       => true,
            ],
            // Data de validade da medida, quando aplicável (sem regra automática).
            'data_validade' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'observacoes' => [
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
        $this->forge->addKey('mulher_id');
        $this->forge->addKey('atendimento_id');
        $this->forge->addKey('situacao');
        $this->forge->addKey('data_solicitacao');
        $this->forge->addKey('data_validade');
        $this->forge->addForeignKey('mulher_id', 'dados_mulher', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('atendimento_id', 'atendimentos', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('medidas_protetivas');

        // Descumprimentos da medida. Ocorrências anteriores nunca são apagadas.
        $this->forge->addField([
            'id' => [
                'type'           => 'INTEGER',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'medida_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'data_ocorrencia' => [
                'type' => 'DATE',
            ],
            'descricao' => [
                'type' => 'TEXT',
            ],
            'observacoes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            // Registro/histórico da ocorrência.
            'historico' => [
                'type' => 'TEXT',
                'null' => true,
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
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('medida_id');
        $this->forge->addKey('data_ocorrencia');
        $this->forge->addForeignKey('medida_id', 'medidas_protetivas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('usuario_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('medida_descumprimentos');

        // Histórico da medida (criação, situação, descumprimento) — segue o
        // mesmo padrão do histórico de acolhimento (estrutura por módulo).
        $this->forge->addField([
            'id' => [
                'type'           => 'INTEGER',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'medida_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'acao' => [
                'type'       => 'VARCHAR',
                'constraint' => '30',
            ],
            'status_anterior' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'null'       => true,
            ],
            'status_novo' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'null'       => true,
            ],
            'observacao' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
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
        $this->forge->addKey('medida_id');
        $this->forge->addKey('acao');
        $this->forge->addForeignKey('medida_id', 'medidas_protetivas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('usuario_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('medida_historico');
    }

    public function down()
    {
        $this->forge->dropTable('medida_historico');
        $this->forge->dropTable('medida_descumprimentos');
        $this->forge->dropTable('medidas_protetivas');
    }
}