<?= $this->extend('layouts/admin') ?>

<?php $t = $tarefa; ?>

<?php
$statusCor = [
    'pendente'    => 'bg-blue-50 border-blue-200 text-blue-700',
    'em_andamento' => 'bg-purple-50 border-purple-200 text-purple-700',
    'concluida'   => 'bg-green-50 border-green-200 text-green-700',
    'cancelada'   => 'bg-gray-50 border-gray-200 text-gray-500',
];
$priorCor = [
    'emergencia' => 'bg-red-50 border-red-200 text-red-700',
    'alta'       => 'bg-amber-50 border-amber-200 text-amber-700',
    'media'      => 'bg-yellow-50 border-yellow-200 text-yellow-700',
    'baixa'      => 'bg-green-50 border-green-200 text-green-700',
];
?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex items-center justify-between gap-3">
    <div class="flex items-center gap-2">
      <a href="<?= base_url('tarefas') ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 transition" title="Voltar">
        <i data-lucide="arrow-left" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
      </a>
      <h1 class="text-gray-600 text-[15px] font-medium">Tarefa #<?= (int) $t['id'] ?></h1>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
      <?php if ($atrasada): ?>
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium border bg-red-50 border-red-200 text-red-700">
          <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
          Atrasada
        </span>
      <?php endif; ?>
      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium border <?= $statusCor[$t['status']] ?? 'bg-gray-50 border-gray-200 text-gray-700' ?>">
        <?= esc($status[$t['status']] ?? $t['status']) ?>
      </span>
    </div>
  </header>

  <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
    <div class="flex flex-col gap-1">
      <p class="text-gray-500 text-[12px] font-medium uppercase tracking-wider">Descrição</p>
      <p class="text-gray-700 text-[14px] font-medium leading-5"><?= esc($t['titulo']) ?></p>
    </div>

    <?php if (! empty($t['descricao'])): ?>
      <div class="flex flex-col gap-1">
        <p class="text-gray-500 text-[12px] font-medium uppercase tracking-wider">Detalhes</p>
        <p class="text-gray-600 text-[13px] leading-5 whitespace-pre-line"><?= esc($t['descricao']) ?></p>
      </div>
    <?php endif; ?>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-amber-50 border border-amber-200 flex items-center justify-center rounded-lg">
          <i data-lucide="clipboard-list" class="w-4 h-4 stroke-amber-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Tarefa</p>
      </div>

      <div class="flex flex-col divide-y divide-gray-100">
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Responsável</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($t['responsavel_nome'] ?? '—') ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Prazo</span>
          <span class="text-gray-600 text-[12.5px] <?= $atrasada ? 'text-red-700 font-medium' : '' ?>"><?= ! empty($t['prazo']) ? date('d/m/Y', strtotime($t['prazo'])) : '—' ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Prioridade</span>
          <span class="px-2 py-0.5 rounded-full text-[11px] font-medium border <?= $priorCor[$t['prioridade']] ?? 'bg-gray-50 border-gray-200 text-gray-700' ?>"><?= esc($prioridades[$t['prioridade']] ?? $t['prioridade']) ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Status</span>
          <span class="px-2 py-0.5 rounded-full text-[11px] font-medium border <?= $statusCor[$t['status']] ?? 'bg-gray-50 border-gray-200 text-gray-700' ?>"><?= esc($status[$t['status']] ?? $t['status']) ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Concluída em</span>
          <span class="text-gray-600 text-[12.5px]"><?= ! empty($t['data_conclusao']) ? date('d/m/Y H:i', strtotime($t['data_conclusao'])) : '—' ?></span>
        </div>
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
          <?php if (! empty($t['mulher_id'])): ?>
            <a href="<?= base_url('mulheres/detalhes/' . (int) $t['mulher_id']) ?>" class="text-primary text-[12.5px] hover:underline text-right"><?= esc($t['mulher_nome'] ?? 'Ficha') ?></a>
          <?php else: ?>
            <span class="text-gray-600 text-[12.5px]">—</span>
          <?php endif; ?>
        </div>
        <div class="flex justify-between py-2 items-center gap-3">
          <span class="text-gray-400 text-[12px]">Atendimento</span>
          <?php if (! empty($t['atendimento_id'])): ?>
            <a href="<?= base_url('atendimentos/' . (int) $t['atendimento_id']) ?>" class="text-primary text-[12.5px] hover:underline font-mono">#<?= esc($t['atendimento_protocolo'] ?? $t['atendimento_id']) ?></a>
          <?php else: ?>
            <span class="text-gray-600 text-[12.5px]">—</span>
          <?php endif; ?>
        </div>
        <div class="flex justify-between py-2 items-center gap-3">
          <span class="text-gray-400 text-[12px]">Encaminhamento</span>
          <?php if (! empty($t['encaminhamento_id'])): ?>
            <a href="<?= base_url('encaminhamentos/' . (int) $t['encaminhamento_id']) ?>" class="text-primary text-[12.5px] hover:underline text-right">#<?= (int) $t['encaminhamento_id'] ?><?= ! empty($t['encaminhamento_orgao']) ? ' · ' . esc($t['encaminhamento_orgao']) : '' ?></a>
          <?php else: ?>
            <span class="text-gray-600 text-[12.5px]">—</span>
          <?php endif; ?>
        </div>
        <?php if (! empty($t['acolhimento_id'])): ?>
          <div class="flex justify-between py-2 items-center gap-3">
            <span class="text-gray-400 text-[12px]">Acolhimento</span>
            <a href="<?= base_url('acolhimentos/' . (int) $t['acolhimento_id']) ?>" class="text-primary text-[12.5px] hover:underline font-mono">#<?= (int) $t['acolhimento_id'] ?></a>
          </div>
        <?php endif; ?>
        <?php if (! empty($t['compromisso_id'])): ?>
          <div class="flex justify-between py-2 items-center gap-3">
            <span class="text-gray-400 text-[12px]">Agenda</span>
            <a href="<?= base_url('agenda/' . (int) $t['compromisso_id']) ?>" class="text-primary text-[12.5px] hover:underline font-mono">#<?= (int) $t['compromisso_id'] ?></a>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-gray-100 border border-gray-200 flex items-center justify-center rounded-lg">
          <i data-lucide="route" class="w-4 h-4 stroke-gray-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Atualizar situação</p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <?php foreach ($fluxoStatus[$t['status']] ?? [] as $proximo): ?>
          <?php if ($proximo === 'cancelada') continue; ?>
          <form method="POST" action="<?= base_url('tarefas/' . (int) $t['id'] . '/status') ?>">
            <input type="hidden" name="status" value="<?= esc($proximo) ?>">
            <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2">
              <i data-lucide="arrow-right" class="w-4 h-4"></i>
              <?= esc($status[$proximo] ?? $proximo) ?>
            </button>
          </form>
        <?php endforeach; ?>

        <?php if (in_array('cancelada', $fluxoStatus[$t['status']] ?? [], true)): ?>
          <form method="POST" action="<?= base_url('tarefas/' . (int) $t['id'] . '/status') ?>" onSubmit="return confirm('Cancelar esta tarefa?');">
            <input type="hidden" name="status" value="cancelada">
            <button type="submit" class="btn-secondary text-[13px] px-4 py-2">
              <i data-lucide="x" class="w-4 h-4"></i>
              Cancelar tarefa
            </button>
          </form>
        <?php endif; ?>
      </div>
      <p class="text-gray-400 text-[11.5px] leading-3.5 pl-0.5">O registro é mantido em todas as situações, apenas muda de status.</p>
    </div>
  </div>

  <div class="flex items-center gap-2">
    <a href="<?= base_url('tarefas/editar/' . (int) $t['id']) ?>" class="btn-secondary text-[13px] px-4 py-2">
      <i data-lucide="pencil" class="w-4 h-4"></i>
      Editar tarefa
    </a>
    <?php if (! empty($t['mulher_id'])): ?>
      <a href="<?= base_url('tarefas/nova?mulher_id=' . (int) $t['mulher_id']) ?>" class="btn-secondary text-[13px] px-4 py-2">
        <i data-lucide="list-plus" class="w-4 h-4"></i>
        Nova tarefa p/ esta mulher
      </a>
    <?php endif; ?>
  </div>
</section>
<?= $this->endSection() ?>