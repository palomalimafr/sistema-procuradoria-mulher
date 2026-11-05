<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCasosTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'protocolo' => ['type' => 'VARCHAR', 'constraint' => 30],
            'mulher_id' => ['type' => 'INTEGER', 'constraint' => 11, 'unsigned' => true],
            'responsavel_id' => ['type' => 'INTEGER', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'prioridade' => ['type' => 'VARCHAR', 'constraint' => 15, 'default' => 'media'],
            'situacao' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'novo'],
            'data_abertura' => ['type' => 'DATETIME'],
            'data_encerramento' => ['type' => 'DATETIME', 'null' => true],
            'motivo_encerramento' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'motivo_encerramento_outra' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'resumo' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('protocolo');
        $this->forge->addKey(['mulher_id', 'situacao']);
        $this->forge->addKey('responsavel_id');
        $this->forge->addKey('prioridade');
        $this->forge->addKey('data_abertura');
        $this->forge->addForeignKey('mulher_id', 'dados_mulher', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('responsavel_id', 'users', 'id', 'SET NULL', 'CASCADE');
        if (! $this->db->tableExists('casos')) {
            $this->forge->createTable('casos');
        }
        $this->forge->reset();

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'caso_id' => ['type' => 'INTEGER', 'constraint' => 11, 'unsigned' => true],
            'usuario_id' => ['type' => 'INTEGER', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'tipo_evento' => ['type' => 'VARCHAR', 'constraint' => 40],
            'descricao' => ['type' => 'VARCHAR', 'constraint' => 255],
            'dados_adicionais' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['caso_id', 'created_at']);
        $this->forge->addKey('tipo_evento');
        $this->forge->addForeignKey('caso_id', 'casos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('usuario_id', 'users', 'id', 'SET NULL', 'CASCADE');
        if (! $this->db->tableExists('caso_historico')) {
            $this->forge->createTable('caso_historico');
        }
        $this->forge->reset();

        foreach (['atendimentos', 'encaminhamentos', 'tarefas', 'medidas_protetivas', 'processos', 'documentos'] as $tabela) {
            if (! $this->db->fieldExists('caso_id', $tabela)) {
                $this->forge->addColumn($tabela, [
                    'caso_id' => ['type' => 'INTEGER', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                ]);
            }
            // SQLite cannot adicionar FKs a tabelas existentes. A validação de
            // pertencimento é feita na aplicação; nos demais drivers, a FK é criada.
            // O prefixo da conexão precisa ser respeitado no SQL cru (o grupo de
            // testes usa 'db_', o ambiente real usa prefixo vazio).
            $nomeTabela = $this->db->prefixTable($tabela);
            $nomeIndice = $this->db->prefixTable($tabela . '_caso_id_idx');
            $nomeChave  = $tabela . '_caso_id_foreign';

            $this->db->query("CREATE INDEX IF NOT EXISTS {$nomeIndice} ON {$nomeTabela} (caso_id)");
            if ($this->db->DBDriver !== 'SQLite3') {
                $this->db->query("ALTER TABLE {$nomeTabela} ADD CONSTRAINT {$nomeChave} FOREIGN KEY (caso_id) REFERENCES {$this->db->prefixTable('casos')} (id) ON DELETE SET NULL ON UPDATE CASCADE");
            }
        }
    }

    public function down()
    {
        foreach (['documentos', 'processos', 'medidas_protetivas', 'tarefas', 'encaminhamentos', 'atendimentos'] as $tabela) {
            if ($this->db->fieldExists('caso_id', $tabela)) {
                $this->db->query('DROP INDEX IF EXISTS ' . $tabela . '_caso_id_idx');
                $this->forge->dropColumn($tabela, 'caso_id');
            }
        }
        $this->forge->dropTable('caso_historico');
        $this->forge->dropTable('casos');
    }
}
