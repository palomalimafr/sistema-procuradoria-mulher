<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex items-center justify-between">
    <div class="flex items-center gap-2">
      <a href="<?= base_url('pedidos-sos') ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 transition" title="Voltar">
        <i data-lucide="arrow-left" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
      </a>
      <h1 class="text-gray-600 text-[15px] font-medium">Pedido SOS #<?= (int) $pedido['id'] ?></h1>
    </div>
    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium <?= $pedido['status'] === 'pendente' ? 'bg-red-50 border border-red-200 text-red-700' : 'bg-gray-50 border border-gray-200 text-gray-600' ?>">
      <i data-lucide="siren" class="w-3.5 h-3.5"></i>
      <?= esc(ucfirst($pedido['status'])) ?>
    </span>
  </header>

  <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 bg-red-50 border border-red-200 flex items-center justify-center rounded-lg">
        <i data-lucide="siren" class="w-4 h-4 stroke-red-600 stroke-[1.5]"></i>
      </div>
      <p class="text-gray-600 text-[13px] font-medium leading-4">Pedido SOS</p>
    </div>

    <div class="flex flex-col divide-y divide-gray-100">
      <div class="flex justify-between py-2">
        <span class="text-gray-400 text-[12px]">Mulher</span>
        <span class="text-gray-600 text-[12.5px]"><?= esc($pedido['nome_mulher']) ?></span>
      </div>
      <div class="flex justify-between py-2">
        <span class="text-gray-400 text-[12px]">Data/Hora</span>
        <span class="text-gray-600 text-[12.5px]"><?= $pedido['data_hora'] ? date('d/m/Y H:i:s', strtotime($pedido['data_hora'])) : '—' ?></span>
      </div>
      <div class="flex justify-between py-2">
        <span class="text-gray-400 text-[12px]">Status</span>
        <span>
          <span class="px-2 py-0.5 rounded-full text-[11px] font-medium <?= $pedido['status'] === 'pendente' ? 'bg-red-50 border border-red-200 text-red-700' : 'bg-gray-50 border border-gray-200 text-gray-600' ?>"><?= esc(ucfirst($pedido['status'])) ?></span>
        </span>
      </div>
    </div>
  </div>

  <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 bg-blue-50 border border-blue-200 flex items-center justify-center rounded-lg">
        <i data-lucide="map-pin" class="w-4 h-4 stroke-blue-600 stroke-[1.5]"></i>
      </div>
      <p class="text-gray-600 text-[13px] font-medium leading-4">Localização</p>
    </div>

    <?php if ($pedido['latitude'] !== null && $pedido['longitude'] !== null): ?>
      <div class="flex flex-col divide-y divide-gray-100">
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Latitude</span>
          <span class="text-gray-600 text-[12.5px] font-mono"><?= esc((string) $pedido['latitude']) ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Longitude</span>
          <span class="text-gray-600 text-[12.5px] font-mono"><?= esc((string) $pedido['longitude']) ?></span>
        </div>
      </div>
      <button type="button" disabled title="Disponível em breve" class="btn-secondary text-[13px] px-3 py-2 opacity-50 cursor-not-allowed self-start">
        <i data-lucide="map" class="w-4 h-4"></i>
        Ver no mapa
      </button>
    <?php else: ?>
      <p class="text-gray-400 text-[13px] py-1">Pedido sem localização registrada.</p>
    <?php endif; ?>
  </div>

  <?php if ($pedido['status'] === 'pendente'): ?>
    <?php if (! empty($atendimentoAberto)): ?>
      <div class="w-full p-4 bg-amber-50 border border-amber-200 rounded-xl flex flex-col gap-2">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 bg-amber-100 border border-amber-200 flex items-center justify-center rounded-lg">
            <i data-lucide="triangle-alert" class="w-4 h-4 stroke-amber-700 stroke-[1.5]"></i>
          </div>
          <p class="text-gray-600 text-[13px] font-medium leading-4">Atendimento em andamento</p>
        </div>
        <p class="text-gray-600 text-[12.5px] leading-4">
          Já existe um atendimento em andamento para esta mulher (protocolo
          <strong><?= esc($atendimentoAberto['protocolo']) ?></strong>). Acesse ou continue o atendimento
          existente antes de abrir um novo a partir deste pedido SOS.
        </p>
        <a href="<?= base_url('atendimentos/' . (int) $atendimentoAberto['id']) ?>" class="btn-secondary text-[13px] px-3 py-2 self-start">
          <i data-lucide="arrow-right" class="w-4 h-4"></i>
          Acessar atendimento
        </a>
      </div>
    <?php else: ?>
      <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 bg-blue-50 border border-blue-200 flex items-center justify-center rounded-lg">
            <i data-lucide="send" class="w-4 h-4 stroke-blue-600 stroke-[1.5]"></i>
          </div>
          <p class="text-gray-600 text-[13px] font-medium leading-4">Direcionar para atendimento</p>
        </div>
        <p class="text-gray-500 text-[12.5px] leading-4">
          Use os dados desta mulher para abrir um atendimento vinculado a este pedido SOS. Você poderá completar as informações obrigatórias antes de salvar.
        </p>
        <form method="POST" action="<?= base_url('pedidos-sos/' . (int) $pedido['id'] . '/direcionar') ?>" class="self-start">
          <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2">
            <i data-lucide="send" class="w-4 h-4"></i>
            Direcionar para Atendimento
          </button>
        </form>
      </div>
    <?php endif; ?>
  <?php elseif (! empty($atendimentoOrigem)): ?>
    <div class="w-full p-4 bg-green-50 border border-green-200 rounded-xl flex flex-col gap-2">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-green-100 border border-green-200 flex items-center justify-center rounded-lg">
          <i data-lucide="circle-check" class="w-4 h-4 stroke-green-700 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Pedido direcionado</p>
      </div>
      <p class="text-gray-600 text-[12.5px] leading-4">
        Este pedido SOS foi direcionado para o atendimento
        <strong><?= esc($atendimentoOrigem['protocolo']) ?></strong>.
      </p>
      <a href="<?= base_url('atendimentos/' . (int) $atendimentoOrigem['id']) ?>" class="btn-secondary text-[13px] px-3 py-2 self-start">
        <i data-lucide="arrow-right" class="w-4 h-4"></i>
        Acessar atendimento
      </a>
    </div>
  <?php else: ?>
    <div class="w-full p-4 bg-gray-50 border border-gray-200 rounded-xl">
      <p class="text-gray-500 text-[12.5px] leading-4">Este pedido SOS não está mais pendente de direcionamento.</p>
    </div>
  <?php endif; ?>
</section>
<?= $this->endSection() ?>