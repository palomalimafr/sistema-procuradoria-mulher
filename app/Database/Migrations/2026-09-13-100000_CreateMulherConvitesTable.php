<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Convites de primeiro acesso das mulheres cadastradas pela equipe.
 *
 * Cada linha é um convite de uso único: guarda apenas o HASH do token (SHA-256),
 * o prazo de expiração e as datas de envio/utilização. O token em texto puro
 * existe somente no e-mail enviado à mulher e nunca é persistido, portanto não
 * pode ser recuperado pelo painel administrativo.
 */
class CreateMulherConvitesTable extends Migration
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
            // SHA-256 em hexadecimal (64 caracteres) do token enviado por e-mail.
            'token_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            'expira_em' => [
                'type' => 'DATETIME',
            ],
            'enviado_em' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'utilizado_em' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            // Cancelado pela equipe (reenvio do convite ou troca de e-mail).
            // Diferente de 'utilizado_em': a mulher precisa ser avisada de pedir
            // um novo convite, e não de que a conta dela já existe.
            'invalidado_em' => [
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
        $this->forge->addUniqueKey('token_hash');
        $this->forge->addKey(['mulher_id', 'utilizado_em']);
        $this->forge->addKey(['mulher_id', 'invalidado_em']);
        $this->forge->addForeignKey('mulher_id', 'dados_mulher', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('mulher_convites');
    }

    public function down()
    {
        $this->forge->dropTable('mulher_convites', true);
    }
}
