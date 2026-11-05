<?php

use App\Models\CriancaModel;
use App\Models\ResponsavelModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\Database\Seeds\TestDataSeeder;

/**
 * Testes dos models da aplicação.
 *
 * @internal
 */
final class ModelsTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = 'App';
    protected $seed      = TestDataSeeder::class;

    public function testCriancaModelListaCriancas(): void
    {
        $model = new CriancaModel();

        $this->assertCount(2, $model->findAll());
    }

    public function testCriancaModelValidacaoRejeitaNomeCurto(): void
    {
        $model = new CriancaModel();

        $this->assertFalse($model->save(['nome' => 'ab', 'status' => 'ativo']));
        $this->assertArrayHasKey('nome', $model->errors());
    }

    public function testCriancaModelContaAtivas(): void
    {
        $model = new CriancaModel();

        $this->assertSame(2, $model->where('status', 'ativo')->countAllResults());
    }

    public function testResponsavelModelInsereEListaPorCrianca(): void
    {
        $model = new ResponsavelModel();

        $id = $model->insert([
            'crianca_id' => 1,
            'nome'       => 'Maria Teste',
            'parentesco' => 'Mãe',
        ]);

        $this->assertNotFalse($id);
        $this->assertCount(1, $model->where('crianca_id', 1)->findAll());
    }

    public function testResponsavelModelValidacaoRejeitaSemNome(): void
    {
        $model = new ResponsavelModel();

        $this->assertFalse($model->save(['crianca_id' => 1, 'nome' => 'ab']));
        $this->assertArrayHasKey('nome', $model->errors());
    }

    public function testUserModelListaUsuarios(): void
    {
        $model = new UserModel();

        $this->assertCount(1, $model->findAll());
    }

    public function testUserModelValidaEmailUnico(): void
    {
        $model = new UserModel();

        $this->assertFalse($model->save([
            'nome'   => 'Duplicado',
            'email'  => 'admin@teste.com',
            'senha'  => '123456',
            'perfil' => 'funcionario',
            'ativo'  => 1,
        ]));
        $this->assertArrayHasKey('email', $model->errors());
    }

    public function testUserModelValidaSenhaCurta(): void
    {
        $model = new UserModel();

        $this->assertFalse($model->save([
            'nome'   => 'Novo Usuário',
            'email'  => 'novo@teste.com',
            'senha'  => '123',
            'perfil' => 'funcionario',
            'ativo'  => 1,
        ]));
        $this->assertArrayHasKey('senha', $model->errors());
    }
}
