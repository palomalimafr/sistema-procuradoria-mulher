<?php
/**
 * Painel de contexto da mulher, exibido durante a criação de uma ação.
 *
 * SOMENTE LEITURA. Este painel não grava nada no registro que está sendo
 * criado: ele apenas reúne a identificação da mulher, o atendimento em
 * aberto (quando existe) e os eventos mais recentes, para a profissional
 * consultar sem sair do formulário.
 *
 * Uso esperado no controller:
 *   'contexto' => (new \App\Libraries\ContextoMulher())->montar($mulherId),
 *
 * Uso esperado na view:
 *   <?= $this->include('mulheres/_contexto', ['contexto' => $contexto ?? null]) ?>
 *
 * Quando o contexto é null (mulher não informada, inválida ou ainda não
 * selecionada), nada é renderizado.
 */
$contexto = $contexto ?? null;
if ($contexto === null) {
    return;
}

$identidade = $contexto['mulher'];
$itens = [];

if (! empty($identidade['nomeSocial'])) {
    $itens[] = $identidade['nomeSocial'];
}
if (! empty($identidade['cpf'])) {
    $itens[] = $identidade['cpf'];
}
if (! empty($identidade['idade'])) {
    $itens[] = $identidade['idade'] . ' anos';
}
if (! empty($identidade['bairro']) || ! empty($identidade['municipio'])) {
    $local = trim(implode(' — ', array_filter([$identidade['bairro'], $identidade['municipio']])));
    if ($local !== '') {
        $itens[] = $local;
    }
}
if (! empty($identidade['zona'])) {
    $itens[] = $identidade['zona'];
}
?>
<div class="w-full rounded-xl border border-gray-200 bg-white shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] overflow-hidden">
  <div class="flex flex-col divide-y divide-gray-100">
    <!-- IDENTIFICAÇÃO -->
    <div class="p-4 flex items-start gap-3">
      <div class="w-8 h-8 shrink-0 bg-blue-50 border border-blue-200 flex items-center justify-center rounded-lg">
        <i data-lucide="user" class="w-4 h-4 stroke-blue-600 stroke-[1.5]"></i>
      </div>
      <div class="flex flex-col gap-1 min-w-0">
        <p class="text-gray-700 text-[13px] font-medium leading-4 truncate"><?= esc($identidade['nome']) ?></p>
        <?php if ($itens !== []): ?>
          <p class="text-gray-400 text-[11.5px] leading-4"><?= esc(implode(' · ', $itens)) ?></p>
        <?php endif; ?>
        <?php if ($identidade['vulneravel']): ?>
          <span class="self-start mt-0.5 inline-flex items-center gap-1 rounded-md bg-amber-50 border border-amber-200 px-1.5 py-0.5 text-[11px] text-amber-700">
            <i data-lucide="alert-triangle" class="w-3 h-3 stroke-[1.5]"></i>
            Situação de vulnerabilidade
          </span>
        <?php endif; ?>
      </div>
    </div>

    <?php if (! empty($contexto['atendimento'])): ?>
      <!-- ATENDIMENTO EM ABERTO -->
      <div class="p-4 flex items-start gap-3 bg-gray-50/60">
        <div class="w-8 h-8 shrink-0 bg-white border border-gray-200 flex items-center justify-center rounded-lg">
          <i data-lucide="briefcase" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
        </div>
        <div class="flex flex-col gap-1 min-w-0">
          <p class="text-gray-400 text-[11.5px] leading-4">Atendimento em aberto</p>
          <a href="<?= base_url($contexto['atendimento']['url']) ?>" target="_blank" rel="noopener"
             class="flex items-center gap-1.5 text-gray-700 text-[13px] font-medium hover:text-primary transition w-fit">
            <span class="font-mono">#<?= esc($contexto['atendimento']['protocolo']) ?></span>
            <?php if (! empty($contexto['atendimento']['data'])): ?>
              <span class="text-gray-400 text-[12px] font-normal">· <?= esc($contexto['atendimento']['data']) ?></span>
            <?php endif; ?>
            <i data-lucide="external-link" class="w-3 h-3 stroke-gray-400 stroke-[1.5]"></i>
          </a>
        </div>
      </div>
    <?php endif; ?>

    <!-- CONTEXTO RECENTE -->
    <div class="p-4 flex items-start gap-3">
      <div class="w-8 h-8 shrink-0 bg-gray-50 border border-gray-200 flex items-center justify-center rounded-lg">
        <i data-lucide="history" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
      </div>
      <div class="flex flex-col gap-2 min-w-0 flex-1">
        <div class="flex items-center gap-2">
          <p class="text-gray-400 text-[11.5px] leading-4">
            <?= $contexto['totalEventos'] === 1 ? '1 registro anterior' : esc($contexto['totalEventos']) . ' registros anteriores' ?>
          </p>
          <a href="<?= base_url($contexto['urlHistorico']) ?>" target="_blank" rel="noopener"
             class="ml-auto text-[11.5px] text-primary hover:underline whitespace-nowrap">Ver histórico</a>
        </div>

        <?php if (($contexto['eventos'] ?? []) === []): ?>
          <p class="text-gray-400 text-[12px] leading-4">Nenhum registro anterior para esta mulher.</p>
        <?php else: ?>
          <ul class="flex flex-col gap-1">
            <?php foreach ($contexto['eventos'] as $evento): ?>
              <li class="flex items-baseline gap-2 min-w-0">
                <span class="text-gray-300 text-[11px] tabular-nums shrink-0 w-[52px]">
                  <?= esc($evento['data'] !== '' ? date('d/m/y', strtotime($evento['data'])) : '—') ?>
                </span>
                <span class="text-gray-500 text-[11px] shrink-0"><?= esc($evento['rotulo']) ?></span>
                <a href="<?= base_url($evento['url']) ?>" target="_blank" rel="noopener"
                   class="text-gray-700 text-[12px] hover:text-primary transition truncate"><?= esc($evento['titulo']) ?></a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <?php
        $resumo = array_filter(
            $contexto['contagem'],
            static fn (int $total): bool => $total > 0
        );
        ?>
        <?php if ($resumo !== []): ?>
          <div class="flex flex-wrap gap-1 pt-0.5">
            <?php foreach ($resumo as $chave => $total): ?>
              <a href="<?= base_url($contexto['urlHistorico'] . '?modulo=' . urlencode((string) $chave)) ?>"
                 target="_blank" rel="noopener"
                 class="inline-flex items-center gap-1 rounded-md border border-gray-200 bg-gray-50 px-1.5 py-0.5 text-[11px] text-gray-500 hover:border-gray-300 transition">
                <?= esc($total) ?> <?= esc(\App\Models\HistoricoMulherModel::MODULOS[$chave]['rotulo'] ?? $chave) ?>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
