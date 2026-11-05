<?= $this->extend('layouts/admin') ?>

<?php $av = $avaliacao; ?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex items-center gap-2">
    <a href="<?= base_url('avaliacoes') ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 transition" title="Voltar">
      <i data-lucide="arrow-left" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
    </a>
    <h1 class="text-gray-600 text-[15px] font-medium">Avaliação de risco #<?= (int) $av['id'] ?></h1>
  </header>

  <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
    <div class="flex items-center justify-between gap-2">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-red-50 border border-red-200 flex items-center justify-center rounded-lg">
          <i data-lucide="shield-alert" class="w-4 h-4 stroke-red-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Classificação do profissional</p>
      </div>
      <?php $cor = ['extremo' => 'bg-red-50 border-red-200 text-red-700', 'alto' => 'bg-amber-50 border-amber-200 text-amber-700', 'medio' => 'bg-yellow-50 border-yellow-200 text-yellow-700', 'baixo' => 'bg-green-50 border-green-200 text-green-700'][$av['classificacao']] ?? 'bg-gray-50 border-gray-200 text-gray-600'; ?>
      <span class="px-3 py-1.5 rounded-full text-[13px] font-semibold border <?= $cor ?>"><?= esc($riscos[$av['classificacao']] ?? $av['classificacao']) ?></span>
    </div>

    <div class="flex flex-col divide-y divide-gray-100">
      <div class="flex justify-between py-2">
        <span class="text-gray-400 text-[12px]">Acolhimento</span>
        <a href="<?= base_url('acolhimentos/' . (int) $av['acolhimento_id']) ?>" class="text-primary text-[12.5px] hover:underline">#<?= (int) $av['acolhimento_id'] ?></a>
      </div>
      <div class="flex justify-between py-2">
        <span class="text-gray-400 text-[12px]">Atendimento</span>
        <a href="<?= base_url('atendimentos/' . (int) $av['atendimento_id']) ?>" class="text-primary text-[12.5px] hover:underline font-mono">#<?= esc($av['atendimento_protocolo']) ?></a>
      </div>
      <div class="flex justify-between py-2">
        <span class="text-gray-400 text-[12px]">Mulher</span>
        <span class="text-gray-600 text-[12.5px]"><?= esc($av['mulher_nome']) ?></span>
      </div>
      <div class="flex justify-between py-2">
        <span class="text-gray-400 text-[12px]">Realizada em</span>
        <span class="text-gray-600 text-[12.5px]"><?= date('d/m/Y H:i', strtotime($av['data_hora'])) ?></span>
      </div>
      <div class="flex justify-between py-2">
        <span class="text-gray-400 text-[12px]">Profissional</span>
        <span class="text-gray-600 text-[12.5px]"><?= esc($av['profissional_nome'] ?? '—') ?></span>
      </div>
    </div>

    <?php if (! empty($av['classificacao_sugerida'])): ?>
      <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-2.5 text-[12.5px] text-blue-800">
        Sugestão automática registrada: <strong><?= esc($riscos[$av['classificacao_sugerida']] ?? $av['classificacao_sugerida']) ?></strong>
        <span class="text-blue-600"> — auxílio apenas; a classificação final foi definida pelo profissional.</span>
      </div>
    <?php endif; ?>
  </div>

  <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 bg-gray-100 border border-gray-200 flex items-center justify-center rounded-lg">
        <i data-lucide="list-checks" class="w-4 h-4 stroke-gray-600 stroke-[1.5]"></i>
      </div>
      <p class="text-gray-600 text-[13px] font-medium leading-4">Respostas</p>
    </div>

    <ul class="flex flex-col divide-y divide-gray-100">
      <?php foreach ($perguntas as $campo => $pergunta): ?>
        <li class="flex items-center justify-between gap-2 py-2">
          <span class="text-gray-600 text-[13px]"><?= esc($pergunta) ?></span>
          <span class="shrink-0">
            <?php if (! empty($av[$campo])): ?>
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-green-50 border border-green-200 text-green-700">
                <i data-lucide="check" class="w-3 h-3"></i>
                Sim
              </span>
            <?php else: ?>
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-50 border border-gray-200 text-gray-500">
                <i data-lucide="x" class="w-3 h-3"></i>
                Não
              </span>
            <?php endif; ?>
          </span>
        </li>
      <?php endforeach; ?>
    </ul>

    <?php if (! empty($av['observacoes_profissionais'])): ?>
      <div class="flex flex-col gap-1 pt-3 border-t border-gray-100">
        <p class="text-gray-500 text-[12px] font-medium uppercase tracking-wider">Observações profissionais</p>
        <p class="text-gray-600 text-[13px] leading-5 whitespace-pre-line"><?= esc($av['observacoes_profissionais']) ?></p>
      </div>
    <?php endif; ?>
  </div>
</section>
<?= $this->endSection() ?>