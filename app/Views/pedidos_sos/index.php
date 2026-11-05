<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex justify-between items-center">
    <h1 class="text-gray-600 text-[15px] font-medium">Pedidos SOS</h1>
    <span class="text-gray-400 text-[12px] leading-4"><?= date('d/m/Y') ?></span>
  </header>

  <div class="w-full bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl overflow-hidden">
    <div class="w-full overflow-x-auto">
      <table class="w-full text-[13px]">
        <thead>
          <tr class="bg-gray-50 border-b border-gray-200">
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Protocolo</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Mulher</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Data/Hora</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Status</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Localização</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (! empty($pedidos)): ?>
            <?php foreach ($pedidos as $p): ?>
              <tr class="border-b border-gray-100 last:border-0 <?= $p['status'] !== 'pendente' ? 'hover:bg-gray-50' : 'bg-red-50/40 hover:bg-red-50/70' ?>">
                <td class="py-2.5 px-3 text-gray-600 font-medium">#<?= (int) $p['id'] ?></td>
                <td class="py-2.5 px-3 text-gray-600"><?= esc($p['nome_mulher']) ?></td>
                <td class="py-2.5 px-3 text-gray-600"><?= $p['data_hora'] ? date('d/m/Y H:i', strtotime($p['data_hora'])) : '—' ?></td>
                <td class="py-2.5 px-3">
                  <?php if ($p['status'] === 'pendente'): ?>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-red-50 border border-red-200 text-red-700">Pendente</span>
                  <?php else: ?>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-50 border border-gray-200 text-gray-600"><?= esc(ucfirst($p['status'])) ?></span>
                  <?php endif; ?>
                </td>
                <td class="py-2.5 px-3">
                  <?php if ($p['latitude'] !== null && $p['longitude'] !== null): ?>
                    <a href="<?= base_url('pedidos-sos/' . (int) $p['id']) ?>" class="inline-flex items-center gap-1.5 text-primary text-[12.5px] hover:underline">
                      <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                      Ver localização
                    </a>
                  <?php else: ?>
                    <span class="text-gray-400 text-[12.5px]">—</span>
                  <?php endif; ?>
                </td>
                <td class="py-2.5 px-3">
                  <div class="flex items-center gap-1.5">
                    <a href="<?= base_url('pedidos-sos/' . (int) $p['id']) ?>" title="Detalhes" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition">
                      <i data-lucide="eye" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="py-6 px-3 text-center text-gray-400">Nenhum pedido SOS encontrado.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if (! empty($pager) && $pager->getPageCount() > 1): ?>
    <div class="flex justify-end">
      <?= $pager->links() ?>
    </div>
  <?php endif; ?>
</section>
<?= $this->endSection() ?>