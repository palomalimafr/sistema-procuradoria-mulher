<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Encaminhamento da mulher para órgãos/serviços da rede de proteção.
 *
 * Vinculado obrigatoriamente à mulher (dados_mulher) e, quando aplicável, ao
 * atendimento que originou a necessidade e ao acolhimento (coluna preparada
 * para a futura relação "acolhimento -> necessidade -> encaminhamento", sem
 * alterar o módulo de acolhimento). Nenhum cadastro independente da mulher.
 *
 * Órgão/serviço são valores configuráveis (constantes do EncaminhamentoModel),
 * estrutura mínima; o cadastro completo da Rede de Proteção fica para o módulo
 * próprio em etapa futura.
 */
class CreateEncaminhamentosTable extends Migration
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
            // Atendimento que originou a necessidade (opcional).
            'atendimento_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            // Preparado para a futura relação com o acolhimento.
            'acolhimento_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'data_hora' => [
                'type' => 'DATETIME',
            ],
            'profissional_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'default'    => 'pendente',
            ],
            'orgao' => [
                'type'       => 'VARCHAR',
                'constraint' => '60',
            ],
            'servico' => [
                'type'       => 'VARCHAR',
                'constraint' => '60',
                'null'       => true,
            ],
            'motivo' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'prioridade' => [
                'type'       => 'VARCHAR',
                'constraint' => '15',
                'default'    => 'media',
            ],
            'prazo' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'observacoes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'data_conclusao' => [
                'type' => 'DATETIME',
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
        $this->forge->addKey('acolhimento_id');
        $this->forge->addKey('profissional_id');
        $this->forge->addKey('status');
        $this->forge->addKey('prioridade');
        $this->forge->addKey('prazo');
        $this->forge->addKey('data_hora');
        $this->forge->addForeignKey('mulher_id', 'dados_mulher', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('atendimento_id', 'atendimentos', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('acolhimento_id', 'acolhimentos', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('profissional_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('encaminhamentos');
    }

    public function down()
    {
        $this->forge->dropTable('encaminhamentos');
    }
}