<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
$temFiltros = $filtros['prioridade'] !== '' || $filtros['origem'] !== '' || $filtros['profissional_id'] > 0
    || $filtros['status'] !== '' || $filtros['bairro'] !== '' || $filtros['idade_min'] > 0
    || $filtros['idade_max'] > 0 || $filtros['data_de'] !== '' || $filtros['data_ate'] !== ''
    || ! empty($filtros['atrasados']);
?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex justify-between items-center">
    <div class="flex items-center gap-2">
      <h1 class="text-gray-600 text-[15px] font-medium">Fila de Atendimento</h1>
      <?php if ($temFiltros): ?>
        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 border border-gray-200 text-gray-600">filtros ativos</span>
      <?php endif; ?>
    </div>
    <a href="<?= base_url('atendimentos/novo') ?>" class="btn-primary text-[13px] text-white px-3 py-2">
      <i data-lucide="plus" class="w-4 h-4"></i>
      Novo atendimento
    </a>
  </header>

  <div class="flex items-center justify-end gap-2 flex-wrap">
    <button type="button" id="btn-filtros" class="btn-secondary text-[13px] px-3 py-2" aria-expanded="false" aria-controls="painel-filtros">
      <i data-lucide="filter" class="w-4 h-4"></i>
      Filtros
    </button>
  </div>

  <form method="GET" action="<?= base_url('atendimentos/fila') ?>" id="painel-filtros" class="hidden w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex-col gap-3">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 bg-gray-100 border border-gray-200 flex items-center justify-center rounded-lg">
        <i data-lucide="filter" class="w-4 h-4 stroke-gray-600 stroke-[1.5]"></i>
      </div>
      <p class="text-gray-600 text-[13px] font-medium leading-4">Filtros</p>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
      <div class="flex flex-col gap-1.5">
        <label for="f_prioridade" class="label-primary">Prioridade</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="f_prioridade" name="prioridade" class="select-primary">
            <option value="">Todas</option>
            <?php foreach ($prioridades as $chave => $rotulo): ?>
              <option value="<?= esc($chave) ?>" <?= $filtros['prioridade'] === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
            <?php endforeach; ?>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
        </div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_origem" class="label-primary">Origem</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="f_origem" name="origem" class="select-primary">
            <option value="">Todas</option>
            <?php foreach ($origens as $chave => $rotulo): ?>
              <option value="<?= esc($chave) ?>" <?= $filtros['origem'] === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
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
        <label for="f_status" class="label-primary">Status</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="f_status" name="status" class="select-primary">
            <option value="">Fila (Novo / Aguardando)</option>
            <?php foreach ($situacoes as $chave => $rotulo): ?>
              <option value="<?= esc($chave) ?>" <?= $filtros['status'] === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
            <?php endforeach; ?>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
        </div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_bairro" class="label-primary">Bairro</label>
        <input type="text" id="f_bairro" name="bairro" value="<?= esc((string) $filtros['bairro']) ?>" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_idade_min" class="label-primary">Idade mínima</label>
        <input type="number" id="f_idade_min" name="idade_min" min="0" max="120" value="<?= $filtros['idade_min'] > 0 ? (int) $filtros['idade_min'] : '' ?>" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_idade_max" class="label-primary">Idade máxima</label>
        <input type="number" id="f_idade_max" name="idade_max" min="0" max="120" value="<?= $filtros['idade_max'] > 0 ? (int) $filtros['idade_max'] : '' ?>" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_data_de" class="label-primary">Data de</label>
        <input type="date" id="f_data_de" name="data_de" value="<?= esc((string) $filtros['data_de']) ?>" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_data_ate" class="label-primary">Data até</label>
        <input type="date" id="f_data_ate" name="data_ate" value="<?= esc((string) $filtros['data_ate']) ?>" class="input-primary">
      </div>

      <div class="flex items-end pb-1 gap-3">
        <label class="flex items-center gap-2 cursor-pointer select-none text-gray-600 text-[13px]">
          <input type="checkbox" name="atrasados" value="1" <?= ! empty($filtros['atrasados']) ? 'checked' : '' ?> class="w-4 h-4 accent-[#a179c3]">
          Somente casos atrasados
        </label>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2 h-11">
        <i data-lucide="filter" class="w-4 h-4"></i>
        Filtrar
      </button>
      <a href="<?= base_url('atendimentos/fila') ?>" class="btn-secondary text-[13px] px-4 py-2 h-11">Limpar</a>
    </div>
  </form>

  <div class="w-full bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl overflow-hidden">
    <div class="w-full overflow-x-auto">
      <table class="w-full text-[13px]">
        <thead>
          <tr class="bg-gray-50 border-b border-gray-200">
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Protocolo</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Mulher</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Data/Hora</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Prioridade</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Situação</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Profissional</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Origem</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Aguardando</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (! empty($atendimentos)): ?>
            <?php foreach ($atendimentos as $a): ?>
              <?php
              $urgente = $a['prioridade'] === 'emergencia';
              $destacado = $urgente ? 'bg-red-50/40' : ($a['prioridade'] === 'alta' ? 'bg-amber-50/30' : '');
              ?>
              <tr class="border-b border-gray-100 last:border-0 <?= $destacado ? $destacado . ' hover:' . $destacado : 'hover:bg-gray-50' ?>">
                <td class="py-2.5 px-3 text-gray-600 font-medium">
                  <a href="<?= base_url('atendimentos/' . (int) $a['id']) ?>" class="text-primary hover:underline">#<?= esc($a['protocolo']) ?></a>
                  <?php if (! empty($a['atrasado'])): ?>
                    <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-red-100 text-red-700">Atrasado</span>
                  <?php endif; ?>
                </td>
                <td class="py-2.5 px-3 text-gray-600"><?= esc($a['mulher_nome']) ?></td>
                <td class="py-2.5 px-3 text-gray-600"><?= date('d/m/Y H:i', strtotime($a['data_hora'])) ?></td>
                <td class="py-2.5 px-3">
                  <?php if ($urgente): ?>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-red-50 border border-red-200 text-red-700">Emergência</span>
                  <?php elseif ($a['prioridade'] === 'alta'): ?>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-amber-50 border border-amber-200 text-amber-700">Alta</span>
                  <?php elseif ($a['prioridade'] === 'media'): ?>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-blue-50 border border-blue-200 text-blue-700">Média</span>
                  <?php else: ?>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-50 border border-gray-200 text-gray-600">Baixa</span>
                  <?php endif; ?>
                </td>
                <td class="py-2.5 px-3">
                  <?php if ($a['situacao'] === 'novo'): ?>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-50 border border-gray-200 text-gray-600">Novo</span>
                  <?php else: ?>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-amber-50 border border-amber-200 text-amber-700"><?= esc($a['situacao_label']) ?></span>
                  <?php endif; ?>
                </td>
                <td class="py-2.5 px-3 text-gray-600"><?= esc($a['profissional_nome'] ?? '—') ?></td>
                <td class="py-2.5 px-3 text-gray-600"><?= esc($a['origem_label']) ?></td>
                <td class="py-2.5 px-3 text-gray-600"><?= esc($a['tempo_aguardando']) ?></td>
                <td class="py-2.5 px-3">
                  <a href="<?= base_url('atendimentos/' . (int) $a['id']) ?>" title="Abrir atendimento" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition">
                    <i data-lucide="eye" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="9" class="py-6 px-3 text-center text-gray-400">Nenhum atendimento na fila com os critérios informados.</td>
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