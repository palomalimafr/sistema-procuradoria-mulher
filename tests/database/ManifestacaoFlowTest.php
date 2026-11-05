<?php

use App\Models\DocumentoModel;
use App\Models\InstituicaoModel;
use App\Models\ManifestacaoHistoricoModel;
use App\Models\ManifestacaoModel;
use App\Models\MulherModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Database\Seeds\TestDataSeeder;

/**
 * Testes do módulo OUVIDORIA > Denúncias/Ouvidoria: protocolo único gerado
 * automaticamente, manifestação anônima ou com dados da pessoa, vínculo
 * opcional com mulher já cadastrada (sem duplicar cadastro), fluxo de situação
 * com transições validadas, encaminhamento para a Rede de Proteção
 * (instituições existentes e ativas — sem tabela paralela), encerramento de
 * manifestações e reuso do repositório de Documentos para anexos.
 *
 * @internal
 */
final class ManifestacaoFlowTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $seed      = TestDataSeeder::class;

    /** @var array<int, string> Caminhos temporários de arquivos de teste. */
    private array $tmpArquivos = [];

    /** @var array<int, string> Caminhos físicos criados no upload dir. */
    private array $arquivosCriados = [];

    protected function tearDown(): void
    {
        foreach ($this->arquivosCriados as $full) {
            if (is_file($full)) {
                @unlink($full);
            }
        }
        foreach ($this->tmpArquivos as $tmp) {
            if (is_file($tmp)) {
                @unlink($tmp);
            }
        }
        $_FILES = [];
        service('superglobals')->setFilesArray([]);

        parent::tearDown();
    }

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

    private function criarMulher(string $nome = 'Mulher Manifestação'): array
    {
        $defaults = [
            'nome_completo'   => $nome,
            'zona'            => 'urbana',
            'bairro'          => 'Centro',
            'municipio'       => 'Campos dos Goytacazes',
            'estado'          => 'RJ',
        ];
        $id = (new MulherModel())->insert($defaults);
        $this->assertIsInt($id);

        return ['id' => $id] + $defaults;
    }

    private function criarInstituicao(array $dados = []): array
    {
        $model = new InstituicaoModel();
        $id = $model->insert(array_merge([
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
        ], $dados));
        $this->assertIsInt($id);

        return $model->find($id);
    }

    private function criarManifestacao(array $dados = [], bool $logar = true): int
    {
        if ($logar) {
            $this->logarComo('admin');
        }

        $result = $this->post('manifestacoes', array_merge([
            'tipo'       => 'denuncia',
            'origem'     => 'whatsapp',
            'prioridade' => 'alta',
            'descricao'  => 'Relato de situação de violência em curso.',
            'anonimo'    => 0,
            'pessoa_nome' => 'Pessoa Manifestante',
        ], $dados));
        $result->assertStatus(302);
        $this->assertStringStartsWith(site_url('/manifestacoes/'), $result->getRedirectUrl());

        return (int) (new ManifestacaoModel())->orderBy('id', 'DESC')->first()['id'];
    }

    private function anexarArquivo(string $nome = 'anexo_manifestacao.pdf'): string
    {
        $conteudo = "%PDF-1.4\n"
            . "1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n"
            . "2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
            . "3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]>>endobj\n"
            . "trailer<</Root 1 0 R>>\n"
            . "%%EOF\n";

        $tmp = sys_get_temp_dir() . '/man_teste_' . bin2hex(random_bytes(6)) . '.pdf';
        file_put_contents($tmp, $conteudo);

        $_FILES['arquivo'] = [
            'name'      => $nome,
            'type'      => 'application/pdf',
            'tmp_name'  => $tmp,
            'error'     => 0,
            'size'      => strlen($conteudo),
            'full_path' => $tmp,
        ];
        service('superglobals')->setFilesArray($_FILES);

        $this->tmpArquivos[] = $tmp;

        return $tmp;
    }

    public function testVisitanteNaoAcessaManifestacoes(): void
    {
        $lista = $this->get('manifestacoes');
        $lista->assertRedirectTo('/login');

        $nova = $this->get('manifestacoes/nova');
        $nova->assertRedirectTo('/login');
    }

    public function testPerfilMulherNaoAcessaManifestacoes(): void
    {
        $this->logarComo('mulher');

        $result = $this->get('manifestacoes');
        $result->assertRedirectTo('/dashboard');
    }

    public function testFuncionarioAcessaManifestacoes(): void
    {
        $this->logarComo('funcionario');

        $result = $this->get('manifestacoes');

        $result->assertStatus(200);
        $body = html_entity_decode($result->getBody(), ENT_QUOTES, 'UTF-8');
        $this->assertStringContainsString('Nova manifestação', $body);
        $this->assertStringContainsString('Denúncias/Ouvidoria', $body);
    }

    public function testCriarManifestacaoGeraProtocoloEDadosIniciais(): void
    {
        $m = $this->criarMulher();
        $this->logarComo('admin');

        $result = $this->post('manifestacoes', [
            'tipo'         => 'denuncia',
            'origem'       => 'presencial',
            'prioridade'   => 'emergencia',
            'anonimo'      => 0,
            'pessoa_nome'  => 'Fulana de Souza',
            'pessoa_cpf'   => '11122233344',
            'mulher_id'    => $m['id'],
            'descricao'    => 'Companheiro ameaçou a vida da mulher na madrugada.',
            'local_relacionado' => 'Residência da vítima',
            'observacoes'  => 'Prioridade máxima: risco imediato.',
        ]);

        $result->assertStatus(302);
        $this->assertStringStartsWith(site_url('/manifestacoes/'), $result->getRedirectUrl());
        $result->assertSessionHas('sucesso');

        $manifestacao = (new ManifestacaoModel())->orderBy('id', 'DESC')->first();
        $this->assertNotNull($manifestacao);
        $this->assertMatchesRegularExpression('/^MAN-\d{2}-\d{6}$/', (string) $manifestacao['protocolo']);
        $this->assertSame('denuncia', $manifestacao['tipo']);
        $this->assertSame('presencial', $manifestacao['origem']);
        $this->assertSame('emergencia', $manifestacao['prioridade']);
        $this->assertSame('nova', $manifestacao['situacao']);
        $this->assertSame(0, (int) $manifestacao['anonimo']);
        $this->assertSame('Fulana de Souza', $manifestacao['pessoa_nome']);
        $this->assertSame((int) $m['id'], (int) $manifestacao['mulher_id']);
        $this->assertSame(1, (int) $manifestacao['responsavel_id']);
        $this->assertNull($manifestacao['instituicao_id']);
        $this->assertNull($manifestacao['data_encerramento']);
        $this->assertNotNull($manifestacao['data_hora']);
        $this->assertNotNull($manifestacao['created_at']);

        $historico = (new ManifestacaoHistoricoModel())
            ->where('manifestacao_id', (int) $manifestacao['id'])
            ->orderBy('id', 'ASC')
            ->first();
        $this->assertNotNull($historico);
        $this->assertSame('criacao', $historico['acao']);
        $this->assertSame('nova', $historico['status_novo']);
    }

    public function testCriarManifestacaoAnonimaZeraIdentificacao(): void
    {
        $this->logarComo('admin');

        $result = $this->post('manifestacoes', [
            'tipo'          => 'solicitacao',
            'origem'        => 'telefone',
            'prioridade'    => 'media',
            'anonimo'       => 1,
            'pessoa_nome'   => 'Deveria ser ignorado',
            'pessoa_email'  => 'ignorar@teste.com',
            'descricao'     => 'Pedido de informações sobre canais de denúncia.',
        ]);

        $result->assertStatus(302);

        $manifestacao = (new ManifestacaoModel())->orderBy('id', 'DESC')->first();
        $this->assertNotNull($manifestacao);
        $this->assertSame(1, (int) $manifestacao['anonimo']);
        $this->assertNull($manifestacao['pessoa_nome']);
        $this->assertNull($manifestacao['pessoa_email']);
        $this->assertNull($manifestacao['mulher_id']);
    }

    public function testCriarManifestacaoSemDescricaoEhRejeitado(): void
    {
        $this->logarComo('admin');

        $result = $this->post('manifestacoes', [
            'tipo'      => 'denuncia',
            'origem'    => 'presencial',
            'prioridade'=> 'media',
            'anonimo'   => 1,
        ]);

        $result->assertStatus(302);
        $result->assertSessionHas('erros');
        $this->assertSame(0, (new ManifestacaoModel())->countAllResults());
    }

    public function testListagemExibeManifestacao(): void
    {
        $id = $this->criarManifestacao(['descricao' => 'Caso exibido na listagem.']);

        $result = $this->get('manifestacoes');

        $result->assertStatus(200);
        $body = html_entity_decode($result->getBody(), ENT_QUOTES, 'UTF-8');
        $manifestacao = (new ManifestacaoModel())->find($id);
        $this->assertStringContainsString($manifestacao['protocolo'], $body);
        $this->assertStringContainsString('Denúncia', $body);
        $this->assertStringContainsString('Nova', $body);
    }

    public function testListagemExibeAnonimaSemNomePessoa(): void
    {
        $this->logarComo('admin');
        $this->post('manifestacoes', [
            'tipo'        => 'sugestao',
            'origem'      => 'email',
            'prioridade'  => 'baixa',
            'anonimo'     => 1,
            'descricao'   => 'Sugestão para melhorar o atendimento.',
        ]);

        $result = $this->get('manifestacoes');

        $result->assertStatus(200);
        $body = html_entity_decode($result->getBody(), ENT_QUOTES, 'UTF-8');
        $this->assertStringContainsString('Anônima', $body);
    }

    public function testDetalhesExibeDadosCompletos(): void
    {
        $m = $this->criarMulher('Mulher Vínculo Detalhes');
        $id = $this->criarManifestacao([
            'mulher_id'  => $m['id'],
            'descricao'  => 'Detalhes completos do relato.',
            'observacoes' => 'Observações internas da equipe.',
        ]);

        $result = $this->get('manifestacoes/' . $id);

        $result->assertStatus(200);
        $body = html_entity_decode($result->getBody(), ENT_QUOTES, 'UTF-8');
        $this->assertStringContainsString('Detalhes completos do relato.', $body);
        $this->assertStringContainsString('Mulher Vínculo Detalhes', $body);
        $this->assertStringContainsString('Manifestação registrada', $body);
    }

    public function testEditarAtualizaMesmoRegistro(): void
    {
        $id = $this->criarManifestacao(['descricao' => 'Texto original.']);
        $this->logarComo('admin');

        $result = $this->post('manifestacoes/atualizar/' . $id, [
            'tipo'         => 'reclamacao',
            'origem'       => 'site_sistema',
            'prioridade'   => 'baixa',
            'anonimo'      => 0,
            'pessoa_nome'  => 'Nome Atualizado',
            'descricao'    => 'Texto editado após análise.',
            'responsavel_id' => 1,
        ]);

        $result->assertStatus(302);
        $result->assertRedirectTo('/manifestacoes/' . $id);

        $manifestacao = (new ManifestacaoModel())->find($id);
        $this->assertSame('reclamacao', $manifestacao['tipo']);
        $this->assertSame('Texto editado após análise.', $manifestacao['descricao']);
        $this->assertSame('Nome Atualizado', $manifestacao['pessoa_nome']);

        $historico = (new ManifestacaoHistoricoModel())
            ->where('manifestacao_id', $id)
            ->orderBy('id', 'DESC')
            ->first();
        $this->assertNotNull($historico);
        $this->assertSame('edicao', $historico['acao']);
    }

    public function testMudarSituacaoFluxoInterno(): void
    {
        $id = $this->criarManifestacao();
        $this->logarComo('admin');

        $result = $this->post('manifestacoes/' . $id . '/status', ['status' => 'em_analise']);

        $result->assertStatus(302);
        $result->assertRedirectTo('/manifestacoes/' . $id);

        $manifestacao = (new ManifestacaoModel())->find($id);
        $this->assertSame('em_analise', $manifestacao['situacao']);
        $this->assertNull($manifestacao['data_encerramento']);

        $historico = (new ManifestacaoHistoricoModel())
            ->where('manifestacao_id', $id)
            ->where('acao', 'situacao')
            ->orderBy('id', 'DESC')
            ->first();
        $this->assertNotNull($historico);
        $this->assertSame('nova', $historico['status_anterior']);
        $this->assertSame('em_analise', $historico['status_novo']);
    }

    public function testSituacaoEncerradaGravaDataEncerramento(): void
    {
        $id = $this->criarManifestacao();
        $this->logarComo('admin');

        $this->post('manifestacoes/' . $id . '/status', ['status' => 'em_analise']);
        $this->post('manifestacoes/' . $id . '/status', ['status' => 'em_atendimento']);
        $result = $this->post('manifestacoes/' . $id . '/status', ['status' => 'resolvida']);

        $result->assertStatus(302);
        $result->assertRedirectTo('/manifestacoes/' . $id);

        $manifestacao = (new ManifestacaoModel())->find($id);
        $this->assertSame('resolvida', $manifestacao['situacao']);
        $this->assertNotNull($manifestacao['data_encerramento']);
    }

    public function testEncaminharExigeInstituicaoAtiva(): void
    {
        $id = $this->criarManifestacao();
        $this->logarComo('admin');

        // Sem instituição: nega.
        $sem = $this->post('manifestacoes/' . $id . '/status', ['status' => 'encaminhada']);
        $sem->assertStatus(302);
        $sem->assertSessionHas('erro');

        // Instituição inativa: nega.
        $inativa = $this->criarInstituicao(['nome' => 'Abrigo Encerrado', 'ativo' => 0]);
        $negada = $this->post('manifestacoes/' . $id . '/status', [
            'status'         => 'encaminhada',
            'instituicao_id' => (int) $inativa['id'],
        ]);
        $negada->assertStatus(302);
        $negada->assertSessionHas('erro');

        // Instituição ativa: aceita e registra no histórico.
        $ativa = $this->criarInstituicao(['nome' => 'CREAS Norte Real']);
        $ok = $this->post('manifestacoes/' . $id . '/status', [
            'status'         => 'encaminhada',
            'instituicao_id' => (int) $ativa['id'],
        ]);
        $ok->assertStatus(302);
        $ok->assertRedirectTo('/manifestacoes/' . $id);

        $manifestacao = (new ManifestacaoModel())->find($id);
        $this->assertSame('encaminhada', $manifestacao['situacao']);
        $this->assertSame((int) $ativa['id'], (int) $manifestacao['instituicao_id']);

        $historico = (new ManifestacaoHistoricoModel())
            ->where('manifestacao_id', $id)
            ->where('acao', 'encaminhamento')
            ->orderBy('id', 'DESC')
            ->first();
        $this->assertNotNull($historico);
        $this->assertStringContainsString('CREAS Norte Real', (string) $historico['observacao']);
        $this->assertSame(1, (int) $historico['usuario_id']);
    }

    public function testRejeitaTransicaoInvalida(): void
    {
        $id = $this->criarManifestacao();
        $this->logarComo('admin');

        $this->post('manifestacoes/' . $id . '/status', ['status' => 'resolvida']);

        $result = $this->post('manifestacoes/' . $id . '/status', ['status' => 'nova']);
        $result->assertStatus(302);
        $result->assertSessionHas('erro');

        $this->assertSame('resolvida', (new ManifestacaoModel())->find($id)['situacao']);
    }

    public function testDetalhesEncaminhadaExibeInstituicao(): void
    {
        $id = $this->criarManifestacao();
        $inst = $this->criarInstituicao(['nome' => 'DEAM Campos Municipal']);
        $this->logarComo('admin');

        $this->post('manifestacoes/' . $id . '/status', [
            'status'         => 'encaminhada',
            'instituicao_id' => (int) $inst['id'],
        ]);

        $result = $this->get('manifestacoes/' . $id);

        $result->assertStatus(200);
        $body = html_entity_decode($result->getBody(), ENT_QUOTES, 'UTF-8');
        $this->assertStringContainsString('DEAM Campos Municipal', $body);
        $this->assertStringContainsString('Encaminhada para a Rede de Proteção', $body);
    }

    public function testBuscarManifestacoesPorProtocolo(): void
    {
        $id = $this->criarManifestacao(['descricao' => 'Caso da busca.']);
        $protocolo = (string) (new ManifestacaoModel())->find($id)['protocolo'];
        $this->logarComo('admin');

        $result = $this->get('manifestacoes/buscar?term=' . urlencode($protocolo));

        $result->assertStatus(200);
        $json = json_decode($result->getJSON(), true);
        $protocolos = array_column($json['manifestacoes'] ?? [], 'protocolo');
        $this->assertContains($protocolo, $protocolos);
    }

    public function testBuscarManifestacoesParaVinculoDeDocumento(): void
    {
        $id = $this->criarManifestacao(['descricao' => 'Caso de anexo.']);
        $protocolo = (string) (new ManifestacaoModel())->find($id)['protocolo'];
        $this->logarComo('admin');

        $result = $this->get('documentos/buscar-manifestacoes?term=' . urlencode($protocolo));

        $result->assertStatus(200);
        $json = json_decode($result->getJSON(), true);
        $protocolos = array_column($json['manifestacoes'] ?? [], 'protocolo');
        $this->assertContains($protocolo, $protocolos);
        $this->assertSame((string) $protocolo, $json['manifestacoes'][0]['protocolo']);
    }

    public function testDocumentoVinculadoAManifestacao(): void
    {
        $m = $this->criarMulher('Mulher Anexo');
        $id = $this->criarManifestacao(['mulher_id' => $m['id'], 'descricao' => 'Caso com anexo.']);
        $this->logarComo('admin');
        $this->anexarArquivo();

        $result = $this->post('documentos', [
            'mulher_id'       => $m['id'],
            'manifestacao_id' => $id,
            'tipo'            => 'outros',
            'sigilo'          => 'sigiloso',
        ]);

        $result->assertStatus(302);
        $this->assertStringContainsString('/documentos/', $result->getRedirectUrl());

        $documento = (new DocumentoModel())->orderBy('id', 'DESC')->first();
        $this->assertNotNull($documento);
        $this->assertSame($id, (int) $documento['manifestacao_id']);
        $this->assertSame((int) $m['id'], (int) $documento['mulher_id']);
        $this->arquivosCriados[] = WRITEPATH . 'uploads/' . $documento['arquivo'];
    }

    public function testDocumentoRejeitaManifestacaoDeOutraMulher(): void
    {
        $m1 = $this->criarMulher('Mulher Manifest A');
        $m2 = $this->criarMulher('Mulher Manifest B');
        $id = $this->criarManifestacao(['mulher_id' => $m1['id'], 'descricao' => 'Anexo de outra mulher.']);
        $this->logarComo('admin');
        $this->anexarArquivo();

        $result = $this->post('documentos', [
            'mulher_id'       => $m2['id'],
            'manifestacao_id' => $id,
            'tipo'            => 'outros',
            'sigilo'          => 'restrito',
        ]);

        $result->assertStatus(302);
        $result->assertSessionHas('erros');
        $this->assertSame(0, (new DocumentoModel())->countAllResults());
    }

    public function testDetalhesManifestacaoListaDocumentosAnexados(): void
    {
        $m = $this->criarMulher('Mulher Detalhe Anexo');
        $id = $this->criarManifestacao(['mulher_id' => $m['id'], 'descricao' => 'Caso da ficha.']);
        $this->logarComo('admin');
        $this->anexarArquivo('boletim.pdf');

        $this->post('documentos', [
            'mulher_id'       => $m['id'],
            'manifestacao_id' => $id,
            'tipo'            => 'boletim_ocorrencia',
            'sigilo'          => 'restrito',
        ]);

        $result = $this->get('manifestacoes/' . $id);

        $result->assertStatus(200);
        $body = html_entity_decode($result->getBody(), ENT_QUOTES, 'UTF-8');
        $this->assertStringContainsString('boletim.pdf', $body);
        $this->assertStringContainsString('Documentos anexados', $body);
    }

    public function testNovoManifestacaoComDescricaoVaziaNaoCriaRegistro(): void
    {
        $this->logarComo('admin');

        $result = $this->post('manifestacoes', [
            'tipo'      => 'outro',
            'origem'    => 'outro',
            'prioridade'=> 'baixa',
            'anonimo'   => 1,
            'pessoa_nome' => '',
        ]);

        $result->assertStatus(302);
        $result->assertSessionHas('erros');
        $this->assertSame(0, (new ManifestacaoModel())->countAllResults());
    }
}