<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Anexos da manifestação (módulo OUVIDORIA > Denúncias/Ouvidoria).
 *
 * Reutiliza o repositório de Documentos existente em vez de criar um sistema
 * paralelo: adiciona a coluna manifestacao_id (nullable) na tabela documentos
 * para associar arquivos a uma manifestação, preservando todos os registros
 * existentes. A integridade é garantida na aplicação (a manifestação é
 * validada como existente no momento do cadastro/edição).
 */
class AddManifestacaoToDocumentos extends Migration
{
    public function up()
    {
        $this->forge->addColumn('documentos', [
            'manifestacao_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
        ]);

        $this->forge->addKey('manifestacao_id', false, false, 'doc_manifestacao_id_idx');
        $this->forge->processIndexes('documentos');
    }

    public function down()
    {
        $this->forge->dropColumn('documentos', 'manifestacao_id');
    }
}