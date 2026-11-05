<?= $this->extend('layouts/admin') ?>

<?php
$p = $processo;
$situacaoCor = [
    'em_andamento' => 'bg-blue-50 border-blue-200 text-blue-700',
    'suspenso'     => 'bg-amber-50 border-amber-200 text-amber-700',
    'encerrado'    => 'bg-gray-50 border-gray-200 text-gray-500',
];
?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex items-center justify-between">
    <div class="flex items-center gap-2">
      <a href="<?= base_url('processos') ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 transition" title="Voltar">
        <i data-lucide="arrow-left" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
      </a>
      <div class="flex items-center gap-2">
        <h1 class="text-gray-600 text-[15px] font-medium"><?= esc($titulo) ?></h1>
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium border <?= $situacaoCor[$p['situacao']] ?? 'bg-gray-50 border-gray-200 text-gray-700' ?>">
          <i data-lucide="<?= $p['situacao'] === 'suspenso' ? 'pause-circle' : ($p['situacao'] === 'encerrado' ? 'circle-check' : 'clock') ?>" class="w-3.5 h-3.5"></i>
          <?= esc($situacoes[$p['situacao']] ?? $p['situacao']) ?>
        </span>
      </div>
    </div>
    <a href="<?= base_url('processos/editar/' . (int) $p['id']) ?>" class="btn-secondary text-[13px] px-4 py-2">
      <i data-lucide="pencil" class="w-4 h-4"></i>
      Editar processo
    </a>
  </header>

  <div class="w-full flex flex-col lg:flex-row gap-3">
    <div class="w-full lg:w-1/2 flex flex-col gap-3">
      <!-- Dados do processo -->
      <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 bg-blue-50 border border-blue-200 flex items-center justify-center rounded-lg">
            <i data-lucide="scale" class="w-4 h-4 stroke-blue-600 stroke-[1.5]"></i>
          </div>
          <p class="text-gray-600 text-[13px] font-medium leading-4">Dados do processo</p>
        </div>
        <div class="flex flex-col divide-y divide-gray-100">
          <div class="flex justify-between py-2 gap-3">
            <span class="text-gray-400 text-[12px] shrink-0">Número do processo</span>
            <span class="text-gray-600 text-[12.5px] text-right break-all"><?= esc($p['numero_processo'] ?? '—') ?></span>
          </div>
          <div class="flex justify-between py-2 gap-3">
            <span class="text-gray-400 text-[12px] shrink-0">Tipo</span>
            <span class="text-gray-600 text-[12.5px]"><?= esc($tipos[$p['tipo']] ?? $p['tipo']) ?></span>
          </div>
          <div class="flex justify-between py-2 gap-3">
            <span class="text-gray-400 text-[12px] shrink-0">Órgão</span>
            <span class="text-gray-600 text-[12.5px]"><?= esc($orgaos[$p['orgao']] ?? $p['orgao'] ?? '—') ?></span>
          </div>
          <div class="flex justify-between py-2 gap-3">
            <span class="text-gray-400 text-[12px] shrink-0">Vara</span>
            <span class="text-gray-600 text-[12.5px]"><?= esc($p['vara'] ?? '—') ?></span>
          </div>
          <div class="flex justify-between py-2 gap-3">
            <span class="text-gray-400 text-[12px] shrink-0">Comarca</span>
            <span class="text-gray-600 text-[12.5px]"><?= esc($p['comarca'] ?? '—') ?></span>
          </div>
          <div class="flex justify-between py-2 gap-3">
            <span class="text-gray-400 text-[12px] shrink-0">Data</span>
            <span class="text-gray-600 text-[12.5px]"><?= ! empty($p['data']) ? date('d/m/Y', strtotime($p['data'])) : '—' ?></span>
          </div>
          <div class="flex justify-between py-2 gap-3">
            <span class="text-gray-400 text-[12px] shrink-0">Responsável</span>
            <span class="text-gray-600 text-[12.5px]"><?= esc($p['responsavel_nome'] ?? '—') ?></span>
          </div>
          <?php if (! empty($p['observacoes'])): ?>
            <div class="pt-3">
              <p class="text-gray-500 text-[12px] font-medium uppercase tracking-wider mb-1">Observações</p>
              <p class="text-gray-600 text-[12.5px] leading-5 whitespace-pre-line"><?= esc($p['observacoes']) ?></p>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Histórico -->
      <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 bg-amber-50 border border-amber-200 flex items-center justify-center rounded-lg">
            <i data-lucide="history" class="w-4 h-4 stroke-amber-600 stroke-[1.5]"></i>
          </div>
          <p class="text-gray-600 text-[13px] font-medium leading-4">Histórico do processo</p>
        </div>

        <?php if (empty($historico)): ?>
          <p class="text-gray-400 text-[12.5px] py-2">Nenhum registro de histórico.</p>
        <?php else: ?>
          <div class="flex flex-col">
            <?php foreach ($historico as $h): ?>
              <div class="relative flex flex-col gap-1 pl-4 pb-4 border-l border-gray-100 last:pb-0">
                <div class="absolute -left-[5px] top-1 w-2 h-2 bg-amber-400 rounded-full"></div>
                <div class="flex items-center justify-between">
                  <span class="text-gray-600 text-[12.5px] font-medium"><?= esc($h['acao'] ?? 'Atualização') ?></span>
                  <span class="text-gray-400 text-[11px]"><?= ! empty($h['created_at']) ? date('d/m/Y H:i', strtotime($h['created_at'])) : '' ?></span>
                </div>
                <?php if (! empty($h['status_anterior']) || ! empty($h['status_novo'])): ?>
                  <p class="text-gray-400 text-[12px]">
                    <?= esc($situacoes[$h['status_anterior']] ?? $h['status_anterior'] ?? '—') ?>
                    <i data-lucide="arrow-right" class="w-3 h-3 stroke-gray-300 inline-block"></i>
                    <?= esc($situacoes[$h['status_novo']] ?? $h['status_novo'] ?? '—') ?>
                  </p>
                <?php endif; ?>
                <?php if (! empty($h['observacao'])): ?>
                  <p class="text-gray-500 text-[12px] leading-4"><?= esc($h['observacao']) ?></p>
                <?php endif; ?>
                <span class="text-gray-300 text-[11px]"><?= esc($h['usuario_nome'] ?? '') ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="w-full lg:w-1/2 flex flex-col gap-3">
      <!-- Mulher -->
      <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 bg-pink-50 border border-pink-200 flex items-center justify-center rounded-lg">
            <i data-lucide="user" class="w-4 h-4 stroke-pink-600 stroke-[1.5]"></i>
          </div>
          <p class="text-gray-600 text-[13px] font-medium leading-4">Mulher</p>
          <a href="<?= base_url('mulheres/detalhes/' . (int) $p['mulher_id']) ?>" class="text-primary text-[12px] hover:underline ml-auto">Ficha ›</a>
        </div>
        <div class="flex flex-col divide-y divide-gray-100">
          <div class="flex justify-between py-2">
            <span class="text-gray-400 text-[12px]">Nome</span>
            <span class="text-gray-600 text-[12.5px]"><?= esc($p['mulher_nome']) ?></span>
          </div>
          <div class="flex justify-between py-2">
            <span class="text-gray-400 text-[12px]">CPF</span>
            <span class="text-gray-600 text-[12.5px]"><?= esc($p['mulher_cpf'] ?? '—') ?></span>
          </div>
        </div>
      </div>

      <!-- Relacionamentos -->
      <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 bg-green-50 border border-green-200 flex items-center justify-center rounded-lg">
            <i data-lucide="link" class="w-4 h-4 stroke-green-600 stroke-[1.5]"></i>
          </div>
          <p class="text-gray-600 text-[13px] font-medium leading-4">Relacionamentos</p>
        </div>

        <div class="flex flex-col divide-y divide-gray-100">
          <div class="py-2">
            <p class="text-gray-400 text-[12px] mb-1">Atendimento</p>
            <?php if (! empty($p['atendimento_id'])): ?>
              <a href="<?= base_url('atendimentos/' . (int) $p['atendimento_id']) ?>" class="text-primary text-[12.5px] hover:underline font-mono">#<?= esc($p['atendimento_protocolo'] ?? $p['atendimento_id']) ?></a>
            <?php else: ?>
              <span class="text-gray-400 text-[12.5px]">Sem vínculo.</span>
            <?php endif; ?>
          </div>

          <div class="py-2">
            <p class="text-gray-400 text-[12px] mb-1">Medida protetiva</p>
            <?php if (! empty($p['medida_protetiva_id'])): ?>
              <div class="flex flex-col gap-0.5">
                <a href="<?= base_url('medidas-protetivas/' . (int) $p['medida_protetiva_id']) ?>" class="text-primary text-[12.5px] hover:underline font-mono">#<?= (int) $p['medida_protetiva_id'] ?><?= ! empty($p['medida_numero']) ? ' · ' . esc($p['medida_numero']) : '' ?></a>
                <?php if (! empty($p['medida_situacao'])): ?>
                  <span class="text-gray-400 text-[11.5px]"><?= esc($situacoesMedida[$p['medida_situacao']] ?? $p['medida_situacao'] ?? '') ?></span>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <span class="text-gray-400 text-[12.5px]">Sem vínculo.</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<?= $this->endSection() ?>