<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMulherPerfilAndDadosTable extends Migration
{
    public function up()
    {
        $db = $this->db;

        $usersTable = $db->prefixTable('users');
        $usersNew   = $db->prefixTable('users_new');

        // SQLite não suporta ALTER COLUMN para modificar uma constraint CHECK.
        // A forma correta é recriar a tabela preservando os dados existentes.
        $temColuna = $db->query("SELECT COUNT(*) AS total FROM pragma_table_info('{$usersTable}') WHERE name = 'perfil'")->getRowArray();

        if ($temColuna && (int) $temColuna['total'] === 1) {
            $db->query("CREATE TABLE `{$usersNew}` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `nome` VARCHAR NOT NULL,
                `email` VARCHAR NOT NULL UNIQUE,
                `senha` VARCHAR NOT NULL,
                `perfil` TEXT CHECK(`perfil` IN ('admin','funcionario','mulher')) NOT NULL DEFAULT 'funcionario',
                `ativo` INT NOT NULL DEFAULT 1,
                `created_at` DATETIME NULL,
                `updated_at` DATETIME NULL
            )");

            $db->query("INSERT INTO `{$usersNew}` (`id`, `nome`, `email`, `senha`, `perfil`, `ativo`, `created_at`, `updated_at`)
                        SELECT `id`, `nome`, `email`, `senha`, `perfil`, `ativo`, `created_at`, `updated_at` FROM `{$usersTable}`");

            $db->query("DROP TABLE `{$usersTable}`");
            $db->query("ALTER TABLE `{$usersNew}` RENAME TO `{$usersTable}`");
        }

        // Tabela de dados específicos da mulher
        $this->forge->addField([
            'id' => [
                'type'           => 'INTEGER',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            // Dados pessoais
            'nome_completo' => [
                'type'       => 'VARCHAR',
                'constraint' => '200',
            ],
            'nome_social' => [
                'type'       => 'VARCHAR',
                'constraint' => '200',
                'null'       => true,
            ],
            'cpf' => [
                'type'       => 'VARCHAR',
                'constraint' => '14',
                'null'       => true,
            ],
            'rg' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'null'       => true,
            ],
            'data_nascimento' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'sexo' => [
                'type'       => 'VARCHAR',
                'constraint' => '1',
                'null'       => true,
            ],
            'estado_civil' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'null'       => true,
            ],
            'escolaridade' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
            ],
            'profissao' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            'ocupacao' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            // Contato
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
                'constraint' => '150',
                'null'       => true,
            ],
            // Endereço
            'cep' => [
                'type'       => 'VARCHAR',
                'constraint' => '10',
                'null'       => true,
            ],
            'logradouro' => [
                'type'       => 'VARCHAR',
                'constraint' => '200',
                'null'       => true,
            ],
            'numero' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'null'       => true,
            ],
            'complemento' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            'bairro' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            'municipio' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            'estado' => [
                'type'       => 'VARCHAR',
                'constraint' => '2',
                'null'       => true,
            ],
            'ponto_referencia' => [
                'type'       => 'VARCHAR',
                'constraint' => '200',
                'null'       => true,
            ],
            'zona' => [
                'type'       => 'VARCHAR',
                'constraint' => '10',
                'null'       => true,
            ],
            // Informações adicionais
            'possui_filhos' => [
                'type'    => 'BOOLEAN',
                'default' => 0,
            ],
            'esta_gravida' => [
                'type'    => 'BOOLEAN',
                'default' => 0,
            ],
            'possui_deficiencia' => [
                'type'    => 'BOOLEAN',
                'default' => 0,
            ],
            'e_idosa' => [
                'type'    => 'BOOLEAN',
                'default' => 0,
            ],
            'situacao_vulnerabilidade' => [
                'type'    => 'BOOLEAN',
                'default' => 0,
            ],
            'observacoes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            // Timestamps
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
        $this->forge->addKey('user_id');
        $this->forge->createTable('dados_mulher');
    }

    public function down()
    {
        $db = $this->db;

        $db->query('DROP TABLE IF EXISTS ' . $db->prefixTable('dados_mulher'));

        $usersTable = $db->prefixTable('users');
        $usersOld   = $db->prefixTable('users_old');

        $temColuna = $db->query("SELECT COUNT(*) AS total FROM pragma_table_info('{$usersTable}') WHERE name = 'perfil'")->getRowArray();

        if ($temColuna && (int) $temColuna['total'] === 1) {
            // Mantém 'mulher' permitido durante o rollback para não perder dados.
            $db->query("CREATE TABLE `{$usersOld}` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `nome` VARCHAR NOT NULL,
                `email` VARCHAR NOT NULL UNIQUE,
                `senha` VARCHAR NOT NULL,
                `perfil` TEXT CHECK(`perfil` IN ('admin','funcionario','mulher')) NOT NULL DEFAULT 'funcionario',
                `ativo` INT NOT NULL DEFAULT 1,
                `created_at` DATETIME NULL,
                `updated_at` DATETIME NULL
            )");

            $db->query("INSERT INTO `{$usersOld}` (`id`, `nome`, `email`, `senha`, `perfil`, `ativo`, `created_at`, `updated_at`)
                        SELECT `id`, `nome`, `email`, `senha`, `perfil`, `ativo`, `created_at`, `updated_at` FROM `{$usersTable}`");

            $db->query("DROP TABLE `{$usersTable}`");
            $db->query("ALTER TABLE `{$usersOld}` RENAME TO `{$usersTable}`");
        }
    }
}