<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
$sosPendente = $sosVisivel && (int) $sosTotal > 0;
?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex justify-between items-center">
    <div class="flex flex-col gap-0.5">
      <h1 class="text-gray-600 text-[15px] font-medium">Dashboard</h1>
      <p class="text-gray-500 text-[12px] leading-4">Situação atual da Procuradoria da Mulher</p>
    </div>
    <p class="text-gray-400 text-[12px] leading-4"><?= date('d/m/Y') ?></p>
  </header>

  <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
    <?php foreach ($cartoes as $cartao): ?>
      <?php
      if (($cartao['mostrar'] ?? true) === false) {
          continue;
      }
      $risco = ($cartao['destaque'] ?? null) === 'alerta' && (int) $cartao['valor'] > 0;
      ?>
      <a href="<?= base_url($cartao['rota']) ?>"
         class="w-full p-4 bg-white border <?= $risco ? 'border-red-200' : 'border-gray-200' ?> shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-1 transition <?= $risco ? 'hover:border-red-300' : 'hover:border-primary' ?>">
        <div class="flex items-center gap-2 mb-1">
          <div class="w-8 h-8 <?= $risco ? 'bg-red-50 border-red-200' : 'bg-primary/10 border-primary/20' ?> border flex items-center justify-center rounded-lg">
            <i data-lucide="<?= esc($cartao['icone']) ?>" class="w-4 h-4 <?= $risco ? 'stroke-red-600' : 'stroke-primary' ?> stroke-[1.5]"></i>
          </div>
          <p class="text-gray-500 text-[12px] leading-4"><?= esc($cartao['rotulo']) ?></p>
        </div>
        <p class="text-gray-600 text-[22px] font-semibold leading-5"><?= number_format((int) $cartao['valor'], 0, ',', '.') ?></p>
        <?php if ($risco): ?>
          <p class="text-red-600 text-[11px] leading-3">Requer atenção</p>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-1.5">
      <p class="text-gray-600 text-[13px] font-medium leading-4">Pendências operacionais</p>

      <?php if ($pendencias === []): ?>
        <div class="flex flex-col items-center justify-center gap-2 py-6">
          <i data-lucide="circle-check" class="w-6 h-6 stroke-primary stroke-[1.3]"></i>
          <p class="text-gray-500 text-[12.5px] font-medium leading-4">Nenhuma pendência operacional</p>
          <p class="text-gray-400 text-[11px] leading-3">Não há itens aguardando ação neste momento.</p>
        </div>
      <?php else: ?>
        <div class="flex flex-col divide-y divide-gray-100 mt-1">
          <?php foreach ($pendencias as $pendencia): ?>
            <?php
            $perigo = $pendencia['tom'] === 'perigo';
            $aviso  = $pendencia['tom'] === 'alerta';
            $tomBadge = $perigo ? 'bg-red-50 text-red-700' : ($aviso ? 'bg-amber-50 text-amber-700' : 'bg-primary/10 text-primary');
            $tomIcone = $perigo ? 'stroke-red-600' : ($aviso ? 'stroke-amber-600' : 'stroke-primary');
            ?>
            <div class="py-2.5 flex items-start gap-2">
              <i data-lucide="<?= esc($pendencia['icone']) ?>" class="w-4 h-4 mt-0.5 <?= $tomIcone ?> stroke-[1.5]"></i>
              <div class="flex flex-col gap-0.5">
                <a href="<?= base_url($pendencia['rota']) ?>" class="text-gray-600 text-[12.5px] hover:text-primary transition"><?= esc($pendencia['rotulo']) ?></a>
                <p class="text-gray-400 text-[11px] leading-3"><?= esc($pendencia['nota']) ?></p>
              </div>
              <span class="ml-auto px-1.5 h-4.5 inline-flex items-center justify-center rounded-full text-[11px] font-semibold <?= $tomBadge ?>"><?= (int) $pendencia['valor'] ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-1.5">
      <p class="text-gray-600 text-[13px] font-medium leading-4">Ações rápidas</p>
      <div class="flex flex-wrap gap-2 mt-2">
        <a href="<?= base_url('atendimentos/novo') ?>" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-primary text-white text-[12.5px] font-medium transition hover:bg-primary/90">
          <i data-lucide="clipboard-plus" class="w-4 h-4"></i>
          Novo atendimento
        </a>
        <a href="<?= base_url('mulheres/cadastro') ?>" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-primary text-white text-[12.5px] font-medium transition hover:bg-primary/90">
          <i data-lucide="user-plus" class="w-4 h-4"></i>
          Cadastro de mulher
        </a>
        <a href="<?= base_url('atendimentos/fila') ?>" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-gray-100 text-gray-700 text-[12.5px] font-medium transition hover:bg-gray-50">
          <i data-lucide="list-ordered" class="w-4 h-4"></i>
          Fila de atendimento
        </a>
        <a href="<?= base_url('casos/urgentes') ?>" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-gray-100 text-gray-700 text-[12.5px] font-medium transition hover:bg-gray-50">
          <i data-lucide="octagon-alert" class="w-4 h-4"></i>
          Casos urgentes
        </a>
        <?php if ($sosVisivel): ?>
          <a href="<?= base_url('pedidos-sos') ?>" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg <?= $sosPendente ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-50' ?> text-[12.5px] font-medium transition">
            <i data-lucide="siren" class="w-4 h-4"></i>
            Pedidos SOS
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?= $this->endSection() ?>
