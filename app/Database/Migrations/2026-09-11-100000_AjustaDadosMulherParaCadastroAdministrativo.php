<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AjustaDadosMulherParaCadastroAdministrativo extends Migration
{
    public function up()
    {
        $db     = $this->db;
        $tabela = $db->prefixTable('dados_mulher');
        $nova   = $db->prefixTable('dados_mulher_nova');

        // SQLite não suporta ALTER COLUMN para tornar user_id NULL nem adicionar
        // colunas em posições determinadas. Recria-se a tabela preservando dados.
        $db->query('PRAGMA foreign_keys = OFF');

        $db->query("CREATE TABLE `{$nova}` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `user_id` INTEGER NULL,
            `nome_completo` VARCHAR(200) NOT NULL,
            `nome_social` VARCHAR(200) NULL,
            `cpf` VARCHAR(14) NULL,
            `rg` VARCHAR(20) NULL,
            `data_nascimento` DATE NULL,
            `sexo` VARCHAR(1) NULL,
            `estado_civil` VARCHAR(20) NULL,
            `escolaridade` VARCHAR(50) NULL,
            `profissao` VARCHAR(100) NULL,
            `ocupacao` VARCHAR(100) NULL,
            `telefone` VARCHAR(20) NULL,
            `whatsapp` VARCHAR(20) NULL,
            `email` VARCHAR(150) NULL,
            `cep` VARCHAR(10) NULL,
            `logradouro` VARCHAR(200) NULL,
            `numero` VARCHAR(20) NULL,
            `complemento` VARCHAR(100) NULL,
            `bairro` VARCHAR(100) NULL,
            `municipio` VARCHAR(100) NULL,
            `estado` VARCHAR(2) NULL,
            `ponto_referencia` VARCHAR(200) NULL,
            `zona` VARCHAR(10) NULL,
            `possui_filhos` BOOLEAN NOT NULL DEFAULT 0,
            `quantidade_filhos` INTEGER NULL,
            `quantidade_dependentes` INTEGER NULL,
            `esta_gravida` BOOLEAN NOT NULL DEFAULT 0,
            `possui_deficiencia` BOOLEAN NOT NULL DEFAULT 0,
            `e_idosa` BOOLEAN NOT NULL DEFAULT 0,
            `situacao_vulnerabilidade` BOOLEAN NOT NULL DEFAULT 0,
            `observacoes` TEXT NULL,
            `created_at` DATETIME NULL,
            `updated_at` DATETIME NULL
        )");

        $cols = '`id`, `user_id`, `nome_completo`, `nome_social`, `cpf`, `rg`, `data_nascimento`,
            `sexo`, `estado_civil`, `escolaridade`, `profissao`, `ocupacao`, `telefone`,
            `whatsapp`, `email`, `cep`, `logradouro`, `numero`, `complemento`, `bairro`,
            `municipio`, `estado`, `ponto_referencia`, `zona`, `possui_filhos`, `esta_gravida`,
            `possui_deficiencia`, `e_idosa`, `situacao_vulnerabilidade`, `observacoes`,
            `created_at`, `updated_at`';

        $db->query("INSERT INTO `{$nova}` ({$cols}) SELECT {$cols} FROM `{$tabela}`");

        $db->query("DROP TABLE `{$tabela}`");
        $db->query("ALTER TABLE `{$nova}` RENAME TO `{$tabela}`");

        $db->query('CREATE INDEX ' . $db->escapeIdentifiers($db->prefixTable('dados_mulher_user_id')) . ' ON `' . $tabela . '` (`user_id`)');
        $db->query('CREATE INDEX ' . $db->escapeIdentifiers($db->prefixTable('dados_mulher_cpf')) . ' ON `' . $tabela . '` (`cpf`)');

        $db->query('PRAGMA foreign_keys = ON');
    }

    public function down()
    {
        $db     = $this->db;
        $tabela = $db->prefixTable('dados_mulher');
        $nova   = $db->prefixTable('dados_mulher_nova');

        $db->query('PRAGMA foreign_keys = OFF');

        $db->query("CREATE TABLE `{$nova}` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `user_id` INTEGER NULL,
            `nome_completo` VARCHAR(200) NOT NULL,
            `nome_social` VARCHAR(200) NULL,
            `cpf` VARCHAR(14) NULL,
            `rg` VARCHAR(20) NULL,
            `data_nascimento` DATE NULL,
            `sexo` VARCHAR(1) NULL,
            `estado_civil` VARCHAR(20) NULL,
            `escolaridade` VARCHAR(50) NULL,
            `profissao` VARCHAR(100) NULL,
            `ocupacao` VARCHAR(100) NULL,
            `telefone` VARCHAR(20) NULL,
            `whatsapp` VARCHAR(20) NULL,
            `email` VARCHAR(150) NULL,
            `cep` VARCHAR(10) NULL,
            `logradouro` VARCHAR(200) NULL,
            `numero` VARCHAR(20) NULL,
            `complemento` VARCHAR(100) NULL,
            `bairro` VARCHAR(100) NULL,
            `municipio` VARCHAR(100) NULL,
            `estado` VARCHAR(2) NULL,
            `ponto_referencia` VARCHAR(200) NULL,
            `zona` VARCHAR(10) NULL,
            `possui_filhos` BOOLEAN NOT NULL DEFAULT 0,
            `esta_gravida` BOOLEAN NOT NULL DEFAULT 0,
            `possui_deficiencia` BOOLEAN NOT NULL DEFAULT 0,
            `e_idosa` BOOLEAN NOT NULL DEFAULT 0,
            `situacao_vulnerabilidade` BOOLEAN NOT NULL DEFAULT 0,
            `observacoes` TEXT NULL,
            `created_at` DATETIME NULL,
            `updated_at` DATETIME NULL
        )");

        $cols = '`id`, `user_id`, `nome_completo`, `nome_social`, `cpf`, `rg`, `data_nascimento`,
            `sexo`, `estado_civil`, `escolaridade`, `profissao`, `ocupacao`, `telefone`,
            `whatsapp`, `email`, `cep`, `logradouro`, `numero`, `complemento`, `bairro`,
            `municipio`, `estado`, `ponto_referencia`, `zona`, `possui_filhos`, `esta_gravida`,
            `possui_deficiencia`, `e_idosa`, `situacao_vulnerabilidade`, `observacoes`,
            `created_at`, `updated_at`';

        $db->query("INSERT INTO `{$nova}` ({$cols}) SELECT {$cols} FROM `{$tabela}`");

        $db->query("DROP TABLE `{$tabela}`");
        $db->query("ALTER TABLE `{$nova}` RENAME TO `{$tabela}`");

        $db->query('CREATE INDEX ' . $db->escapeIdentifiers($db->prefixTable('dados_mulher_user_id')) . ' ON `' . $tabela . '` (`user_id`)');

        $db->query('PRAGMA foreign_keys = ON');
    }
}