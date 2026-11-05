<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
$temFiltros = $filtros['q'] !== '' || $filtros['orgao'] !== '' || $filtros['servico'] !== ''
    || $filtros['profissional_id'] > 0 || $filtros['status'] !== '' || $filtros['prioridade'] !== ''
    || $filtros['data_de'] !== '' || $filtros['data_ate'] !== '' || $filtros['prazo'] !== '';
?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex justify-between items-center">
    <div class="flex items-center gap-2">
      <h1 class="text-gray-600 text-[15px] font-medium"><?= $visao === 'pendentes' ? 'Encaminhamentos pendentes' : 'Encaminhamentos concluídos' ?></h1>
      <?php if ($temFiltros): ?>
        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 border border-gray-200 text-gray-600">filtros ativos</span>
      <?php endif; ?>
    </div>
    <a href="<?= base_url('encaminhamentos/novo') ?>" class="btn-primary text-[13px] text-white px-3 py-2">
      <i data-lucide="plus" class="w-4 h-4"></i>
      Novo encaminhamento
    </a>
  </header>

  <div class="flex items-center justify-end gap-2 flex-wrap">
    <button type="button" id="btn-filtros" class="btn-secondary text-[13px] px-3 py-2" aria-expanded="false" aria-controls="painel-filtros">
      <i data-lucide="filter" class="w-4 h-4"></i>
      Filtros
    </button>
  </div>

  <form method="GET" action="<?= base_url($visao === 'pendentes' ? 'encaminhamentos/pendentes' : 'encaminhamentos/concluidos') ?>" id="painel-filtros" class="hidden w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex-col gap-3">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 bg-gray-100 border border-gray-200 flex items-center justify-center rounded-lg">
        <i data-lucide="filter" class="w-4 h-4 stroke-gray-600 stroke-[1.5]"></i>
      </div>
      <p class="text-gray-600 text-[13px] font-medium leading-4">Filtros</p>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
      <div class="flex flex-col gap-1.5">
        <label for="f_q" class="label-primary">Mulher</label>
        <input type="text" id="f_q" name="q" value="<?= esc((string) $filtros['q']) ?>" placeholder="Nome ou CPF" class="input-primary">
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
        <label for="f_servico" class="label-primary">Serviço</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="f_servico" name="servico" class="select-primary">
            <option value="">Todos</option>
            <?php foreach ($servicos as $chave => $rotulo): ?>
              <option value="<?= esc($chave) ?>" <?= $filtros['servico'] === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
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
            <option value=""><?= $visao === 'pendentes' ? 'Em acompanhamento' : 'Concluídos' ?></option>
            <?php foreach ($status as $chave => $rotulo): ?>
              <?php if ($visao === 'pendentes' && $chave === 'concluido') continue; ?>
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
        <label for="f_data_de" class="label-primary">Data de</label>
        <input type="date" id="f_data_de" name="data_de" value="<?= esc((string) $filtros['data_de']) ?>" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_data_ate" class="label-primary">Data até</label>
        <input type="date" id="f_data_ate" name="data_ate" value="<?= esc((string) $filtros['data_ate']) ?>" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_prazo" class="label-primary">Prazo</label>
        <input type="date" id="f_prazo" name="prazo" value="<?= esc((string) $filtros['prazo']) ?>" class="input-primary">
      </div>
    </div>

    <div class="flex items-center gap-2">
      <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2 h-11">
        <i data-lucide="filter" class="w-4 h-4"></i>
        Filtrar
      </button>
      <a href="<?= base_url($visao === 'pendentes' ? 'encaminhamentos/pendentes' : 'encaminhamentos/concluidos') ?>" class="btn-secondary text-[13px] px-4 py-2 h-11">Limpar</a>
    </div>
  </form>

  <div class="w-full bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl overflow-hidden">
    <div class="w-full overflow-x-auto">
      <table class="w-full text-[13px]">
        <thead>
          <tr class="bg-gray-50 border-b border-gray-200">
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Enc.</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Mulher</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Órgão</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Serviço</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Profissional</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Data</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Prioridade</th>
            <?php if ($visao === 'pendentes'): ?>
              <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Prazo</th>
              <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Status</th>
            <?php else: ?>
              <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Concluído em</th>
              <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Motivo</th>
            <?php endif; ?>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (! empty($encaminhamentos)): ?>
            <?php foreach ($encaminhamentos as $e): ?>
              <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
                <td class="py-2.5 px-3 text-gray-600 font-medium">
                  <a href="<?= base_url('encaminhamentos/' . (int) $e['id']) ?>" class="text-primary hover:underline">#<?= (int) $e['id'] ?></a>
                </td>
                <td class="py-2.5 px-3 text-gray-600"><?= esc($e['mulher_nome']) ?></td>
                <td class="py-2.5 px-3">
                  <?php if (! empty($e['instituicao_id']) && ! empty($e['instituicao_nome'])): ?>
                    <a href="<?= base_url('rede/instituicoes/' . (int) $e['instituicao_id']) ?>" class="text-primary hover:underline"><?= esc($e['instituicao_nome']) ?></a>
                  <?php else: ?>
                    <span class="text-gray-600"><?= esc($orgaos[$e['orgao']] ?? $e['orgao']) ?></span>
                  <?php endif; ?>
                </td>
                <td class="py-2.5 px-3 text-gray-600"><?= ! empty($e['servico']) ? esc($servicos[$e['servico']] ?? $e['servico']) : '—' ?></td>
                <td class="py-2.5 px-3 text-gray-600"><?= esc($e['profissional_nome'] ?? '—') ?></td>
                <td class="py-2.5 px-3 text-gray-600"><?= date('d/m/Y', strtotime($e['data_hora'])) ?></td>
                <td class="py-2.5 px-3">
                  <?php $priorOrd = ['emergencia' => 'bg-red-50 border-red-200 text-red-700', 'alta' => 'bg-amber-50 border-amber-200 text-amber-700', 'media' => 'bg-yellow-50 border-yellow-200 text-yellow-700', 'baixa' => 'bg-green-50 border-green-200 text-green-700']; ?>
                  <span class="px-2 py-0.5 rounded-full text-[11px] font-medium border <?= $priorOrd[$e['prioridade']] ?? 'bg-gray-50 border-gray-200 text-gray-700' ?>"><?= esc($prioridades[$e['prioridade']] ?? $e['prioridade']) ?></span>
                </td>
                <?php if ($visao === 'pendentes'): ?>
                  <td class="py-2.5 px-3 text-gray-600"><?= ! empty($e['prazo']) ? date('d/m/Y', strtotime($e['prazo'])) : '—' ?></td>
                  <td class="py-2.5 px-3">
                    <?php $statusCor = $e['status'] === 'cancelado' ? 'bg-gray-50 border-gray-200 text-gray-500' : ($e['status'] === 'aguardando_retorno' ? 'bg-purple-50 border-purple-200 text-purple-700' : 'bg-amber-50 border-amber-200 text-amber-700'); ?>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium border <?= $statusCor ?>"><?= esc($status[$e['status']] ?? $e['status']) ?></span>
                  </td>
                <?php else: ?>
                  <td class="py-2.5 px-3 text-gray-600"><?= ! empty($e['data_conclusao']) ? date('d/m/Y', strtotime($e['data_conclusao'])) : '—' ?></td>
                  <td class="py-2.5 px-3 text-gray-600 max-w-[200px]">
                    <span class="block truncate" title="<?= esc($e['motivo'] ?? '') ?>"><?= esc(! empty($e['motivo']) ? $e['motivo'] : '—') ?></span>
                  </td>
                <?php endif; ?>
                <td class="py-2.5 px-3">
                  <a href="<?= base_url('encaminhamentos/' . (int) $e['id']) ?>" title="Abrir encaminhamento" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition">
                    <i data-lucide="eye" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="11" class="py-6 px-3 text-center text-gray-400">
                <?= $visao === 'pendentes' ? 'Nenhum encaminhamento em acompanhamento com os critérios informados.' : 'Nenhum encaminhamento concluído com os critérios informados.' ?>
              </td>
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