<?= $this->extend('layouts/admin') ?>
<?php
$titulos = ['ativos' => 'Casos ativos', 'urgentes' => 'Casos urgentes', 'atrasados' => 'Casos atrasados', 'encerrados' => 'Casos encerrados'];
$priorCor = ['emergencia' => 'bg-red-50 border-red-200 text-red-700', 'alta' => 'bg-amber-50 border-amber-200 text-amber-700', 'media' => 'bg-yellow-50 border-yellow-200 text-yellow-700', 'baixa' => 'bg-green-50 border-green-200 text-green-700'];
$situacaoCor = ['novo' => 'bg-amber-50 border-amber-200 text-amber-700', 'em_acolhimento' => 'bg-purple-50 border-purple-200 text-purple-700', 'em_acompanhamento' => 'bg-blue-50 border-blue-200 text-blue-700', 'encaminhado' => 'bg-blue-50 border-blue-200 text-blue-700', 'aguardando_retorno' => 'bg-amber-50 border-amber-200 text-amber-700', 'em_atendimento_rede' => 'bg-purple-50 border-purple-200 text-purple-700', 'resolvido' => 'bg-green-50 border-green-200 text-green-700', 'encerrado' => 'bg-gray-50 border-gray-200 text-gray-500', 'arquivado' => 'bg-gray-100 border-gray-200 text-gray-500'];
$temFiltros = array_filter($filtros) !== [];
?>
<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex items-center justify-between gap-3"><div class="flex items-center gap-2"><h1 class="text-gray-600 text-[15px] font-medium"><?= esc($titulos[$visao]) ?></h1><?php if ($temFiltros): ?><span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 border border-gray-200 text-gray-600">filtros ativos</span><?php endif ?></div></header>
  <div class="flex items-center justify-between gap-2 flex-wrap"><div class="flex items-center gap-1 border border-gray-200 rounded-lg p-1 bg-white"><?php foreach ($titulos as $chave => $rotulo): ?><?php if ($visao === $chave): ?><span class="px-3 py-1.5 rounded-md text-[12.5px] font-medium text-white bg-primary cursor-default"><?= esc($rotulo) ?></span><?php else: ?><a href="<?= base_url('casos/' . $chave) ?>" class="px-3 py-1.5 rounded-md text-[12.5px] font-medium text-gray-600 hover:bg-gray-50 transition"><?= esc($rotulo) ?></a><?php endif ?><?php endforeach ?></div><button type="button" id="btn-filtros" class="btn-secondary text-[13px] px-3 py-2" aria-expanded="false" aria-controls="painel-filtros"><i data-lucide="filter" class="w-4 h-4"></i>Filtros</button></div>
  <form method="GET" action="<?= base_url('casos/' . $visao) ?>" id="painel-filtros" class="hidden w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex-col gap-3"><div class="flex items-center gap-2"><div class="w-8 h-8 bg-gray-100 border border-gray-200 flex items-center justify-center rounded-lg"><i data-lucide="filter" class="w-4 h-4 stroke-gray-600 stroke-[1.5]"></i></div><p class="text-gray-600 text-[13px] font-medium">Filtros</p></div><div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3"><div class="flex flex-col gap-1.5"><label class="label-primary">Protocolo, mulher ou CPF</label><input name="q" value="<?= esc($filtros['q']) ?>" class="input-primary" placeholder="Pesquisar"></div><?php foreach (['prioridade' => 'Prioridade', 'situacao' => 'Situação'] as $campo => $rotulo): ?><div class="flex flex-col gap-1.5"><label class="label-primary"><?= $rotulo ?></label><div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden"><select name="<?= $campo ?>" class="select-primary"><option value="">Todas</option><?php foreach (($campo === 'prioridade' ? $prioridades : $situacoes) as $k => $v): ?><option value="<?= esc($k) ?>" <?= $filtros[$campo] === $k ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach ?></select><i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i></div></div><?php endforeach ?><div class="flex flex-col gap-1.5"><label class="label-primary">Responsável</label><div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden"><select name="responsavel" class="select-primary"><option value="">Todos</option><?php foreach ($equipe as $u): ?><option value="<?= (int) $u['id'] ?>" <?= (int) $filtros['responsavel'] === (int) $u['id'] ? 'selected' : '' ?>><?= esc($u['nome']) ?></option><?php endforeach ?></select><i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i></div></div></div><div class="flex items-center gap-2"><button class="btn-primary text-[13px] text-white px-4 py-2 h-11"><i data-lucide="filter" class="w-4 h-4"></i>Filtrar</button><a href="<?= base_url('casos/' . $visao) ?>" class="btn-secondary text-[13px] px-4 py-2 h-11">Limpar</a></div></form>
  <div class="w-full bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl overflow-hidden"><div class="w-full overflow-x-auto"><table class="w-full text-[13px]"><thead><tr class="bg-gray-50 border-b border-gray-200"><?php foreach (['Protocolo', 'Mulher', 'Abertura', 'Prioridade', 'Situação', 'Responsável', 'Acompanhamento'] as $coluna): ?><th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]"><?= $coluna ?></th><?php endforeach ?></tr></thead><tbody><?php if (! empty($casos)): foreach ($casos as $c): ?><tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50"><td class="py-2.5 px-3"><a href="<?= base_url('casos/' . (int) $c['id']) ?>" class="text-gray-700 font-medium hover:text-primary hover:underline font-mono"><?= esc($c['protocolo']) ?></a></td><td class="py-2.5 px-3 text-gray-600"><?= esc($c['mulher_nome']) ?></td><td class="py-2.5 px-3 text-gray-600"><?= date('d/m/Y', strtotime($c['data_abertura'])) ?></td><td class="py-2.5 px-3"><span class="px-2 py-0.5 rounded-full text-[11px] font-medium border <?= $priorCor[$c['prioridade']] ?? 'bg-gray-50 border-gray-200 text-gray-600' ?>"><?= esc($prioridades[$c['prioridade']] ?? $c['prioridade']) ?></span></td><td class="py-2.5 px-3"><span class="px-2 py-0.5 rounded-full text-[11px] font-medium border <?= $situacaoCor[$c['situacao']] ?? 'bg-gray-50 border-gray-200 text-gray-600' ?>"><?= esc($situacoes[$c['situacao']] ?? $c['situacao']) ?></span></td><td class="py-2.5 px-3 text-gray-600"><?= esc($c['responsavel_nome'] ?? '—') ?></td><td class="py-2.5 px-3"><?php if ($c['atrasado']): ?><span class="px-2 py-0.5 rounded-full text-[11px] font-medium border bg-red-50 border-red-200 text-red-700">Pendente vencida</span><?php else: ?><span class="text-gray-400">—</span><?php endif ?></td></tr><?php endforeach; else: ?><tr><td colspan="7" class="py-8 px-3 text-center text-gray-400 text-[13px]">Nenhum caso encontrado.</td></tr><?php endif ?></tbody></table></div></div>
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
