<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex justify-between items-center">
    <h1 class="text-gray-600 text-[15px] font-medium">Usuários</h1>
    <a href="<?= base_url('usuarios/novo') ?>" class="btn-primary text-[13px] text-white px-3 py-2">
      <i data-lucide="plus" class="w-4 h-4"></i>
      Novo Usuário
    </a>
  </header>

  <div class="w-full bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl overflow-hidden">
    <div class="w-full overflow-x-auto">
      <table class="w-full text-[13px]">
        <thead>
          <tr class="bg-gray-50 border-b border-gray-200">
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Nome</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">E-mail</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Perfil</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Status</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($usuarios as $u): ?>
            <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
              <td class="py-2.5 px-3 text-gray-600"><?= esc($u['nome']) ?></td>
              <td class="py-2.5 px-3 text-gray-600"><?= esc($u['email']) ?></td>
              <td class="py-2.5 px-3">
                <?php if ($u['perfil'] === 'admin'): ?>
                  <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-red-50 border border-red-200 text-red-700">Administrador</span>
                <?php else: ?>
                  <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-blue-50 border border-blue-200 text-blue-700">Funcionário</span>
                <?php endif; ?>
              </td>
              <td class="py-2.5 px-3">
                <?php if ($u['ativo']): ?>
                  <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-green-50 border border-green-200 text-green-700">Ativo</span>
                <?php else: ?>
                  <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-50 border border-gray-200 text-gray-600">Inativo</span>
                <?php endif; ?>
              </td>
              <td class="py-2.5 px-3">
                <div class="flex items-center gap-1.5">
                  <a href="<?= base_url('usuarios/editar/' . $u['id']) ?>" title="Editar" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition">
                    <i data-lucide="pencil" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
                  </a>
                  <?php if ((int) $u['id'] !== (int) session()->get('usuario_id')): ?>
                    <a href="<?= base_url('usuarios/excluir/' . $u['id']) ?>" title="Excluir" data-confirm data-confirm-message="Excluir este usuário?" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:border-red-300 hover:text-red-600 hover:bg-red-50 transition">
                      <i data-lucide="trash-2" class="w-4 h-4 stroke-[1.5]"></i>
                    </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
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
