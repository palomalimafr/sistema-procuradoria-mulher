<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', static function () {
    return redirect()->to('/dashboard');
});

// Autenticação
$routes->get('login', 'Auth::login');
$routes->post('login/autenticar', 'Auth::autenticar');
$routes->get('logout', 'Auth::logout');

// Cadastro público da mulher
$routes->get('cadastro', 'MulherController::cadastro');
$routes->post('cadastro/registrar', 'MulherController::registrar');

// Primeiro acesso da mulher cadastrada pela equipe (convite por e-mail).
// Público de propósito: a mulher chega pelo link e ainda não tem senha nem
// sessão. A segurança vem do token aleatório de uso único, não do filtro.
$routes->get('primeiro-acesso/(:segment)', 'PrimeiroAcesso::index/$1');
$routes->post('primeiro-acesso/(:segment)', 'PrimeiroAcesso::criar/$1');

// Rotas protegidas por autenticação
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    $routes->get('dashboard', 'Dashboard::index');

    // Exemplo de CRUD completo: Crianças
    $routes->get('criancas', 'Criancas::index');
    $routes->get('criancas/novo', 'Criancas::novo');
    $routes->post('criancas', 'Criancas::criar');
    $routes->get('criancas/(:num)', 'Criancas::detalhes/$1');
    $routes->get('criancas/editar/(:num)', 'Criancas::editar/$1');
    $routes->post('criancas/atualizar/(:num)', 'Criancas::atualizar/$1');
    $routes->get('criancas/excluir/(:num)', 'Criancas::excluir/$1');
    $routes->post('criancas/(:num)/responsaveis', 'Criancas::adicionarResponsavel/$1');
    $routes->get('responsaveis/excluir/(:num)', 'Criancas::excluirResponsavel/$1');

    // Usuários (apenas admin)
    $routes->get('usuarios', 'Usuarios::index', ['filter' => 'admin']);
    $routes->get('usuarios/novo', 'Usuarios::novo', ['filter' => 'admin']);
    $routes->post('usuarios', 'Usuarios::criar', ['filter' => 'admin']);
    $routes->get('usuarios/editar/(:num)', 'Usuarios::editar/$1', ['filter' => 'admin']);
    $routes->post('usuarios/atualizar/(:num)', 'Usuarios::atualizar/$1', ['filter' => 'admin']);
    $routes->get('usuarios/excluir/(:num)', 'Usuarios::excluir/$1', ['filter' => 'admin']);

    // Configurações do sistema (SISTEMA > Configurações — apenas admin)
    $routes->get('sistema/configuracoes', 'Configuracoes::index', ['filter' => 'admin']);
    $routes->post('sistema/configuracoes', 'Configuracoes::salvar', ['filter' => 'admin']);
    $routes->post('sistema/configuracoes/remover-logo', 'Configuracoes::removerLogo', ['filter' => 'admin']);
    // Listas configuráveis (SISTEMA > Configurações > Listas — apenas admin)
    $routes->get('sistema/configuracoes/listas', 'ConfiguracoesListas::index', ['filter' => 'admin']);
    $routes->get('sistema/configuracoes/listas/(:segment)', 'ConfiguracoesListas::categoria/$1', ['filter' => 'admin']);
    $routes->post('sistema/configuracoes/listas/(:segment)/itens', 'ConfiguracoesListas::criarItem/$1', ['filter' => 'admin']);
    $routes->post('sistema/configuracoes/listas/(:segment)/itens/(:num)/renomear', 'ConfiguracoesListas::renomearItem/$1/$2', ['filter' => 'admin']);
    $routes->post('sistema/configuracoes/listas/(:segment)/itens/(:num)/situacao', 'ConfiguracoesListas::alternarAtivo/$1/$2', ['filter' => 'admin']);
    // Auditoria (SISTEMA > Auditoria — apenas admin)
    $routes->get('sistema/auditoria', 'Auditoria::index', ['filter' => 'admin']);
    $routes->get('sistema/auditoria/(:num)', 'Auditoria::detalhes/$1', ['filter' => 'admin']);

    // Logo servido para o layout (qualquer usuário autenticado)
    $routes->get('sistema/logo', 'Configuracoes::logo');

    // Área da mulher
    $routes->get('mulher/dashboard', 'MulherController::dashboard', ['filter' => 'mulher']);
    $routes->get('mulher/perfil', 'MulherController::perfil', ['filter' => 'mulher']);
    $routes->post('mulher/perfil/atualizar', 'MulherController::atualizarPerfil', ['filter' => 'mulher']);

    // Pedido SOS da mulher
    $routes->post('pedidos-sos/criar', 'PedidosSos::criar', ['filter' => 'mulher']);

    // Pedidos SOS (apenas admin)
    $routes->get('pedidos-sos', 'PedidosSos::index', ['filter' => 'admin']);
    $routes->get('pedidos-sos/novos', 'PedidosSos::novos', ['filter' => 'admin']);
    $routes->get('pedidos-sos/(:num)', 'PedidosSos::detalhes/$1', ['filter' => 'admin']);
    $routes->post('pedidos-sos/(:num)/direcionar', 'PedidosSos::direcionar/$1', ['filter' => 'admin']);

    // Atendimentos (equipe: admin e funcionário)
    $routes->get('atendimentos/novo', 'Atendimentos::novo');
    $routes->post('atendimentos', 'Atendimentos::criar');
    $routes->get('atendimentos/buscar-mulheres', 'Atendimentos::buscarMulheres');
    $routes->get('atendimentos/buscar', 'Atendimentos::buscar');
    $routes->get('atendimentos/fila', 'Atendimentos::fila');
    $routes->get('atendimentos/em-andamento', 'Atendimentos::emAndamento');
    $routes->get('atendimentos/encerrados', 'Atendimentos::encerrados');
    // Casos: entidade central de acompanhamento, independente do atendimento.
    $routes->get('casos/ativos', 'Casos::ativos');
    $routes->get('casos/urgentes', 'Casos::urgentes');
    $routes->get('casos/atrasados', 'Casos::atrasados');
    $routes->get('casos/encerrados', 'Casos::encerrados');
    $routes->get('casos/novo', 'Casos::novo');
