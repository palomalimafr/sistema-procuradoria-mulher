<?= $this->extend('layouts/admin') ?>

<?php
$statusCor = [
    'agendado'   => 'bg-blue-50 border-blue-200 text-blue-700',
    'confirmado' => 'bg-purple-50 border-purple-200 text-purple-700',
    'realizado'  => 'bg-green-50 border-green-200 text-green-700',
    'faltou'     => 'bg-amber-50 border-amber-200 text-amber-700',
    'cancelado'  => 'bg-gray-50 border-gray-200 text-gray-500',
    'reagendado' => 'bg-gray-50 border-gray-200 text-gray-400',
];

$formatarData = static fn (string $data): string => date('d/m/Y', strtotime($data));

$temFiltros = $filtros['q'] !== '' || $filtros['tipo'] !== '' || $filtros['status'] !== '' || $filtros['profissional_id'] > 0;
?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex justify-between items-center">
    <div class="flex items-center gap-2">
      <h1 class="text-gray-600 text-[15px] font-medium">Agenda</h1>
      <?php if ($temFiltros): ?>
        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 border border-gray-200 text-gray-600">filtros ativos</span>
      <?php endif; ?>
    </div>
    <a href="<?= base_url('agenda/novo') ?>" class="btn-primary text-[13px] text-white px-3 py-2">
      <i data-lucide="plus" class="w-4 h-4"></i>
      Novo compromisso
    </a>
  </header>

  <!-- ABAS E NAVEGAÇÃO -->
  <div class="w-full bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl p-3 flex flex-col gap-3">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <div class="flex items-center gap-1 border border-gray-200 rounded-lg p-1">
        <?php foreach (['dia' => 'Dia', 'semana' => 'Semana', 'mes' => 'Mês'] as $chave => $rotulo): ?>
          <?php if ($visao === $chave): ?>
            <span class="px-3 py-1.5 rounded-md text-[12.5px] font-medium text-white bg-primary cursor-default"><?= $rotulo ?></span>
          <?php else: ?>
            <a href="<?= base_url('agenda?visao=' . $chave . '&data=' . $data) ?>" class="px-3 py-1.5 rounded-md text-[12.5px] font-medium text-gray-600 hover:bg-gray-50 transition"><?= $rotulo ?></a>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>

      <div class="flex items-center gap-2">
        <?php
        $anterior = $visao === 'mes'
            ? date('Y-m-d', strtotime($de . ' -1 month'))
            : date('Y-m-d', strtotime($de . ' -7 days'));
        $proxima = $visao === 'mes'
            ? date('Y-m-d', strtotime($ate . ' +1 month'))
            : date('Y-m-d', strtotime($de . ' +7 days'));
        ?>
        <a href="<?= base_url('agenda?visao=' . $visao . '&data=' . $anterior) ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 transition" title="Anterior">
          <i data-lucide="chevron-left" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
        </a>
        <a href="<?= base_url('agenda?visao=' . $visao . '&data=' . $hoje) ?>" class="px-3 py-1.5 rounded-lg border border-gray-200 text-[12.5px] font-medium text-gray-600 hover:bg-gray-50 transition">Hoje</a>
        <a href="<?= base_url('agenda?visao=' . $visao . '&data=' . $proxima) ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 transition" title="Próxima">
          <i data-lucide="chevron-right" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
        </a>
        <button type="button" id="btn-filtros" class="btn-secondary text-[13px] px-3 py-2" aria-expanded="false" aria-controls="painel-filtros">
          <i data-lucide="filter" class="w-4 h-4"></i>
          Filtros
        </button>
      </div>
    </div>

    <!-- FAZENDA DO CALENDÁRIO -->
    <div class="text-gray-600 text-[13px] font-medium">
      <?php if ($visao === 'mes'): ?>
        <?= $formatarData($de) ?> a <?= $formatarData($ate) ?>
      <?php elseif ($visao === 'semana'): ?>
        <?= $formatarData($de) ?> a <?= $formatarData($ate) ?>
      <?php else: ?>
        <?= $formatarData($data) ?>
      <?php endif; ?>
      <span class="ml-1 text-gray-400 font-normal">· <?= count($agenda) ?> <?= count($agenda) === 1 ? 'compromisso' : 'compromissos' ?></span>
    </div>

    <div class="flex flex-wrap items-center gap-3">
      <?php foreach ($status as $chave => $rotulo): ?>
        <span class="flex items-center gap-1.5 text-[11.5px] text-gray-500">
          <span class="w-2.5 h-2.5 rounded-full border <?= $statusCor[$chave] ?? 'bg-gray-50 border-gray-200' ?>"></span>
          <?= esc($rotulo) ?>
        </span>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- FILTROS (painel recolhível - padrão Tarefas) -->
  <form method="GET" action="<?= base_url('agenda') ?>" id="painel-filtros" class="hidden w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex-col gap-3">
    <input type="hidden" name="visao" value="<?= esc($visao) ?>">
    <input type="hidden" name="data" value="<?= esc($data) ?>">

    <div class="flex items-center gap-2">
      <div class="w-8 h-8 bg-gray-100 border border-gray-200 flex items-center justify-center rounded-lg">
        <i data-lucide="filter" class="w-4 h-4 stroke-gray-600 stroke-[1.5]"></i>
      </div>
      <p class="text-gray-600 text-[13px] font-medium leading-4">Filtros</p>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
      <div class="flex flex-col gap-1.5">
        <label for="f_q" class="label-primary">Mulher</label>
        <input type="text" id="f_q" name="q" value="<?= esc((string) $filtros['q']) ?>" placeholder="Nome ou CPF" class="input-primary">
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
            <option value="">Todos</option>
            <?php foreach ($status as $chave => $rotulo): ?>
              <option value="<?= esc($chave) ?>" <?= $filtros['status'] === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
            <?php endforeach; ?>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
        </div>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2 h-11">
        <i data-lucide="filter" class="w-4 h-4"></i>
        Filtrar
      </button>
      <a href="<?= base_url('agenda?visao=' . $visao . '&data=' . $data) ?>" class="btn-secondary text-[13px] px-4 py-2 h-11">Limpar</a>
    </div>
  </form>

  <!-- CALENDÁRIO -->
  <div class="w-full bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl overflow-hidden">
    <?php if ($visao === 'dia'): ?>
      <?php $dia = $porDia[$data] ?? []; ?>
      <div class="flex flex-col">
        <?php if (! empty($dia)): ?>
          <?php foreach ($dia as $c): ?>
            <a href="<?= base_url('agenda/' . (int) $c['id']) ?>" class="flex items-center gap-3 px-4 py-3 border-b border-gray-100 last:border-0 hover:bg-gray-50 transition">
              <span class="w-14 shrink-0 text-gray-700 text-[13px] font-medium tabular-nums"><?= esc(substr($c['hora_inicio'], 0, 5)) ?></span>
              <span class="w-2.5 h-2.5 shrink-0 rounded-full border <?= $statusCor[$c['status']] ?? 'bg-gray-50 border-gray-200' ?>"></span>
              <span class="flex flex-col min-w-0 flex-1">
                <strong class="text-gray-700 text-[13px] font-medium truncate"><?= esc($tipos[$c['tipo']] ?? $c['tipo']) ?></strong>
                <small class="text-gray-400 text-[12px] truncate">
                  <?= esc($c['mulher_nome'] ?? 'Sem mulher vinculada') ?>
                  <?= ! empty($c['mulher_nome']) && ! empty($c['encaminhamento_id']) ? '· Enc. #' . (int) $c['encaminhamento_id'] : '' ?>
                </small>
              </span>
              <span class="flex items-center gap-3 shrink-0">
                <span class="hidden md:inline-block px-2 py-0.5 rounded-full text-[11px] font-medium border <?= $statusCor[$c['status']] ?? 'bg-gray-50 border-gray-200 text-gray-700' ?>"><?= esc($status[$c['status']] ?? $c['status']) ?></span>
                <span class="text-gray-400 text-[12px]"><?= esc($c['profissional_nome'] ?? '—') ?></span>
              </span>
            </a>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="py-8 px-4 text-center text-gray-400 text-[13px]">Nenhum compromisso neste dia<?= $temFiltros ? ' com os critérios informados.' : '.' ?></p>
        <?php endif; ?>
      </div>

    <?php elseif ($visao === 'semana'): ?>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-7 divide-y sm:divide-y-0 sm:divide-x divide-gray-100">
        <?php
        $diasSemana = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];
        for ($i = 0; $i < 7; $i++):
            $diaData = date('Y-m-d', strtotime($de . ' +' . $i . ' days'));
            $ehoje = $diaData === $hoje;
        ?>
          <div class="flex flex-col <?= $ehoje ? 'bg-blue-50/40' : '' ?>">
            <div class="px-3 py-2 border-b border-gray-100 flex items-center justify-between">
              <span class="text-[11px] font-semibold uppercase tracking-wider <?= $ehoje ? 'text-primary' : 'text-gray-500' ?>"><?= $diasSemana[$i] ?></span>
              <span class="text-[12px] <?= $ehoje ? 'text-primary font-medium' : 'text-gray-400' ?>"><?= date('d/m', strtotime($diaData)) ?></span>
            </div>
            <div class="flex flex-col gap-1 p-2 min-h-24">
              <?php foreach (($porDia[$diaData] ?? []) as $c): ?>
                <a href="<?= base_url('agenda/' . (int) $c['id']) ?>" class="flex flex-col gap-0.5 px-2 py-1.5 rounded-lg border <?= $statusCor[$c['status']] ?? 'bg-gray-50 border-gray-200' ?> hover:opacity-80 transition" title="<?= esc($tipos[$c['tipo']] ?? $c['tipo']) ?> · <?= esc($c['mulher_nome'] ?? '') ?>">
                  <span class="text-[11px] font-semibold tabular-nums"><?= esc(substr($c['hora_inicio'], 0, 5)) ?> · <?= esc($tipos[$c['tipo']] ?? $c['tipo']) ?></span>
                  <?php if (! empty($c['mulher_nome'])): ?>
                    <span class="text-[11px] leading-3.5 truncate opacity-90"><?= esc($c['mulher_nome']) ?></span>
                  <?php endif; ?>
                </a>
              <?php endforeach; ?>
              <?php if (empty($porDia[$diaData] ?? [])): ?>
                <p class="text-gray-300 text-[11.5px] px-1">Sem compromissos</p>
              <?php endif; ?>
            </div>
          </div>
        <?php endfor; ?>
      </div>

    <?php else: ?>
      <?php
      $primeiro = strtotime($de);
      $diasNoMes = (int) date('t', $primeiro);
      $inicioSemana = (int) date('N', $primeiro);
      ?>
      <div class="hidden lg:grid grid-cols-7 divide-x divide-y divide-gray-100">
        <?php foreach (['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'] as $rotuloLinha): ?>
          <div class="px-3 py-2 text-center text-[11px] font-semibold uppercase tracking-wider text-gray-500 bg-gray-50"><?= $rotuloLinha ?></div>
        <?php endforeach; ?>

        <?php for ($i = 0; $i < $inicioSemana - 1; $i++): ?>
          <div class="min-h-24 bg-gray-50/60"></div>
        <?php endfor; ?>

        <?php for ($d = 1; $d <= $diasNoMes; $d++):
            $diaData = date('Y-m-d', strtotime($de . ' -' . ($inicioSemana - 1) . ' days +' . ($d - 1) . ' days'));
            $ehoje = $diaData === $hoje;
            $itens = $porDia[$diaData] ?? [];
        ?>
          <div class="flex flex-col gap-1 p-2 min-h-24 <?= $ehoje ? 'bg-blue-50/40' : '' ?>">
            <span class="w-6 h-6 flex items-center justify-center rounded-md text-[12px] <?= $ehoje ? 'bg-primary text-white font-semibold' : 'text-gray-600' ?>"><?= $d ?></span>
            <?php foreach (array_slice($itens, 0, 3) as $c): ?>
              <a href="<?= base_url('agenda/' . (int) $c['id']) ?>" class="px-1.5 py-0.5 rounded border <?= $statusCor[$c['status']] ?? 'bg-gray-50 border-gray-200' ?> hover:opacity-80 transition" title="<?= esc($tipos[$c['tipo']] ?? $c['tipo']) ?> · <?= esc($c['mulher_nome'] ?? '') ?>">
                <span class="text-[10.5px] leading-3.5 block truncate"><?= esc(substr($c['hora_inicio'], 0, 5)) ?> <?= esc($tipos[$c['tipo']] ?? $c['tipo']) ?></span>
              </a>
            <?php endforeach; ?>
            <?php if (count($itens) > 3): ?>
              <a href="<?= base_url('agenda?visao=dia&data=' . $diaData) ?>" class="text-[11px] text-primary font-medium px-1.5 hover:underline">+<?= count($itens) - 3 ?> mais</a>
            <?php endif; ?>
          </div>
        <?php endfor; ?>
      </div>

      <div class="lg:hidden flex flex-col divide-y divide-gray-100">
        <?php for ($d = 1; $d <= $diasNoMes; $d++):
            $diaData = date('Y-m-d', strtotime($de . ' -' . ($inicioSemana - 1) . ' days +' . ($d - 1) . ' days'));
            $ehoje = $diaData === $hoje;
            $itens = $porDia[$diaData] ?? [];
        ?>
          <div class="px-4 py-3 flex items-start justify-between gap-3 <?= $ehoje ? 'bg-blue-50/40' : '' ?>">
            <span class="w-9 shrink-0 text-[13px] <?= $ehoje ? 'text-primary font-semibold' : 'text-gray-600' ?>"><?= $d ?></span>
            <div class="flex flex-col gap-1 flex-1 min-w-0">
              <?php if (! empty($itens)): ?>
                <?php foreach (array_slice($itens, 0, 2) as $c): ?>
                  <a href="<?= base_url('agenda/' . (int) $c['id']) ?>" class="flex items-center gap-2 text-[12px] text-gray-600 hover:text-primary">
                    <span class="tabular-nums font-medium"><?= esc(substr($c['hora_inicio'], 0, 5)) ?></span>
                    <span class="truncate"><?= esc($tipos[$c['tipo']] ?? $c['tipo']) ?><?= ! empty($c['mulher_nome']) ? ' · ' . esc($c['mulher_nome']) : '' ?></span>
                  </a>
                <?php endforeach; ?>
                <?php if (count($itens) > 2): ?>
                  <a href="<?= base_url('agenda?visao=dia&data=' . $diaData) ?>" class="text-[12px] text-primary font-medium hover:underline">+<?= count($itens) - 2 ?> mais</a>
                <?php endif; ?>
              <?php else: ?>
                <p class="text-gray-300 text-[12px]">Sem compromissos</p>
              <?php endif; ?>
            </div>
          </div>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </div>

  <?php if (empty($agenda)): ?>
    <p class="text-center text-gray-400 text-[12.5px]">
      <a href="<?= base_url('agenda/novo') ?>" class="text-primary hover:underline">Agendar o primeiro compromisso ›</a>
    </p>
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