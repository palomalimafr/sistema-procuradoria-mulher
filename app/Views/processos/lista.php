<?= $this->extend('layouts/admin') ?>

<?php
$situacaoCor = [
    'em_andamento' => 'bg-blue-50 border-blue-200 text-blue-700',
    'suspenso'     => 'bg-amber-50 border-amber-200 text-amber-700',
    'encerrado'    => 'bg-gray-50 border-gray-200 text-gray-500',
];
$temFiltros = $filtros['mulher'] !== '' || $filtros['numero'] !== '' || $filtros['tipo'] !== ''
    || $filtros['orgao'] !== '' || $filtros['vara'] !== '' || $filtros['comarca'] !== ''
    || $filtros['situacao'] !== '' || $filtros['responsavel'] > 0
    || $filtros['data_de'] !== '' || $filtros['data_ate'] !== '';
?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex justify-between items-center gap-3">
    <div class="flex items-center gap-2">
      <h1 class="text-gray-600 text-[15px] font-medium">Processos</h1>
      <?php if ($temFiltros): ?>
        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 border border-gray-200 text-gray-600">filtros ativos</span>
      <?php endif; ?>
    </div>
    <a href="<?= base_url('processos/nova') ?>" class="btn-primary text-[13px] text-white px-3 py-2">
      <i data-lucide="plus" class="w-4 h-4"></i>
      Novo processo
    </a>
  </header>

  <div class="flex items-center justify-end gap-2 flex-wrap">
    <button type="button" id="btn-filtros" class="btn-secondary text-[13px] px-3 py-2" aria-expanded="false" aria-controls="painel-filtros">
      <i data-lucide="filter" class="w-4 h-4"></i>
      Filtros
    </button>
  </div>

  <!-- FILTROS COMPACTOS (painel recolhível) -->
  <form method="GET" action="<?= base_url('processos') ?>" id="painel-filtros" class="hidden w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex-col gap-3">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 bg-gray-100 border border-gray-200 flex items-center justify-center rounded-lg">
        <i data-lucide="filter" class="w-4 h-4 stroke-gray-600 stroke-[1.5]"></i>
      </div>
      <p class="text-gray-600 text-[13px] font-medium leading-4">Filtros</p>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
      <div class="flex flex-col gap-1.5">
        <label for="f_mulher" class="label-primary">Mulher</label>
        <input type="text" id="f_mulher" name="mulher" value="<?= esc((string) $filtros['mulher']) ?>" placeholder="Nome ou CPF" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_numero" class="label-primary">Número do processo</label>
        <input type="text" id="f_numero" name="numero" value="<?= esc((string) $filtros['numero']) ?>" placeholder="Nº informado" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_tipo" class="label-primary">Tipo</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="f_tipo" name="tipo" class="select-primary">
            <option value="">Todos</option>
            <?php foreach ($tipos as $chave => $rotulo): ?>
              <option value="<?= esc($chave) ?>" <?= $filtros['tipo'] === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
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
        <label for="f_vara" class="label-primary">Vara</label>
        <input type="text" id="f_vara" name="vara" value="<?= esc((string) $filtros['vara']) ?>" placeholder="Vara" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_comarca" class="label-primary">Comarca</label>
        <input type="text" id="f_comarca" name="comarca" value="<?= esc((string) $filtros['comarca']) ?>" placeholder="Comarca" class="input-primary">
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
        <label for="f_responsavel" class="label-primary">Responsável</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="f_responsavel" name="responsavel" class="select-primary">
            <option value="">Todos</option>
            <?php foreach ($equipe as $u): ?>
              <option value="<?= (int) $u['id'] ?>" <?= (int) $filtros['responsavel'] === (int) $u['id'] ? 'selected' : '' ?>><?= esc($u['nome']) ?></option>
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
      <a href="<?= base_url('processos') ?>" class="btn-secondary text-[13px] px-4 py-2 h-11">Limpar</a>
    </div>
  </form>

  <!-- LISTAGEM -->
  <div class="w-full bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl overflow-hidden">
    <div class="w-full overflow-x-auto">
      <table class="w-full text-[13px]">
        <thead>
          <tr class="bg-gray-50 border-b border-gray-200">
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Mulher</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Número do processo</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Tipo</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Órgão</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Vara</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Comarca</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Data</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Situação</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Responsável</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (! empty($processos)): ?>
            <?php foreach ($processos as $p): ?>
              <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
                <td class="py-2.5 px-3">
                  <a href="<?= base_url('mulheres/detalhes/' . (int) $p['mulher_id']) ?>" class="text-primary hover:underline"><?= esc($p['mulher_nome']) ?></a>
                </td>
                <td class="py-2.5 px-3">
                  <a href="<?= base_url('processos/' . (int) $p['id']) ?>" class="text-gray-700 font-medium hover:text-primary hover:underline block truncate max-w-[240px]" title="<?= esc($p['numero_processo']) ?>"><?= esc($p['numero_processo'] ?? '—') ?></a>
                </td>
                <td class="py-2.5 px-3 text-gray-600"><?= esc($tipos[$p['tipo']] ?? $p['tipo']) ?></td>
                <td class="py-2.5 px-3 text-gray-600"><?= esc($orgaos[$p['orgao']] ?? $p['orgao'] ?? '—') ?></td>
                <td class="py-2.5 px-3 text-gray-600"><?= esc($p['vara'] ?? '—') ?></td>
                <td class="py-2.5 px-3 text-gray-600"><?= esc($p['comarca'] ?? '—') ?></td>
                <td class="py-2.5 px-3 text-gray-600"><?= ! empty($p['data']) ? date('d/m/Y', strtotime($p['data'])) : '—' ?></td>
                <td class="py-2.5 px-3">
                  <span class="px-2 py-0.5 rounded-full text-[11px] font-medium border <?= $situacaoCor[$p['situacao']] ?? 'bg-gray-50 border-gray-200 text-gray-700' ?>"><?= esc($situacoes[$p['situacao']] ?? $p['situacao']) ?></span>
                </td>
                <td class="py-2.5 px-3 text-gray-600"><?= esc($p['responsavel_nome'] ?? '—') ?></td>
                <td class="py-2.5 px-3">
                  <div class="flex items-center gap-1">
                    <a href="<?= base_url('processos/' . (int) $p['id']) ?>" title="Visualizar processo" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition">
                      <i data-lucide="eye" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
                    </a>
                    <a href="<?= base_url('processos/editar/' . (int) $p['id']) ?>" title="Editar processo" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition">
                      <i data-lucide="pencil" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="10" class="py-6 px-3 text-center text-gray-400">
                Nenhum processo<?= $temFiltros ? ' com os critérios informados.' : ' registrado ainda.' ?>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if (empty($processos)): ?>
    <p class="text-center text-gray-400 text-[12.5px]">
      <a href="<?= base_url('processos/nova') ?>" class="text-primary hover:underline">Registrar o primeiro processo ›</a>
    </p>
  <?php endif; ?>

  <?php if (! empty($pager) && $pager->getPageCount() > 1): ?>
    <div class="w-full flex items-center justify-center pt-1">
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