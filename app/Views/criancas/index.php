<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex justify-between items-center">
    <h1 class="text-gray-600 text-[15px] font-medium">Crianças Assistidas</h1>
    <a href="<?= base_url('criancas/novo') ?>" class="btn-primary text-[13px] text-white px-3 py-2">
      <i data-lucide="plus" class="w-4 h-4"></i>
      Nova Criança
    </a>
  </header>

  <div class="w-full bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl overflow-hidden">
    <div class="w-full overflow-x-auto">
      <table class="w-full text-[13px]">
        <thead>
          <tr class="bg-gray-50 border-b border-gray-200">
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Nome</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Nascimento</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Sexo</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Responsável (Mãe)</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Telefone</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Status</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (! empty($criancas)): ?>
            <?php foreach ($criancas as $c): ?>
              <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
                <td class="py-2.5 px-3 text-gray-600"><?= esc($c['nome']) ?></td>
                <td class="py-2.5 px-3 text-gray-600"><?= $c['data_nascimento'] ? date('d/m/Y', strtotime($c['data_nascimento'])) : '—' ?></td>
                <td class="py-2.5 px-3 text-gray-600"><?= $c['sexo'] === 'M' ? 'Masculino' : ($c['sexo'] === 'F' ? 'Feminino' : '—') ?></td>
                <td class="py-2.5 px-3 text-gray-600"><?= esc($c['nome_mae'] ?? '—') ?></td>
                <td class="py-2.5 px-3 text-gray-600"><?= esc($c['telefone'] ?? '—') ?></td>
                <td class="py-2.5 px-3">
                  <?php if ($c['status'] === 'ativo'): ?>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-green-50 border border-green-200 text-green-700">Ativo</span>
                  <?php else: ?>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-50 border border-gray-200 text-gray-600">Inativo</span>
                  <?php endif; ?>
                </td>
                <td class="py-2.5 px-3">
                  <div class="flex items-center gap-1.5">
                    <a href="<?= base_url('criancas/' . $c['id']) ?>" title="Detalhes" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition">
                      <i data-lucide="eye" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
                    </a>
                    <a href="<?= base_url('criancas/editar/' . $c['id']) ?>" title="Editar" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition">
                      <i data-lucide="pencil" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
                    </a>
                    <a href="<?= base_url('criancas/excluir/' . $c['id']) ?>" title="Excluir" data-confirm data-confirm-message="Excluir esta criança?" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:border-red-300 hover:text-red-600 hover:bg-red-50 transition">
                      <i data-lucide="trash-2" class="w-4 h-4 stroke-[1.5]"></i>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="py-6 px-3 text-center text-gray-400">Nenhuma criança cadastrada.</td>
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
