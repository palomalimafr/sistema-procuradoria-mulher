<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Integração mínima da Rede de Proteção com os Encaminhamentos.
 *
 * Adiciona a coluna instituicao_id (nullable) na tabela encaminhamentos,
 * preservando as colunas legadas orgao/servico e todos os registros
 * existentes. A integridade é garantida na aplicação (a instituição é
 * validada como existente e ativa no momento do cadastro); não há exclusão
 * física de instituições.
 */
class AddInstituicaoToEncaminhamentos extends Migration
{
    public function up()
    {
        $this->forge->addColumn('encaminhamentos', [
            'instituicao_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
        ]);

        $this->forge->addKey('instituicao_id', false, false, 'enc_instituicao_id_idx');
        $this->forge->processIndexes('encaminhamentos');
    }

    public function down()
    {
        $this->forge->dropColumn('encaminhamentos', 'instituicao_id');
    }
}