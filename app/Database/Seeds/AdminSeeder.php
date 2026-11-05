<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run()
    {
        // Usuário admin padrão
        $this->db->table('users')->insert([
            'nome'       => 'Administrador',
            'email'      => 'admin@modelo.com.br',
            'senha'      => password_hash('admin123', PASSWORD_DEFAULT),
            'perfil'     => 'admin',
            'ativo'      => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Usuário funcionário
        $this->db->table('users')->insert([
            'nome'       => 'Funcionário Teste',
            'email'      => 'funcionario@modelo.com.br',
            'senha'      => password_hash('func123', PASSWORD_DEFAULT),
            'perfil'     => 'funcionario',
            'ativo'      => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Crianças de exemplo
        $this->db->table('criancas')->insertBatch([
            [
                'nome'            => 'Ana Clara Souza',
                'data_nascimento' => '2015-03-12',
                'sexo'            => 'F',
                'data_matricula'  => date('Y-m-d'),
                'nome_mae'        => 'Maria Souza',
                'telefone'        => '(11) 99999-1111',
                'cidade'          => 'São Paulo',
                'uf'              => 'SP',
                'status'          => 'ativo',
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ],
            [
                'nome'            => 'Pedro Henrique Lima',
                'data_nascimento' => '2016-07-25',
                'sexo'            => 'M',
                'data_matricula'  => date('Y-m-d'),
                'nome_mae'        => 'Juliana Lima',
                'telefone'        => '(11) 98888-2222',
                'cidade'          => 'São Paulo',
                'uf'              => 'SP',
                'status'          => 'ativo',
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ],
            [
                'nome'            => 'Lucas Oliveira',
                'data_nascimento' => '2014-11-02',
                'sexo'            => 'M',
                'data_matricula'  => date('Y-m-d'),
                'nome_mae'        => 'Fernanda Oliveira',
                'telefone'        => '(11) 97777-3333',
                'cidade'          => 'São Paulo',
                'uf'              => 'SP',
                'status'          => 'inativo',
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ],
        ]);
    }
}
