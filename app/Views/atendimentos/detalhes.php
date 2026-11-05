<?= $this->extend('layouts/admin') ?>

<?php $a = $atendimento; ?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex items-center justify-between">
    <div class="flex items-center gap-2">
      <a href="<?= base_url('atendimentos/fila') ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 transition" title="Voltar">
        <i data-lucide="arrow-left" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
      </a>
      <h1 class="text-gray-600 text-[15px] font-medium">Atendimento <?= esc($a['protocolo']) ?></h1>
    </div>
    <?php if ($a['situacao'] === 'encerrado'): ?>
      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-gray-50 border border-gray-200 text-gray-600">
        <i data-lucide="circle-check" class="w-3.5 h-3.5"></i>
        Encerrado
      </span>
    <?php elseif ($a['prioridade'] === 'emergencia'): ?>
      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-red-50 border border-red-200 text-red-700">
        <i data-lucide="octagon-alert" class="w-3.5 h-3.5"></i>
        Emergência
      </span>
    <?php endif; ?>
  </header>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-blue-50 border border-blue-200 flex items-center justify-center rounded-lg">
          <i data-lucide="user" class="w-4 h-4 stroke-blue-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Mulher atendida</p>
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
          <i data-lucide="briefcase" class="w-4 h-4 stroke-gray-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Dados do atendimento</p>
      </div>

      <div class="flex flex-col divide-y divide-gray-100">
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Protocolo</span>
          <span class="text-gray-600 text-[12.5px] font-mono"><?= esc($a['protocolo']) ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Data/Hora</span>
          <span class="text-gray-600 text-[12.5px]"><?= date('d/m/Y H:i', strtotime($a['data_hora'])) ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Profissional</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($a['profissional_nome'] ?? '—') ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Unidade / setor</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($a['unidade_setor'] ?? '—') ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Forma de atendimento</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($formas[$a['forma_atendimento']] ?? $a['forma_atendimento']) ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Origem</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($origens[$a['origem']] ?? $a['origem']) ?><?= ! empty($a['origem_outra']) ? ' — ' . esc($a['origem_outra']) : '' ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Motivo</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($a['motivo_atendimento'] ?? '—') ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Classificação</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($a['classificacao'] ?? '—') ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Prioridade</span>
          <span>
            <?php if ($a['prioridade'] === 'emergencia'): ?>
              <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-red-50 border border-red-200 text-red-700">Emergência</span>
            <?php elseif ($a['prioridade'] === 'alta'): ?>
              <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-amber-50 border border-amber-200 text-amber-700">Alta</span>
            <?php elseif ($a['prioridade'] === 'media'): ?>
              <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-blue-50 border border-blue-200 text-blue-700">Média</span>
            <?php else: ?>
              <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-50 border border-gray-200 text-gray-600">Baixa</span>
            <?php endif; ?>
          </span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Situação</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($situacoes[$a['situacao']] ?? $a['situacao']) ?></span>
        </div>
      </div>
    </div>
  </div>

  <?php if (! empty($a['descricao_caso']) || ! empty($a['observacoes'])): ?>
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <?php if (! empty($a['descricao_caso'])): ?>
        <div class="flex flex-col gap-1">
          <p class="text-gray-500 text-[12px] font-medium uppercase tracking-wider">Descrição do caso</p>
          <p class="text-gray-600 text-[13px] leading-5 whitespace-pre-line"><?= esc($a['descricao_caso']) ?></p>
        </div>
      <?php endif; ?>

      <?php if (! empty($a['observacoes'])): ?>
        <div class="flex flex-col gap-1 <?= ! empty($a['descricao_caso']) ? 'pt-3 border-t border-gray-100' : '' ?>">
          <p class="text-gray-500 text-[12px] font-medium uppercase tracking-wider">Observações</p>
          <p class="text-gray-600 text-[13px] leading-5 whitespace-pre-line"><?= esc($a['observacoes']) ?></p>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

    <div class="flex flex-col gap-3">
    <div class="flex items-center gap-2">
      <?php if (! empty($a['caso_id'])): ?>
        <a href="<?= base_url('casos/' . (int) $a['caso_id']) ?>" class="btn-secondary text-[13px] px-4 py-2"><i data-lucide="folder-open" class="w-4 h-4"></i>Ver caso</a>
      <?php else: ?>
        <a href="<?= base_url('casos/novo/atendimento/' . (int) $a['id']) ?>" class="btn-secondary text-[13px] px-4 py-2"><i data-lucide="folder-plus" class="w-4 h-4"></i>Abrir caso</a>
      <?php endif; ?>
      <a href="<?= base_url('acolhimentos/novo?atendimento_id=' . (int) $a['id']) ?>" class="btn-primary text-[13px] text-white px-4 py-2">
        <i data-lucide="heart-handshake" class="w-4 h-4"></i>
        Novo acolhimento
      </a>
      <a href="<?= base_url('encaminhamentos/novo?atendimento_id=' . (int) $a['id']) ?>" class="btn-secondary text-[13px] px-4 py-2">
        <i data-lucide="send" class="w-4 h-4"></i>
        Novo encaminhamento
      </a>
      <a href="<?= base_url('agenda/novo?atendimento_id=' . (int) $a['id'] . '&mulher_id=' . (int) $a['mulher_id']) ?>" class="btn-secondary text-[13px] px-4 py-2">
        <i data-lucide="calendar-plus" class="w-4 h-4"></i>
        Novo compromisso
      </a>
      <a href="<?= base_url('tarefas/nova?atendimento_id=' . (int) $a['id'] . '&mulher_id=' . (int) $a['mulher_id']) ?>" class="btn-secondary text-[13px] px-4 py-2">
        <i data-lucide="list-checks" class="w-4 h-4"></i>
        Nova tarefa
      </a>
    </div>

    <?php if (in_array($a['situacao'], ['novo', 'aguardando_atendimento'], true)): ?>
      <form method="POST" action="<?= base_url('atendimentos/' . (int) $a['id'] . '/iniciar') ?>">
        <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2">
          <i data-lucide="play" class="w-4 h-4"></i>
          Iniciar atendimento
        </button>
      </form>
    <?php endif; ?>

    <?php if ($a['situacao'] !== 'encerrado'): ?>
      <form method="POST" action="<?= base_url('atendimentos/' . (int) $a['id'] . '/encerrar') ?>" class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 bg-green-50 border border-green-200 flex items-center justify-center rounded-lg">
            <i data-lucide="circle-check" class="w-4 h-4 stroke-green-600 stroke-[1.5]"></i>
          </div>
          <p class="text-gray-600 text-[13px] font-medium leading-4">Encerrar atendimento</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
          <div class="flex flex-col gap-1.5">
            <label for="motivo_encerramento" class="label-primary">Motivo do encerramento *</label>
            <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
              <select id="motivo_encerramento" name="motivo_encerramento" required class="select-primary">
                <option value="">Selecione o motivo</option>
                <?php foreach ($motivos as $chave => $rotulo): ?>
                  <option value="<?= esc($chave) ?>"><?= esc($rotulo) ?></option>
                <?php endforeach; ?>
              </select>
              <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
            </div>
          </div>

          <div id="campo_motivo_outra" class="flex flex-col gap-1.5 hidden">
            <label for="motivo_encerramento_outra" class="label-primary">Qual o motivo? *</label>
            <input type="text" id="motivo_encerramento_outra" name="motivo_encerramento_outra" maxlength="150" class="input-primary">
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button type="submit" class="btn-delete-confirm text-[13px] text-white px-4 py-2">
            <i data-lucide="circle-check" class="w-4 h-4"></i>
            Encerrar atendimento
          </button>
        </div>
      </form>
    <?php else: ?>
      <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 bg-gray-100 border border-gray-200 flex items-center justify-center rounded-lg">
            <i data-lucide="archive" class="w-4 h-4 stroke-gray-600 stroke-[1.5]"></i>
          </div>
          <p class="text-gray-600 text-[13px] font-medium leading-4">Encerramento</p>
        </div>
        <div class="flex flex-col divide-y divide-gray-100">
          <div class="flex justify-between py-2">
            <span class="text-gray-400 text-[12px]">Encerrado em</span>
            <span class="text-gray-600 text-[12.5px]"><?= $a['data_encerramento'] ? date('d/m/Y H:i', strtotime($a['data_encerramento'])) : '—' ?></span>
          </div>
          <div class="flex justify-between py-2">
            <span class="text-gray-400 text-[12px]">Motivo</span>
            <span class="text-gray-600 text-[12.5px] text-right"><?= esc($motivos[$a['motivo_encerramento']] ?? $a['motivo_encerramento']) ?><?= ! empty($a['motivo_encerramento_outra']) ? ' — ' . esc($a['motivo_encerramento_outra']) : '' ?></span>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<script>
(function () {
  var motivo = document.getElementById('motivo_encerramento');
  if (!motivo) return;
  var campo = document.getElementById('campo_motivo_outra');

  function alternar() {
    campo.classList.toggle('hidden', motivo.value !== 'outros');
    var entrada = document.getElementById('motivo_encerramento_outra');
    if (entrada) entrada.required = motivo.value === 'outros';
  }

  motivo.addEventListener('change', alternar);
  alternar();
})();
</script>
<?= $this->endSection() ?>
