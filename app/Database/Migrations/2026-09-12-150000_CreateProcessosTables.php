<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Processos e procedimentos (módulo PROCESSOS > Processos).
 *
 * O processo é vinculado diretamente à mulher (dados_mulher — cadastro
 * administrativo) e pode, quando aplicável, ter relação com um atendimento
 * e/ou uma medida protetiva existente. Não depende do módulo CASOS.
 *
 * - Órgão reutiliza a estrutura existente (EncaminhamentoModel::ORGAOS).
 * - Situação e tipo usam listas simples e configuráveis (sem fluxo jurídico
 *   complexo).
 * - Número é informado livremente (sem geração automática).
 *
 * Tabelas criadas:
 *  - processos            (registro do processo/procedimento)
 *  - processo_historico   (histórico no mesmo padrão do acolhimento/medida)
 */
class CreateProcessosTables extends Migration
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
            // Atendimento que originou o processo (opcional).
            'atendimento_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            // Vínculo com uma medida protetiva existente (opcional).
            'medida_protetiva_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            // Número informado livremente, preservando pontuação/formatação.
            'numero_processo' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            // Tipo simples/configurável (sem lista extensa nesta etapa).
            'tipo' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'default'    => 'outros',
            ],
            // Órgão responsável (chaves de EncaminhamentoModel::ORGAOS).
            'orgao' => [
                'type'       => 'VARCHAR',
                'constraint' => '60',
                'null'       => true,
            ],
            // Vara e comarca como texto nesta etapa (sem módulos separados).
            'vara' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            'comarca' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            'data' => [
                'type' => 'DATE',
                'null' => true,
            ],
            // Situação genérica/configurável (sem fluxo jurídico complexo).
            'situacao' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'default'    => 'em_andamento',
            ],
            'responsavel_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
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
        $this->forge->addKey('medida_protetiva_id');
        $this->forge->addKey('numero_processo');
        $this->forge->addKey('tipo');
        $this->forge->addKey('orgao');
        $this->forge->addKey('situacao');
        $this->forge->addKey('data');
        $this->forge->addKey('responsavel_id');
        $this->forge->addForeignKey('mulher_id', 'dados_mulher', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('atendimento_id', 'atendimentos', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('medida_protetiva_id', 'medidas_protetivas', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('responsavel_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('processos');

        // Histórico do processo (criação, situação, responsável, edição).
        $this->forge->addField([
            'id' => [
                'type'           => 'INTEGER',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'processo_id' => [
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
        $this->forge->addKey('processo_id');
        $this->forge->addKey('acao');
        $this->forge->addForeignKey('processo_id', 'processos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('usuario_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('processo_historico');
    }

    public function down()
    {
        $this->forge->dropTable('processo_historico');
        $this->forge->dropTable('processos');
    }
}