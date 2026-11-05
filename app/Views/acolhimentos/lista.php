<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
$temFiltros = $filtros['q'] !== '' || $filtros['status'] !== '' || $filtros['profissional_id'] > 0
    || $filtros['data_de'] !== '' || $filtros['data_ate'] !== '';
?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex justify-between items-center gap-3">
    <div class="flex items-center gap-2">
      <h1 class="text-gray-600 text-[15px] font-medium">Acolhimentos</h1>
      <?php if ($temFiltros): ?>
        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 border border-gray-200 text-gray-600">filtros ativos</span>
      <?php endif; ?>
    </div>
    <a href="<?= base_url('acolhimentos/novo') ?>" class="btn-primary text-[13px] text-white px-3 py-2">
      <i data-lucide="plus" class="w-4 h-4"></i>
      Novo acolhimento
    </a>
  </header>

  <div class="flex items-center justify-end gap-2 flex-wrap">
    <button type="button" id="btn-filtros" class="btn-secondary text-[13px] px-3 py-2" aria-expanded="false" aria-controls="painel-filtros">
      <i data-lucide="filter" class="w-4 h-4"></i>
      Filtros
    </button>
  </div>

  <form method="GET" action="<?= base_url('acolhimentos') ?>" id="painel-filtros" class="hidden w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex-col gap-3">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 bg-gray-100 border border-gray-200 flex items-center justify-center rounded-lg">
        <i data-lucide="filter" class="w-4 h-4 stroke-gray-600 stroke-[1.5]"></i>
      </div>
      <p class="text-gray-600 text-[13px] font-medium leading-4">Filtros</p>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
      <div class="flex flex-col gap-1.5">
        <label for="f_q" class="label-primary">Atendimento / mulher</label>
        <input type="text" id="f_q" name="q" value="<?= esc((string) $filtros['q']) ?>" placeholder="Protocolo ou nome" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_status" class="label-primary">Status</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="f_status" name="status" class="select-primary">
            <option value="">Todos</option>
            <?php foreach ($status as $chave => $rotulo): ?>
              <option value="<?= esc($chave) ?>" <?= $filtros['status'] === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
            <?php endforeach; ?>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
        </div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_profissional" class="label-primary">Profissional</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="f_profissional" name="profissional" class="select-primary">
            <option value="">Todos</option>
            <?php foreach ($equipe as $u): ?>
              <option value="<?= (int) $u['id'] ?>" <?= (int) $filtros['profissional_id'] === (int) $u['id'] ? 'selected' : '' ?>><?= esc($u['nome']) ?></option>
            <?php endforeach; ?>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
        </div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_data_de" class="label-primary">Data de</label>
        <input type="date" id="f_data_de" name="data_de" value="<?= esc((string) $filtros['data_de']) ?>" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_data_ate" class="label-primary">Data até</label>
        <input type="date" id="f_data_ate" name="data_ate" value="<?= esc((string) $filtros['data_ate']) ?>" class="input-primary">
      </div>
    </div>

    <div class="flex items-center gap-2">
      <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2 h-11">
        <i data-lucide="filter" class="w-4 h-4"></i>
        Filtrar
      </button>
      <a href="<?= base_url('acolhimentos') ?>" class="btn-secondary text-[13px] px-4 py-2 h-11">Limpar</a>
    </div>
  </form>

  <div class="w-full bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl overflow-hidden">
    <div class="w-full overflow-x-auto">
      <table class="w-full text-[13px]">
        <thead>
          <tr class="bg-gray-50 border-b border-gray-200">
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Acolhimento</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Atendimento</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Mulher</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Data/Hora</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Profissional</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Status</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Risco</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (! empty($acolhimentos)): ?>
            <?php foreach ($acolhimentos as $a): ?>
              <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
                <td class="py-2.5 px-3 text-gray-600 font-medium">
                  <a href="<?= base_url('acolhimentos/' . (int) $a['id']) ?>" class="text-primary hover:underline">#<?= (int) $a['id'] ?></a>
                </td>
                <td class="py-2.5 px-3 text-gray-600 font-mono">#<?= esc($a['atendimento_protocolo']) ?></td>
                <td class="py-2.5 px-3 text-gray-600"><?= esc($a['mulher_nome']) ?></td>
                <td class="py-2.5 px-3 text-gray-600"><?= date('d/m/Y H:i', strtotime($a['data_hora'])) ?></td>
                <td class="py-2.5 px-3 text-gray-600"><?= esc($a['profissional_nome'] ?? '—') ?></td>
                <td class="py-2.5 px-3">
                  <?php if ($a['status'] === 'concluido'): ?>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-green-50 border border-green-200 text-green-700">Concluído</span>
                  <?php else: ?>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-amber-50 border border-amber-200 text-amber-700">Em andamento</span>
                  <?php endif; ?>
                </td>
                <td class="py-2.5 px-3">
                  <?php if (! empty($a['risco_identificado']) && isset($riscos[$a['risco_identificado']])): ?>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-50 border border-gray-200 text-gray-700"><?= esc($riscos[$a['risco_identificado']]) ?></span>
                  <?php else: ?>
                    <span class="text-gray-300 text-[12px]">—</span>
                  <?php endif; ?>
                </td>
                <td class="py-2.5 px-3">
                  <a href="<?= base_url('acolhimentos/' . (int) $a['id']) ?>" title="Abrir acolhimento" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition">
                    <i data-lucide="eye" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="py-6 px-3 text-center text-gray-400">Nenhum acolhimento registrado com os critérios informados.</td>
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

<script>
(function () {
  var btn = document.getElementById('btn-filtros');
  var painel = document.getElementById('painel-filtros');
  <?php if ($temFiltros): ?>
  if (btn && painel) {
    painel.classList.remove('hidden');
    painel.classList.add('flex');
    btn.setAttribute('aria-expanded', 'true');
    btn.querySelector('[data-lucide]').setAttribute('data-lucide', 'filter-x');
  }
  <?php endif; ?>
  if (btn && painel) {
    btn.addEventListener('click', function () {
      var aberto = painel.classList.toggle('hidden');
      painel.classList.toggle('flex', !aberto);
      btn.setAttribute('aria-expanded', String(!aberto));
      var icone = btn.querySelector('[data-lucide]');
      if (icone) icone.setAttribute('data-lucide', aberto ? 'filter' : 'filter-x');
      if (window.lucide) window.lucide.createIcons();
    });
  }
  if (window.lucide) window.lucide.createIcons();
})();
</script>
<?= $this->endSection() ?>