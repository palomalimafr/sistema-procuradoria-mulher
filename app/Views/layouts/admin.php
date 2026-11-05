<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc((string) ($title ?? 'Painel Administrativo')) ?> · Procuradoria da Mulher</title>
  <meta name="theme-color" content="#a179c3">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="apple-mobile-web-app-title" content="Procuradoria da Mulher">
  <link rel="manifest" href="<?= base_url('manifest.json') ?>">
  <link rel="apple-touch-icon" href="<?= base_url('assets/pwa/apple-touch-icon.png') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>?v=<?= filemtime(ROOTPATH . 'public/assets/css/app.css') ?: time() ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <style>
    body {
      font-family: "Inter", sans-serif !important;
      font-optical-sizing: auto;
      min-height: 100vh !important;
      margin: 0 !important;
    }
    .ec-dialog-root{position:fixed;inset:0;z-index:1000;display:none;align-items:center;justify-content:center;padding:1rem}
    .ec-dialog-root.ec-open{display:flex}
    .ec-dialog-backdrop{position:absolute;inset:0;background:rgba(17,24,39,.45);opacity:0;transition:opacity .2s ease}
    .ec-dialog-root.ec-open .ec-dialog-backdrop{opacity:1}
    .ec-dialog-panel{position:relative;width:100%;max-width:22rem;background:#fff;border-radius:1rem;padding:1.5rem;display:flex;flex-direction:column;align-items:center;text-align:center;gap:.5rem;box-shadow:0 20px 40px -12px rgba(31,41,55,.35);opacity:0;transform:translateY(8px) scale(.96);transition:opacity .2s ease,transform .2s ease}
    .ec-dialog-root.ec-open .ec-dialog-panel{opacity:1;transform:none}
    .ec-dialog-icon{width:3rem;height:3rem;border-radius:9999px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .ec-dialog-icon i{width:1.5rem;height:1.5rem}
    .ec-dialog-title{margin:0;font-size:15px;font-weight:600;color:#374151;font-family:'Inter',system-ui,sans-serif}
    .ec-dialog-message{margin:0;font-size:13px;line-height:1.55;color:#6b7280;font-family:'Inter',system-ui,sans-serif}
    .ec-dialog-actions{display:flex;gap:.5rem;width:100%;margin-top:.5rem}
    .ec-btn{flex:1;display:flex;align-items:center;justify-content:center;cursor:pointer;border-radius:.5rem;border:1px solid transparent;padding:.6rem 1rem;font-size:13px;font-weight:500;font-family:'Inter',system-ui,sans-serif;transition:background-color .15s ease,border-color .15s ease,color .15s ease}
    .ec-btn-primary{background:#a179c3;color:#fff}
    .ec-btn-primary:hover{background:#8967a6}
    .ec-btn-secondary{background:#fff;border-color:#e5e7eb;color:#4b5563}
    .ec-btn-secondary:hover{border-color:#d1d5db;background:#f9fafb}
    .ec-btn-danger{background:#dc2626;color:#fff}
    .ec-btn-danger:hover{background:#b91c1c}
  </style>
</head>

<?php
$uriPath = trim((string) uri_string(), '/');

$startsWith = static function (string $prefix) use ($uriPath): bool {
    $prefix = trim($prefix, '/');
    if ($prefix === '') {
        return false;
    }
    return $uriPath === $prefix || str_starts_with($uriPath, $prefix . '/');
};

$isActive = static function (array $prefixes) use ($uriPath, $startsWith): bool {
    foreach ($prefixes as $prefix) {
        $prefix = trim((string) $prefix, '/');
        if ($prefix === '') {
            continue;
        }
        if ($uriPath === $prefix || $startsWith($prefix)) {
            return true;
        }
    }
    return false;
};

$buildItem = static function (string $label, string $href, array $prefixes, string $icon = 'circle', bool $disabled = false) use ($isActive): array {
    return [
        'label'    => $label,
        'href'     => $href,
        'icon'     => $icon,
        'active'   => $isActive($prefixes),
        'disabled' => $disabled,
    ];
};

$loggedUserName = (string) (session()->get('usuario_nome') ?? 'Administrador');
$loggedUserPerfil = (string) (session()->get('perfil') ?? 'admin');

// Configurações institucionais (SISTEMA > Configurações) usadas na marca.
$configSistema = (new \App\Models\ConfiguracaoModel())->obterTodos();
$nomeMarca = (string) ($configSistema['nome_procuradoria'] ?? 'Procuradoria da Mulher');
$nomeSistemaMarca = (string) ($configSistema['nome_sistema'] ?? 'Sistema de Gestão');
$logoMarca = $configSistema['logo'] ?? null;

$menuSections = [];

if ($loggedUserPerfil === 'mulher') {
    $menuSections = [
        [
            'title' => 'Principal',
            'icon' => 'layout-dashboard',
            'items' => [
                $buildItem('Dashboard', '/mulher/dashboard', ['mulher/dashboard'], 'layout-dashboard'),
            ],
        ],
        [
            'title' => 'Minha Conta',
            'icon' => 'settings',
            'items' => [
                $buildItem('Meus Dados', '/mulher/perfil', ['mulher/perfil'], 'user'),
            ],
        ],
    ];
} else {
    $menuSections = [
        [
            'title' => 'Principal',
            'icon' => 'layout-dashboard',
            'items' => [
                $buildItem('Dashboard', '/dashboard', ['dashboard'], 'layout-dashboard'),
            ],
        ],
        [
            'title' => 'Atendimentos',
            'icon' => 'briefcase',
            'items' => [
                $buildItem('Novo atendimento', '/atendimentos/novo', ['atendimentos/novo'], 'clipboard-plus'),
                $buildItem('Fila de atendimento', '/atendimentos/fila', ['atendimentos/fila'], 'list-ordered'),
                $buildItem('Em andamento', '/atendimentos/em-andamento', ['atendimentos/em-andamento'], 'loader'),
                $buildItem('Encerrados', '/atendimentos/encerrados', ['atendimentos/encerrados'], 'circle-check'),
            ],
        ],
        [
            'title' => 'Mulheres',
            'icon' => 'users',
            'items' => [
                $buildItem('Cadastro', '/mulheres/cadastro', ['mulheres/cadastro'], 'user-plus'),
                $buildItem('Pesquisa', '/mulheres/pesquisa', ['mulheres/pesquisa', 'mulheres/detalhes', 'mulheres/editar'], 'search'),
            ],
        ],
        [
            'title' => 'Casos',
            'icon' => 'folder-open',
            'items' => [
                $buildItem('Casos ativos', '/casos/ativos', ['casos/ativos'], 'folder-check'),
                $buildItem('Casos urgentes', '/casos/urgentes', ['casos/urgentes'], 'octagon-alert'),
                $buildItem('Casos atrasados', '/casos/atrasados', ['casos/atrasados'], 'clock-alert'),
                $buildItem('Encerrados', '/casos/encerrados', ['casos/encerrados'], 'archive'),
            ],
        ],
        [
            'title' => 'Acolhimento',
            'icon' => 'heart-handshake',
            'items' => [
                $buildItem('Novo acolhimento', '/acolhimentos/novo', ['acolhimentos/novo', 'acolhimentos'], 'heart'),
                $buildItem('Avaliação de risco', '/avaliacoes', ['avaliacoes'], 'shield-alert'),
            ],
        ],
        [
            'title' => 'Encaminhamentos',
            'icon' => 'send',
            'items' => [
                $buildItem('Novo encaminhamento', '/encaminhamentos/novo', ['encaminhamentos/novo'], 'file-plus'),
                $buildItem('Pendentes', '/encaminhamentos/pendentes', ['encaminhamentos/pendentes'], 'hourglass'),
                $buildItem('Concluídos', '/encaminhamentos/concluidos', ['encaminhamentos/concluidos'], 'badge-check'),
            ],
        ],
        [
            'title' => 'Agenda',
            'icon' => 'calendar',
            'items' => [
                $buildItem('Agenda', '/agenda', ['agenda'], 'calendar-days'),
                $buildItem('Tarefas', '/tarefas', ['tarefas'], 'list-checks'),
            ],
        ],
        [
            'title' => 'Medidas Protetivas',
            'icon' => 'shield-check',
            'items' => [
                $buildItem('Medidas Protetivas', '/medidas-protetivas', ['medidas-protetivas'], 'shield-check'),
            ],
        ],
        [
            'title' => 'Processos',
            'icon' => 'scale',
            'items' => [
                $buildItem('Processos', '/processos', ['processos'], 'scale'),
                $buildItem('Documentos', '/documentos', ['documentos'], 'file-text'),
            ],
        ],
        [
            'title' => 'Rede de Proteção',
            'icon' => 'network',
            'items' => [
                $buildItem('Instituições', '/rede/instituicoes', ['rede'], 'building-2'),
            ],
        ],
        [
            'title' => 'Ouvidoria',
            'icon' => 'megaphone',
            'items' => [
                $buildItem('Denúncias/Ouvidoria', '/manifestacoes', ['manifestacoes'], 'megaphone'),
            ],
        ],
        [
            'title' => 'Relatórios',
            'icon' => 'bar-chart-3',
            'items' => [
                $buildItem('Relatórios', '/relatorios', ['relatorios'], 'bar-chart-3'),
            ],
        ],
        [
            'title' => 'Sistema',
            'icon' => 'settings',
            'items' => [
                $buildItem('Usuários', '/usuarios', ['usuarios'], 'user-cog'),
                $buildItem('Configurações', '/sistema/configuracoes', ['configuracoes'], 'settings'),
                $buildItem('Listas', '/sistema/configuracoes/listas', ['sistema/configuracoes/listas'], 'list'),
                $buildItem('Auditoria', '/sistema/auditoria', ['sistema/auditoria'], 'scroll-text'),
            ],
        ],
    ];

    if ($loggedUserPerfil === 'admin') {
        $menuSections[] = [
            'title' => 'Emergência',
            'icon' => 'siren',
            'items' => [
                $buildItem('Pedidos SOS', '/pedidos-sos', ['pedidos-sos'], 'siren'),
            ],
        ];
    }
}

if ($loggedUserPerfil === 'admin') {
    $perfilLabel = 'Administrador';
} elseif ($loggedUserPerfil === 'mulher') {
    $perfilLabel = 'Mulher';
} else {
    $perfilLabel = 'Funcionário';
}
?>

<body class="w-screen h-screen flex flex-col lg:flex-row overflow-hidden">

<header class="lg:hidden w-full shrink-0 bg-white border-b border-gray-200 z-40">
  <div class="flex items-center justify-between gap-3 px-4 py-3">
    <a href="<?= base_url('dashboard') ?>" class="flex items-center gap-2.5">
      <?php if ($logoMarca): ?>
        <img src="<?= base_url('sistema/logo') ?>" alt="Logo do sistema" class="w-10 h-10 object-contain bg-white border border-gray-200 rounded-xl">
      <?php else: ?>
        <div class="w-10 h-10 bg-primary border border-black/10 shadow-[inset_0_1px_0_0_rgba(255,255,255,0.2),0px_1px_1px_rgba(31,41,55,0.1)] flex items-center justify-center rounded-xl">
          <i data-lucide="heart-handshake" class="text-white w-6 aspect-square stroke-[1.2]"></i>
        </div>
      <?php endif; ?>
      <div class="flex flex-col">
        <p class="text-gray-700 text-[15px] font-semibold leading-4"><?= esc($nomeMarca) ?></p>
        <p class="text-gray-500 text-[12px] leading-4"><?= esc($nomeSistemaMarca) ?></p>
      </div>
    </a>
    <button type="button" id="mobile-menu-btn" title="Menu" class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition" aria-expanded="false" aria-controls="sidebar">
      <i data-lucide="menu" class="w-4.5 h-4.5 stroke-[1.3]"></i>
    </button>
  </div>
</header>

<div id="mobile-backdrop" class="lg:hidden fixed inset-0 z-40 bg-black/30 opacity-0 invisible pointer-events-none transition-opacity duration-300" aria-hidden="true"></div>

<aside id="sidebar" class="fixed top-0 left-0 lg:static h-screen w-71.25 bg-white border-r border-gray-200 shadow-[1px_0px_2px_0px_rgba(31,41,55,0.04)] -translate-x-full lg:translate-x-0 transition-transform duration-300 z-50 lg:z-0 flex flex-col p-3">
  <div class="flex flex-col gap-1">
    <div class="flex flex-row items-center gap-2 px-1 mt-1">
      <?php if ($logoMarca): ?>
        <img src="<?= base_url('sistema/logo') ?>" alt="Logo do sistema" class="w-11.5 h-11.5 object-contain bg-white border border-gray-200 rounded-xl">
      <?php else: ?>
        <div class="min-w-11.5 min-h-11.5 bg-primary border border-black/10 shadow-[inset_0_1px_0_0_rgba(255,255,255,0.2),0px_1px_1px_rgba(31,41,55,0.1)] flex items-center justify-center rounded-xl">
          <i data-lucide="heart-handshake" class="text-white w-8 aspect-square stroke-[1.2]"></i>
        </div>
      <?php endif; ?>
      <p class="text-gray-600 text-[13.5px] leading-4">
        <?= esc($nomeMarca) ?>
      </p>
    </div>
    <hr class="w-full h-px bg-gray-200 border-0 my-2" />
    <div class="w-full h-14 bg-gray-50 border border-gray-200 shadow-[inset_0_1px_0_0_rgba(255,255,255,1),inset_0_-1px_0_0_rgba(0,0,0,0.02)] flex flex-row items-center gap-1.5 rounded-xl p-1.25 mb-2">
      <div class="flex flex-row items-center gap-2">
        <?php if ($logoMarca): ?>
          <img src="<?= base_url('sistema/logo') ?>" alt="Logo do sistema" class="w-11 min-w-11 aspect-square object-contain bg-white border border-gray-200 rounded-lg">
        <?php else: ?>
          <div class="w-11 min-w-11 aspect-square bg-white border border-gray-200 rounded-lg relative flex items-center justify-center overflow-hidden">
            <i data-lucide="heart" class="w-6 h-6 stroke-gray-400 stroke-[1]"></i>
          </div>
        <?php endif; ?>
        <p class="text-gray-600 text-[13px] leading-4">
          <?= esc($nomeMarca) ?>
          <br><span class="text-gray-500 text-[12px]"><?= esc($nomeSistemaMarca) ?></span>
        </p>
      </div>
    </div>
  </div>

  <div class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden py-1">
    <?php foreach ($menuSections as $section): ?>
      <?php
      $secaoTemAtivo = false;
      foreach ($section['items'] as $__item) {
          if (! empty($__item['active'])) {
              $secaoTemAtivo = true;
              break;
          }
      }
      unset($__item);
      ?>
      <div class="w-full bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl overflow-hidden mb-1.5">
        <button type="button" data-menu-toggle class="w-full flex items-center gap-1.5 px-2.5 py-2 bg-gray-50 border-b border-gray-200 text-left cursor-pointer transition hover:bg-gray-100/70" title="<?= $secaoTemAtivo ? 'Recolher' : 'Expandir' ?>">
          <i data-lucide="<?= esc((string) $section['icon']) ?>" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
          <p class="text-gray-500 text-[11px] font-semibold uppercase tracking-wider leading-3"><?= esc((string) $section['title']) ?></p>
          <span data-menu-chevron class="ml-auto w-4 h-4 flex items-center justify-center text-gray-500 transition-transform duration-200 <?= $secaoTemAtivo ? 'rotate-180' : '' ?>">
            <i data-lucide="chevron-down" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
          </span>
        </button>
        <div data-menu-panel class="flex flex-col p-1 <?= $secaoTemAtivo ? '' : 'hidden' ?>">
          <?php foreach ($section['items'] as $item): ?>
            <?php if (in_array($item['label'], ['Usuários', 'Configurações', 'Listas', 'Auditoria'], true) && session()->get('perfil') !== 'admin') continue; ?>
            <?php if (! empty($item['disabled'])): ?>
              <span title="Disponível em breve" class="w-full min-h-9 flex flex-row items-center gap-2 px-2.5 rounded-lg opacity-45 cursor-not-allowed select-none">
                <i data-lucide="<?= esc((string) $item['icon']) ?>" class="w-4.5 aspect-square stroke-gray-500 stroke-[1.3]"></i>
                <p class="text-gray-600 text-[12.5px] leading-3.75"><?= esc((string) $item['label']) ?></p>
              </span>
            <?php else: ?>
            <a href="<?= base_url((string) $item['href']) ?>" <?= !empty($item['target']) ? 'target="' . esc((string) $item['target']) . '" rel="noopener"' : '' ?> class="w-full min-h-9 flex flex-row items-center gap-2 px-2.5 rounded-lg cursor-pointer transition <?= !empty($item['active']) ? 'bg-gray-100/85' : 'hover:bg-gray-50 active:bg-gray-100/85' ?>">
              <i data-lucide="<?= esc((string) $item['icon']) ?>" class="w-4.5 aspect-square stroke-gray-500 stroke-[1.3] <?= !empty($item['active']) ? 'stroke-gray-700' : '' ?>"></i>
              <p class="text-gray-600 text-[12.5px] leading-3.75 <?= !empty($item['active']) ? 'text-gray-700 font-medium' : '' ?>"><?= esc((string) $item['label']) ?>
                <?php if ($item['label'] === 'Pedidos SOS'): ?>
                  <span id="sos-menu-count" class="hidden align-middle ml-1 px-1.5 min-w-5 h-4 inline-flex items-center justify-center rounded-full text-[10.5px] font-semibold bg-red-100 text-red-700"></span>
                <?php endif; ?>
              </p>
            </a>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="flex flex-col gap-2 pt-1">
    <div class="w-full h-14 bg-gray-50 border border-gray-200 shadow-[inset_0_1px_0_0_rgba(255,255,255,1),inset_0_-1px_0_0_rgba(0,0,0,0.02)] flex flex-row items-center justify-between rounded-xl p-1.25">
      <div class="flex flex-row items-center gap-2">
        <div class="w-11 min-w-11 h-11 bg-white border border-gray-200 rounded-lg relative flex items-center justify-center overflow-hidden">
          <i data-lucide="user-round" class="w-5.25 aspect-square stroke-gray-400 stroke-[0.8]"></i>
        </div>
        <div class="max-w-38.25 flex flex-col">
          <p class="text-gray-600 text-[13.5px] leading-4 text-nowrap overflow-hidden text-ellipsis">
            <?= esc($loggedUserName) ?>
          </p>
          <p class="text-gray-500 text-[12.25px] leading-4 mt text-nowrap overflow-hidden text-ellipsis capitalize">
            <?= esc($perfilLabel) ?>
          </p>
        </div>
      </div>
      <a href="<?= base_url('logout') ?>" class="w-7 h-7 mr-1 flex justify-center items-center rounded-lg transition cursor-pointer" title="Sair">
        <i data-lucide="log-out" class="w-4.75 aspect-square stroke-gray-600 stroke-[1.2]"></i>
      </a>
    </div>
  </div>
</aside>

<main class="w-full h-full lg:h-screen lg:max-w-[calc(100%-285px)] p-3 bg-zinc-50 flex flex-col gap-3 overflow-y-auto overflow-x-hidden z-0 lg:z-0">

  <?= $this->renderSection('content') ?>
</main>

<div id="toast-container" class="fixed top-4 right-4 z-50 flex flex-col gap-2"></div>

<div id="dialog-root" class="ec-dialog-root" aria-hidden="true">
  <div class="ec-dialog-backdrop"></div>
  <div class="ec-dialog-panel" role="dialog" aria-modal="true">
    <div class="ec-dialog-icon" id="dialog-icon-wrap">
      <i id="dialog-icon" data-lucide="triangle-alert"></i>
    </div>
    <h2 id="dialog-title" class="ec-dialog-title"></h2>
    <p id="dialog-message" class="ec-dialog-message"></p>
    <div class="ec-dialog-actions">
      <button type="button" id="dialog-cancel" class="ec-btn ec-btn-secondary">Cancelar</button>
      <button type="button" id="dialog-confirm" class="ec-btn ec-btn-primary">Confirmar</button>
    </div>
  </div>
</div>

<script>
  const BASE = '<?= base_url() ?>';

  function openModal(id) {
    const modal = document.getElementById(id);
    modal.classList.remove('hidden');
    modal.classList.add('flex');
  }

  function closeModal(id) {
    const modal = document.getElementById(id);
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }

  let _ecDialog = null;

  function _ecOpenDialog(opts, resolve) {
    const root = document.getElementById('dialog-root');
    const iconWrap = document.getElementById('dialog-icon-wrap');
    const iconEl = document.getElementById('dialog-icon');
    const title = document.getElementById('dialog-title');
    const message = document.getElementById('dialog-message');
    const cancelBtn = document.getElementById('dialog-cancel');
    const confirmBtn = document.getElementById('dialog-confirm');
    if (!root || !iconWrap || !title) { resolve(false); return; }

    const type = opts.type || 'warning';
    const palettes = {
      danger: ['#fef2f2', '#dc2626'],
      error: ['#fef2f2', '#dc2626'],
      warning: ['#fef3c7', '#d97706'],
      success: ['#ecfdf5', '#059669'],
      question: ['#f4eff8', '#a179c3'],
    };
    const [wrapBg, iconColor] = palettes[type] || palettes.warning;
    iconWrap.style.background = wrapBg;
    iconEl.style.color = iconColor;
    iconEl.setAttribute('data-lucide', opts.icon || (type === 'danger' ? 'trash-2' : type === 'success' ? 'circle-check' : type === 'question' ? 'help-circle' : 'triangle-alert'));

    title.textContent = opts.title || '';
    message.textContent = opts.message || '';
    message.style.display = opts.message ? '' : 'none';
    confirmBtn.textContent = opts.confirmText || (type === 'danger' ? 'Excluir' : 'Confirmar');
    confirmBtn.className = 'ec-btn ' + (type === 'danger' ? 'ec-btn-danger' : 'ec-btn-primary');
    cancelBtn.textContent = opts.cancelText || 'Cancelar';
    const isAlert = opts.alert === true;
    cancelBtn.style.display = isAlert ? 'none' : '';

    if (window.lucide) window.lucide.createIcons();

    root.classList.add('ec-open');
    root.setAttribute('aria-hidden', 'false');

    let settled = false;
    const finish = (result) => {
      if (settled) return;
      settled = true;
      _ecDialog = null;
      setTimeout(() => {
        root.classList.remove('ec-open');
        root.setAttribute('aria-hidden', 'true');
        resolve(result);
      }, 180);
    };
    _ecDialog = { finish };

    confirmBtn.onclick = () => finish(isAlert ? undefined : true);
    cancelBtn.onclick = () => finish(false);
    root.querySelector('.ec-dialog-backdrop').onclick = () => finish(isAlert ? undefined : false);
  }

  function showConfirm(opts) {
    return new Promise(resolve => _ecOpenDialog({ alert: false, ...opts }, resolve));
  }

  function showAlert(opts) {
    return new Promise(resolve => _ecOpenDialog({ alert: true, ...opts }, resolve));
  }

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && _ecDialog) _ecDialog.finish(undefined);
  });

  function showToast(message, type = 'error') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    const palette = {
      success: 'bg-green-600',
      warning: 'bg-amber-500',
      error: 'bg-red-600',
    };
    const icon = type === 'success' ? 'circle-check' : type === 'warning' ? 'triangle-alert' : 'circle-x';

    toast.className = 'flex items-center gap-2.5 px-4 py-3 ' + (palette[type] || palette.error) + ' text-white text-[13px] font-medium rounded-xl shadow-lg translate-x-4 opacity-0 transition-all duration-300';
    toast.innerHTML = '<i data-lucide="' + icon + '" class="w-4.5 h-4.5 shrink-0"></i>';
    const span = document.createElement('span');
    span.textContent = message;
    toast.appendChild(span);
    container.appendChild(toast);

    if (window.lucide) window.lucide.createIcons();

    requestAnimationFrame(() => {
      toast.classList.remove('translate-x-4', 'opacity-0');
    });

    setTimeout(() => {
      toast.classList.add('translate-x-4', 'opacity-0');
      setTimeout(() => toast.remove(), 300);
    }, 3200);
  }

  (function showFlashToasts() {
    const success = <?= json_encode(session()->getFlashdata('sucesso')) ?>;
    const error = <?= json_encode(session()->getFlashdata('erro')) ?>;
    const errors = <?= json_encode(session()->getFlashdata('errors') ?: session()->getFlashdata('erros')) ?>;

    function toMsgList(value) {
      if (Array.isArray(value)) return value.filter(Boolean);
      if (value && typeof value === 'object') return Object.values(value).filter(Boolean);
      return value ? [String(value)] : [];
    }

    if (success) {
      showToast(String(success), 'success');
    }

    const all = [...toMsgList(error), ...toMsgList(errors)];

    if (all.length) {
      const extra = all.length - 1;
      showToast(all[0] + (extra > 0 ? ` (+${extra} ${extra === 1 ? 'erro' : 'erros'})` : ''), 'error');
    }
  })();

  (function initDeleteConfirms() {
    document.addEventListener('click', function (e) {
      const link = e.target.closest('a[data-confirm]');
      if (!link) return;
      e.preventDefault();
      const href = link.getAttribute('href');
      const msg = link.getAttribute('data-confirm-message') || 'Esta ação não poderá ser desfeita.';
      showConfirm({
        type: 'danger',
        title: 'Excluir registro?',
        message: msg,
        confirmText: 'Excluir'
      }).then(ok => {
        if (ok) window.location.href = href;
      });
    });
  })();

  (function initMobileMenu() {
    const btn = document.getElementById('mobile-menu-btn');
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('mobile-backdrop');
    if (!btn || !sidebar || !backdrop) return;

    function setMenu(open) {
      sidebar.classList.toggle('-translate-x-full', !open);
      sidebar.classList.toggle('translate-x-0', open);
      sidebar.classList.toggle('shadow-[16px_0_32px_-12px_rgba(31,41,55,0.25)]', open);
      backdrop.classList.toggle('opacity-100', open);
      backdrop.classList.toggle('visible', open);
      backdrop.classList.toggle('pointer-events-auto', open);
      backdrop.classList.toggle('opacity-0', !open);
      backdrop.classList.toggle('invisible', !open);
      backdrop.classList.toggle('pointer-events-none', !open);
      btn.setAttribute('aria-expanded', String(open));
      const icon = btn.querySelector('[data-lucide]');
      if (icon) icon.setAttribute('data-lucide', open ? 'x' : 'menu');
      if (window.lucide) window.lucide.createIcons();
    }

    btn.addEventListener('click', () => setMenu(!sidebar.classList.contains('translate-x-0')));
    backdrop.addEventListener('click', () => setMenu(false));
    sidebar.querySelectorAll('a').forEach(link => link.addEventListener('click', () => setMenu(false)));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setMenu(false); });
    window.addEventListener('resize', () => { if (window.innerWidth >= 1024) setMenu(false); });
  })();

(function initMenuAccordion() {
    document.querySelectorAll('[data-menu-toggle]').forEach(function (toggle) {
      toggle.addEventListener('click', function () {
        const panel = toggle.nextElementSibling;
        const chevron = toggle.querySelector('[data-menu-chevron]');
        const isHidden = panel.classList.toggle('hidden');
        if (chevron) chevron.classList.toggle('rotate-180', !isHidden);
        toggle.setAttribute('title', isHidden ? 'Expandir' : 'Recolher');
      });
    });
  })();

  lucide.createIcons();
</script>

<?php if (session()->get('perfil') === 'admin'): ?>
<script>
(function sosAlertaAdmin() {
  var INTERVALO = 15000;
  var badge = document.getElementById('sos-menu-count');
  var dashCount = document.getElementById('sos-dash-count');
  var container = document.getElementById('toast-container');
  var alerta = null;
  var alertados = new Set();
  var idsNoAlerta = new Set();

  function atualizarContador(total) {
    var n = Number(total) || 0;
    if (badge) {
      badge.textContent = n > 0 ? String(n) : '';
      badge.classList.toggle('hidden', n === 0);
    }
    if (dashCount) {
      dashCount.textContent = String(n);
      var zero = document.getElementById('sos-sem-pedidos');
      var linha = document.getElementById('sos-dash-linha');
      var plural = document.getElementById('sos-dash-plural');
      if (zero) zero.classList.toggle('hidden', n > 0);
      if (linha) linha.classList.toggle('hidden', n === 0);
      if (plural) plural.style.display = n === 1 ? 'none' : '';
    }
  }

  function construirAlerta(pedidos) {
    var box = document.createElement('div');
    box.setAttribute('role', 'alert');
    box.style.cssText = 'width:360px;max-width:100%;background:linear-gradient(180deg,#b91c1c,#991b1b);color:#fff;border-radius:16px;box-shadow:0 22px 44px -14px rgba(127,29,29,.55);overflow:hidden';

    var head = document.createElement('div');
    head.style.cssText = 'display:flex;align-items:center;gap:9px;padding:12px 14px;border-bottom:1px solid rgba(255,255,255,.25)';
    head.innerHTML = '<span style="font-size:17px;line-height:1">🚨</span><span style="font-weight:700;font-size:14px;letter-spacing:.2px">' + (pedidos.length === 1 ? 'NOVO PEDIDO SOS' : pedidos.length + ' NOVOS PEDIDOS SOS') + '</span>';

    var fechar = document.createElement('button');
    fechar.type = 'button';
    fechar.setAttribute('aria-label', 'Fechar alerta');
    fechar.innerHTML = '&times;';
    fechar.style.cssText = 'margin-left:auto;background:rgba(255,255,255,.15);border:0;border-radius:8px;color:#fff;width:26px;height:26px;font-size:15px;line-height:1;cursor:pointer';
    fechar.onclick = function () { box.remove(); alerta = null; idsNoAlerta.clear(); };
    head.appendChild(fechar);

    var corpo = document.createElement('div');
    corpo.style.cssText = 'padding:10px 14px 14px;display:flex;flex-direction:column;gap:8px';

    box.appendChild(head);
    box.appendChild(corpo);
    container.appendChild(box);

    box.style.transform = 'translateX(16px)';
    box.style.opacity = '0';
    requestAnimationFrame(function () {
      box.style.transition = 'transform .3s ease, opacity .3s ease';
      box.style.transform = 'translateX(0)';
      box.style.opacity = '1';
    });

    alerta = { box: box, corpo: corpo };
    idsNoAlerta.clear();
    return alerta;
  }

  function adicionarPedido(p) {
    if (!alerta || !alerta.box.isConnected || idsNoAlerta.has(p.id)) return;

    var row = document.createElement('div');
    row.style.cssText = 'display:flex;align-items:center;justify-content:space-between;gap:10px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.25);padding:9px 11px;border-radius:10px';

    var info = document.createElement('div');
    info.style.cssText = 'min-width:0;display:flex;flex-direction:column;gap:1px';
    var nome = document.createElement('strong');
    nome.style.cssText = 'font-size:13px;line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis';
    nome.textContent = p.mulher || 'Mulher';
    var quando = document.createElement('small');
    quando.style.cssText = 'font-size:11px;opacity:.92;line-height:1.2';
    quando.textContent = p.data_hora || '';
    info.appendChild(nome);
    info.appendChild(quando);

    var btn = document.createElement('a');
    btn.href = BASE + 'pedidos-sos/' + p.id;
    btn.style.cssText = 'flex-shrink:0;background:#fff;color:#b91c1c;font-size:12px;font-weight:700;padding:7px 12px;border-radius:9px;text-decoration:none';
    btn.textContent = 'Ver pedido';
    btn.onclick = function () { alertados.add(p.id); };

    row.appendChild(info);
    row.appendChild(btn);
    alerta.corpo.appendChild(row);
    idsNoAlerta.add(p.id);
  }

  function verificar() {
    fetch(BASE + 'pedidos-sos/novos', {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { if (!r.ok) throw new Error(String(r.status)); return r.json(); })
      .then(function (data) {
        if (Number.isFinite(Number(data.pendentes))) atualizarContador(data.pendentes);
        var novos = (data.novos || []).filter(function (p) {
          return p && p.id && !alertados.has(p.id);
        });
        if (!novos.length) return;
        novos.forEach(function (p) { alertados.add(p.id); });
        if (!alerta || !alerta.box.isConnected) construirAlerta(novos);
        novos.forEach(function (p) { adicionarPedido(p); });
      })
      .catch(function () {});
  }

  verificar();
  setInterval(verificar, INTERVALO);
})();
</script>
<?php endif; ?>

<script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker
        .register('<?= base_url('sw.js') ?>', { scope: '<?= base_url() ?>' })
        .catch(function () {});
    });
  }
</script>
</body>
</html>
