<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Manifestações (módulo OUVIDORIA > Denúncias/Ouvidoria).
 *
 * Recebe e acompanha manifestações (denúncias, reclamações, solicitações,
 * informações, elogios, sugestões) relacionadas à Procuradoria da Mulher.
 *
 * - Protocolo gerado automaticamente (único, padrão MAN-AANN-NNNNNN).
 * - Identificação opcional: a manifestação pode ser anônima ou registrar os
 *   dados da pessoa (nome/CPF/telefone/e-mail) sem obrigatoriedade.
 * - Pode ser vinculada a uma mulher já cadastrada (opcional, não duplica).
 * - Pode ser encaminhada a uma instituição da Rede de Proteção EXISTENTE
 *   (rede_instituicoes) — não cria tabela paralela de instituições.
 * - Situação segue fluxo com transições validadas em aplicação
 *   (ManifestacaoModel::FLUXO_STATUS); resolvida/encerrada gravam
 *   data_encerramento.
 * - Prioridade reutiliza AtendimentoModel::PRIORIDADES.
 *
 * Tabelas criadas:
 *  - manifestacoes           (registro da manifestação)
 *  - manifestacao_historico  (histórico no padrão de processo/documento)
 */
class CreateManifestacoesTables extends Migration
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
            // Protocolo único gerado automaticamente (MAN-AANN-NNNNNN).
            'protocolo' => [
                'type'       => 'VARCHAR',
                'constraint' => '30',
            ],
            // Data e hora do registro da manifestação.
            'data_hora' => [
                'type' => 'DATETIME',
            ],
            // Tipo da manifestação (chaves de ManifestacaoModel::TIPOS).
            'tipo' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'default'    => 'denuncia',
            ],
            // Origem/canal da entrada (chaves de ManifestacaoModel::ORIGENS).
            'origem' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'default'    => 'presencial',
            ],
            // Manifestação anônima (não exige identificação da pessoa).
            'anonimo' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            // Identificação da pessoa (opcional — nunca obrigatória).
            'pessoa_nome' => [
                'type'       => 'VARCHAR',
                'constraint' => '120',
                'null'       => true,
            ],
            'pessoa_cpf' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'null'       => true,
            ],
            'pessoa_telefone' => [
                'type'       => 'VARCHAR',
                'constraint' => '30',
                'null'       => true,
            ],
            'pessoa_email' => [
                'type'       => 'VARCHAR',
                'constraint' => '120',
                'null'       => true,
            ],
            // Vínculo OPcional com mulher já cadastrada (sem duplicar cadastro).
            'mulher_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            // Relato da manifestação/denúncia (obrigatório).
            'descricao' => [
                'type' => 'TEXT',
            ],
            // Local relacionado ao fato (opcional).
            'local_relacionado' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'observacoes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            // Prioridade reutiliza AtendimentoModel::PRIORIDADES.
            'prioridade' => [
                'type'       => 'VARCHAR',
                'constraint' => '15',
                'default'    => 'media',
            ],
            // Situação (chaves de ManifestacaoModel::SITUACOES).
            'situacao' => [
                'type'       => 'VARCHAR',
                'constraint' => '30',
                'default'    => 'nova',
            ],
            // Responsável pelo atendimento/análise (users — equipe).
            'responsavel_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            // Instituição da Rede de Proteção para onde foi encaminhada
            // (rede_instituicoes — reutiliza a estrutura existente).
            'instituicao_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'data_encerramento' => [
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
        $this->forge->addUniqueKey('protocolo');
        $this->forge->addKey('data_hora');
        $this->forge->addKey('tipo');
        $this->forge->addKey('origem');
        $this->forge->addKey('mulher_id');
        $this->forge->addKey('prioridade');
        $this->forge->addKey('situacao');
        $this->forge->addKey('responsavel_id');
        $this->forge->addKey('instituicao_id');
        $this->forge->addForeignKey('mulher_id', 'dados_mulher', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('responsavel_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('instituicao_id', 'rede_instituicoes', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('manifestacoes');

        // Histórico da manifestação (criação, situação, encaminhamento,
        // responsável e edição).
        $this->forge->addField([
            'id' => [
                'type'           => 'INTEGER',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'manifestacao_id' => [
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
                'constraint' => '30',
                'null'       => true,
            ],
            'status_novo' => [
                'type'       => 'VARCHAR',
                'constraint' => '30',
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
        $this->forge->addKey('manifestacao_id');
        $this->forge->addKey('acao');
        $this->forge->addForeignKey('manifestacao_id', 'manifestacoes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('usuario_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('manifestacao_historico');
    }

    public function down()
    {
        $this->forge->dropTable('manifestacao_historico');
        $this->forge->dropTable('manifestacoes');
    }
}