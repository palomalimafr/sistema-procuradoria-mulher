<?= $this->extend('layouts/admin') ?>

<?php $e = $encaminhamento; ?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex items-center justify-between">
    <div class="flex items-center gap-2">
      <a href="<?= base_url($e['status'] === 'concluido' ? 'encaminhamentos/concluidos' : 'encaminhamentos/pendentes') ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 transition" title="Voltar">
        <i data-lucide="arrow-left" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
      </a>
      <h1 class="text-gray-600 text-[15px] font-medium">Encaminhamento #<?= (int) $e['id'] ?></h1>
    </div>
    <?php
    $badge = [
        'pendente'           => 'bg-amber-50 border-amber-200 text-amber-700',
        'encaminhado'        => 'bg-blue-50 border-blue-200 text-blue-700',
        'em_atendimento'     => 'bg-purple-50 border-purple-200 text-purple-700',
        'aguardando_retorno' => 'bg-orange-50 border-orange-200 text-orange-700',
        'concluido'          => 'bg-green-50 border-green-200 text-green-700',
        'cancelado'          => 'bg-gray-50 border-gray-200 text-gray-500',
    ];
    ?>
    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium border <?= $badge[$e['status']] ?? 'bg-gray-50 border-gray-200 text-gray-700' ?>">
      <i data-lucide="<?= $e['status'] === 'concluido' ? 'circle-check' : ($e['status'] === 'cancelado' ? 'x' : 'clock') ?>" class="w-3.5 h-3.5"></i>
      <?= esc($status[$e['status']] ?? $e['status']) ?>
    </span>
  </header>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-blue-50 border border-blue-200 flex items-center justify-center rounded-lg">
          <i data-lucide="user" class="w-4 h-4 stroke-blue-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Mulher</p>
        <a href="<?= base_url('mulheres/detalhes/' . (int) $e['mulher_id']) ?>" class="text-primary text-[12px] hover:underline ml-auto">Ficha ›</a>
      </div>

      <div class="flex flex-col divide-y divide-gray-100">
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Nome</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($e['mulher_nome']) ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">CPF</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($e['mulher_cpf'] ?? '—') ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Nascimento</span>
          <span class="text-gray-600 text-[12.5px]"><?= ! empty($e['mulher_nascimento']) ? date('d/m/Y', strtotime($e['mulher_nascimento'])) : '—' ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Bairro / Município</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc(trim(($e['mulher_bairro'] ?? '') . ($e['mulher_municipio'] ? ' · ' . $e['mulher_municipio'] : '')) ?: '—') ?></span>
        </div>
      </div>
    </div>

    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-amber-50 border border-amber-200 flex items-center justify-center rounded-lg">
          <i data-lucide="send" class="w-4 h-4 stroke-amber-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Encaminhamento</p>
      </div>

      <div class="flex flex-col divide-y divide-gray-100">
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Instituição</span>
          <?php if (! empty($e['instituicao_id']) && ! empty($e['instituicao_nome'])): ?>
            <a href="<?= base_url('rede/instituicoes/' . (int) $e['instituicao_id']) ?>" class="text-primary text-[12.5px] hover:underline"><?= esc($e['instituicao_nome']) ?></a>
          <?php else: ?>
            <span class="text-gray-600 text-[12.5px]"><?= esc($orgaos[$e['orgao']] ?? $e['orgao']) ?></span>
          <?php endif; ?>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Serviço</span>
          <span class="text-gray-600 text-[12.5px]"><?= ! empty($e['servico']) ? esc($servicos[$e['servico']] ?? $e['servico']) : '—' ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Atendimento</span>
          <?php if (! empty($e['atendimento_id'])): ?>
            <a href="<?= base_url('atendimentos/' . (int) $e['atendimento_id']) ?>" class="text-primary text-[12.5px] hover:underline font-mono">#<?= esc($e['atendimento_protocolo'] ?? $e['atendimento_id']) ?></a>
          <?php else: ?>
            <span class="text-gray-600 text-[12.5px]">—</span>
          <?php endif; ?>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Data</span>
          <span class="text-gray-600 text-[12.5px]"><?= date('d/m/Y H:i', strtotime($e['data_hora'])) ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Profissional</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($e['profissional_nome'] ?? '—') ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Prioridade</span>
          <span class="px-2 py-0.5 rounded-full text-[11px] font-medium border <?= $e['prioridade'] === 'emergencia' ? 'bg-red-50 border-red-200 text-red-700' : ($e['prioridade'] === 'alta' ? 'bg-amber-50 border-amber-200 text-amber-700' : ($e['prioridade'] === 'media' ? 'bg-yellow-50 border-yellow-200 text-yellow-700' : 'bg-green-50 border-green-200 text-green-700')) ?>">
            <?= esc($prioridades[$e['prioridade']] ?? $e['prioridade']) ?>
          </span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Prazo</span>
          <span class="text-gray-600 text-[12.5px]"><?= ! empty($e['prazo']) ? date('d/m/Y', strtotime($e['prazo'])) : '—' ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Concluído em</span>
          <span class="text-gray-600 text-[12.5px]"><?= ! empty($e['data_conclusao']) ? date('d/m/Y H:i', strtotime($e['data_conclusao'])) : '—' ?></span>
        </div>
      </div>
    </div>
  </div>

  <?php if (! empty($e['motivo']) || ! empty($e['observacoes'])): ?>
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <?php if (! empty($e['motivo'])): ?>
        <div class="flex flex-col gap-1">
          <p class="text-gray-500 text-[12px] font-medium uppercase tracking-wider">Motivo</p>
          <p class="text-gray-600 text-[13px] leading-5 whitespace-pre-line"><?= esc($e['motivo']) ?></p>
        </div>
      <?php endif; ?>

      <?php if (! empty($e['observacoes'])): ?>
        <div class="flex flex-col gap-1 <?= ! empty($e['motivo']) ? 'pt-3 border-t border-gray-100' : '' ?>">
          <p class="text-gray-500 text-[12px] font-medium uppercase tracking-wider">Observações</p>
          <p class="text-gray-600 text-[13px] leading-5 whitespace-pre-line"><?= esc($e['observacoes']) ?></p>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="flex items-center gap-2">
    <a href="<?= base_url('agenda/novo?encaminhamento_id=' . (int) $e['id']) ?>" class="btn-primary text-[13px] text-white px-4 py-2">
      <i data-lucide="calendar-plus" class="w-4 h-4"></i>
      Novo compromisso
    </a>
    <a href="<?= base_url('tarefas/nova?encaminhamento_id=' . (int) $e['id']) ?>" class="btn-secondary text-[13px] px-4 py-2">
      <i data-lucide="list-checks" class="w-4 h-4"></i>
      Nova tarefa
    </a>
  </div>

  <?php if ($e['status'] !== 'concluido' && $e['status'] !== 'cancelado'): ?>
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-gray-100 border border-gray-200 flex items-center justify-center rounded-lg">
          <i data-lucide="route" class="w-4 h-4 stroke-gray-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Acompanhamento do encaminhamento</p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <?php foreach ($fluxoStatus[$e['status']] ?? [] as $i => $proximo): ?>
          <?php if ($proximo === 'cancelado') continue; ?>
          <form method="POST" action="<?= base_url('encaminhamentos/' . (int) $e['id'] . '/status') ?>">
            <input type="hidden" name="status" value="<?= esc($proximo) ?>">
            <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2">
              <i data-lucide="arrow-right" class="w-4 h-4"></i>
              <?= esc($status[$proximo] ?? $proximo) ?>
            </button>
          </form>
        <?php endforeach; ?>

        <form method="POST" action="<?= base_url('encaminhamentos/' . (int) $e['id'] . '/status') ?>" onSubmit="return confirm('Cancelar este encaminhamento?');">
          <input type="hidden" name="status" value="cancelado">
          <button type="submit" class="btn-secondary text-[13px] px-4 py-2">
            <i data-lucide="x" class="w-4 h-4"></i>
            Cancelar encaminhamento
          </button>
        </form>
      </div>
    </div>
  <?php endif; ?>
</section>
<?= $this->endSection() ?>