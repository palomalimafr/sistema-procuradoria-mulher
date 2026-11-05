<?php

namespace Tests\Support\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run()
    {
        $this->db->table('users')->insert([
            'nome'       => 'Admin Teste',
            'email'      => 'admin@teste.com',
            'senha'      => password_hash('admin123', PASSWORD_DEFAULT),
            'perfil'     => 'admin',
            'ativo'      => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->db->table('criancas')->insertBatch([
            [
                'nome'            => 'Ana Teste',
                'data_nascimento' => '2015-03-12',
                'sexo'            => 'F',
                'status'          => 'ativo',
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ],
            [
                'nome'            => 'Pedro Teste',
                'data_nascimento' => '2016-07-25',
                'sexo'            => 'M',
                'status'          => 'ativo',
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ],
        ]);
    }
}
