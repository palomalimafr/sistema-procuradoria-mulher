<?= $this->extend('layouts/admin') ?>

<?php
$temFiltros = $filtros['q'] !== '' || $filtros['tipo'] !== '' || $filtros['municipio'] !== ''
    || $filtros['servico'] !== '' || $filtros['situacao'] !== '';
?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex justify-between items-center gap-3">
    <div class="flex items-center gap-2">
      <h1 class="text-gray-600 text-[15px] font-medium">Instituições</h1>
      <?php if ($temFiltros): ?>
        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 border border-gray-200 text-gray-600">filtros ativos</span>
      <?php endif; ?>
    </div>
    <a href="<?= base_url('rede/instituicoes/nova') ?>" class="btn-primary text-[13px] text-white px-3 py-2">
      <i data-lucide="plus" class="w-4 h-4"></i>
      Nova instituição
    </a>
  </header>

  <div class="flex items-center justify-between gap-2 flex-wrap">
    <p class="text-gray-400 text-[12.5px]">Órgãos e serviços da rede de proteção. Instituições inativas continuam preservadas no histórico.</p>
    <button type="button" id="btn-filtros" class="btn-secondary text-[13px] px-3 py-2" aria-expanded="false" aria-controls="painel-filtros">
      <i data-lucide="filter" class="w-4 h-4"></i>
      Filtros
    </button>
  </div>

  <!-- FILTROS COMPACTOS (painel recolhível) -->
  <form method="GET" action="<?= base_url('rede/instituicoes') ?>" id="painel-filtros" class="hidden w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex-col gap-3">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 bg-gray-100 border border-gray-200 flex items-center justify-center rounded-lg">
        <i data-lucide="filter" class="w-4 h-4 stroke-gray-600 stroke-[1.5]"></i>
      </div>
      <p class="text-gray-600 text-[13px] font-medium leading-4">Filtros</p>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
      <div class="flex flex-col gap-1.5">
        <label for="f_q" class="label-primary">Nome</label>
        <input type="text" id="f_q" name="q" value="<?= esc((string) $filtros['q']) ?>" placeholder="Nome da instituição" class="input-primary">
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
        <label for="f_municipio" class="label-primary">Município</label>
        <input type="text" id="f_municipio" name="municipio" value="<?= esc((string) $filtros['municipio']) ?>" placeholder="Município" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_servico" class="label-primary">Serviço</label>
        <input type="text" id="f_servico" name="servico" value="<?= esc((string) $filtros['servico']) ?>" placeholder="Serviço oferecido" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_situacao" class="label-primary">Situação</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="f_situacao" name="situacao" class="select-primary">
            <option value="">Todas</option>
            <option value="ativas" <?= $filtros['situacao'] === 'ativas' ? 'selected' : '' ?>>Ativas</option>
            <option value="inativas" <?= $filtros['situacao'] === 'inativas' ? 'selected' : '' ?>>Inativas</option>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
        </div>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2 h-11">
        <i data-lucide="filter" class="w-4 h-4"></i>
        Filtrar
      </button>
      <a href="<?= base_url('rede/instituicoes') ?>" class="btn-secondary text-[13px] px-4 py-2 h-11">Limpar</a>
    </div>
  </form>

  <!-- LISTAGEM -->
  <div class="w-full bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl overflow-hidden">
    <div class="w-full overflow-x-auto">
      <table class="w-full text-[13px]">
        <thead>
          <tr class="bg-gray-50 border-b border-gray-200">
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Instituição</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Tipo</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Município / Endereço</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Telefone</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Responsável</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Serviços</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Situação</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (! empty($instituicoes)): ?>
            <?php foreach ($instituicoes as $inst): ?>
              <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
                <td class="py-2.5 px-3">
                  <a href="<?= base_url('rede/instituicoes/' . (int) $inst['id']) ?>" class="text-gray-700 font-medium hover:text-primary hover:underline block truncate max-w-[220px]" title="<?= esc($inst['nome']) ?>"><?= esc($inst['nome']) ?></a>
                </td>
                <td class="py-2.5 px-3">
                  <span class="px-2 py-0.5 rounded-full text-[11px] font-medium border bg-gray-50 border-gray-200 text-gray-700"><?= esc($tipos[$inst['tipo']] ?? $inst['tipo']) ?></span>
                </td>
                <td class="py-2.5 px-3 text-gray-600">
                  <?php if (! empty($inst['municipio'])): ?>
                    <span class="block"><?= esc($inst['municipio']) ?></span>
                  <?php endif; ?>
                  <?php if (! empty($inst['endereco'])): ?>
                    <span class="block text-gray-400 text-[12px] truncate max-w-[220px]" title="<?= esc($inst['endereco']) ?>"><?= esc($inst['endereco']) ?></span>
                  <?php endif; ?>
                  <?php if (empty($inst['municipio']) && empty($inst['endereco'])): ?>
                    <span class="text-gray-400">—</span>
                  <?php endif; ?>
                </td>
                <td class="py-2.5 px-3 text-gray-600"><?= esc(! empty($inst['telefone']) ? $inst['telefone'] : '—') ?></td>
                <td class="py-2.5 px-3 text-gray-600"><?= esc(! empty($inst['responsavel']) ? $inst['responsavel'] : '—') ?></td>
                <td class="py-2.5 px-3">
                  <?php $listaServicos = $servicosPor[(int) $inst['id']] ?? []; ?>
                  <?php if (empty($listaServicos)): ?>
                    <span class="text-gray-400">—</span>
                  <?php else: ?>
                    <div class="flex flex-wrap gap-1">
                      <?php foreach (array_slice($listaServicos, 0, 2) as $nomeServico): ?>
                        <span class="px-1.5 py-0.5 rounded-full text-[11px] font-medium border bg-blue-50 border-blue-200 text-blue-700"><?= esc($nomeServico) ?></span>
                      <?php endforeach; ?>
                      <?php if (count($listaServicos) > 2): ?>
                        <span class="px-1.5 py-0.5 rounded-full text-[11px] font-medium border bg-gray-50 border-gray-200 text-gray-600">+<?= count($listaServicos) - 2 ?></span>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td class="py-2.5 px-3">
                  <span class="px-2 py-0.5 rounded-full text-[11px] font-medium border <?= (int) $inst['ativo'] === 1 ? 'bg-green-50 border-green-200 text-green-700' : 'bg-gray-50 border-gray-200 text-gray-500' ?>">
                    <?= (int) $inst['ativo'] === 1 ? 'Ativa' : 'Inativa' ?>
                  </span>
                </td>
                <td class="py-2.5 px-3">
                  <div class="flex items-center gap-1.5">
                    <a href="<?= base_url('rede/instituicoes/' . (int) $inst['id']) ?>" title="Visualizar" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition">
                      <i data-lucide="eye" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
                    </a>
                    <a href="<?= base_url('rede/instituicoes/editar/' . (int) $inst['id']) ?>" title="Editar" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition">
                      <i data-lucide="pencil" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
                    </a>
                    <form method="POST" action="<?= base_url('rede/instituicoes/' . (int) $inst['id'] . '/situacao') ?>" onSubmit="return confirm('<?= (int) $inst['ativo'] === 1 ? 'Desativar esta instituição? Ela deixará de aparecer para novos encaminhamentos (histórico preservado).' : 'Reativar esta instituição?' ?>');">
                      <button type="submit" title="<?= (int) $inst['ativo'] === 1 ? 'Desativar' : 'Reativar' ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition cursor-pointer">
                        <i data-lucide="<?= (int) $inst['ativo'] === 1 ? 'toggle-right' : 'toggle-left' ?>" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="py-6 px-3 text-center text-gray-400">
                Nenhuma instituição<?= $temFiltros ? ' com os critérios informados.' : ' cadastrada ainda.' ?>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if (empty($instituicoes)): ?>
    <p class="text-center text-gray-400 text-[12.5px]">
      <a href="<?= base_url('rede/instituicoes/nova') ?>" class="text-primary hover:underline">Cadastrar a primeira instituição ›</a>
    </p>
  <?php endif; ?>

  <?php if (! empty($pager) && $pager->getPageCount() > 1): ?>
    <div class="flex justify-end">
      <?= $pager->links() ?>
    </div>
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