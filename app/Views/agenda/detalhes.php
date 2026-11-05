<?= $this->extend('layouts/admin') ?>

<?php $c = $compromisso; ?>

<?php
$statusCor = [
    'agendado'   => 'bg-blue-50 border-blue-200 text-blue-700',
    'confirmado' => 'bg-purple-50 border-purple-200 text-purple-700',
    'realizado'  => 'bg-green-50 border-green-200 text-green-700',
    'faltou'     => 'bg-amber-50 border-amber-200 text-amber-700',
    'cancelado'  => 'bg-gray-50 border-gray-200 text-gray-500',
    'reagendado' => 'bg-gray-50 border-gray-200 text-gray-400',
];
$iconeStatus = [
    'agendado'   => 'clock',
    'confirmado' => 'calendar-check',
    'realizado'  => 'circle-check',
    'faltou'     => 'user-x',
    'cancelado'  => 'x',
    'reagendado' => 'redo-2',
];
$podeReagendar = in_array($c['status'], $reagendarDe, true);
?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex items-center justify-between">
    <div class="flex items-center gap-2">
      <a href="<?= base_url('agenda') ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 transition" title="Voltar">
        <i data-lucide="arrow-left" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
      </a>
      <h1 class="text-gray-600 text-[15px] font-medium">Compromisso #<?= (int) $c['id'] ?></h1>
    </div>
    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium border <?= $statusCor[$c['status']] ?? 'bg-gray-50 border-gray-200 text-gray-700' ?>">
      <i data-lucide="<?= $iconeStatus[$c['status']] ?? 'clock' ?>" class="w-3.5 h-3.5"></i>
      <?= esc($status[$c['status']] ?? $c['status']) ?>
    </span>
  </header>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-blue-50 border border-blue-200 flex items-center justify-center rounded-lg">
          <i data-lucide="calendar" class="w-4 h-4 stroke-blue-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Compromisso</p>
      </div>

      <div class="flex flex-col divide-y divide-gray-100">
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Tipo</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($tipos[$c['tipo']] ?? $c['tipo']) ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Data</span>
          <span class="text-gray-600 text-[12.5px]"><?= date('d/m/Y', strtotime($c['data'])) ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Horário</span>
          <span class="text-gray-600 text-[12.5px] tabular-nums">
            <?= esc(substr($c['hora_inicio'], 0, 5)) ?><?= ! empty($c['hora_fim']) ? ' às ' . esc(substr($c['hora_fim'], 0, 5)) : '' ?>
          </span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Profissional</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($c['profissional_nome'] ?? '—') ?></span>
        </div>
        <?php if (! empty($c['observacoes'])): ?>
          <div class="flex justify-between py-2 !items-start gap-3">
            <span class="text-gray-400 text-[12px] shrink-0">Observações</span>
            <span class="text-gray-600 text-[12.5px] text-right whitespace-pre-line"><?= esc($c['observacoes']) ?></span>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-purple-50 border border-purple-200 flex items-center justify-center rounded-lg">
          <i data-lucide="link" class="w-4 h-4 stroke-purple-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Vínculos</p>
      </div>

      <div class="flex flex-col divide-y divide-gray-100">
        <div class="flex justify-between py-2 items-center gap-3">
          <span class="text-gray-400 text-[12px]">Mulher</span>
          <?php if (! empty($c['mulher_id'])): ?>
            <a href="<?= base_url('mulheres/detalhes/' . (int) $c['mulher_id']) ?>" class="text-primary text-[12.5px] hover:underline text-right"><?= esc($c['mulher_nome'] ?? 'Ficha') ?></a>
          <?php else: ?>
            <span class="text-gray-600 text-[12.5px]">—</span>
          <?php endif; ?>
        </div>
        <div class="flex justify-between py-2 items-center gap-3">
          <span class="text-gray-400 text-[12px]">Atendimento</span>
          <?php if (! empty($c['atendimento_id'])): ?>
            <a href="<?= base_url('atendimentos/' . (int) $c['atendimento_id']) ?>" class="text-primary text-[12.5px] hover:underline font-mono">#<?= esc($c['atendimento_protocolo'] ?? $c['atendimento_id']) ?></a>
          <?php else: ?>
            <span class="text-gray-600 text-[12.5px]">—</span>
          <?php endif; ?>
        </div>
        <div class="flex justify-between py-2 items-center gap-3">
          <span class="text-gray-400 text-[12px]">Encaminhamento</span>
          <?php if (! empty($c['encaminhamento_id'])): ?>
            <a href="<?= base_url('encaminhamentos/' . (int) $c['encaminhamento_id']) ?>" class="text-primary text-[12.5px] hover:underline text-right">#<?= (int) $c['encaminhamento_id'] ?><?= ! empty($c['encaminhamento_orgao']) ? ' · ' . esc($c['encaminhamento_orgao']) : '' ?></a>
          <?php else: ?>
            <span class="text-gray-600 text-[12.5px]">—</span>
          <?php endif; ?>
        </div>
        <?php if (! empty($c['acolhimento_id'])): ?>
          <div class="flex justify-between py-2 items-center gap-3">
            <span class="text-gray-400 text-[12px]">Acolhimento</span>
            <a href="<?= base_url('acolhimentos/' . (int) $c['acolhimento_id']) ?>" class="text-primary text-[12.5px] hover:underline font-mono">#<?= (int) $c['acolhimento_id'] ?></a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if (! empty($reagendadoPara)): ?>
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex items-center gap-3">
      <div class="w-8 h-8 bg-blue-50 border border-blue-200 flex items-center justify-center rounded-lg">
        <i data-lucide="redo-2" class="w-4 h-4 stroke-blue-600 stroke-[1.5]"></i>
      </div>
      <div class="flex flex-col">
        <p class="text-gray-600 text-[13px] font-medium leading-4">Reagendado</p>
        <p class="text-gray-400 text-[12.5px] leading-4">Este compromisso foi reagendado para o novo compromisso
          <a href="<?= base_url('agenda/' . (int) $reagendadoPara['id']) ?>" class="text-primary hover:underline font-medium">#<?= (int) $reagendadoPara['id'] ?></a>
          em <?= date('d/m/Y', strtotime($reagendadoPara['data'])) ?> às <?= esc(substr($reagendadoPara['hora_inicio'], 0, 5)) ?>.
        </p>
      </div>
    </div>
  <?php endif; ?>

  <?php if (! in_array($c['status'], ['realizado', 'cancelado', 'reagendado'], true)): ?>
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-gray-100 border border-gray-200 flex items-center justify-center rounded-lg">
          <i data-lucide="route" class="w-4 h-4 stroke-gray-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Atualizar situação</p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <?php foreach ($fluxoStatus[$c['status']] ?? [] as $proximo): ?>
          <?php if ($proximo === 'cancelado') continue; ?>
          <form method="POST" action="<?= base_url('agenda/' . (int) $c['id'] . '/status') ?>">
            <input type="hidden" name="status" value="<?= esc($proximo) ?>">
            <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2">
              <i data-lucide="arrow-right" class="w-4 h-4"></i>
              <?= esc($status[$proximo] ?? $proximo) ?>
            </button>
          </form>
        <?php endforeach; ?>

        <?php if (in_array('cancelado', $fluxoStatus[$c['status']] ?? [], true)): ?>
          <form method="POST" action="<?= base_url('agenda/' . (int) $c['id'] . '/status') ?>" onSubmit="return confirm('Cancelar este compromisso?');">
            <input type="hidden" name="status" value="cancelado">
            <button type="submit" class="btn-secondary text-[13px] px-4 py-2">
              <i data-lucide="x" class="w-4 h-4"></i>
              Cancelar
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($podeReagendar): ?>
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-amber-50 border border-amber-200 flex items-center justify-center rounded-lg">
          <i data-lucide="redo-2" class="w-4 h-4 stroke-amber-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Reagendar compromisso</p>
      </div>

      <form method="POST" action="<?= base_url('agenda/' . (int) $c['id'] . '/reagendar') ?>" class="grid grid-cols-1 sm:grid-cols-[auto_auto_auto] gap-3 items-end justify-start">
        <div class="flex flex-col gap-1.5">
          <label for="rg_data" class="label-primary">Nova data *</label>
          <input type="date" id="rg_data" name="data" class="input-primary" required>
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="rg_hora" class="label-primary">Novo horário *</label>
          <input type="time" id="rg_hora" name="hora_inicio" class="input-primary" required>
        </div>
        <button type="submit" class="btn-secondary text-[13px] px-4 py-2 h-11">
          <i data-lucide="redo-2" class="w-4 h-4"></i>
          Reagendar
        </button>
      </form>
      <p class="text-gray-400 text-[11.5px] leading-3.5 pl-0.5 -mt-1">O compromisso atual vira "Reagendado" e permanece como histórico do novo #.</p>
    </div>
  <?php endif; ?>

  <div class="flex items-center gap-2">
    <a href="<?= base_url('agenda/editar/' . (int) $c['id']) ?>" class="btn-secondary text-[13px] px-4 py-2">
      <i data-lucide="pencil" class="w-4 h-4"></i>
      Editar compromisso
    </a>
    <?php if (! empty($c['mulher_id'])): ?>
      <a href="<?= base_url('agenda/novo?mulher_id=' . (int) $c['mulher_id']) ?>" class="btn-secondary text-[13px] px-4 py-2">
        <i data-lucide="calendar-plus" class="w-4 h-4"></i>
        Novo compromisso p/ esta mulher
      </a>
    <?php endif; ?>
  </div>
</section>
<?= $this->endSection() ?>