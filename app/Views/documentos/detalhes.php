<?= $this->extend('layouts/admin') ?>

<?php
$formatarTamanho = static function ($bytes) {
    $b = (int) $bytes;
    if ($b >= 1048576) {
        return number_format($b / 1048576, 2, ',', '.') . ' MB';
    }
    if ($b >= 1024) {
        return number_format($b / 1024, 1, ',', '.') . ' KB';
    }
    return $b . ' B';
};
$dataHora = static function ($v) {
    $t = strtotime($v ?? '');
    return $t ? date('d/m/Y H:i', $t) : '—';
};
$visualizarUrl = base_url('documentos/visualizar/' . (int) $documento['id']);
$downloadUrl = base_url('documentos/download/' . (int) $documento['id']);
?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex items-center justify-between gap-3">
    <div class="flex items-center gap-2 min-w-0">
      <a href="<?= base_url('documentos') ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 transition shrink-0" title="Voltar para documentos">
        <i data-lucide="arrow-left" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
      </a>
      <h1 class="text-gray-600 text-[15px] font-medium truncate"><?= esc($titulo) ?> · <?= esc($documento['arquivo_original']) ?></h1>
    </div>
    <div class="flex items-center gap-2 shrink-0">
      <?php if ($arquivoExiste): ?>
        <a href="<?= $visualizarUrl ?>" target="_blank" rel="noopener" class="btn-secondary text-[13px] px-3 py-2" title="Abrir o arquivo em nova aba">
          <i data-lucide="eye" class="w-4 h-4"></i>
          Abrir
        </a>
        <a href="<?= $downloadUrl ?>" class="btn-primary text-[13px] text-white px-3 py-2">
          <i data-lucide="download" class="w-4 h-4"></i>
          Baixar arquivo
        </a>
      <?php endif; ?>
      <a href="<?= base_url('documentos/editar/' . (int) $documento['id']) ?>" class="btn-secondary text-[13px] px-3 py-2">
        <i data-lucide="pencil" class="w-4 h-4"></i>
        Editar
      </a>
    </div>
  </header>

  <!-- ARQUIVO -->
  <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 bg-blue-50 border border-blue-200 flex items-center justify-center rounded-lg">
        <i data-lucide="file-text" class="w-4 h-4 stroke-blue-600 stroke-[1.5]"></i>
      </div>
      <p class="text-gray-600 text-[13px] font-medium leading-4">Arquivo</p>
      <?php if ($arquivoExiste): ?>
        <span class="inline-flex items-center gap-1 text-[11.5px] text-green-700 bg-green-50 border border-green-200 rounded-full px-2.5 py-0.5 ml-2">
          <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i>
          disponível
        </span>
      <?php else: ?>
        <span class="inline-flex items-center gap-1 text-[11.5px] text-red-700 bg-red-50 border border-red-200 rounded-full px-2.5 py-0.5 ml-2">
          <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
          arquivo físico indisponível
        </span>
      <?php endif; ?>
      <span class="px-2 py-0.5 rounded-full text-[11px] font-medium border ml-auto <?= $sigiloCores[$documento['sigilo']] ?? 'bg-gray-50 border-gray-200 text-gray-700' ?>"><?= esc($sigilos[$documento['sigilo']] ?? $documento['sigilo']) ?></span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-3">
      <div class="flex flex-col gap-1">
        <span class="text-gray-400 text-[11.5px] font-medium uppercase tracking-wide">Nome do arquivo</span>
        <span class="text-gray-800 text-[13.5px] break-all"><?= esc($documento['arquivo_original']) ?></span>
      </div>
      <div class="flex flex-col gap-1">
        <span class="text-gray-400 text-[11.5px] font-medium uppercase tracking-wide">Formato</span>
        <span class="text-gray-800 text-[13.5px]"><?= esc($documento['tipo_mime'] ?? '—') ?></span>
      </div>
      <div class="flex flex-col gap-1">
        <span class="text-gray-400 text-[11.5px] font-medium uppercase tracking-wide">Tamanho</span>
        <span class="text-gray-800 text-[13.5px]"><?= esc($formatarTamanho($documento['tamanho_bytes'] ?? 0)) ?></span>
      </div>
      <div class="flex flex-col gap-1">
        <span class="text-gray-400 text-[11.5px] font-medium uppercase tracking-wide">Armazenamento</span>
        <span class="text-gray-500 text-[13px] font-mono break-all" title="Caminho relativo em writable/uploads/"><?= esc($documento['arquivo']) ?></span>
      </div>
    </div>

    <?php if ($arquivoExiste): ?>
      <div class="w-full flex flex-col sm:flex-row gap-2 border-t border-gray-100 pt-3">
        <a href="<?= $visualizarUrl ?>" target="_blank" rel="noopener" class="btn-secondary text-[13px] px-3 py-2">
          <i data-lucide="external-link" class="w-4 h-4"></i>
          Abrir em nova aba
        </a>
        <a href="<?= $downloadUrl ?>" class="btn-secondary text-[13px] px-3 py-2">
          <i data-lucide="download" class="w-4 h-4"></i>
          Baixar arquivo
        </a>
        <p class="text-gray-400 text-[11.5px] self-center leading-4">
          Alternativas: <code class="text-gray-500"><?= esc($visualizarUrl) ?></code> (visualização) e <code class="text-gray-500"><?= esc($downloadUrl) ?></code> (download).
          O acesso fica registrado no histórico.
        </p>
      </div>
    <?php endif; ?>
  </div>

  <!-- DADOS -->
  <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 bg-amber-50 border border-amber-200 flex items-center justify-center rounded-lg">
        <i data-lucide="tag" class="w-4 h-4 stroke-amber-600 stroke-[1.5]"></i>
      </div>
      <p class="text-gray-600 text-[13px] font-medium leading-4">Dados do registro</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-3">
      <div class="flex flex-col gap-1">
        <span class="text-gray-400 text-[11.5px] font-medium uppercase tracking-wide">Tipo de documento</span>
        <span class="text-gray-800 text-[13.5px]"><?= esc($tipos[$documento['tipo']] ?? $documento['tipo']) ?></span>
      </div>
      <div class="flex flex-col gap-1">
        <span class="text-gray-400 text-[11.5px] font-medium uppercase tracking-wide">Responsável</span>
        <span class="text-gray-800 text-[13.5px]"><?= esc($documento['usuario_nome'] ?? '—') ?></span>
      </div>
      <div class="flex flex-col gap-1">
        <span class="text-gray-400 text-[11.5px] font-medium uppercase tracking-wide">Enviado em</span>
        <span class="text-gray-800 text-[13.5px]"><?= esc($dataHora($documento['created_at'])) ?></span>
      </div>
      <div class="flex flex-col gap-1">
        <span class="text-gray-400 text-[11.5px] font-medium uppercase tracking-wide">Última atualização</span>
        <span class="text-gray-800 text-[13.5px]"><?= esc($dataHora($documento['updated_at'])) ?></span>
      </div>
    </div>

    <?php if (! empty($documento['observacoes'])): ?>
      <div class="flex flex-col gap-1 border-t border-gray-100 pt-3">
        <span class="text-gray-400 text-[11.5px] font-medium uppercase tracking-wide">Observações</span>
        <p class="text-gray-700 text-[13.5px] leading-6 whitespace-pre-line"><?= esc($documento['observacoes']) ?></p>
      </div>
    <?php endif; ?>
  </div>

  <!-- MULHER -->
  <a href="<?= base_url('mulheres/detalhes/' . (int) $documento['mulher_id']) ?>" class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3 hover:border-gray-300 transition">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 bg-purple-50 border border-purple-200 flex items-center justify-center rounded-lg">
        <i data-lucide="user" class="w-4 h-4 stroke-purple-600 stroke-[1.5]"></i>
      </div>
      <p class="text-gray-600 text-[13px] font-medium leading-4">Mulher</p>
      <i data-lucide="arrow-up-right" class="w-4 h-4 stroke-gray-400 ml-auto"></i>
    </div>
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 bg-gray-100 border border-gray-200 rounded-full flex items-center justify-center">
        <i data-lucide="user" class="w-5 h-5 stroke-gray-400"></i>
      </div>
      <div class="flex flex-col gap-0.5">
        <span class="text-gray-800 text-[14px] font-medium"><?= esc($documento['mulher_nome']) ?></span>
        <span class="text-gray-400 text-[12.5px]"><?= esc($documento['mulher_cpf'] ?? '') ?></span>
      </div>
    </div>
  </a>

  <!-- RELACIONAMENTOS -->
  <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 bg-cyan-50 border border-cyan-200 flex items-center justify-center rounded-lg">
        <i data-lucide="link" class="w-4 h-4 stroke-cyan-600 stroke-[1.5]"></i>
      </div>
      <p class="text-gray-600 text-[13px] font-medium leading-4">Relacionamentos</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-3">
      <div class="flex flex-col gap-1">
        <span class="text-gray-400 text-[11.5px] font-medium uppercase tracking-wide">Atendimento</span>
        <?php if ($documento['atendimento_id']): ?>
          <a href="<?= base_url('atendimentos/' . (int) $documento['atendimento_id']) ?>" class="text-primary hover:underline text-[13.5px] font-medium">#<?= esc($documento['atendimento_protocolo'] ?? '') ?></a>
        <?php else: ?>
          <span class="text-gray-400 text-[13.5px]">—</span>
        <?php endif; ?>
      </div>

      <div class="flex flex-col gap-1">
        <span class="text-gray-400 text-[11.5px] font-medium uppercase tracking-wide">Processo</span>
        <?php if ($documento['processo_id']): ?>
          <a href="<?= base_url('processos/' . (int) $documento['processo_id']) ?>" class="text-primary hover:underline text-[13.5px] font-medium break-all"><?= esc($documento['processo_numero'] ?? '#' . (int) $documento['processo_id']) ?></a>
        <?php else: ?>
          <span class="text-gray-400 text-[13.5px]">—</span>
        <?php endif; ?>
      </div>

      <div class="flex flex-col gap-1">
        <span class="text-gray-400 text-[11.5px] font-medium uppercase tracking-wide">Medida protetiva</span>
        <?php if ($documento['medida_protetiva_id']): ?>
          <a href="<?= base_url('medidas-protetivas/' . (int) $documento['medida_protetiva_id']) ?>" class="text-primary hover:underline text-[13.5px] font-medium"><?= esc($documento['medida_numero'] ?? '#' . (int) $documento['medida_protetiva_id']) ?></a>
          <span class="inline-flex items-center gap-1 text-[11px] font-medium mt-1">
            <span class="w-1.5 h-1.5 rounded-full <?= ($documento['medida_situacao'] ?? '') === 'ativa' ? 'bg-green-500' : 'bg-gray-400' ?>"></span>
            <?= esc($situacoesMedida[$documento['medida_situacao']] ?? '—') ?>
          </span>
        <?php else: ?>
          <span class="text-gray-400 text-[13.5px]">—</span>
        <?php endif; ?>
      </div>

      <div class="flex flex-col gap-1">
        <span class="text-gray-400 text-[11.5px] font-medium uppercase tracking-wide">Encaminhamento</span>
        <?php if ($documento['encaminhamento_id']): ?>
          <a href="<?= base_url('encaminhamentos/' . (int) $documento['encaminhamento_id']) ?>" class="text-primary hover:underline text-[13.5px] font-medium">#<?= esc($documento['encaminhamento_id']) ?></a>
          <span class="inline-flex items-center gap-1 text-[11px] font-medium mt-1">
            <span class="w-1.5 h-1.5 rounded-full <?= ($documento['encaminhamento_status'] ?? '') === 'concluido' ? 'bg-green-500' : 'bg-amber-500' ?>"></span>
            <?= esc($situacoesEncaminhamento[$documento['encaminhamento_status']] ?? '—') ?>
          </span>
        <?php else: ?>
          <span class="text-gray-400 text-[13.5px]">—</span>
        <?php endif; ?>
      </div>

      <div class="flex flex-col gap-1">
        <span class="text-gray-400 text-[11.5px] font-medium uppercase tracking-wide">Manifestação</span>
        <?php if ($documento['manifestacao_id']): ?>
          <a href="<?= base_url('manifestacoes/' . (int) $documento['manifestacao_id']) ?>" class="text-primary hover:underline text-[13.5px] font-medium"><?= esc($documento['manifestacao_protocolo'] ?? '#' . (int) $documento['manifestacao_id']) ?></a>
          <?php if ((int) ($documento['manifestacao_situacao'] ?? 0) !== 0 || ($documento['manifestacao_situacao'] ?? '') !== ''): ?>
            <span class="inline-flex items-center gap-1 text-[11px] font-medium mt-1">
              <span class="w-1.5 h-1.5 rounded-full <?= in_array($documento['manifestacao_situacao'] ?? '', ['resolvida', 'encerrada'], true) ? 'bg-green-500' : 'bg-amber-500' ?>"></span>
              <?= esc($situacoesManifestacao[$documento['manifestacao_situacao']] ?? '—') ?>
            </span>
          <?php endif; ?>
        <?php else: ?>
          <span class="text-gray-400 text-[13.5px]">—</span>
        <?php endif; ?>
      </div>
    </div>
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
          <?php $mensagem = [
            'upload'    => 'Documento enviado',
            'edicao'    => 'Dados editados',
            'sigilo'    => 'Sigilo alterado',
            'download'  => 'Arquivo baixado ou visualizado',
            'exclusao'  => 'Documento excluído (lógico)',
          ][$h['acao']] ?? 'Evento registrado'; ?>
          <div class="flex gap-3 py-2.5 <?= $indice < $n - 1 ? 'border-b border-gray-100' : '' ?>">
            <div class="w-8 h-8 bg-gray-50 border border-gray-200 rounded-full flex items-center justify-center shrink-0">
              <i data-lucide="<?= $h['acao'] === 'exclusao' ? 'trash-2' : ($h['acao'] === 'download' ? 'download' : 'circle-check') ?>" class="w-3.5 h-3.5 stroke-gray-500"></i>
            </div>
            <div class="flex flex-col gap-0.5 min-w-0">
              <p class="text-gray-700 text-[13px] font-medium"><?= esc($mensagem) ?></p>
              <?php if (! empty($h['status_anterior']) || ! empty($h['status_novo'])): ?>
                <p class="text-gray-500 text-[12px]">
                  <?= esc(! empty($h['status_anterior']) ? $sigilos[$h['status_anterior']] ?? $h['status_anterior'] : '') ?>
                  <?= (! empty($h['status_anterior']) && ! empty($h['status_novo'])) ? ' → ' : '' ?>
                  <?= esc(! empty($h['status_novo']) ? $sigilos[$h['status_novo']] ?? $h['status_novo'] : '') ?>
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