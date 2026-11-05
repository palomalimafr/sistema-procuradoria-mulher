<?= $this->extend('layouts/admin') ?>

<?php
$priorCor = [
    'emergencia' => 'bg-red-50 border-red-200 text-red-700',
    'alta'       => 'bg-amber-50 border-amber-200 text-amber-700',
    'media'      => 'bg-yellow-50 border-yellow-200 text-yellow-700',
    'baixa'      => 'bg-green-50 border-green-200 text-green-700',
];
$statusCor = [
    'nova'               => 'bg-blue-50 border-blue-200 text-blue-700',
    'em_analise'         => 'bg-purple-50 border-purple-200 text-purple-700',
    'em_atendimento'     => 'bg-teal-50 border-teal-200 text-teal-700',
    'encaminhada'        => 'bg-orange-50 border-orange-200 text-orange-700',
    'aguardando_retorno' => 'bg-amber-50 border-amber-200 text-amber-700',
    'resolvida'          => 'bg-green-50 border-green-200 text-green-700',
    'encerrada'          => 'bg-gray-50 border-gray-200 text-gray-500',
];
$temFiltros = $filtros['protocolo'] !== '' || $filtros['tipo'] !== '' || $filtros['origem'] !== ''
    || $filtros['prioridade'] !== '' || $filtros['situacao'] !== '' || $filtros['responsavel'] > 0
    || $filtros['data_de'] !== '' || $filtros['data_ate'] !== '';
