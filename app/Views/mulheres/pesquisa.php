<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
$statusRotulo = [
    'novo'                    => 'Fila',
    'aguardando_atendimento'  => 'Aguardando',
    'em_andamento'            => 'Em andamento',
    'encerrado'               => 'Encerrado',
];
$statusCor = [
    'novo'                    => 'bg-amber-50 border-amber-200 text-amber-700',
    'aguardando_atendimento'  => 'bg-amber-50 border-amber-200 text-amber-700',
    'em_andamento'            => 'bg-blue-50 border-blue-200 text-blue-700',
    'encerrado'               => 'bg-green-50 border-green-200 text-green-700',
];
$temFiltros = ($filtros['q'] ?? '') !== '' || ($filtros['nome'] ?? '') !== ''
    || ($filtros['nome_social'] ?? '') !== '' || ($filtros['cpf'] ?? '') !== ''
    || ($filtros['telefone'] ?? '') !== '' || ($filtros['whatsapp'] ?? '') !== ''
    || ($filtros['municipio'] ?? '') !== '' || ($filtros['bairro'] ?? '') !== ''
    || ($filtros['zona'] ?? '') !== '' || ($filtros['faixa_etaria'] ?? '') !== ''
    || ($filtros['idade'] ?? '') !== '';
?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex justify-between items-center">
    <div class="flex items-center gap-2">
      <h1 class="text-gray-600 text-[15px] font-medium">Pesquisa de Mulheres</h1>
      <?php if ($temFiltros): ?>
        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 border border-gray-200 text-gray-600">filtros ativos</span>
      <?php endif; ?>
    </div>
    <a href="<?= base_url('mulheres/cadastro') ?>" class="btn-primary text-[13px] text-white px-3 py-2">
      <i data-lucide="user-plus" class="w-4 h-4"></i>
      Novo cadastro
    </a>
  </header>

  <div class="flex items-center justify-end gap-2 flex-wrap">
    <button type="button" id="btn-filtros" class="btn-secondary text-[13px] px-3 py-2" aria-expanded="false" aria-controls="painel-filtros">
      <i data-lucide="filter" class="w-4 h-4"></i>
      Filtros
    </button>
  </div>

  <form method="GET" action="<?= base_url('mulheres/pesquisa') ?>" id="painel-filtros" class="hidden w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex-col gap-3">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 bg-gray-100 border border-gray-200 flex items-center justify-center rounded-lg">
        <i data-lucide="filter" class="w-4 h-4 stroke-gray-600 stroke-[1.5]"></i>
      </div>
      <p class="text-gray-600 text-[13px] font-medium leading-4">Filtros</p>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
      <div class="flex flex-col gap-1.5">
        <label for="f_q" class="label-primary">Busca rápida</label>
        <input type="text" id="f_q" name="q" value="<?= esc((string) ($filtros['q'] ?? '')) ?>" placeholder="Nome ou CPF" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_nome" class="label-primary">Nome</label>
        <input type="text" id="f_nome" name="nome" value="<?= esc((string) ($filtros['nome'] ?? '')) ?>" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_nome_social" class="label-primary">Nome social</label>
        <input type="text" id="f_nome_social" name="nome_social" value="<?= esc((string) ($filtros['nome_social'] ?? '')) ?>" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_cpf" class="label-primary">CPF</label>
        <input type="text" id="f_cpf" name="cpf" value="<?= esc((string) ($filtros['cpf'] ?? '')) ?>" placeholder="000.000.000-00" class="input-primary" data-mascara="cpf">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_telefone" class="label-primary">Telefone</label>
        <input type="text" id="f_telefone" name="telefone" value="<?= esc((string) ($filtros['telefone'] ?? '')) ?>" placeholder="(00) 0000-0000" class="input-primary" data-mascara="telefone_fixo">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_whatsapp" class="label-primary">WhatsApp</label>
        <input type="text" id="f_whatsapp" name="whatsapp" value="<?= esc((string) ($filtros['whatsapp'] ?? '')) ?>" placeholder="(00) 00000-0000" class="input-primary" data-mascara="telefone_celular">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_municipio" class="label-primary">Município</label>
        <input type="text" id="f_municipio" name="municipio" value="<?= esc((string) ($filtros['municipio'] ?? '')) ?>" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_bairro" class="label-primary">Bairro</label>
        <input type="text" id="f_bairro" name="bairro" value="<?= esc((string) ($filtros['bairro'] ?? '')) ?>" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_zona" class="label-primary">Zona</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="f_zona" name="zona" class="select-primary">
            <option value="">Todas</option>
            <?php foreach ($zonas as $chave => $rotulo): ?>
              <option value="<?= esc($chave) ?>" <?= ($filtros['zona'] ?? '') === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
            <?php endforeach; ?>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
        </div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_faixa_etaria" class="label-primary">Faixa etária</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="f_faixa_etaria" name="faixa_etaria" class="select-primary">
            <option value="">Todas</option>
            <?php foreach ($faixasEtarias as $chave => $rotulo): ?>
              <option value="<?= esc($chave) ?>" <?= ($filtros['faixa_etaria'] ?? '') === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
            <?php endforeach; ?>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
        </div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_idade" class="label-primary">Idade exata</label>
        <input type="number" id="f_idade" name="idade" min="0" max="120" value="<?= ($filtros['idade'] ?? '') !== '' ? (int) $filtros['idade'] : '' ?>" class="input-primary">
      </div>
    </div>

    <div class="flex items-center gap-2">
      <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2 h-11">
        <i data-lucide="search" class="w-4 h-4"></i>
        Pesquisar
      </button>
      <a href="<?= base_url('mulheres/pesquisa') ?>" class="btn-secondary text-[13px] px-4 py-2 h-11">Limpar</a>
      <span class="text-gray-400 text-[12px]"><?= count($mulheres) ?> resultado(s) nesta página</span>
    </div>
  </form>

  <div class="w-full bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl overflow-hidden">
    <div class="w-full overflow-x-auto">
      <table class="w-full text-[13px]">
        <thead>
          <tr class="bg-gray-50 border-b border-gray-200">
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Nome</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Nome social</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">CPF</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Telefone</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Município</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Status</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (! empty($mulheres)): ?>
            <?php foreach ($mulheres as $m): ?>
              <?php
              $info = $status[(int) $m['id']] ?? null;
              $situacao = $info['situacao'] ?? null;
              ?>
              <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
                <td class="py-2.5 px-3 text-gray-600 font-medium">
                  <a href="<?= base_url('mulheres/detalhes/' . (int) $m['id']) ?>" class="text-primary hover:underline"><?= esc($m['nome_completo']) ?></a>
                </td>
                <td class="py-2.5 px-3 text-gray-600"><?= esc($m['nome_social'] ?: '—') ?></td>
                <td class="py-2.5 px-3 text-gray-600 font-mono text-[12.5px]"><?= esc($m['cpf'] ?: '—') ?></td>
                <td class="py-2.5 px-3 text-gray-600"><?= esc($m['telefone'] ?: '—') ?></td>
                <td class="py-2.5 px-3 text-gray-600"><?= esc(trim(($m['municipio'] ?? '') . ($m['estado'] ? '/' . $m['estado'] : '')) ?: '—') ?></td>
                <td class="py-2.5 px-3">
                  <?php if ($situacao !== null): ?>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium border <?= $statusCor[$situacao] ?? 'bg-gray-50 border-gray-200 text-gray-600' ?>"><?= esc($statusRotulo[$situacao] ?? '—') ?></span>
                  <?php else: ?>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-50 border border-gray-200 text-gray-500">Sem atendimento</span>
                  <?php endif; ?>
                  <?php if (! empty($m['user_id'])): ?>
                    <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-600" title="Possui conta de acesso">Com conta</span>
                  <?php else: ?>
                    <span class="ml-1 text-[10px] text-gray-400" title="Sem conta de acesso">Sem conta</span>
                  <?php endif; ?>
                </td>
                <td class="py-2.5 px-3">
                  <div class="flex items-center gap-1.5">
                    <a href="<?= base_url('mulheres/detalhes/' . (int) $m['id']) ?>" title="Visualizar" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition">
                      <i data-lucide="eye" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
                    </a>
                    <a href="<?= base_url('mulheres/editar/' . (int) $m['id']) ?>" title="Editar" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition">
                      <i data-lucide="pencil" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
                    </a>
                    <a href="<?= base_url('mulheres/historico/' . (int) $m['id']) ?>" title="Ver histórico" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition">
                      <i data-lucide="history" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
                    </a>
                    <a href="<?= base_url('mulheres/relatorio-pdf/' . (int) $m['id']) ?>" target="_blank" rel="noopener" title="Relatório PDF" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition">
                      <i data-lucide="file-text" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="py-6 px-3 text-center text-gray-400">Nenhuma mulher encontrada com os critérios informados.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

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