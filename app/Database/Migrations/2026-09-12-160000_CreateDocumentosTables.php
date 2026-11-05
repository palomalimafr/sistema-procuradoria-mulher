<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Repositório de documentos (módulo PROCESSOS > Documentos).
 *
 * O documento é vinculado diretamente à mulher (dados_mulher — cadastro
 * administrativo) e pode, quando aplicável, ter relação com atendimento,
 * processo, medida protetiva e/ou encaminhamento existentes. Não depende do
 * módulo CASOS.
 *
 * - O arquivo físico fica fora do docroot (writable/uploads/documentos) e só
 *   é acessado por endpoints do sistema (visualizar/download).
 * - O nome físico armazenado é gerado aleatoriamente (nunca o nome original).
 * - Exclusão é LÓGICA (excluido_em/excluido_por): preserva arquivo e
 *   histórico, permite recuperação futura.
 * - Sigilo e tipo usam listas simples e configuráveis.
 *
 * Tabelas criadas:
 *  - documentos           (metadados + caminho seguro do arquivo)
 *  - documento_historico  (histórico no mesmo padrão de processo/acolhimento)
 */
class CreateDocumentosTables extends Migration
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
            // Vínculos opcionais (todos da MESMA mulher vinculada).
            'atendimento_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'processo_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'medida_protetiva_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'encaminhamento_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            // Tipo simples/configurável (padrão "outros").
            'tipo' => [
                'type'       => 'VARCHAR',
                'constraint' => '30',
                'default'    => 'outros',
            ],
            // Classificação de sigilo (público/restrito/confidencial/sigiloso).
            'sigilo' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'default'    => 'restrito',
            ],
            // Nome original informado pelo usuário no envio (apenas exposição).
            'arquivo_original' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            // Caminho relativo em writable/uploads (nome físico aleatório).
            'arquivo' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'tamanho_bytes' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'tipo_mime' => [
                'type'       => 'VARCHAR',
                'constraint' => '120',
                'null'       => true,
            ],
            'observacoes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            // Responsável pelo envio/edição = usuário autenticado (sempre).
            'usuario_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            // Exclusão lógica (preserva arquivo físico e histórico).
            'excluido_em' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'excluido_por' => [
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
        $this->forge->addKey('mulher_id');
        $this->forge->addKey('atendimento_id');
        $this->forge->addKey('processo_id');
        $this->forge->addKey('medida_protetiva_id');
        $this->forge->addKey('encaminhamento_id');
        $this->forge->addKey('tipo');
        $this->forge->addKey('sigilo');
        $this->forge->addKey('usuario_id');
        $this->forge->addKey('created_at');
        $this->forge->addForeignKey('mulher_id', 'dados_mulher', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('atendimento_id', 'atendimentos', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('processo_id', 'processos', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('medida_protetiva_id', 'medidas_protetivas', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('encaminhamento_id', 'encaminhamentos', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('usuario_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('excluido_por', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('documentos');

        // Histórico do documento (upload, edição, sigilo, download, exclusão).
        $this->forge->addField([
            'id' => [
                'type'           => 'INTEGER',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'documento_id' => [
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
        $this->forge->addKey('documento_id');
        $this->forge->addKey('acao');
        $this->forge->addForeignKey('documento_id', 'documentos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('usuario_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('documento_historico');
    }

    public function down()
    {
        $this->forge->dropTable('documento_historico');
        $this->forge->dropTable('documentos');
    }
}