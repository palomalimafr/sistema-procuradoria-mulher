<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3" data-categoria="<?= esc($categoria) ?>">
  <header class="flex items-center gap-2">
    <a href="<?= base_url('sistema/configuracoes/listas') ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition" title="Voltar para as listas">
      <i data-lucide="arrow-left" class="w-4 h-4 stroke-[1.5]"></i>
    </a>
    <h1 class="text-gray-600 text-[15px] font-medium"><?= esc($info['rotulo']) ?></h1>
  </header>

  <p class="text-gray-500 text-[13px] leading-5"><?= esc($info['descricao']) ?></p>

  <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
    <div>
      <h2 class="text-gray-700 text-[14px] font-medium">Cadastrar novo item</h2>
      <p class="text-gray-500 text-[12px] leading-4 mt-0.5">A chave não deve ser alterada depois do cadastro; use letras minúsculas, números e sublinhado.</p>
    </div>

    <form action="<?= base_url('sistema/configuracoes/listas/' . $categoria . '/itens') ?>" method="POST" class="flex flex-col sm:flex-row gap-2">
      <div class="flex flex-col gap-1.5 flex-1">
        <label for="chave" class="label-primary">Chave *</label>
        <input type="text" id="chave" name="chave" value="<?= esc(old('chave')) ?>" maxlength="60" placeholder="ex.: novo_padrao" class="input-primary" required>
      </div>
      <div class="flex flex-col gap-1.5 flex-[2]">
        <label for="rotulo" class="label-primary">Rótulo exibido *</label>
        <input type="text" id="rotulo" name="rotulo" value="<?= esc(old('rotulo')) ?>" maxlength="120" placeholder="ex.: Novo padrão" class="input-primary" required>
      </div>
      <div class="flex items-start pt-1.5">
        <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2.5">
          <i data-lucide="plus" class="w-4 h-4"></i>
          Adicionar
        </button>
      </div>
    </form>

    <?php $__errosChave = session()->getFlashdata('errors') ?? session()->getFlashdata('erros'); ?>
    <?php if (! empty($__errosChave)): ?>
      <div class="flex flex-col gap-1 text-[12.5px] text-red-600">
        <?php foreach (is_array($__errosChave) ? $__errosChave : [$__errosChave] as $__e): ?>
          <span><?= esc($__e) ?></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="w-full bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl overflow-hidden">
    <div class="w-full overflow-x-auto">
      <table class="w-full text-[13px]">
        <thead>
          <tr class="bg-gray-50 border-b border-gray-200">
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Rótulo</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Chave</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Status</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($itens === []): ?>
            <tr class="border-b border-gray-100 last:border-0">
              <td colspan="4" class="py-6 px-3 text-center text-gray-400 text-[13px]">Nenhum item cadastrado nesta categoria.</td>
            </tr>
          <?php endif; ?>

          <?php foreach ($itens as $item): ?>
            <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50" data-editavel>
              <td class="py-2.5 px-3 text-gray-700">
                <span data-rotulo-exibir><?= esc($item['rotulo']) ?></span>

                <form data-form-editar action="<?= base_url('sistema/configuracoes/listas/' . $categoria . '/itens/' . $item['id'] . '/renomear') ?>" method="POST" class="hidden items-center gap-1.5">
                  <input type="text" name="rotulo" value="<?= esc($item['rotulo']) ?>" maxlength="120" class="input-primary !h-9 !py-1 text-[13px] min-w-40" required>
                  <button type="submit" title="Salvar" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-green-600 hover:border-green-300 hover:bg-green-50 transition">
                    <i data-lucide="check" class="w-4 h-4 stroke-[1.5]"></i>
                  </button>
                  <button type="button" data-cancelar-edicao title="Cancelar" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 transition">
                    <i data-lucide="x" class="w-4 h-4 stroke-[1.5]"></i>
                  </button>
                </form>
              </td>
              <td class="py-2.5 px-3">
                <code class="text-[12px] text-gray-500 bg-gray-50 border border-gray-200 rounded-md px-1.5 py-0.5"><?= esc($item['chave']) ?></code>
              </td>
              <td class="py-2.5 px-3">
                <?php if ((int) $item['ativo']): ?>
                  <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-green-50 border border-green-200 text-green-700">Ativo</span>
                <?php else: ?>
                  <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-50 border border-gray-200 text-gray-600" title="Válido apenas no histórico">Inativo</span>
                <?php endif; ?>
              </td>
              <td class="py-2.5 px-3">
                <div class="flex items-center gap-1.5">
                  <button type="button" data-toggle-edit title="Renomear rótulo" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition">
                    <i data-lucide="pencil" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
                  </button>

                  <form action="<?= base_url('sistema/configuracoes/listas/' . $categoria . '/itens/' . $item['id'] . '/situacao') ?>" method="POST"
                        onsubmit="return confirm('<?= (int) $item['ativo'] ? 'Inativar' : 'Reativar' ?> este item?\nRegistros antigos continuam exibindo o rótulo.')">
                    <?php if ((int) $item['ativo']): ?>
                      <button type="submit" title="Inativar" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:border-amber-300 hover:text-amber-600 hover:bg-amber-50 transition">
                        <i data-lucide="eye-off" class="w-4 h-4 stroke-[1.5]"></i>
                      </button>
                    <?php else: ?>
                      <button type="submit" title="Reativar" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-green-600 hover:border-green-300 hover:bg-green-50 transition">
                        <i data-lucide="eye" class="w-4 h-4 stroke-[1.5]"></i>
                      </button>
                    <?php endif; ?>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<script>
  (function initEdicaoRotulo() {
    document.querySelectorAll('[data-toggle-edit]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var row = btn.closest('[data-editavel]');
        if (!row) return;
        var span = row.querySelector('[data-rotulo-exibir]');
        var form = row.querySelector('[data-form-editar]');
        span.classList.add('hidden');
        form.classList.remove('hidden');
        form.classList.add('flex');
        var input = form.querySelector('input[name="rotulo"]');
        if (input) input.focus();
      });
    });

    document.querySelectorAll('[data-cancelar-edicao]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var row = btn.closest('[data-editavel]');
        if (!row) return;
        row.querySelector('[data-rotulo-exibir]').classList.remove('hidden');
        var form = row.querySelector('[data-form-editar]');
        form.classList.add('hidden');
        form.classList.remove('flex');
      });
    });

    lucide.createIcons();
  })();
</script>
<?= $this->endSection() ?>