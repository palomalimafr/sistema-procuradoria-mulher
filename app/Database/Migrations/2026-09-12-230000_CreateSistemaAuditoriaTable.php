<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Trilha de auditoria do sistema (SISTEMA > Auditoria).
 *
 * Registro APPEND-ONLY de cada ação relevante executada nos módulos: quem
 * executou, quando, o que foi feito (ação), em qual módulo e registro, além
 * do IP/rota/método da requisição e um JSON opcional de contexto. Nunca há
 * exclusão/edição na interface; o histórico fica preservado para rastreio.
 */
class CreateSistemaAuditoriaTable extends Migration
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
            'acao' => [
                'type'       => 'VARCHAR',
                'constraint' => '30',
            ],
            'modulo' => [
                'type'       => 'VARCHAR',
                'constraint' => '30',
            ],
            'registro_tipo' => [
                'type'       => 'VARCHAR',
                'constraint' => '30',
                'null'       => true,
            ],
            'registro_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'registro_ref' => [
                'type'       => 'VARCHAR',
                'constraint' => '120',
                'null'       => true,
            ],
            'ip' => [
                'type'       => 'VARCHAR',
                'constraint' => '45',
                'null'       => true,
            ],
            'rota' => [
                'type'       => 'VARCHAR',
                'constraint' => '150',
                'null'       => true,
            ],
            'metodo' => [
                'type'       => 'VARCHAR',
                'constraint' => '10',
                'null'       => true,
            ],
            'detalhes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('created_at');
        $this->forge->addKey('usuario_id');
        $this->forge->addKey('acao');
        $this->forge->addKey('modulo');
        $this->forge->addKey(['registro_tipo', 'registro_id']);
        $this->forge->addForeignKey('usuario_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('sistema_auditoria');
    }

    public function down()
    {
        $this->forge->dropTable('sistema_auditoria');
    }
}