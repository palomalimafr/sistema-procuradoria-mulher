<?= $this->extend('layouts/admin') ?>

<?php $a = $acolhimento; ?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex items-center justify-between">
    <div class="flex items-center gap-2">
      <a href="<?= base_url('acolhimentos') ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 transition" title="Voltar">
        <i data-lucide="arrow-left" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
      </a>
      <h1 class="text-gray-600 text-[15px] font-medium">Acolhimento #<?= (int) $a['id'] ?></h1>
    </div>
    <?php if ($a['status'] === 'concluido'): ?>
      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-green-50 border border-green-200 text-green-700">
        <i data-lucide="circle-check" class="w-3.5 h-3.5"></i>
        Concluído
      </span>
    <?php else: ?>
      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-amber-50 border border-amber-200 text-amber-700">
        <i data-lucide="loader" class="w-3.5 h-3.5"></i>
        Em andamento
      </span>
    <?php endif; ?>
  </header>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-blue-50 border border-blue-200 flex items-center justify-center rounded-lg">
          <i data-lucide="user" class="w-4 h-4 stroke-blue-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Mulher</p>
      </div>

      <div class="flex flex-col divide-y divide-gray-100">
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Nome</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($a['mulher_nome']) ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">CPF</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($a['mulher_cpf'] ?? '—') ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Nascimento</span>
          <span class="text-gray-600 text-[12.5px]"><?= $a['mulher_nascimento'] ? date('d/m/Y', strtotime($a['mulher_nascimento'])) : '—' ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Bairro / Município</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc(trim(($a['mulher_bairro'] ?? '') . ($a['mulher_municipio'] ? ' · ' . $a['mulher_municipio'] : '')) ?: '—') ?></span>
        </div>
      </div>
    </div>

    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-gray-100 border border-gray-200 flex items-center justify-center rounded-lg">
          <i data-lucide="heart-handshake" class="w-4 h-4 stroke-gray-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Acolhimento</p>
      </div>

      <div class="flex flex-col divide-y divide-gray-100">
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Atendimento</span>
          <a href="<?= base_url('atendimentos/' . (int) $a['atendimento_id']) ?>" class="text-primary text-[12.5px] hover:underline font-mono">#<?= esc($a['atendimento_protocolo']) ?></a>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Data/hora</span>
          <span class="text-gray-600 text-[12.5px]"><?= date('d/m/Y H:i', strtotime($a['data_hora'])) ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Profissional</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($a['profissional_nome'] ?? '—') ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Risco identificado</span>
          <span class="text-gray-600 text-[12.5px]"><?= ! empty($a['risco_identificado']) ? esc($riscos[$a['risco_identificado']] ?? $a['risco_identificado']) : '—' ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Concluído em</span>
          <span class="text-gray-600 text-[12.5px]"><?= ! empty($a['data_conclusao']) ? date('d/m/Y H:i', strtotime($a['data_conclusao'])) : '—' ?></span>
        </div>
      </div>
    </div>
  </div>

  <?php if (! empty($a['motivo_procura']) || ! empty($a['situacao_apresentada']) || ! empty($a['necessidades_imediatas'])): ?>
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <?php if (! empty($a['motivo_procura'])): ?>
        <div class="flex flex-col gap-1">
          <p class="text-gray-500 text-[12px] font-medium uppercase tracking-wider">Motivo da procura</p>
          <p class="text-gray-600 text-[13px] leading-5 whitespace-pre-line"><?= esc($a['motivo_procura']) ?></p>
        </div>
      <?php endif; ?>

      <?php if (! empty($a['situacao_apresentada'])): ?>
        <div class="flex flex-col gap-1 <?= ! empty($a['motivo_procura']) ? 'pt-3 border-t border-gray-100' : '' ?>">
          <p class="text-gray-500 text-[12px] font-medium uppercase tracking-wider">Situação apresentada</p>
          <p class="text-gray-600 text-[13px] leading-5 whitespace-pre-line"><?= esc($a['situacao_apresentada']) ?></p>
        </div>
      <?php endif; ?>

      <?php if (! empty($a['necessidades_imediatas'])): ?>
        <div class="flex flex-col gap-1 <?= ! empty($a['situacao_apresentada']) ? 'pt-3 border-t border-gray-100' : '' ?>">
          <p class="text-gray-500 text-[12px] font-medium uppercase tracking-wider">Necessidades imediatas</p>
          <p class="text-gray-600 text-[13px] leading-5 whitespace-pre-line"><?= esc($a['necessidades_imediatas']) ?></p>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if (! empty($necessidadesMarcadas) || ! empty($a['observacoes'])): ?>
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-purple-50 border border-purple-200 flex items-center justify-center rounded-lg">
          <i data-lucide="list-checks" class="w-4 h-4 stroke-purple-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Necessidades identificadas</p>
      </div>

      <?php if (! empty($necessidadesMarcadas)): ?>
        <div class="flex flex-wrap gap-2">
          <?php foreach ($necessidadesMarcadas as $rotulo => $detalhe): ?>
            <span class="px-2.5 py-1 rounded-full text-[12px] font-medium bg-purple-50 border border-purple-200 text-purple-700">
              <?= esc($rotulo) ?><?= $detalhe !== '' ? ': ' . esc($detalhe) : '' ?>
            </span>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="text-gray-400 text-[13px]">Nenhuma necessidade marcada.</p>
      <?php endif; ?>

      <?php if (! empty($a['observacoes'])): ?>
        <div class="flex flex-col gap-1 pt-3 border-t border-gray-100">
          <p class="text-gray-500 text-[12px] font-medium uppercase tracking-wider">Observações</p>
          <p class="text-gray-600 text-[13px] leading-5 whitespace-pre-line"><?= esc($a['observacoes']) ?></p>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
    <div class="flex items-center justify-between gap-2">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-red-50 border border-red-200 flex items-center justify-center rounded-lg">
          <i data-lucide="shield-alert" class="w-4 h-4 stroke-red-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Avaliação de risco</p>
      </div>
      <?php if ($avaliacao !== null && isset($avaliacao['id'])): ?>
        <a href="<?= base_url('avaliacoes/' . (int) $avaliacao['id']) ?>" class="text-primary text-[13px] hover:underline">Ver avaliação ›</a>
      <?php endif; ?>
    </div>

    <?php if ($avaliacao !== null && isset($avaliacao['id'])): ?>
      <div class="flex flex-col gap-2">
        <div class="flex flex-wrap gap-2">
          <span class="px-2.5 py-1 rounded-full text-[12px] font-medium <?= $avaliacao['classificacao'] === 'extremo' ? 'bg-red-50 border border-red-200 text-red-700' : ($avaliacao['classificacao'] === 'alto' ? 'bg-amber-50 border border-amber-200 text-amber-700' : ($avaliacao['classificacao'] === 'medio' ? 'bg-yellow-50 border border-yellow-200 text-yellow-700' : 'bg-green-50 border border-green-200 text-green-700')) ?>">
            Risco: <?= esc($riscos[$avaliacao['classificacao']] ?? $avaliacao['classificacao']) ?>
          </span>
          <span class="text-gray-400 text-[12px] self-center">em <?= date('d/m/Y H:i', strtotime($avaliacao['data_hora'])) ?></span>
        </div>
        <?php if (! empty($avaliacaoPerguntas) && $avaliacao['classificacao_sugerida'] !== null): ?>
          <p class="text-gray-400 text-[12px]">Sugestão automática registrada: <?= esc($riscos[$avaliacao['classificacao_sugerida']] ?? $avaliacao['classificacao_sugerida']) ?> (sempre conferida pelo profissional).</p>
        <?php endif; ?>
        <?php
        $sim = [];
        foreach ($avaliacaoPerguntas as $campo => $pergunta) {
            if (! empty($avaliacao[$campo])) {
                $sim[] = $pergunta;
            }
        }
        ?>
        <?php if (! empty($sim)): ?>
          <ul class="flex flex-col gap-1">
            <?php foreach ($sim as $pergunta): ?>
              <li class="flex items-start gap-2 text-gray-600 text-[13px]">
                <i data-lucide="check" class="w-4 h-4 mt-0.5 stroke-green-600 stroke-[2] shrink-0"></i>
                <span><?= esc($pergunta) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <p class="text-gray-400 text-[13px]">Nenhuma avaliação registrada para este acolhimento.</p>
      <div>
        <a href="<?= base_url('avaliacoes/novo/' . (int) $a['id']) ?>" class="btn-primary text-[13px] text-white px-4 py-2 inline-flex items-center gap-2">
          <i data-lucide="shield-alert" class="w-4 h-4"></i>
          Avaliar risco
        </a>
      </div>
    <?php endif; ?>
  </div>

  <div class="flex flex-col gap-3">
    <div class="flex items-center gap-2">
      <a href="<?= base_url('acolhimentos/editar/' . (int) $a['id']) ?>" class="btn-secondary text-[13px] px-4 py-2">
        <i data-lucide="pencil" class="w-4 h-4"></i>
        Editar
      </a>
      <?php if ($a['status'] !== 'concluido'): ?>
        <form method="POST" action="<?= base_url('acolhimentos/' . (int) $a['id'] . '/concluir') ?>">
          <button type="submit" class="btn-delete-confirm text-[13px] text-white px-4 py-2">
            <i data-lucide="circle-check" class="w-4 h-4"></i>
            Concluir acolhimento
          </button>
        </form>
      <?php endif; ?>
    </div>

    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-gray-100 border border-gray-200 flex items-center justify-center rounded-lg">
          <i data-lucide="history" class="w-4 h-4 stroke-gray-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Histórico</p>
      </div>

      <?php if (! empty($historico)): ?>
        <ul class="flex flex-col divide-y divide-gray-100">
          <?php foreach ($historico as $h): ?>
            <li class="flex items-start gap-2 py-2">
              <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-50 border border-gray-200 text-gray-600 shrink-0">
                <?= $h['status_anterior'] !== null ? esc($status[$h['status_anterior']] ?? $h['status_anterior']) : '—' ?> → <?= esc($status[$h['status_novo']] ?? $h['status_novo']) ?>
              </span>
              <span class="text-gray-500 text-[12px]"><?= date('d/m/Y H:i', strtotime($h['created_at'])) ?> por <?= esc($h['usuario_nome'] ?? 'equipe') ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="text-gray-400 text-[13px]">Sem histórico de alterações registradas.</p>
      <?php endif; ?>
    </div>
  </div>
</section>
<?= $this->endSection() ?>