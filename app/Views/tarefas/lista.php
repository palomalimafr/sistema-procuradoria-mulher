<?= $this->extend('layouts/admin') ?>

<?php
$priorCor = [
    'emergencia' => 'bg-red-50 border-red-200 text-red-700',
    'alta'       => 'bg-amber-50 border-amber-200 text-amber-700',
    'media'      => 'bg-yellow-50 border-yellow-200 text-yellow-700',
    'baixa'      => 'bg-green-50 border-green-200 text-green-700',
];
$statusCor = [
    'pendente'    => 'bg-blue-50 border-blue-200 text-blue-700',
    'em_andamento' => 'bg-purple-50 border-purple-200 text-purple-700',
    'concluida'   => 'bg-green-50 border-green-200 text-green-700',
    'cancelada'   => 'bg-gray-50 border-gray-200 text-gray-500',
];
$temFiltros = $filtros['q'] !== '' || $filtros['responsavel_id'] > 0 || $filtros['status'] !== ''
    || $filtros['prioridade'] !== '' || $filtros['data_de'] !== '' || $filtros['data_ate'] !== ''
    || $filtros['atendimento'] !== '' || $filtros['encaminhamento'] !== '';
?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex justify-between items-center gap-3">
    <div class="flex items-center gap-2">
      <h1 class="text-gray-600 text-[15px] font-medium">Tarefas</h1>
      <?php if ($temFiltros): ?>
        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 border border-gray-200 text-gray-600">filtros ativos</span>
      <?php endif; ?>
    </div>
    <a href="<?= base_url('tarefas/nova') ?>" class="btn-primary text-[13px] text-white px-3 py-2">
      <i data-lucide="plus" class="w-4 h-4"></i>
      Nova tarefa
    </a>
  </header>

  <!-- VISÕES (filtros sobre a mesma tabela) -->
  <div class="flex items-center justify-between gap-2 flex-wrap">
    <div class="flex items-center gap-1 border border-gray-200 rounded-lg p-1 bg-white">
      <?php foreach ($visoes as $chave => $rotulo): ?>
        <?php if ($visao === $chave): ?>
          <span class="px-3 py-1.5 rounded-md text-[12.5px] font-medium text-white bg-primary cursor-default"><?= esc($rotulo) ?></span>
        <?php else: ?>
          <a href="<?= base_url('tarefas?visao=' . $chave) ?>" class="px-3 py-1.5 rounded-md text-[12.5px] font-medium text-gray-600 hover:bg-gray-50 transition"><?= esc($rotulo) ?></a>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>

    <button type="button" id="btn-filtros" class="btn-secondary text-[13px] px-3 py-2" aria-expanded="false" aria-controls="painel-filtros">
      <i data-lucide="filter" class="w-4 h-4"></i>
      Filtros
    </button>
  </div>

  <!-- FILTROS COMPACTOS (painel recolhível) -->
  <form method="GET" action="<?= base_url('tarefas') ?>" id="painel-filtros" class="hidden w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex-col gap-3">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 bg-gray-100 border border-gray-200 flex items-center justify-center rounded-lg">
        <i data-lucide="filter" class="w-4 h-4 stroke-gray-600 stroke-[1.5]"></i>
      </div>
      <p class="text-gray-600 text-[13px] font-medium leading-4">Filtros</p>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
      <div class="flex flex-col gap-1.5">
        <label for="f_q" class="label-primary">Descrição / Mulher</label>
        <input type="text" id="f_q" name="q" value="<?= esc((string) $filtros['q']) ?>" placeholder="Título, nome ou CPF" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_responsavel" class="label-primary">Responsável</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="f_responsavel" name="responsavel" class="select-primary">
            <option value="">Todos</option>
            <?php foreach ($equipe as $u): ?>
              <option value="<?= (int) $u['id'] ?>" <?= (int) $filtros['responsavel_id'] === (int) $u['id'] ? 'selected' : '' ?>><?= esc($u['nome']) ?></option>
            <?php endforeach; ?>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
        </div>
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
        <label for="f_atendimento" class="label-primary">Atendimento</label>
        <input type="text" id="f_atendimento" name="atendimento" value="<?= esc((string) $filtros['atendimento']) ?>" placeholder="Protocolo" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_encaminhamento" class="label-primary">Encaminhamento</label>
        <input type="text" id="f_encaminhamento" name="encaminhamento" value="<?= esc((string) $filtros['encaminhamento']) ?>" placeholder="Nº ou órgão" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_data_de" class="label-primary">Prazo de</label>
        <input type="date" id="f_data_de" name="data_de" value="<?= esc((string) $filtros['data_de']) ?>" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_data_ate" class="label-primary">Prazo até</label>
        <input type="date" id="f_data_ate" name="data_ate" value="<?= esc((string) $filtros['data_ate']) ?>" class="input-primary">
      </div>
    </div>

    <div class="flex items-center gap-2">
      <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2 h-11">
        <i data-lucide="filter" class="w-4 h-4"></i>
        Filtrar
      </button>
      <a href="<?= base_url('tarefas') ?>" class="btn-secondary text-[13px] px-4 py-2 h-11">Limpar</a>
    </div>
  </form>

  <!-- LISTAGEM -->
  <div class="w-full bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl overflow-hidden">
    <div class="w-full overflow-x-auto">
      <table class="w-full text-[13px]">
        <thead>
          <tr class="bg-gray-50 border-b border-gray-200">
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Descrição</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Mulher</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Atendimento</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Responsável</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Prazo</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Prioridade</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Status</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Concluída em</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (! empty($tarefas)): ?>
            <?php foreach ($tarefas as $t): ?>
              <?php $atrasada = $t['prazo'] !== null && $t['prazo'] < $hoje && in_array($t['status'], ['pendente', 'em_andamento'], true); ?>
              <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
                <td class="py-2.5 px-3">
                  <a href="<?= base_url('tarefas/' . (int) $t['id']) ?>" class="text-gray-700 font-medium hover:text-primary hover:underline block truncate max-w-[240px]" title="<?= esc($t['titulo']) ?>"><?= esc($t['titulo']) ?></a>
                  <?php if ($atrasada): ?>
                    <span class="inline-flex items-center gap-1 mt-0.5 px-1.5 py-0.5 rounded-full text-[10.5px] font-semibold bg-red-50 border border-red-200 text-red-700">
                      <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                      Atrasada
                    </span>
                  <?php endif; ?>
                </td>
                <td class="py-2.5 px-3 text-gray-600">
                  <?php if (! empty($t['mulher_nome'])): ?>
                    <a href="<?= base_url('mulheres/detalhes/' . (int) $t['mulher_id']) ?>" class="text-primary hover:underline"><?= esc($t['mulher_nome']) ?></a>
                  <?php else: ?>
                    —
                  <?php endif; ?>
                </td>
                <td class="py-2.5 px-3 text-gray-600">
                  <?php if (! empty($t['atendimento_id'])): ?>
                    <a href="<?= base_url('atendimentos/' . (int) $t['atendimento_id']) ?>" class="text-primary hover:underline font-mono">#<?= esc($t['atendimento_protocolo'] ?? $t['atendimento_id']) ?></a>
                  <?php else: ?>
                    —
                  <?php endif; ?>
                </td>
                <td class="py-2.5 px-3 text-gray-600"><?= esc($t['responsavel_nome'] ?? '—') ?></td>
                <td class="py-2.5 px-3">
                  <?php if (! empty($t['prazo'])): ?>
                    <span class="<?= $atrasada ? 'text-red-700 font-medium' : 'text-gray-600' ?>"><?= date('d/m/Y', strtotime($t['prazo'])) ?></span>
                  <?php else: ?>
                    <span class="text-gray-400">—</span>
                  <?php endif; ?>
                </td>
                <td class="py-2.5 px-3">
                  <span class="px-2 py-0.5 rounded-full text-[11px] font-medium border <?= $priorCor[$t['prioridade']] ?? 'bg-gray-50 border-gray-200 text-gray-700' ?>"><?= esc($prioridades[$t['prioridade']] ?? $t['prioridade']) ?></span>
                </td>
                <td class="py-2.5 px-3">
                  <span class="px-2 py-0.5 rounded-full text-[11px] font-medium border <?= $statusCor[$t['status']] ?? 'bg-gray-50 border-gray-200 text-gray-700' ?>"><?= esc($status[$t['status']] ?? $t['status']) ?></span>
                </td>
                <td class="py-2.5 px-3 text-gray-600"><?= ! empty($t['data_conclusao']) ? date('d/m/Y H:i', strtotime($t['data_conclusao'])) : '—' ?></td>
                <td class="py-2.5 px-3">
                  <a href="<?= base_url('tarefas/' . (int) $t['id']) ?>" title="Abrir tarefa" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition">
                    <i data-lucide="eye" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="9" class="py-6 px-3 text-center text-gray-400">
                Nenhuma tarefa<?= $visao !== 'todas' ? ' ' . esc(strtolower($visoes[$visao])) : '' ?><?= $temFiltros ? ' com os critérios informados.' : ' registrada ainda.' ?>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if (empty($tarefas)): ?>
    <p class="text-center text-gray-400 text-[12.5px]">
      <a href="<?= base_url('tarefas/nova') ?>" class="text-primary hover:underline">Registrar a primeira tarefa ›</a>
    </p>
  <?php endif; ?>
</section>

<script>
(function () {
  var btn = document.getElementById('btn-filtros');
  var painel = document.getElementById('painel-filtros');
  <?php if ($visao !== 'todas' || $temFiltros): ?>
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