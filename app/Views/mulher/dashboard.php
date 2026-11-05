<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>

<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex justify-between items-center">
    <h1 class="text-gray-600 text-[15px] font-medium">Olá, <?= esc(session()->get('usuario_nome')) ?></h1>
    <p class="text-gray-400 text-[12px] leading-4"><?= date('d/m/Y') ?></p>
  </header>

  <?php if (session()->getFlashdata('sos_sucesso')): ?>
  <div class="w-full p-5 bg-green-50 border border-green-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex items-start gap-4">
    <div class="w-12 h-12 bg-green-100 border border-green-200 flex items-center justify-center rounded-xl shrink-0">
      <i data-lucide="circle-check" class="w-6 h-6 stroke-green-600 stroke-[1.5]"></i>
    </div>
    <div class="flex flex-col gap-1">
      <p class="text-gray-700 text-[15px] font-semibold leading-5">Pedido SOS enviado com sucesso</p>
      <p class="text-gray-500 text-[13px] leading-4">Localização registrada.</p>
      <p class="text-gray-500 text-[13px] leading-4">
        <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-medium bg-amber-50 border border-amber-200 text-amber-700">Status: Pendente</span>
      </p>
    </div>
  </div>
  <?php endif; ?>

  <!-- Botão SOS -->
  <form id="sos-form" action="<?= base_url('pedidos-sos/criar') ?>" method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="latitude" id="sos-latitude" value="">
    <input type="hidden" name="longitude" id="sos-longitude" value="">
    <div class="w-full p-5 bg-gradient-to-r from-red-50 to-red-100 border border-red-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <div class="flex flex-col sm:flex-row items-center gap-4">
        <div class="w-14 h-14 bg-red-100 border border-red-200 flex items-center justify-center rounded-xl shrink-0">
          <i data-lucide="siren" class="w-7 h-7 stroke-red-600 stroke-[1.5]"></i>
        </div>
        <div class="flex flex-col gap-1 flex-1 text-center sm:text-left">
          <p class="text-gray-700 text-[15px] font-semibold leading-5">Pedido SOS</p>
          <p class="text-gray-500 text-[13px] leading-4">Em caso de emergência, utilize o botão abaixo para acionar o serviço de proteção.</p>
        </div>
        <button type="button" id="sos-btn" class="btn-primary text-[13px] text-white px-5 py-2.5">
          <i data-lucide="siren" class="w-4 h-4"></i>
          Pedir SOS
        </button>
      </div>
      <div id="sos-status" class="hidden items-center gap-2 px-4 py-3 rounded-lg border text-[13px] leading-4">
        <i id="sos-status-icon" data-lucide="loader-circle" class="w-4 h-4 animate-spin shrink-0"></i>
        <span id="sos-status-text"></span>
      </div>
    </div>
  </form>

  <!-- Informações da conta -->
  <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 bg-primary/10 border border-primary/20 flex items-center justify-center rounded-lg">
        <i data-lucide="user" class="w-4 h-4 stroke-primary stroke-[1.5]"></i>
      </div>
      <p class="text-gray-600 text-[13px] font-medium leading-4">Minha Conta</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div class="flex flex-col gap-0.5">
        <p class="text-gray-400 text-[11px] leading-3 uppercase tracking-wider">Nome</p>
        <p class="text-gray-600 text-[13px] leading-4"><?= esc(session()->get('usuario_nome')) ?></p>
      </div>
      <div class="flex flex-col gap-0.5">
        <p class="text-gray-400 text-[11px] leading-3 uppercase tracking-wider">E-mail</p>
        <p class="text-gray-600 text-[13px] leading-4"><?= esc(session()->get('usuario_email')) ?></p>
      </div>
      <?php if (!empty($dadosMulher['telefone'])): ?>
      <div class="flex flex-col gap-0.5">
        <p class="text-gray-400 text-[11px] leading-3 uppercase tracking-wider">Telefone</p>
        <p class="text-gray-600 text-[13px] leading-4"><?= esc($dadosMulher['telefone']) ?></p>
      </div>
      <?php endif; ?>
      <?php if (!empty($dadosMulher['municipio']) || !empty($dadosMulher['estado'])): ?>
      <div class="flex flex-col gap-0.5">
        <p class="text-gray-400 text-[11px] leading-3 uppercase tracking-wider">Localização</p>
        <p class="text-gray-600 text-[13px] leading-4"><?= esc($dadosMulher['municipio'] ?? '') ?><?= !empty($dadosMulher['estado']) ? '/' . esc($dadosMulher['estado']) : '' ?></p>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Ações rápidas -->
  <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 bg-blue-50 border border-blue-200 flex items-center justify-center rounded-lg">
        <i data-lucide="settings" class="w-4 h-4 stroke-blue-600 stroke-[1.5]"></i>
      </div>
      <p class="text-gray-600 text-[13px] font-medium leading-4">Ações</p>
    </div>

    <div class="flex flex-wrap gap-2">
      <a href="<?= base_url('mulher/perfil') ?>" class="btn-secondary text-[13px] px-4 py-2">
        <i data-lucide="pencil" class="w-4 h-4"></i>
        Editar meus dados
      </a>
      <a href="<?= base_url('logout') ?>" class="btn-secondary text-[13px] px-4 py-2 text-gray-600 hover:text-red-600 hover:border-red-300">
        <i data-lucide="log-out" class="w-4 h-4"></i>
        Sair
      </a>
    </div>
  </div>
