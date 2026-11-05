<?= $this->extend('layouts/admin') ?>

<?php $m = $medida; ?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex items-center justify-between">
    <div class="flex items-center gap-2">
      <a href="<?= base_url('medidas-protetivas') ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 transition" title="Voltar">
        <i data-lucide="arrow-left" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
      </a>
      <div class="flex items-center gap-2">
        <h1 class="text-gray-600 text-[15px] font-medium"><?= esc($titulo) ?></h1>
        <?php
        $badge = [
            'solicitada'  => 'bg-amber-50 border-amber-200 text-amber-700',
            'concedida'   => 'bg-blue-50 border-blue-200 text-blue-700',
            'ativa'       => 'bg-green-50 border-green-200 text-green-700',
            'descumprida' => 'bg-red-50 border-red-200 text-red-700',
            'encerrada'   => 'bg-gray-50 border-gray-200 text-gray-500',
            'cancelada'   => 'bg-gray-100 border-gray-200 text-gray-400',
        ];
        ?>
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium border <?= $badge[$m['situacao']] ?? $badge['solicitada'] ?>">
          <i data-lucide="<?= in_array($m['situacao'], ['ativa', 'concedida']) ? 'shield-check' : ($m['situacao'] === 'descumprida' ? 'shield-off' : 'clock') ?>" class="w-3.5 h-3.5"></i>
          <?= esc($situacoes[$m['situacao']] ?? $m['situacao']) ?>
        </span>
      </div>
    </div>
    <a href="<?= base_url('medidas-protetivas/editar/' . (int) $m['id']) ?>" class="btn-secondary text-[13px] px-4 py-2">
      <i data-lucide="pencil" class="w-4 h-4"></i>
      Editar medida
    </a>
  </header>

  <div class="w-full flex flex-col lg:flex-row gap-3">
    <div class="w-full lg:w-1/2 flex flex-col gap-3">
      <!-- Mulher -->
      <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 bg-pink-50 border border-pink-200 flex items-center justify-center rounded-lg">
            <i data-lucide="user" class="w-4 h-4 stroke-pink-600 stroke-[1.5]"></i>
          </div>
          <p class="text-gray-600 text-[13px] font-medium leading-4">Mulher</p>
          <a href="<?= base_url('mulheres/detalhes/' . (int) $m['mulher_id']) ?>" class="text-primary text-[12px] hover:underline ml-auto">Ficha ›</a>
        </div>
        <div class="flex flex-col divide-y divide-gray-100">
          <div class="flex justify-between py-2">
            <span class="text-gray-400 text-[12px]">Nome</span>
            <span class="text-gray-600 text-[12.5px]"><?= esc($m['mulher_nome']) ?></span>
          </div>
          <div class="flex justify-between py-2">
            <span class="text-gray-400 text-[12px]">CPF</span>
            <span class="text-gray-600 text-[12.5px]"><?= esc($m['mulher_cpf'] ?? '—') ?></span>
          </div>
          <div class="flex justify-between py-2">
            <span class="text-gray-400 text-[12px]">Bairro / Município</span>
            <span class="text-gray-600 text-[12.5px]"><?= esc(trim(($m['mulher_bairro'] ?? '') . ($m['mulher_municipio'] ? ' � ' . $m['mulher_municipio'] : '')) ?: '—') ?></span>
          </div>
          <div class="flex justify-between py-2">
            <span class="text-gray-400 text-[12px]">Atendimento</span>
            <span class="text-gray-600 text-[12.5px]"><?= ! empty($m['atendimento_protocolo']) ? '#' . esc($m['atendimento_protocolo']) : '—' ?></span>
          </div>
        </div>
      </div>
    </div>

    <div class="w-full lg:w-1/2 flex flex-col gap-3">
      <!-- Medida -->
      <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 bg-red-50 border border-red-200 flex items-center justify-center rounded-lg">
            <i data-lucide="shield-check" class="w-4 h-4 stroke-red-600 stroke-[1.5]"></i>
          </div>
          <p class="text-gray-600 text-[13px] font-medium leading-4">Medida protetiva</p>
          <span class="ml-auto text-[12px] <?= $m['possui_medida'] ? 'text-gray-600' : 'text-gray-400' ?>"><?= $m['possui_medida'] ? 'Registrada' : 'Sem registro' ?></span>
        </div>
        <div class="flex flex-col divide-y divide-gray-100">
          <div class="flex justify-between py-2">
            <span class="text-gray-400 text-[12px]">Número</span>
            <span class="text-gray-600 text-[12.5px]"><?= esc($m['numero'] ?? '—') ?></span>
          </div>
          <div class="flex justify-between py-2">
            <span class="text-gray-400 text-[12px]">Órgão</span>
            <span class="text-gray-600 text-[12.5px]"><?= esc($orgaos[$m['orgao']] ?? $m['orgao'] ?? '—') ?></span>
          </div>
          <div class="flex justify-between py-2">
            <span class="text-gray-400 text-[12px]">Processo</span>
            <span class="text-gray-600 text-[12.5px]"><?= esc($m['processo'] ?? '—') ?></span>
          </div>
          <div class="flex justify-between py-2">
            <span class="text-gray-400 text-[12px]">Data de validade</span>
            <span class="text-gray-600 text-[12.5px]"><?= ! empty($m['data_validade']) ? date('d/m/Y', strtotime($m['data_validade'])) : '—' ?></span>
          </div>
          <?php if (! empty($m['observacoes'])): ?>
            <div class="pt-3">
              <p class="text-gray-500 text-[12px] font-medium uppercase tracking-wider mb-1">Observações</p>
              <p class="text-gray-600 text-[12.5px] leading-5 whitespace-pre-line"><?= esc($m['observacoes']) ?></p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="w-full flex flex-wrap items-center gap-2">
    <a href="<?= base_url('medidas-protetivas/' . (int) $m['id'] . '/descumprimentos/novo') ?>" class="btn-primary text-[13px] text-white px-4 py-2">
      <i data-lucide="shield-off" class="w-4 h-4"></i>
      Registrar descumprimento
    </a>

    <?php if (! empty($fluxoSituacoes[$m['situacao']] ?? [])): ?>
      <?php foreach ((array) $fluxoSituacoes[$m['situacao']] as $destino): ?>
        <?php if ($destino === 'cancelada') continue; ?>
        <form method="POST" action="<?= base_url('medidas-protetivas/' . (int) $m['id'] . '/situacao') ?>">
          <input type="hidden" name="situacao" value="<?= esc($destino) ?>">
          <button type="submit" class="btn-secondary text-[13px] px-4 py-2">
            <i data-lucide="arrow-right" class="w-4 h-4"></i>
            <?= esc($situacoes[$destino] ?? $destino) ?>
          </button>
        </form>
      <?php endforeach; ?>
      <?php if (in_array('cancelada', (array) $fluxoSituacoes[$m['situacao']], true)): ?>
        <form method="POST" action="<?= base_url('medidas-protetivas/' . (int) $m['id'] . '/situacao') ?>" onSubmit="return confirm('Cancelar esta medida protetiva?');">
          <input type="hidden" name="situacao" value="cancelada">
          <button type="submit" class="btn-secondary text-[13px] px-4 py-2">
            <i data-lucide="x" class="w-4 h-4"></i>
            Cancelar medida
          </button>
        </form>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <!-- Descumprimentos -->
  <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 bg-red-50 border border-red-200 flex items-center justify-center rounded-lg">
        <i data-lucide="shield-off" class="w-4 h-4 stroke-red-600 stroke-[1.5]"></i>
      </div>
      <p class="text-gray-600 text-[13px] font-medium leading-4">Descumprimentos</p>
      <span class="ml-auto px-2 py-0.5 rounded-full text-[11px] font-medium border border-red-200 bg-red-50 text-red-700"><?= (int) ($m['qtd_descumprimentos'] ?? 0) ?></span>
    </div>

    <?php if (empty($descumprimentos)): ?>
      <p class="text-gray-400 text-[12.5px] py-2">Nenhum descumprimento registrado até o momento.</p>
    <?php else: ?>
      <div class="flex flex-col divide-y divide-gray-100">
        <?php foreach ($descumprimentos as $d): ?>
          <div class="py-3 flex flex-col gap-1">
            <div class="flex items-center justify-between">
              <span class="text-gray-600 text-[13px] font-medium"><?= date('d/m/Y', strtotime($d['data_ocorrencia'])) ?></span>
              <span class="text-gray-400 text-[11.5px]"><?= esc($d['usuario_nome'] ?? '') ?></span>
            </div>
            <p class="text-gray-600 text-[12.5px] leading-5"><?= esc($d['descricao']) ?></p>
            <?php if (! empty($d['observacoes'])): ?>
              <p class="text-gray-400 text-[12px] leading-4"><?= esc($d['observacoes']) ?></p>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Historico -->
  <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 bg-amber-50 border border-amber-200 flex items-center justify-center rounded-lg">
        <i data-lucide="history" class="w-4 h-4 stroke-amber-600 stroke-[1.5]"></i>
      </div>
      <p class="text-gray-600 text-[13px] font-medium leading-4">Histórico da medida</p>
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
</section>
<?= $this->endSection() ?>
