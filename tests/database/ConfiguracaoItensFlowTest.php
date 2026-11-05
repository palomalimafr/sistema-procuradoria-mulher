<?php

use App\Models\AtendimentoModel;
use App\Models\ConfiguracaoListaModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Database\Seeds\TestDataSeeder;

/**
 * Testes do módulo SISTEMA > Configurações > Listas configuráveis.
 *
 * Cobre o acesso restrito ao admin, a administração do catálogo
 * `sistema_configuracoes_itens` (cadastro, chave duplicada/inválida, renomear e
 * inativação lógica) e a integração com a validação `in_list` dos models:
 * itens novos passam a ser aceitos, itens inativados continuam válidos no
 * histórico (opcoesTodas/rotulo) mas somem dos formulários novos (opcoes).
 *
 * @internal
 */
final class ConfiguracaoItensFlowTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $seed      = TestDataSeeder::class;

    private function logarComo(string $perfil): void
    {
        $this->withSession([
            'logado'        => true,
            'usuario_id'    => 1,
            'usuario_nome'  => 'Equipe Teste',
            'usuario_email' => 'equipe@teste.com',
            'perfil'        => $perfil,
        ]);
    }

    private function totalItens(string $categoria): int
    {
        return count((new ConfiguracaoListaModel())->itens($categoria));
    }

    private function idDoItem(string $categoria, string $chave): int
    {
        $linha = (new ConfiguracaoListaModel())
            ->where('categoria', $categoria)
            ->where('chave', $chave)
            ->first();

        $this->assertNotNull($linha, "Item {$categoria}.{$chave} esperado no seed.");

        return (int) $linha['id'];
    }

    public function testVisitanteNaoAcessaListas(): void
    {
        $indice = $this->get('sistema/configuracoes/listas');
        $indice->assertRedirectTo('/login');

        $categoria = $this->get('sistema/configuracoes/listas/tipo_atendimento');
        $categoria->assertRedirectTo('/login');

        $criar = $this->post('sistema/configuracoes/listas/tipo_atendimento/itens', [
            'chave'  => 'videochamada',
            'rotulo' => 'Videochamada',
        ]);
        $criar->assertRedirectTo('/login');
    }

    public function testFuncionarioNaoAcessaListas(): void
    {
        $this->logarComo('funcionario');

        $indice = $this->get('sistema/configuracoes/listas');
        $indice->assertRedirectTo('/dashboard');

        $categoria = $this->get('sistema/configuracoes/listas/tipo_atendimento');
        $categoria->assertRedirectTo('/dashboard');

        $criar = $this->post('sistema/configuracoes/listas/tipo_atendimento/itens', [
            'chave'  => 'videochamada',
            'rotulo' => 'Videochamada',
        ]);
        $criar->assertRedirectTo('/dashboard');

        $this->assertSame(7, $this->totalItens('tipo_atendimento'));
    }

    public function testAdminAcessaIndiceDeListas(): void
    {
        $this->logarComo('admin');

        $result = $this->get('sistema/configuracoes/listas');

        $result->assertStatus(200);
        $texto = html_entity_decode($result->getBody(), ENT_QUOTES, 'UTF-8');
        $this->assertStringContainsString('Listas configuráveis', $texto);
        $this->assertStringContainsString('Cadastros', $texto);
        $this->assertStringContainsString('Status e situações', $texto);
        $this->assertStringContainsString('Documentos', $texto);
        $this->assertStringContainsString('Tipos de atendimento', $texto);
        $this->assertStringContainsString('Situações de medida protetiva', $texto);
        $this->assertStringContainsString('4 itens', $texto);
    }

    public function testAdminAcessaCategoriaDeLista(): void
    {
        $this->logarComo('admin');

        $result = $this->get('sistema/configuracoes/listas/tipo_atendimento');

        $result->assertStatus(200);
        $texto = html_entity_decode($result->getBody(), ENT_QUOTES, 'UTF-8');
        $this->assertStringContainsString('Cadastrar novo item', $texto);
        $this->assertStringContainsString('Presencial', $texto);
        $this->assertStringContainsString('WhatsApp', $texto);
        $this->assertStringContainsString('presencial', $texto);
        $this->assertSame(7, $this->totalItens('tipo_atendimento'));
    }

    public function testAdminCadastraNovoItemNaCategoria(): void
    {
        $this->logarComo('admin');

        $result = $this->post('sistema/configuracoes/listas/tipo_atendimento/itens', [
            'chave'  => 'videochamada',
            'rotulo' => 'Videochamada',
        ]);

        $result->assertRedirectTo('/sistema/configuracoes/listas/tipo_atendimento');

        $this->assertSame(8, $this->totalItens('tipo_atendimento'));
        $opcoes = ConfiguracaoListaModel::opcoesCategoria('tipo_atendimento');
        $this->assertArrayHasKey('videochamada', $opcoes);
        $this->assertSame('Videochamada', $opcoes['videochamada']);
    }

    public function testChaveDuplicadaEhRejeitada(): void
    {
        $this->logarComo('admin');

        $result = $this->post('sistema/configuracoes/listas/tipo_atendimento/itens', [
            'chave'  => 'presencial',
            'rotulo' => 'Presencial (duplicado)',
        ]);

        $result->assertRedirectTo('/sistema/configuracoes/listas/tipo_atendimento');

        $this->assertSame(7, $this->totalItens('tipo_atendimento'));
        $this->assertSame('Presencial', ConfiguracaoListaModel::rotuloCategoria('tipo_atendimento', 'presencial'));
    }

    public function testChaveInvalidaEhRejeitada(): void
    {
        $this->logarComo('admin');

        $result = $this->post('sistema/configuracoes/listas/tipo_atendimento/itens', [
            'chave'  => 'Nova Chave',
            'rotulo' => 'Nova',
        ]);

        $result->assertRedirectTo('/sistema/configuracoes/listas/tipo_atendimento');

        $this->assertSame(7, $this->totalItens('tipo_atendimento'));
    }

    public function testRenomearItemAtualizaRotulo(): void
    {
        $this->logarComo('admin');
        $id = $this->idDoItem('tipo_atendimento', 'presencial');

        $result = $this->post('sistema/configuracoes/listas/tipo_atendimento/itens/' . $id . '/renomear', [
            'rotulo' => 'Presencial (atualizado)',
        ]);

        $result->assertRedirectTo('/sistema/configuracoes/listas/tipo_atendimento');

        $this->assertSame('Presencial (atualizado)', ConfiguracaoListaModel::rotuloCategoria('tipo_atendimento', 'presencial'));
    }

    public function testRenomearItemDeOutraCategoriaEhRejeitado(): void
    {
        $this->logarComo('admin');
        $id = $this->idDoItem('tipo_atendimento', 'presencial');

        $result = $this->post('sistema/configuracoes/listas/prioridade/itens/' . $id . '/renomear', [
            'rotulo' => 'Hack',
        ]);

        $result->assertRedirectTo('/sistema/configuracoes/listas/prioridade');

        $this->assertSame('Presencial', ConfiguracaoListaModel::rotuloCategoria('tipo_atendimento', 'presencial'));
        $this->assertSame('Emergência', ConfiguracaoListaModel::rotuloCategoria('prioridade', 'emergencia'));
    }

    public function testInativarItemRemoveDeFormulariosMasMantemNoHistorico(): void
    {
        $this->logarComo('admin');
        $id = $this->idDoItem('status_atendimento', 'encerrado');

        $this->post('sistema/configuracoes/listas/status_atendimento/itens/' . $id . '/situacao')
            ->assertRedirectTo('/sistema/configuracoes/listas/status_atendimento');

        $formularios = ConfiguracaoListaModel::opcoesCategoria('status_atendimento');
        $this->assertArrayNotHasKey('encerrado', $formularios);

        $todas = ConfiguracaoListaModel::todasCategoria('status_atendimento');
        $this->assertArrayHasKey('encerrado', $todas);
        $this->assertSame('Encerrado', $todas['encerrado']);
        $this->assertSame('Encerrado', ConfiguracaoListaModel::rotuloCategoria('status_atendimento', 'encerrado'));

        $this->post('sistema/configuracoes/listas/status_atendimento/itens/' . $id . '/situacao')
            ->assertRedirectTo('/sistema/configuracoes/listas/status_atendimento');

        $this->assertArrayHasKey('encerrado', ConfiguracaoListaModel::opcoesCategoria('status_atendimento'));
    }

    public function testItemInativoContinuaValidoNaValidacaoDosModels(): void
    {
        $this->logarComo('admin');
        $id = $this->idDoItem('status_atendimento', 'encerrado');

        $this->post('sistema/configuracoes/listas/status_atendimento/itens/' . $id . '/situacao')
            ->assertRedirectTo('/sistema/configuracoes/listas/status_atendimento');

        $regras = (new AtendimentoModel())->getValidationRules();
        $situacao = (string) ($regras['situacao'] ?? '');

        $this->assertStringContainsString('encerrado', $situacao);
        $this->assertStringContainsString('em_andamento', $situacao);
    }

    public function testNovoItemEhAceitoNaValidacaoInList(): void
    {
        $this->logarComo('admin');

        $result = $this->post('sistema/configuracoes/listas/status_atendimento/itens', [
            'chave'  => 'aguardando_documentacao',
            'rotulo' => 'Aguardando documentação',
        ]);

        $result->assertRedirectTo('/sistema/configuracoes/listas/status_atendimento');

        $regras = (new AtendimentoModel())->getValidationRules();
        $situacao = (string) ($regras['situacao'] ?? '');

        $this->assertStringContainsString('aguardando_documentacao', $situacao);
        $this->assertStringContainsString('encerrado', $situacao);
    }

    public function testCategoriaVaziaExibeMensagem(): void
    {
        $this->logarComo('admin');

        $result = $this->get('sistema/configuracoes/listas/tipo_violencia');

        $result->assertStatus(200);
        $texto = html_entity_decode($result->getBody(), ENT_QUOTES, 'UTF-8');
        $this->assertStringContainsString('Nenhum item cadastrado nesta categoria', $texto);
        $this->assertSame(0, $this->totalItens('tipo_violencia'));
    }

    public function testCategoriaInexistenteRedirecionaParaIndice(): void
    {
        $this->logarComo('admin');

        $result = $this->get('sistema/configuracoes/listas/categoria_fantasma');

        $result->assertRedirectTo('/sistema/configuracoes/listas');
    }
}