<?php

use App\Models\EncaminhamentoModel;
use App\Models\InstituicaoModel;
use App\Models\MulherModel;
use App\Models\ServicoInstituicaoModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Database\Seeds\TestDataSeeder;

/**
 * Testes do módulo REDE DE PROTEÇÃO: cadastro de instituições (sem exclusão
 * física — ativar/inativar), lista dinâmica de serviços e integração mínima
 * com os Encaminhamentos (busca de instituições ativas; dados legados de
 * orgao/servico preservados).
 *
 * @internal
 */
final class RedeProtecaoFlowTest extends CIUnitTestCase
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

    private function criarMulher(string $nome = 'Mulher Rede'): int
    {
        $id = (new MulherModel())->insert([
            'nome_completo'   => $nome,
            'zona'            => 'urbana',
            'bairro'          => 'Centro',
            'municipio'       => 'Campos dos Goytacazes',
            'estado'          => 'RJ',
        ]);
        $this->assertIsInt($id);

        return $id;
    }

    private function criarInstituicao(array $dados = [], array $servicos = []): array
    {
        $defaults = [
            'nome'                => 'CREAS Municipal Centro',
            'tipo'                => 'assistencia_social',
            'endereco'            => 'Rua da Proteção, 100',
            'municipio'           => 'Campos dos Goytacazes',
            'telefone'            => '(22) 9999-0000',
            'whatsapp'            => '(22) 99999-0000',
            'email'               => 'creas.centro@exemplo.gov.br',
            'responsavel'         => 'Maria Equipe',
            'horario_atendimento' => 'Segunda a sexta, 8h às 17h',
            'observacoes'         => 'Atende demandas de violência doméstica.',
            'ativo'               => 1,
        ];

        $model = new InstituicaoModel();
        $fusion = array_merge($defaults, $dados);
        $id = $model->insert($fusion);
        $this->assertIsInt($id);

        if (! empty($servicos)) {
            $model->substituirServicos($id, $servicos);
        }

        return $model->find($id);
    }

    public function testVisitanteNaoAcessaRedeDeProtecao(): void
    {
        $lista = $this->get('rede/instituicoes');
        $lista->assertRedirectTo('/login');

        $nova = $this->get('rede/instituicoes/nova');
        $nova->assertRedirectTo('/login');
    }

    public function testPerfilMulherNaoAcessaRedeDeProtecao(): void
    {
        $this->logarComo('mulher');

        $result = $this->get('rede/instituicoes');
        $result->assertRedirectTo('/dashboard');
    }

    public function testFuncionarioAcessaListagemDeInstituicoes(): void
    {
        $this->criarInstituicao(['nome' => 'Hospital das Clínicas']);
        $this->logarComo('funcionario');

        $result = $this->get('rede/instituicoes');

        $result->assertStatus(200);
        $body = html_entity_decode($result->getBody(), ENT_QUOTES, 'UTF-8');
        $this->assertStringContainsString('Hospital das Clínicas', $body);
        $this->assertStringContainsString('Nova instituição', $body);
    }

    public function testCriarInstituicaoSemNomeEhRejeitado(): void
    {
        $this->logarComo('admin');

        $result = $this->post('rede/instituicoes', [
            'tipo' => 'saude',
        ]);

        $result->assertStatus(302);
        $result->assertSessionHas('erros');
        $this->assertSame(0, (new InstituicaoModel())->countAllResults());
    }

    public function testCriarInstituicaoComServicos(): void
    {
        $this->logarComo('admin');

        $result = $this->post('rede/instituicoes', [
            'nome'                => 'CRAS Jardim Carioca',
            'tipo'                => 'assistencia_social',
            'endereco'            => 'Rua X, 10',
            'municipio'           => 'Campos dos Goytacazes',
            'telefone'            => '(22) 3333-4444',
            'whatsapp'            => '(22) 99999-1234',
            'email'               => 'cras.jardim@exemplo.gov.br',
            'responsavel'         => 'Ana Coordenadora',
            'horario_atendimento' => 'Segunda a sexta, 9h às 18h',
            'observacoes'         => 'CRAS da região central.',
            'ativo'               => 1,
            'servicos'            => ['Atendimento social', 'Cadastro único', 'Orientações',
            ],
        ]);

        $result->assertStatus(302);
        $this->assertStringStartsWith(
            site_url('/rede/instituicoes/'),
            $result->getRedirectUrl()
        );

        $instituicao = (new InstituicaoModel())->orderBy('id', 'DESC')->first();
        $this->assertNotNull($instituicao);
        $this->assertSame('CRAS Jardim Carioca', $instituicao['nome']);
        $this->assertSame('assistencia_social', $instituicao['tipo']);
        $this->assertSame('Ana Coordenadora', $instituicao['responsavel']);
        $this->assertSame(1, (int) $instituicao['ativo']);

        $servicos = (new ServicoInstituicaoModel())
            ->where('instituicao_id', (int) $instituicao['id'])
            ->orderBy('nome', 'ASC')
            ->findAll();
        $this->assertCount(3, $servicos);
        $nomes = array_column($servicos, 'nome');
        $this->assertContains('Atendimento social', $nomes);
        $this->assertContains('Cadastro único', $nomes);
        $this->assertContains('Orientações', $nomes);
    }

    public function testListagemExibeInstituicaoEServicos(): void
    {
        $this->criarInstituicao(['nome' => 'DEAM Campos'], ['Atendimento à mulher', 'Registro de boletim']);
        $this->logarComo('admin');

        $result = $this->get('rede/instituicoes');

        $result->assertStatus(200);
        $body = html_entity_decode($result->getBody(), ENT_QUOTES, 'UTF-8');
        $this->assertStringContainsString('DEAM Campos', $body);
        $this->assertStringContainsString('Atendimento à mulher', $body);
        $this->assertStringContainsString('Ativa', $body);
    }

    public function testDetalhesExibeDadosInstituicaoEServicos(): void
    {
        $inst = $this->criarInstituicao(['nome' => 'Casa Abrigo Vida'], ['Abrigamento', 'Acompanhamento social']);
        $this->logarComo('admin');

        $result = $this->get('rede/instituicoes/' . (int) $inst['id']);

        $result->assertStatus(200);
        $body = html_entity_decode($result->getBody(), ENT_QUOTES, 'UTF-8');
        $this->assertStringContainsString('Casa Abrigo Vida', $body);
        $this->assertStringContainsString('Assistência Social', $body);
        $this->assertStringContainsString('Rua da Proteção, 100', $body);
        $this->assertStringContainsString('Maria Equipe', $body);
        $this->assertStringContainsString('Abrigamento', $body);
        $this->assertStringContainsString('Atende demandas de violência doméstica.', $body);
    }

    public function testEditarAtualizaESubstituiServicos(): void
    {
        $inst = $this->criarInstituicao(['nome' => 'UBS Centro'], ['Atendimento médico']);
        $this->logarComo('admin');

        $pagina = $this->get('rede/instituicoes/editar/' . (int) $inst['id']);
        $pagina->assertStatus(200);
        $this->assertStringContainsString(
            'Editar Instituição',
            html_entity_decode($pagina->getBody(), ENT_QUOTES, 'UTF-8')
        );

        $result = $this->post('rede/instituicoes/atualizar/' . (int) $inst['id'], [
            'nome'      => 'UBS Jardim Oeste',
            'tipo'      => 'saude',
            'municipio' => 'Campos dos Goytacazes',
            'ativo'     => 1,
            'servicos'  => ['Atendimento médico', 'Vacinação'],
        ]);

        $result->assertRedirectTo('/rede/instituicoes/' . (int) $inst['id']);

        $atual = (new InstituicaoModel())->find((int) $inst['id']);
        $this->assertSame('UBS Jardim Oeste', $atual['nome']);
        $this->assertSame('saude', $atual['tipo']);

        $nomes = (new InstituicaoModel())->servicos((int) $inst['id']);
        $this->assertContains('Atendimento médico', $nomes);
        $this->assertContains('Vacinação', $nomes);
        $this->assertNotContains('Atendimento social', $nomes);
    }

    public function testDesativarEReativarInstituicao(): void
    {
        $inst = $this->criarInstituicao(['nome' => 'DP Mulher Teste']);
        $this->logarComo('admin');

        $result = $this->post('rede/instituicoes/' . (int) $inst['id'] . '/situacao');
        $result->assertStatus(302);
        $result->assertSessionHas('sucesso');
        $this->assertSame(0, (int) (new InstituicaoModel())->find((int) $inst['id'])['ativo']);

        $result = $this->post('rede/instituicoes/' . (int) $inst['id'] . '/situacao');
        $result->assertStatus(302);
        $result->assertSessionHas('sucesso');
        $this->assertSame(1, (int) (new InstituicaoModel())->find((int) $inst['id'])['ativo']);
    }

    public function testBuscaRetornaSomenteInstituicoesAtivas(): void
    {
        $this->criarInstituicao(['nome' => 'CAPS Vida Nova']);
        $this->criarInstituicao(['nome' => 'CAPS Desativado Antigo', 'ativo' => 0]);
        $this->logarComo('admin');

        $result = $this->get('rede/buscar-instituicoes?term=CAPS');

        $result->assertStatus(200);
        $json = json_decode($result->getJSON(), true);
        $nomes = array_column($json['instituicoes'], 'nome');
        $this->assertContains('CAPS Vida Nova', $nomes);
        $this->assertNotContains('CAPS Desativado Antigo', $nomes);
    }

    public function testNovoEncaminhamentoUsaInstituicaoAtiva(): void
    {
        $mulherId = $this->criarMulher();
        $inst = $this->criarInstituicao(['nome' => 'CREAS Norte Real'], ['Atendimento social']);
        $this->logarComo('admin');

        $result = $this->post('encaminhamentos', [
            'mulher_id'       => $mulherId,
            'instituicao_id'  => (int) $inst['id'],
            'servico'         => 'juridico',
            'motivo'          => 'Pedido de acompanhamento.',
            'prioridade'      => 'alta',
            'data_hora'       => '2026-09-12T10:00',
        ]);

        $result->assertStatus(302);
        $this->assertStringStartsWith(site_url('/encaminhamentos/'), $result->getRedirectUrl());

        $enc = (new EncaminhamentoModel())->orderBy('id', 'DESC')->first();
        $this->assertNotNull($enc);
        $this->assertSame((int) $inst['id'], (int) $enc['instituicao_id']);
        $this->assertSame('CREAS Norte Real', $enc['orgao']);
        $this->assertSame('juridico', $enc['servico']);
    }

    public function testNovoEncaminhamentoRejeitaInstituicaoInativa(): void
    {
        $mulherId = $this->criarMulher();
        $inst = $this->criarInstituicao(['nome' => 'Abrigo Encerrado', 'ativo' => 0]);
        $this->logarComo('admin');

        $result = $this->post('encaminhamentos', [
            'mulher_id'      => $mulherId,
            'instituicao_id' => (int) $inst['id'],
            'servico'        => 'abrigamento',
            'motivo'         => 'Teste de rejeição.',
            'prioridade'     => 'media',
            'data_hora'      => '2026-09-12T10:00',
        ]);

        $result->assertStatus(302);
        $result->assertSessionHas('erros');
        $this->assertSame(0, (new EncaminhamentoModel())->countAllResults());
    }

    public function testNovoEncaminhamentoSemInstituicaoEhRejeitado(): void
    {
        $mulherId = $this->criarMulher();
        $this->logarComo('admin');

        $result = $this->post('encaminhamentos', [
            'mulher_id'   => $mulherId,
            'servico'     => 'juridico',
            'motivo'      => 'Sem instituição.',
            'prioridade'  => 'media',
            'data_hora'   => '2026-09-12T10:00',
        ]);

        $result->assertStatus(302);
        $result->assertSessionHas('erros');
        $this->assertSame(0, (new EncaminhamentoModel())->countAllResults());
    }

    public function testDetalhesEncaminhamentoExibeInstituicaoVinculada(): void
    {
        $mulherId = $this->criarMulher('Mulher Enc Rede');
        $inst = $this->criarInstituicao(['nome' => 'MP Campos Unitária'], ['Orientação jurídica']);
        $this->logarComo('admin');

        $id = (new EncaminhamentoModel())->insert([
            'mulher_id'       => $mulherId,
            'instituicao_id'  => (int) $inst['id'],
            'orgao'           => (string) $inst['nome'],
            'servico'         => 'juridico',
            'data_hora'       => date('Y-m-d H:i:s'),
            'status'          => 'pendente',
            'profissional_id' => 1,
            'prioridade'      => 'media',
            'motivo'          => 'Apoio jurídico.',
        ]);
        $this->assertIsInt($id);

        $result = $this->get('encaminhamentos/' . $id);

        $result->assertStatus(200);
        $body = html_entity_decode($result->getBody(), ENT_QUOTES, 'UTF-8');
        $this->assertStringContainsString('MP Campos Unitária', $body);
        $this->assertStringContainsString(base_url('rede/instituicoes/' . (int) $inst['id']), $result->getBody());
    }

    public function testEncaminhamentoLegadoContinuaExibindoOrgao(): void
    {
        $mulherId = $this->criarMulher('Mulher Legado Rede');
        $id = (new EncaminhamentoModel())->insert([
            'mulher_id'       => $mulherId,
            'orgao'           => 'cras',
            'servico'         => 'social',
            'data_hora'       => date('Y-m-d H:i:s'),
            'status'          => 'pendente',
            'profissional_id' => 1,
            'prioridade'      => 'media',
            'motivo'          => 'Registro legado.',
        ]);
        $this->assertIsInt($id);
        $this->logarComo('admin');

        $result = $this->get('encaminhamentos/pendentes');

        $result->assertStatus(200);
        $this->assertStringContainsString('>CRAS<', $result->getBody());
    }
}