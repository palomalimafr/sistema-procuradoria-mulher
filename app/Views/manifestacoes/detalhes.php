<?= $this->extend('layouts/admin') ?>

<?php
$m = $manifestacao;
$statusCor = [
    'nova'               => 'bg-blue-50 border-blue-200 text-blue-700',
    'em_analise'         => 'bg-purple-50 border-purple-200 text-purple-700',
    'em_atendimento'     => 'bg-teal-50 border-teal-200 text-teal-700',
    'encaminhada'        => 'bg-orange-50 border-orange-200 text-orange-700',
    'aguardando_retorno' => 'bg-amber-50 border-amber-200 text-amber-700',
    'resolvida'          => 'bg-green-50 border-green-200 text-green-700',
    'encerrada'          => 'bg-gray-50 border-gray-200 text-gray-500',
];
$priorCor = [
    'emergencia' => 'bg-red-50 border-red-200 text-red-700',
    'alta'       => 'bg-amber-50 border-amber-200 text-amber-700',
    'media'      => 'bg-yellow-50 border-yellow-200 text-yellow-700',
    'baixa'      => 'bg-green-50 border-green-200 text-green-700',
];
$anonimo = (int) ($m['anonimo'] ?? 0) === 1;
$encerradas = \App\Models\ManifestacaoModel::SITUACOES_FINALIZADAS;
?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex items-center justify-between gap-3">
    <div class="flex items-center gap-2 min-w-0">
      <a href="<?= base_url('manifestacoes') ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 transition shrink-0" title="Voltar">
        <i data-lucide="arrow-left" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
      </a>
      <h1 class="text-gray-600 text-[15px] font-medium truncate">Manifestação #<?= (int) $m['id'] ?> · <?= esc($m['protocolo']) ?></h1>
    </div>
    <div class="flex items-center gap-2 flex-wrap shrink-0">
      <span class="px-2 py-0.5 rounded-full text-[11px] font-medium border <?= $statusCor[$m['situacao']] ?? 'bg-gray-50 border-gray-200 text-gray-700' ?>">
        <?= esc($situacoes[$m['situacao']] ?? $m['situacao']) ?>
      </span>
      <span class="px-2 py-0.5 rounded-full text-[11px] font-medium border <?= $priorCor[$m['prioridade']] ?? 'bg-gray-50 border-gray-200 text-gray-700' ?>">
        <?= esc($prioridades[$m['prioridade']] ?? $m['prioridade']) ?>
      </span>
    </div>
  </header>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
    <!-- MANIFESTAÇÃO -->
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-blue-50 border border-blue-200 flex items-center justify-center rounded-lg">
          <i data-lucide="megaphone" class="w-4 h-4 stroke-blue-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Manifestação</p>
        <span class="ml-auto inline-flex items-center gap-1 font-mono text-[11.5px] text-gray-500 bg-gray-50 border border-gray-200 rounded-full px-2.5 py-1">
          <?= esc($m['protocolo']) ?>
        </span>
      </div>
      <div class="flex flex-col divide-y divide-gray-100">
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Tipo</span>
          <span class="px-2 py-0.5 rounded-full text-[11px] font-medium border bg-gray-50 border-gray-200 text-gray-700"><?= esc($tipos[$m['tipo']] ?? $m['tipo']) ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Origem</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($origens[$m['origem']] ?? $m['origem']) ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Prioridade</span>
          <span class="px-2 py-0.5 rounded-full text-[11px] font-medium border <?= $priorCor[$m['prioridade']] ?? 'bg-gray-50 border-gray-200 text-gray-700' ?>"><?= esc($prioridades[$m['prioridade']] ?? $m['prioridade']) ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Responsável</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($m['responsavel_nome'] ?? '—') ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Data/Hora</span>
          <span class="text-gray-600 text-[12.5px]"><?= date('d/m/Y H:i', strtotime($m['data_hora'])) ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Situação</span>
          <span class="px-2 py-0.5 rounded-full text-[11px] font-medium border <?= $statusCor[$m['situacao']] ?? 'bg-gray-50 border-gray-200 text-gray-700' ?>"><?= esc($situacoes[$m['situacao']] ?? $m['situacao']) ?></span>
        </div>
        <?php if (! empty($m['data_encerramento'])): ?>
          <div class="flex justify-between py-2">
            <span class="text-gray-400 text-[12px]">Encerrada em</span>
            <span class="text-gray-600 text-[12.5px]"><?= date('d/m/Y H:i', strtotime($m['data_encerramento'])) ?></span>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- IDENTIFICAÇÃO -->
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-slate-50 border border-slate-200 flex items-center justify-center rounded-lg">
          <i data-lucide="<?= $anonimo ? 'eye-off' : 'user' ?>" class="w-4 h-4 stroke-slate-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Identificação</p>
      </div>

      <?php if ($anonimo): ?>
        <span class="inline-flex items-center gap-1.5 self-start px-2.5 py-1 rounded-full text-[11px] font-medium border bg-slate-100 border-slate-200 text-slate-600">
          <i data-lucide="eye-off" class="w-3.5 h-3.5"></i>
          Manifestação anônima
        </span>
        <p class="text-gray-400 text-[12px]">Os dados da pessoa não foram registrados.</p>
      <?php else: ?>
        <div class="flex flex-col divide-y divide-gray-100">
          <?php if (! empty($m['pessoa_nome'])): ?>
            <div class="flex justify-between py-2">
              <span class="text-gray-400 text-[12px]">Nome</span>
              <span class="text-gray-600 text-[12.5px]"><?= esc($m['pessoa_nome']) ?></span>
            </div>
          <?php endif; ?>
          <?php if (! empty($m['pessoa_cpf'])): ?>
            <div class="flex justify-between py-2">
              <span class="text-gray-400 text-[12px]">CPF</span>
              <span class="text-gray-600 text-[12.5px]"><?= esc($m['pessoa_cpf']) ?></span>
            </div>
          <?php endif; ?>
          <?php if (! empty($m['pessoa_telefone'])): ?>
            <div class="flex justify-between py-2">
              <span class="text-gray-400 text-[12px]">Telefone</span>
              <span class="text-gray-600 text-[12.5px]"><?= esc($m['pessoa_telefone']) ?></span>
            </div>
          <?php endif; ?>
          <?php if (! empty($m['pessoa_email'])): ?>
            <div class="flex justify-between py-2">
              <span class="text-gray-400 text-[12px]">E-mail</span>
              <span class="text-gray-600 text-[12.5px]"><?= esc($m['pessoa_email']) ?></span>
            </div>
          <?php endif; ?>
          <?php if (empty($m['pessoa_nome']) && empty($m['pessoa_cpf']) && empty($m['pessoa_telefone']) && empty($m['pessoa_email'])): ?>
            <p class="text-gray-400 text-[12px] py-2">Sem dados de identificação.</p>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if (! empty($m['mulher_id'])): ?>
        <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
          <i data-lucide="user" class="w-4 h-4 text-primary shrink-0"></i>
          <a href="<?= base_url('mulheres/detalhes/' . (int) $m['mulher_id']) ?>" class="text-primary text-[12.5px] hover:underline truncate">Ficha: <?= esc($m['mulher_nome'] ?? 'Ver mulher') ?></a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- RELATO -->
  <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 bg-amber-50 border border-amber-200 flex items-center justify-center rounded-lg">
        <i data-lucide="file-text" class="w-4 h-4 stroke-amber-600 stroke-[1.5]"></i>
      </div>
      <p class="text-gray-600 text-[13px] font-medium leading-4">Relato</p>
    </div>
    <div class="flex flex-col gap-1">
      <p class="text-gray-500 text-[12px] font-medium uppercase tracking-wider">Descrição</p>
      <p class="text-gray-700 text-[13px] leading-6 whitespace-pre-line"><?= esc($m['descricao']) ?></p>
    </div>
    <?php if (! empty($m['local_relacionado']) || ! empty($m['observacoes'])): ?>
      <div class="flex flex-col gap-1 pt-2 border-t border-gray-100">
        <?php if (! empty($m['local_relacionado'])): ?>
          <div class="flex flex-col gap-0.5">
            <span class="text-gray-400 text-[11.5px] font-medium uppercase tracking-wide">Local relacionado</span>
            <p class="text-gray-700 text-[13px] leading-5"><?= esc($m['local_relacionado']) ?></p>
          </div>
        <?php endif; ?>
        <?php if (! empty($m['observacoes'])): ?>
          <div class="flex flex-col gap-0.5 <?= ! empty($m['local_relacionado']) ? 'pt-2 border-t border-gray-100' : '' ?>">
            <span class="text-gray-400 text-[11.5px] font-medium uppercase tracking-wide">Observações internas</span>
            <p class="text-gray-500 text-[12px] leading-5 whitespace-pre-line"><?= esc($m['observacoes']) ?></p>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- ENCAMINHAMENTO PARA A REDE -->
  <?php if (! empty($m['instituicao_id'])): ?>
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-orange-50 border border-orange-200 flex items-center justify-center rounded-lg">
          <i data-lucide="send" class="w-4 h-4 stroke-orange-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Encaminhada para a Rede de Proteção</p>
      </div>
      <div class="flex flex-col divide-y divide-gray-100">
        <div class="flex justify-between py-2 items-center gap-3">
          <span class="text-gray-400 text-[12px]">Instituição</span>
          <a href="<?= base_url('rede/instituicoes/' . (int) $m['instituicao_id']) ?>" class="text-primary text-[12.5px] hover:underline text-right"><?= esc($m['instituicao_nome'] ?? 'Ver instituição') ?></a>
        </div>
        <?php if (! empty($m['instituicao_municipio'])): ?>
          <div class="flex justify-between py-2">
            <span class="text-gray-400 text-[12px]">Município</span>
            <span class="text-gray-600 text-[12.5px]"><?= esc($m['instituicao_municipio']) ?></span>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- DOCUMENTOS VINCULADOS -->
  <?php if (! empty($documentos)): ?>
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-indigo-50 border border-indigo-200 flex items-center justify-center rounded-lg">
          <i data-lucide="paperclip" class="w-4 h-4 stroke-indigo-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Documentos anexados</p>
        <span class="ml-auto text-gray-400 text-[11.5px]"><?= count($documentos) ?> arquivo(s)</span>
      </div>
      <div class="flex flex-col divide-y divide-gray-100">
        <?php foreach ($documentos as $doc): ?>
          <div class="flex justify-between py-2 items-center gap-3">
            <span class="text-gray-700 text-[13px] truncate"><?= esc($doc['arquivo_original'] ?? 'Arquivo') ?></span>
            <a href="<?= base_url('documentos/' . (int) $doc['id']) ?>" class="text-primary text-[12.5px] hover:underline shrink-0">Ver</a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- ACOMPANHAMENTO / FLUXO -->
  <?php if (! in_array($m['situacao'], $encerradas, true)): ?>
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-gray-100 border border-gray-200 flex items-center justify-center rounded-lg">
          <i data-lucide="route" class="w-4 h-4 stroke-gray-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Acompanhamento</p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <?php foreach ($fluxoStatus[$m['situacao']] ?? [] as $proximo): ?>
          <?php if ($proximo === 'encaminhada'): ?>
            <form method="POST" action="<?= base_url('manifestacoes/' . (int) $m['id'] . '/status') ?>" class="flex items-center gap-2 flex-wrap">
              <input type="hidden" name="status" value="encaminhada">
              <label for="instituicao_id" class="text-gray-500 text-[12px]">Instituição da Rede *</label>
              <select name="instituicao_id" id="instituicao_id" class="select-primary max-w-[260px]" required>
                <option value="">Selecione…</option>
                <?php foreach ($instituicoesAtivas as $inst): ?>
                  <option value="<?= (int) $inst['id'] ?>"><?= esc($inst['nome']) ?><?= ! empty($inst['municipio']) ? ' · ' . esc($inst['municipio']) : '' ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2">
                <i data-lucide="send" class="w-4 h-4"></i>
                Encaminhar
              </button>
            </form>
          <?php else: ?>
            <form method="POST" action="<?= base_url('manifestacoes/' . (int) $m['id'] . '/status') ?>">
              <input type="hidden" name="status" value="<?= esc($proximo) ?>">
              <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2">
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
                <?= esc($situacoes[$proximo] ?? $proximo) ?>
              </button>
            </form>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
      <p class="text-gray-400 text-[11.5px] leading-3.5 pl-0.5">O registro é mantido em todas as situações — apenas muda de status.</p>
    </div>
  <?php endif; ?>

  <!-- AÇÕES -->
  <div class="flex items-center gap-2">
    <a href="<?= base_url('manifestacoes/editar/' . (int) $m['id']) ?>" class="btn-secondary text-[13px] px-4 py-2">
      <i data-lucide="pencil" class="w-4 h-4"></i>
      Editar manifestação
    </a>
    <a href="<?= base_url('documentos/nova?manifestacao_id=' . (int) $m['id']) ?>" class="btn-secondary text-[13px] px-4 py-2">
      <i data-lucide="paperclip" class="w-4 h-4"></i>
      Anexar documento
    </a>
    <?php if (! empty($m['mulher_id'])): ?>
      <a href="<?= base_url('manifestacoes/nova?mulher_id=' . (int) $m['mulher_id']) ?>" class="btn-secondary text-[13px] px-4 py-2">
        <i data-lucide="megaphone" class="w-4 h-4"></i>
        Nova manifestação p/ esta mulher
      </a>
    <?php endif; ?>
  </div>

  <!-- HISTÓRICO -->
  <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 bg-gray-100 border border-gray-200 flex items-center justify-center rounded-lg">
        <i data-lucide="history" class="w-4 h-4 stroke-gray-600 stroke-[1.5]"></i>
      </div>
      <p class="text-gray-600 text-[13px] font-medium leading-4">Histórico</p>
    </div>

    <?php if (empty($historico)): ?>
      <p class="text-gray-400 text-[13px] py-2">Nenhum evento registrado ainda.</p>
    <?php else: ?>
      <div class="flex flex-col gap-0">
        <?php $n = count($historico); ?>
        <?php foreach ($historico as $indice => $h): ?>
          <?php
          $mensagem = [
            'criacao'        => 'Manifestação registrada',
            'situacao'       => 'Situação atualizada',
            'encaminhamento' => 'Encaminhada para a Rede de Proteção',
            'responsavel'    => 'Responsável atualizado',
            'edicao'         => 'Dados da manifestação editados',
          ][$h['acao']] ?? 'Evento registrado';
          $icone = [
            'criacao' => 'megaphone',
            'situacao' => 'route',
            'encaminhamento' => 'send',
            'responsavel' => 'user',
            'edicao' => 'pencil',
          ][$h['acao']] ?? 'circle-check';
          ?>
          <div class="flex gap-3 py-2.5 <?= $indice < $n - 1 ? 'border-b border-gray-100' : '' ?>">
            <div class="w-8 h-8 bg-gray-50 border border-gray-200 rounded-full flex items-center justify-center shrink-0">
              <i data-lucide="<?= esc($icone) ?>" class="w-3.5 h-3.5 stroke-gray-500"></i>
            </div>
            <div class="flex flex-col gap-0.5 min-w-0">
              <p class="text-gray-700 text-[13px] font-medium"><?= esc($mensagem) ?></p>
              <?php if (! empty($h['status_anterior']) || ! empty($h['status_novo'])): ?>
                <p class="text-gray-500 text-[12px]">
                  <?= esc(! empty($h['status_anterior']) ? ($situacoes[$h['status_anterior']] ?? $h['status_anterior']) : '') ?>
                  <?= (! empty($h['status_anterior']) && ! empty($h['status_novo'])) ? ' → ' : '' ?>
                  <?= esc(! empty($h['status_novo']) ? ($situacoes[$h['status_novo']] ?? $h['status_novo']) : '') ?>
                </p>
              <?php endif; ?>
              <?php if (! empty($h['observacao'])): ?>
                <p class="text-gray-500 text-[12px] break-words"><?= esc($h['observacao']) ?></p>
              <?php endif; ?>
              <p class="text-gray-400 text-[11.5px]"><?= esc($h['usuario_nome'] ?? '—') ?> · <?= esc(date('d/m/Y H:i', strtotime($h['created_at']))) ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?= $this->endSection() ?>