</section>

<script>
(function initSosButton() {
  const form = document.getElementById('sos-form');
  const btn = document.getElementById('sos-btn');
  const statusBox = document.getElementById('sos-status');
  const statusText = document.getElementById('sos-status-text');
  const statusIcon = document.getElementById('sos-status-icon');
  const latInput = document.getElementById('sos-latitude');
  const lngInput = document.getElementById('sos-longitude');
  if (!form || !btn || !statusBox || !statusText || !statusIcon || !latInput || !lngInput) return;

  let enviando = false;

  function mostraEstado(mensagem, tipo) {
    statusBox.classList.remove('hidden');
    statusBox.classList.add('flex');

    statusBox.classList.remove('bg-gray-50', 'border-gray-200', 'text-gray-600', 'bg-red-50', 'border-red-200', 'text-red-700');
    statusIcon.classList.remove('animate-spin');

    if (tipo === 'erro') {
      statusBox.classList.add('bg-red-50', 'border-red-200', 'text-red-700');
      statusIcon.setAttribute('data-lucide', 'circle-alert');
    } else {
      statusBox.classList.add('bg-gray-50', 'border-gray-200', 'text-gray-600');
      statusIcon.setAttribute('data-lucide', 'loader-circle');
      statusIcon.classList.add('animate-spin');
    }

    statusText.textContent = mensagem;
    if (window.lucide) window.lucide.createIcons();
  }

  function escondeEstado() {
    statusBox.classList.add('hidden');
    statusBox.classList.remove('flex');
    statusText.textContent = '';
  }

  function liberaBotao() {
    enviando = false;
    btn.disabled = false;
    btn.classList.remove('opacity-50', 'cursor-not-allowed');
    const icon = btn.querySelector('[data-lucide]');
    if (icon) icon.setAttribute('data-lucide', 'siren');
    if (window.lucide) window.lucide.createIcons();
  }

  function mensagemErroLocalizacao(codigo) {
    switch (codigo) {
      case 1: // PERMISSION_DENIED
        return 'Para enviar um pedido SOS, é necessário permitir o acesso à sua localização.';
      case 2: // POSITION_UNAVAILABLE
        return 'Não foi possível obter sua localização. Verifique se a localização está ativada e tente novamente.';
      case 3: // TIMEOUT
        return 'Não foi possível obter sua localização a tempo. Tente novamente.';
      default: // UNKNOWN_ERROR
        return 'Não foi possível obter sua localização. Tente novamente.';
    }
  }

  function enviarSos() {
    enviando = true;
    btn.disabled = true;
    btn.classList.add('opacity-50', 'cursor-not-allowed');
    const icon = btn.querySelector('[data-lucide]');
    if (icon) icon.setAttribute('data-lucide', 'loader-circle');
    if (window.lucide) window.lucide.createIcons();

    if (!('geolocation' in navigator)) {
      mostraEstado('Não foi possível obter sua localização. Verifique se a localização está ativada e tente novamente.', 'erro');
      liberaBotao();
      return;
    }

    mostraEstado('Obtendo sua localização...', 'carregando');

    navigator.geolocation.getCurrentPosition(
      function (posicao) {
        latInput.value = posicao.coords.latitude;
        lngInput.value = posicao.coords.longitude;
        mostraEstado('Enviando pedido SOS...', 'carregando');
        form.submit();
      },
      function (erro) {
        mostraEstado(mensagemErroLocalizacao(erro.code), 'erro');
        liberaBotao();
      },
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
      }
    );
  }

  btn.addEventListener('click', function () {
    if (enviando || btn.disabled) return;

    showConfirm({
      type: 'danger',
      icon: 'siren',
      title: 'Solicitar atendimento de emergência?',
      message: 'Você está prestes a solicitar atendimento de emergência. Deseja continuar?',
      confirmText: 'Confirmar SOS',
      cancelText: 'Cancelar'
    }).then((ok) => {
      if (!ok) return;
      escondeEstado();
      enviarSos();
    });
  });
})();
</script>

<?= $this->endSection() ?>