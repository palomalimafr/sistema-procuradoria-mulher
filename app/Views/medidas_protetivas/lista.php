<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex justify-between items-center gap-3">
    <div class="flex items-center gap-2">
      <h1 class="text-gray-600 text-[15px] font-medium">Medidas Protetivas</h1>
      <?php
      $temFiltros = $filtros['q'] !== '' || $filtros['situacao'] !== '' || $filtros['orgao'] !== ''
          || $filtros['sol_de'] !== '' || $filtros['sol_ate'] !== ''
          || $filtros['con_de'] !== '' || $filtros['con_ate'] !== ''
          || $filtros['val_de'] !== '' || $filtros['val_ate'] !== ''
          || $filtros['descumprimento'] !== '';
      ?>
      <?php if ($temFiltros): ?>
        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 border border-gray-200 text-gray-600">filtros ativos</span>
      <?php endif; ?>
    </div>
    <a href="<?= base_url('medidas-protetivas/nova') ?>" class="btn-primary text-[13px] text-white px-3 py-2">
      <i data-lucide="plus" class="w-4 h-4"></i>
      Nova medida
    </a>
  </header>

  <?php
  $situacaoCor = [
      'solicitada' => 'bg-blue-50 border-blue-200 text-blue-700',
      'em_analise' => 'bg-amber-50 border-amber-200 text-amber-700',
      'concedida'  => 'bg-green-50 border-green-200 text-green-700',
      'negada'     => 'bg-red-50 border-red-200 text-red-700',
      'revogada'   => 'bg-orange-50 border-orange-200 text-orange-700',
      'encerrada'  => 'bg-gray-50 border-gray-200 text-gray-500',
  ];
  ?>

  <div class="flex items-center justify-end">
    <button type="button" id="btn-filtros" class="btn-secondary text-[13px] px-3 py-2" aria-expanded="false" aria-controls="painel-filtros">
      <i data-lucide="filter" class="w-4 h-4"></i>
      Filtros
    </button>
  </div>

  <!-- FILTROS COMPACTOS (painel recolhivel) -->
  <form method="GET" action="<?= base_url('medidas-protetivas') ?>" id="painel-filtros" class="hidden w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex-col gap-3">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 bg-gray-100 border border-gray-200 flex items-center justify-center rounded-lg">
        <i data-lucide="filter" class="w-4 h-4 stroke-gray-600 stroke-[1.5]"></i>
      </div>
      <p class="text-gray-600 text-[13px] font-medium leading-4">Filtros</p>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
      <div class="flex flex-col gap-1.5">
        <label for="f_q" class="label-primary">Mulher / Número / Processo</label>
        <input type="text" id="f_q" name="q" value="<?= esc((string) $filtros['q']) ?>" placeholder="Nome, CPF, n� ou processo" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_situacao" class="label-primary">Situação</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="f_situacao" name="situacao" class="select-primary">
            <option value="">Todas</option>
            <?php foreach ($situacoes as $chave => $rotulo): ?>
              <option value="<?= esc($chave) ?>" <?= $filtros['situacao'] === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
            <?php endforeach; ?>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
        </div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_orgao" class="label-primary">Órgão</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="f_orgao" name="orgao" class="select-primary">
            <option value="">Todos</option>
            <?php foreach ($orgaos as $chave => $rotulo): ?>
              <option value="<?= esc($chave) ?>" <?= $filtros['orgao'] === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
            <?php endforeach; ?>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
        </div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_descumprimento" class="label-primary">Descumprimento</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="f_descumprimento" name="descumprimento" class="select-primary">
            <option value="">Todos</option>
            <option value="com" <?= $filtros['descumprimento'] === 'com' ? 'selected' : '' ?>>Com descumprimento</option>
            <option value="sem" <?= $filtros['descumprimento'] === 'sem' ? 'selected' : '' ?>>Sem descumprimento</option>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
        </div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_sol_de" class="label-primary">Solicitação de</label>
        <input type="date" id="f_sol_de" name="sol_de" value="<?= esc((string) $filtros['sol_de']) ?>" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_sol_ate" class="label-primary">Solicitação até</label>
        <input type="date" id="f_sol_ate" name="sol_ate" value="<?= esc((string) $filtros['sol_ate']) ?>" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_con_de" class="label-primary">Concessão de</label>
        <input type="date" id="f_con_de" name="con_de" value="<?= esc((string) $filtros['con_de']) ?>" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_con_ate" class="label-primary">Concessão até</label>
        <input type="date" id="f_con_ate" name="con_ate" value="<?= esc((string) $filtros['con_ate']) ?>" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_val_de" class="label-primary">Validade de</label>
        <input type="date" id="f_val_de" name="val_de" value="<?= esc((string) $filtros['val_de']) ?>" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_val_ate" class="label-primary">Validade até</label>
        <input type="date" id="f_val_ate" name="val_ate" value="<?= esc((string) $filtros['val_ate']) ?>" class="input-primary">
      </div>

      <div class="flex items-end gap-2">
        <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2 h-11">
          <i data-lucide="filter" class="w-4 h-4"></i>
          Filtrar
        </button>
        <a href="<?= base_url('medidas-protetivas') ?>" class="btn-secondary text-[13px] px-4 py-2 h-11">Limpar</a>
      </div>
    </div>
  </form>

  <!-- LISTAGEM -->
  <div class="w-full bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl overflow-hidden">
    <div class="w-full overflow-x-auto">
      <table class="w-full text-[13px]">
        <thead>
          <tr class="bg-gray-50 border-b border-gray-200">
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Mulher</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Número</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Solicitação</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Concessão</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Órgão</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Situação</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Validade</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Descumprimentos</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (! empty($medidas)): ?>
            <?php foreach ($medidas as $m): ?>
              <?php $semMedida = empty($m['possui_medida']); ?>
              <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
                <td class="py-2.5 px-3 text-gray-600">
                  <a href="<?= base_url('mulheres/detalhes/' . (int) $m['mulher_id']) ?>" class="text-primary hover:underline"><?= esc($m['mulher_nome']) ?></a>
                </td>
                <td class="py-2.5 px-3">
                  <?php if ($semMedida): ?>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-50 border border-gray-200 text-gray-500">Não possui medida</span>
                  <?php else: ?>
                    <a href="<?= base_url('medidas-protetivas/' . (int) $m['id']) ?>" class="text-gray-700 font-medium hover:text-primary hover:underline"><?= esc(! empty($m['numero']) ? $m['numero'] : '#' . (int) $m['id']) ?></a>
                  <?php endif; ?>
                </td>
                <td class="py-2.5 px-3 text-gray-600"><?= ! empty($m['data_solicitacao']) ? date('d/m/Y', strtotime($m['data_solicitacao'])) : '—' ?></td>
                <td class="py-2.5 px-3 text-gray-600"><?= ! empty($m['data_concessao']) ? date('d/m/Y', strtotime($m['data_concessao'])) : '—' ?></td>
                <td class="py-2.5 px-3 text-gray-600"><?= ! empty($m['orgao']) ? esc($orgaos[$m['orgao']] ?? $m['orgao']) : '—' ?></td>
                <td class="py-2.5 px-3">
                  <?php if ($semMedida): ?>
                    <span class="text-gray-400">—</span>
                  <?php else: ?>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium border <?= $situacaoCor[$m['situacao']] ?? 'bg-gray-50 border-gray-200 text-gray-700' ?>"><?= esc($situacoes[$m['situacao']] ?? $m['situacao']) ?></span>
                  <?php endif; ?>
                </td>
                <td class="py-2.5 px-3 text-gray-600"><?= ! empty($m['data_validade']) ? date('d/m/Y', strtotime($m['data_validade'])) : '—' ?></td>
                <td class="py-2.5 px-3">
                  <?php $qtd = (int) ($m['qtd_descumprimentos'] ?? 0); ?>
                  <?php if ($qtd > 0): ?>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-red-50 border border-red-200 text-red-700"><?= $qtd ?> <?= $qtd === 1 ? 'ocorrência' : 'ocorrências' ?></span>
                  <?php else: ?>
                    <span class="text-gray-400">—</span>
                  <?php endif; ?>
                </td>
                <td class="py-2.5 px-3">
                  <div class="flex items-center gap-1.5">
                    <a href="<?= base_url('medidas-protetivas/' . (int) $m['id']) ?>" title="Visualizar" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition">
                      <i data-lucide="eye" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
                    </a>
                    <a href="<?= base_url('medidas-protetivas/editar/' . (int) $m['id']) ?>" title="Editar" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition">
                      <i data-lucide="pencil" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
                    </a>
                    <?php if (! $semMedida): ?>
                      <a href="<?= base_url('medidas-protetivas/' . (int) $m['id'] . '/descumprimentos/novo') ?>" title="Registrar descumprimento" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition">
                        <i data-lucide="shield-off" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
                      </a>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="9" class="py-6 px-3 text-center text-gray-400">
                Nenhuma medida protetiva<?= $temFiltros ? ' com os critérios informados.' : ' registrada ainda.' ?>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if (empty($medidas)): ?>
    <p class="text-center text-gray-400 text-[12.5px]">
      <a href="<?= base_url('medidas-protetivas/nova') ?>" class="text-primary hover:underline">Registrar a primeira medida protetiva ›</a>
    </p>
  <?php endif; ?>

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