$routes->get('casos/novo/atendimento/(:num)', 'Casos::novo/$1');
    $routes->post('casos', 'Casos::criar');
    $routes->post('casos/(:num)/status', 'Casos::status/$1');
    $routes->post('casos/(:num)/encerrar', 'Casos::encerrar/$1');
    $routes->post('casos/(:num)/associar', 'Casos::associar/$1');
    $routes->get('casos/(:num)', 'Casos::detalhes/$1');
    $routes->get('atendimentos/(:num)', 'Atendimentos::detalhes/$1');
    $routes->post('atendimentos/(:num)/iniciar', 'Atendimentos::iniciar/$1');
    $routes->post('atendimentos/(:num)/encerrar', 'Atendimentos::encerrar/$1');

    // Acolhimento (equipe: admin e funcionário)
    $routes->get('acolhimentos', 'Acolhimentos::consulta');
    $routes->get('acolhimentos/novo', 'Acolhimentos::novo');
    $routes->post('acolhimentos', 'Acolhimentos::criar');
    $routes->get('acolhimentos/buscar-atendimentos', 'Acolhimentos::buscarAtendimentos');
    $routes->get('acolhimentos/editar/(:num)', 'Acolhimentos::editar/$1');
    $routes->post('acolhimentos/atualizar/(:num)', 'Acolhimentos::atualizar/$1');
    $routes->post('acolhimentos/(:num)/concluir', 'Acolhimentos::concluir/$1');
    $routes->get('acolhimentos/(:num)', 'Acolhimentos::detalhes/$1');

    // Avaliação de risco (vinculada ao acolhimento)
    $routes->get('avaliacoes', 'Avaliacoes::consulta');
    $routes->get('avaliacoes/novo/(:num)', 'Avaliacoes::novo/$1');
    $routes->post('avaliacoes', 'Avaliacoes::salvar');
    $routes->get('avaliacoes/(:num)', 'Avaliacoes::detalhes/$1');

    // Encaminhamentos (equipe: admin e funcionário)
    $routes->get('encaminhamentos/novo', 'Encaminhamentos::novo');
    $routes->get('encaminhamentos/pendentes', 'Encaminhamentos::pendentes');
    $routes->get('encaminhamentos/concluidos', 'Encaminhamentos::concluidos');
    $routes->post('encaminhamentos', 'Encaminhamentos::criar');
    $routes->post('encaminhamentos/(:num)/status', 'Encaminhamentos::mudarStatus/$1');
    $routes->get('encaminhamentos/(:num)', 'Encaminhamentos::detalhes/$1');

    // Rede de Proteção (instituições — reutilizadas pelos Encaminhamentos)
    $routes->get('rede/instituicoes/nova', 'Instituicoes::nova');
    $routes->get('rede/instituicoes/editar/(:num)', 'Instituicoes::editar/$1');
    $routes->post('rede/instituicoes', 'Instituicoes::criar');
    $routes->post('rede/instituicoes/atualizar/(:num)', 'Instituicoes::atualizar/$1');
    $routes->post('rede/instituicoes/(:num)/situacao', 'Instituicoes::mudarSituacao/$1');
    $routes->get('rede/buscar-instituicoes', 'Instituicoes::buscar');
    $routes->get('rede/instituicoes/(:num)', 'Instituicoes::detalhes/$1');
    $routes->get('rede/instituicoes', 'Instituicoes::index');

    // Medidas protetivas (equipe: admin e funcionario)
    $routes->get('medidas-protetivas/nova', 'MedidasProtetivas::nova');
    $routes->get('medidas-protetivas/editar/(:num)', 'MedidasProtetivas::editar/$1');
    $routes->get('medidas-protetivas/(:num)/descumprimentos/novo', 'MedidasProtetivas::novoDescumprimento/$1');
    $routes->post('medidas-protetivas', 'MedidasProtetivas::criar');
    $routes->post('medidas-protetivas/atualizar/(:num)', 'MedidasProtetivas::atualizar/$1');
    $routes->post('medidas-protetivas/(:num)/situacao', 'MedidasProtetivas::mudarSituacao/$1');
    $routes->post('medidas-protetivas/(:num)/descumprimentos', 'MedidasProtetivas::registrarDescumprimento/$1');
    $routes->get('medidas-protetivas/(:num)', 'MedidasProtetivas::detalhes/$1');
    $routes->get('medidas-protetivas', 'MedidasProtetivas::index');
    // Agenda de compromissos (equipe: admin e funcionário)
    $routes->get('agenda/novo', 'Agenda::novo');
    $routes->post('agenda', 'Agenda::criar');
    $routes->get('agenda/buscar-encaminhamentos', 'Agenda::buscarEncaminhamentos');
    $routes->get('agenda/editar/(:num)', 'Agenda::editar/$1');
    $routes->post('agenda/atualizar/(:num)', 'Agenda::atualizar/$1');
    $routes->post('agenda/(:num)/status', 'Agenda::mudarStatus/$1');
    $routes->post('agenda/(:num)/reagendar', 'Agenda::reagendar/$1');
    $routes->get('agenda/(:num)', 'Agenda::detalhes/$1');
    $routes->get('agenda', 'Agenda::index');

    // Processos (módulo PROCESSOS > Processos — equipe: admin e funcionário)
    $routes->get('processos/nova', 'Processos::nova');
    $routes->post('processos', 'Processos::criar');
    $routes->get('processos/editar/(:num)', 'Processos::editar/$1');
    $routes->post('processos/atualizar/(:num)', 'Processos::atualizar/$1');
    $routes->get('processos/buscar-medidas', 'Processos::buscarMedidas');
    $routes->get('processos/(:num)', 'Processos::detalhes/$1');
    $routes->get('processos', 'Processos::index');

    // Documentos (módulo PROCESSOS > Documentos — equipe: admin e funcionário)
    $routes->get('documentos/novo', 'Documentos::novo');
    $routes->post('documentos', 'Documentos::criar');
    $routes->get('documentos/buscar-processos', 'Documentos::buscarProcessos');
    $routes->get('documentos/buscar-manifestacoes', 'Documentos::buscarManifestacoes');
    $routes->get('documentos/editar/(:num)', 'Documentos::editar/$1');
    $routes->post('documentos/atualizar/(:num)', 'Documentos::atualizar/$1');
    $routes->post('documentos/(:num)/excluir', 'Documentos::excluir/$1');
    $routes->get('documentos/visualizar/(:num)', 'Documentos::visualizar/$1');
    $routes->get('documentos/download/(:num)', 'Documentos::download/$1');
    $routes->get('documentos/(:num)', 'Documentos::detalhes/$1');
    $routes->get('documentos', 'Documentos::index');

    // Manifestações (módulo OUVIDORIA > Denúncias/Ouvidoria — equipe)
    $routes->get('manifestacoes/nova', 'Manifestacoes::nova');
    $routes->post('manifestacoes', 'Manifestacoes::criar');
    $routes->get('manifestacoes/buscar', 'Manifestacoes::buscarManifestacoes');
    $routes->get('manifestacoes/editar/(:num)', 'Manifestacoes::editar/$1');
    $routes->post('manifestacoes/atualizar/(:num)', 'Manifestacoes::atualizar/$1');
    $routes->post('manifestacoes/(:num)/status', 'Manifestacoes::mudarSituacao/$1');
    $routes->get('manifestacoes/(:num)', 'Manifestacoes::detalhes/$1');
    $routes->get('manifestacoes', 'Manifestacoes::index');

    // Relatórios (central de análise com dados reais — equipe: admin e funcionário)
    $routes->get('relatorios', 'Relatorios::index');
    $routes->get('relatorios/exportar', 'Relatorios::exportar');
    $routes->get('relatorios/pdf', 'Relatorios::exportarPdf');

    // Tarefas (subopção da Agenda — equipe: admin e funcionário)
    $routes->get('tarefas/nova', 'Tarefas::nova');
    $routes->post('tarefas', 'Tarefas::criar');
    $routes->get('tarefas/editar/(:num)', 'Tarefas::editar/$1');
    $routes->post('tarefas/atualizar/(:num)', 'Tarefas::atualizar/$1');
    $routes->post('tarefas/(:num)/status', 'Tarefas::mudarStatus/$1');
    $routes->get('tarefas/(:num)', 'Tarefas::detalhes/$1');
    $routes->get('tarefas', 'Tarefas::index');

    // Cadastro administrativo, pesquisa e histórico de mulheres (equipe)
    $routes->get('mulheres/cadastro', 'Mulheres::cadastro');
    $routes->post('mulheres/cadastro/salvar', 'Mulheres::salvar');
    $routes->get('mulheres/pesquisa', 'Mulheres::pesquisa');
    $routes->get('mulheres/detalhes/(:num)', 'Mulheres::detalhes/$1');
    $routes->post('mulheres/convite/(:num)', 'Mulheres::reenviarConvite/$1');
    $routes->get('mulheres/editar/(:num)', 'Mulheres::editar/$1');
    $routes->post('mulheres/atualizar/(:num)', 'Mulheres::atualizar/$1');
    $routes->get('mulheres/historico/(:num)', 'Mulheres::historico/$1');
    $routes->get('mulheres/relatorio-pdf/(:num)', 'Mulheres::relatorioPdf/$1');
});
