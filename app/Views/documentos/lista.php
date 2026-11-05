<?= $this->extend('layouts/admin') ?>

<?php
$temFiltros = $filtros['mulher'] !== '' || $filtros['tipo'] !== '' || $filtros['sigilo'] !== ''
    || $filtros['atendimento'] !== '' || $filtros['processo'] !== '' || $filtros['medida'] !== ''
    || $filtros['encaminhamento'] !== '' || $filtros['responsavel'] > 0
    || $filtros['data_de'] !== '' || $filtros['data_ate'] !== '';
?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex justify-between items-center gap-3">
    <div class="flex items-center gap-2">
      <h1 class="text-gray-600 text-[15px] font-medium">Documentos</h1>
      <?php if ($temFiltros): ?>
        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 border border-gray-200 text-gray-600">filtros ativos</span>
      <?php endif; ?>
    </div>
    <a href="<?= base_url('documentos/novo') ?>" class="btn-primary text-[13px] text-white px-3 py-2">
      <i data-lucide="file-plus" class="w-4 h-4"></i>
      Enviar documento
    </a>
  </header>

  <div class="flex items-center justify-end gap-2 flex-wrap">
    <button type="button" id="btn-filtros" class="btn-secondary text-[13px] px-3 py-2" aria-expanded="false" aria-controls="painel-filtros">
      <i data-lucide="filter" class="w-4 h-4"></i>
      Filtros
    </button>
  </div>

  <!-- FILTROS COMPACTOS (painel recolhível) -->
  <form method="GET" action="<?= base_url('documentos') ?>" id="painel-filtros" class="hidden w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex-col gap-3">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 bg-gray-100 border border-gray-200 flex items-center justify-center rounded-lg">
        <i data-lucide="filter" class="w-4 h-4 stroke-gray-600 stroke-[1.5]"></i>
      </div>
      <p class="text-gray-600 text-[13px] font-medium leading-4">Filtros</p>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
      <div class="flex flex-col gap-1.5">
        <label for="f_mulher" class="label-primary">Mulher</label>
        <input type="text" id="f_mulher" name="mulher" value="<?= esc((string) $filtros['mulher']) ?>" placeholder="Nome ou CPF" class="input-primary">
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
        <label for="f_sigilo" class="label-primary">Sigilo</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="f_sigilo" name="sigilo" class="select-primary">
            <option value="">Todos</option>
            <?php foreach ($sigilos as $chave => $rotulo): ?>
              <option value="<?= esc($chave) ?>" <?= $filtros['sigilo'] === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
            <?php endforeach; ?>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
        </div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_atendimento" class="label-primary">Atendimento</label>
        <input type="text" id="f_atendimento" name="atendimento" value="<?= esc((string) $filtros['atendimento']) ?>" placeholder="Protocolo" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_processo" class="label-primary">Processo</label>
        <input type="text" id="f_processo" name="processo" value="<?= esc((string) $filtros['processo']) ?>" placeholder="Nº do processo" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_medida" class="label-primary">Medida protetiva</label>
        <input type="text" id="f_medida" name="medida" value="<?= esc((string) $filtros['medida']) ?>" placeholder="Nº da medida" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_encaminhamento" class="label-primary">Encaminhamento</label>
        <input type="text" id="f_encaminhamento" name="encaminhamento" value="<?= esc((string) $filtros['encaminhamento']) ?>" placeholder="Nº do encaminhamento" class="input-primary">
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
        <label for="f_data_de" class="label-primary">Data de</label>
        <input type="date" id="f_data_de" name="data_de" value="<?= esc((string) $filtros['data_de']) ?>" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_data_ate" class="label-primary">Data até</label>
        <input type="date" id="f_data_ate" name="data_ate" value="<?= esc((string) $filtros['data_ate']) ?>" class="input-primary">
      </div>
    </div>

    <div class="flex items-center gap-2">
      <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2 h-11">
        <i data-lucide="filter" class="w-4 h-4"></i>
        Filtrar
      </button>
      <a href="<?= base_url('documentos') ?>" class="btn-secondary text-[13px] px-4 py-2 h-11">Limpar</a>
    </div>
  </form>

  <!-- LISTAGEM -->
  <div class="w-full bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl overflow-hidden">
    <div class="w-full overflow-x-auto">
      <table class="w-full text-[13px]">
        <thead>
          <tr class="bg-gray-50 border-b border-gray-200">
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Arquivo</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Tipo</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Mulher</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Atendimento</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Processo</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Data</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Responsável</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Sigilo</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (! empty($documentos)): ?>
            <?php foreach ($documentos as $d): ?>
              <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
                <td class="py-2.5 px-3">
                  <a href="<?= base_url('documentos/' . (int) $d['id']) ?>" class="text-gray-700 font-medium hover:text-primary hover:underline block truncate max-w-[220px] break-all" title="<?= esc($d['arquivo_original']) ?>">
                    <span class="inline-flex items-center gap-1.5">
                      <i data-lucide="file-text" class="w-4 h-4 stroke-gray-400 stroke-[1.5] shrink-0"></i>
                      <span class="truncate"><?= esc($d['arquivo_original']) ?></span>
                    </span>
                  </a>
                </td>
                <td class="py-2.5 px-3 text-gray-600"><?= esc($tipos[$d['tipo']] ?? $d['tipo']) ?></td>
                <td class="py-2.5 px-3">
                  <a href="<?= base_url('mulheres/detalhes/' . (int) $d['mulher_id']) ?>" class="text-primary hover:underline"><?= esc($d['mulher_nome']) ?></a>
                </td>
                <td class="py-2.5 px-3 text-gray-600">
                  <?= ! empty($d['atendimento_id']) ? '<a href="' . base_url('atendimentos/' . (int) $d['atendimento_id']) . '" class="text-primary hover:underline font-mono">#' . esc($d['atendimento_protocolo'] ?? $d['atendimento_id']) . '</a>' : '—' ?>
                </td>
                <td class="py-2.5 px-3 text-gray-600">
                  <?= ! empty($d['processo_id']) ? '<a href="' . base_url('processos/' . (int) $d['processo_id']) . '" class="text-primary hover:underline block truncate max-w-[160px]" title="' . esc($d['processo_numero'] ?? '') . '">' . esc($d['processo_numero'] ?? '#' . (int) $d['processo_id']) . '</a>' : '—' ?>
                </td>
                <td class="py-2.5 px-3 text-gray-600"><?= ! empty($d['created_at']) ? date('d/m/Y', strtotime($d['created_at'])) : '—' ?></td>
                <td class="py-2.5 px-3 text-gray-600"><?= esc($d['usuario_nome'] ?? '—') ?></td>
                <td class="py-2.5 px-3">
                  <span class="px-2 py-0.5 rounded-full text-[11px] font-medium border <?= $sigiloCores[$d['sigilo']] ?? 'bg-gray-50 border-gray-200 text-gray-700' ?>"><?= esc($sigilos[$d['sigilo']] ?? $d['sigilo']) ?></span>
                </td>
                <td class="py-2.5 px-3">
                  <div class="flex items-center gap-1">
                    <a href="<?= base_url('documentos/' . (int) $d['id']) ?>" title="Visualizar registro" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition">
                      <i data-lucide="eye" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
                    </a>
                    <a href="<?= base_url('documentos/download/' . (int) $d['id']) ?>" title="Baixar arquivo" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition">
                      <i data-lucide="download" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
                    </a>
                    <a href="<?= base_url('documentos/editar/' . (int) $d['id']) ?>" title="Editar metadados" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition">
                      <i data-lucide="pencil" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
                    </a>
                    <form method="POST" action="<?= base_url('documentos/' . (int) $d['id'] . '/excluir') ?>" class="m-0 p-0 js-confirm-excluir" data-nome="<?= esc($d['arquivo_original']) ?>">
                      <button type="submit" title="Excluir documento" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-red-200 hover:bg-red-50 transition cursor-pointer">
                        <i data-lucide="trash-2" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="9" class="py-6 px-3 text-center text-gray-400">
                Nenhum documento<?= $temFiltros ? ' com os critérios informados.' : ' enviado ainda.' ?>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if (empty($documentos)): ?>
    <p class="text-center text-gray-400 text-[12.5px]">
      <a href="<?= base_url('documentos/novo') ?>" class="text-primary hover:underline">Enviar o primeiro documento ›</a>
    </p>
  <?php endif; ?>

  <?php if (! empty($pager) && $pager->getPageCount() > 1): ?>
    <div class="w-full flex items-center justify-center pt-1">
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

  // Exclusão controlada (POST + confirmação).
  document.querySelectorAll('form.js-confirm-excluir').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var nome = form.getAttribute('data-nome') || '';
      var acao = (typeof window.showConfirm === 'function')
        ? window.showConfirm({
            type: 'danger',
            title: 'Excluir documento?',
            message: 'O documento "' + nome + '" será ocultado da listagem. O arquivo físico e o histórico serão preservados.',
            confirmText: 'Excluir'
          })
        : Promise.resolve(window.confirm('Excluir o documento "' + nome + '"?'));
      acao.then(function (ok) {
        if (ok) form.submit();
      });
    });
  });
})();
</script>
<?= $this->endSection() ?>