$resumoDe = static function (?string $texto): string {
    return mb_strimwidth((string) $texto, 0, 90, '…');
};
?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex items-center justify-between gap-3">
    <div class="flex items-center gap-2">
      <h1 class="text-gray-600 text-[15px] font-medium">Denúncias/Ouvidoria</h1>
      <?php if ($temFiltros): ?>
        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 border border-gray-200 text-gray-600">filtros ativos</span>
      <?php endif; ?>
    </div>
    <a href="<?= base_url('manifestacoes/nova') ?>" class="btn-primary text-[13px] text-white px-3 py-2">
      <i data-lucide="plus" class="w-4 h-4"></i>
      Nova manifestação
    </a>
  </header>

  <div class="flex items-center justify-between gap-2 flex-wrap">
    <p class="text-gray-400 text-[12.5px]">
      Manifestações registradas, com acompanhamento sigiloso e encaminhamento para a Rede de Proteção.
    </p>
    <button type="button" id="btn-filtros" class="btn-secondary text-[13px] px-3 py-2" aria-expanded="false" aria-controls="painel-filtros">
      <i data-lucide="filter" class="w-4 h-4"></i>
      Filtros
    </button>
  </div>

  <!-- FILTROS COMPACTOS (painel recolhível) -->
  <form method="GET" action="<?= base_url('manifestacoes') ?>" id="painel-filtros" class="hidden w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex-col gap-3">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 bg-gray-100 border border-gray-200 flex items-center justify-center rounded-lg">
        <i data-lucide="filter" class="w-4 h-4 stroke-gray-600 stroke-[1.5]"></i>
      </div>
      <p class="text-gray-600 text-[13px] font-medium leading-4">Filtros</p>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
      <div class="flex flex-col gap-1.5">
        <label for="f_protocolo" class="label-primary">Protocolo</label>
        <input type="text" id="f_protocolo" name="protocolo" value="<?= esc((string) $filtros['protocolo']) ?>" placeholder="Ex.: MAN-26-000001" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_tipo" class="label-primary">Tipo</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="f_tipo" name="tipo" class="select-primary">
            <option value="">Todos</option>
            <?php foreach ($tipos as $chave => $rotulo): ?>
              <option value="<?= esc($chave) ?>" <?= $filtros['tipo'] === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
            <?php endforeach; ?>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
        </div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_origem" class="label-primary">Origem</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="f_origem" name="origem" class="select-primary">
            <option value="">Todas</option>
            <?php foreach ($origens as $chave => $rotulo): ?>
              <option value="<?= esc($chave) ?>" <?= $filtros['origem'] === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
            <?php endforeach; ?>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
        </div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_prioridade" class="label-primary">Prioridade</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="f_prioridade" name="prioridade" class="select-primary">
            <option value="">Todas</option>
            <?php foreach ($prioridades as $chave => $rotulo): ?>
              <option value="<?= esc($chave) ?>" <?= $filtros['prioridade'] === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
            <?php endforeach; ?>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
        </div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_situacao" class="label-primary">Situação</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="f_situacao" name="situacao" class="select-primary">
            <option value="">Todas</option>
            <?php foreach ($situacoes as $chave => $rotulo): ?>
              <option value="<?= esc($chave) ?>" <?= $filtros['situacao'] === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
            <?php endforeach; ?>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
        </div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_responsavel" class="label-primary">Responsável</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="f_responsavel" name="responsavel" class="select-primary">
            <option value="">Todos</option>
            <?php foreach ($equipe as $u): ?>
              <option value="<?= (int) $u['id'] ?>" <?= (int) $filtros['responsavel'] === (int) $u['id'] ? 'selected' : '' ?>><?= esc($u['nome']) ?></option>
            <?php endforeach; ?>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
        </div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_data_de" class="label-primary">Registrada de</label>
        <input type="date" id="f_data_de" name="data_de" value="<?= esc((string) $filtros['data_de']) ?>" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_data_ate" class="label-primary">Registrada até</label>
        <input type="date" id="f_data_ate" name="data_ate" value="<?= esc((string) $filtros['data_ate']) ?>" class="input-primary">
      </div>
    </div>

    <div class="flex items-center gap-2">
      <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2 h-11">
        <i data-lucide="filter" class="w-4 h-4"></i>
        Filtrar
      </button>
      <a href="<?= base_url('manifestacoes') ?>" class="btn-secondary text-[13px] px-4 py-2 h-11">Limpar</a>
    </div>
  </form>

  <!-- LISTAGEM -->
  <div class="w-full bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl overflow-hidden">
    <div class="w-full overflow-x-auto">
      <table class="w-full text-[13px]">
        <thead>
          <tr class="bg-gray-50 border-b border-gray-200">
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Manifestação</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Data</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Tipo</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Identificação</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Prioridade</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Situação</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Responsável</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (! empty($manifestacoes)): ?>
            <?php foreach ($manifestacoes as $m): ?>
              <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
                <td class="py-2.5 px-3">
                  <a href="<?= base_url('manifestacoes/' . (int) $m['id']) ?>" class="text-gray-700 font-medium hover:text-primary hover:underline font-mono"><?= esc($m['protocolo']) ?></a>
                  <p class="text-gray-400 text-[12px] leading-4 block truncate max-w-[260px] mt-0.5" title="<?= esc($m['descricao']) ?>"><?= esc($resumoDe($m['descricao'])) ?></p>
                  <span class="text-gray-400 text-[11px]"><?= esc($origens[$m['origem']] ?? $m['origem']) ?></span>
                </td>
                <td class="py-2.5 px-3 text-gray-600 whitespace-nowrap"><?= date('d/m/Y H:i', strtotime($m['data_hora'])) ?></td>
                <td class="py-2.5 px-3">
                  <span class="px-2 py-0.5 rounded-full text-[11px] font-medium border bg-gray-50 border-gray-200 text-gray-700"><?= esc($tipos[$m['tipo']] ?? $m['tipo']) ?></span>
                </td>
                <td class="py-2.5 px-3">
                  <?php if ((int) $m['anonimo'] === 1): ?>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium border bg-slate-100 border-slate-200 text-slate-600">
                      <i data-lucide="eye-off" class="w-3 h-3"></i>
                      Anônima
                    </span>
                  <?php elseif (! empty($m['pessoa_nome'])): ?>
                    <span class="text-gray-700"><?= esc($m['pessoa_nome']) ?></span>
                  <?php elseif (! empty($m['mulher_nome'])): ?>
                    <a href="<?= base_url('mulheres/detalhes/' . (int) $m['mulher_id']) ?>" class="text-primary hover:underline"><?= esc($m['mulher_nome']) ?></a>
                  <?php else: ?>
                    <span class="text-gray-400">—</span>
                  <?php endif; ?>
                </td>
                <td class="py-2.5 px-3">
                  <span class="px-2 py-0.5 rounded-full text-[11px] font-medium border <?= $priorCor[$m['prioridade']] ?? 'bg-gray-50 border-gray-200 text-gray-700' ?>"><?= esc($prioridades[$m['prioridade']] ?? $m['prioridade']) ?></span>
                </td>
                <td class="py-2.5 px-3">
                  <span class="px-2 py-0.5 rounded-full text-[11px] font-medium border <?= $statusCor[$m['situacao']] ?? 'bg-gray-50 border-gray-200 text-gray-700' ?>"><?= esc($situacoes[$m['situacao']] ?? $m['situacao']) ?></span>
                </td>
                <td class="py-2.5 px-3 text-gray-600"><?= esc($m['responsavel_nome'] ?? '—') ?></td>
                <td class="py-2.5 px-3">
                  <a href="<?= base_url('manifestacoes/' . (int) $m['id']) ?>" title="Abrir manifestação" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition">
                    <i data-lucide="eye" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="py-6 px-3 text-center text-gray-400">
                Nenhuma manifestação<?= $temFiltros ? ' com os critérios informados.' : ' registrada ainda.' ?>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if (! empty($pager)): ?>
    <?= $pager->links() ?>
  <?php endif; ?>

  <?php if (empty($manifestacoes)): ?>
    <p class="text-center text-gray-400 text-[12.5px]">
      <a href="<?= base_url('manifestacoes/nova') ?>" class="text-primary hover:underline">Registrar a primeira manifestação ›</a>
    </p>
  <?php endif; ?>
</section>

<script>
(function () {
  var btn = document.getElementById('btn-filtros');
  var painel = document.getElementById('painel-filtros');
  <?php if ($temFiltros): ?>
  if (btn && painel) {
    painel.classList.remove('hidden');
    painel.classList.add('flex');
    btn.setAttribute('aria-expanded', 'true');
    btn.querySelector('[data-lucide]').setAttribute('data-lucide', 'filter-x');
  }
  <?php endif; ?>
  if (btn && painel) {
    btn.addEventListener('click', function () {
      var aberto = painel.classList.toggle('hidden');
      painel.classList.toggle('flex', !aberto);
      btn.setAttribute('aria-expanded', String(!aberto));
      var icone = btn.querySelector('[data-lucide]');
      if (icone) icone.setAttribute('data-lucide', aberto ? 'filter' : 'filter-x');
      if (window.lucide) window.lucide.createIcons();
    });
  }
  if (window.lucide) window.lucide.createIcons();
})();
</script>
<?= $this->endSection() ?>