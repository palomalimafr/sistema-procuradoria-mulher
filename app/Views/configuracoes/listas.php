<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex items-center gap-2">
    <h1 class="text-gray-600 text-[15px] font-medium">Listas configuráveis</h1>
  </header>

  <p class="text-gray-500 text-[13px] leading-5">
    As opções exibidas nos formulários e filtros dos módulos são listas configuráveis.
    Administre os itens de cada categoria. Itens inativados deixam de aparecer em novos
    registros, mas continuam válidos no histórico.
  </p>

  <?php foreach ($grupos as $grupo): ?>
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <h2 class="text-gray-700 text-[14px] font-medium"><?= esc($grupo['rotulo']) ?></h2>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
        <?php foreach ($grupo['categorias'] as $categoria): ?>
          <a href="<?= base_url('sistema/configuracoes/listas/' . $categoria['categoria']) ?>"
             class="flex items-center justify-between gap-2 px-3 py-2.5 rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition">
            <span class="flex flex-col min-w-0">
              <span class="text-gray-700 text-[13px] font-medium"><?= esc($categoria['rotulo']) ?></span>
              <span class="text-gray-500 text-[12px] leading-4 truncate"><?= esc($categoria['descricao']) ?></span>
            </span>
            <span class="shrink-0 flex items-center gap-2">
              <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-50 border border-gray-200 text-gray-600"><?= (int) $categoria['total'] ?> itens</span>
              <i data-lucide="chevron-right" class="w-4 h-4 stroke-gray-400"></i>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>
</section>
<?= $this->endSection() ?>