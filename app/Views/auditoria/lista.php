<?= $this->extend('layouts/admin') ?>

<?php
$acaoCor = [
    'LOGIN'           => 'bg-emerald-50 border-emerald-200 text-emerald-700',
    'LOGOUT'          => 'bg-gray-50 border-gray-200 text-gray-600',
    'CREATE'          => 'bg-blue-50 border-blue-200 text-blue-700',
    'UPDATE'          => 'bg-amber-50 border-amber-200 text-amber-700',
    'DELETE'          => 'bg-red-50 border-red-200 text-red-700',
    'VIEW_SENSITIVE'  => 'bg-purple-50 border-purple-200 text-purple-700',
    'DOWNLOAD'        => 'bg-cyan-50 border-cyan-200 text-cyan-700',
    'UPLOAD'          => 'bg-indigo-50 border-indigo-200 text-indigo-700',
    'STATUS_CHANGE'   => 'bg-amber-50 border-amber-200 text-amber-700',
    'PRIORITY_CHANGE' => 'bg-orange-50 border-orange-200 text-orange-700',
    'FORWARD'         => 'bg-teal-50 border-teal-200 text-teal-700',
    'CLOSE'           => 'bg-gray-50 border-gray-200 text-gray-600',
];
$temFiltros = $filtros['usuario_id'] > 0 || $filtros['acao'] !== '' || $filtros['modulo'] !== ''
    || $filtros['registro'] !== '' || $filtros['ip'] !== ''
    || $filtros['data_de'] !== '' || $filtros['data_ate'] !== '';
?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex justify-between items-center gap-3">
    <div class="flex items-center gap-2">
      <h1 class="text-gray-600 text-[15px] font-medium">Auditoria</h1>
      <?php if ($temFiltros): ?>
        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 border border-gray-200 text-gray-600">filtros ativos</span>
      <?php endif; ?>
    </div>
  </header>

  <div class="flex items-center gap-2 px-3 py-2 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 text-[12px] leading-4">
    <i data-lucide="shield-check" class="w-4 h-4 shrink-0 stroke-amber-700"></i>
    <p>Este histórico é de escrita única (append-only): as ações registradas não podem ser editadas nem apagadas.</p>
  </div>

  <div class="flex items-center justify-end">
    <button type="button" id="btn-filtros" class="btn-secondary text-[13px] px-3 py-2" aria-expanded="false" aria-controls="painel-filtros">
      <i data-lucide="filter" class="w-4 h-4"></i>
      Filtros
    </button>
  </div>

  <!-- FILTROS COMPACTOS (painel recolhível) -->
  <form method="GET" action="<?= base_url('sistema/auditoria') ?>" id="painel-filtros" class="hidden w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex-col gap-3">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 bg-gray-100 border border-gray-200 flex items-center justify-center rounded-lg">
        <i data-lucide="filter" class="w-4 h-4 stroke-gray-600 stroke-[1.5]"></i>
      </div>
      <p class="text-gray-600 text-[13px] font-medium leading-4">Filtros</p>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
      <div class="flex flex-col gap-1.5">
        <label for="f_usuario" class="label-primary">Usuário</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="f_usuario" name="usuario_id" class="select-primary">
            <option value="">Todos</option>
            <?php foreach ($usuarios as $u): ?>
              <option value="<?= (int) $u['id'] ?>" <?= (int) $filtros['usuario_id'] === (int) $u['id'] ? 'selected' : '' ?>><?= esc($u['nome']) ?></option>
            <?php endforeach; ?>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
        </div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_acao" class="label-primary">Ação</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="f_acao" name="acao" class="select-primary">
            <option value="">Todas</option>
            <?php foreach ($acoes as $chave => $rotulo): ?>
              <option value="<?= esc($chave) ?>" <?= $filtros['acao'] === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
            <?php endforeach; ?>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
        </div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_modulo" class="label-primary">Módulo</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="f_modulo" name="modulo" class="select-primary">
            <option value="">Todos</option>
            <?php foreach ($modulos as $chave => $rotulo): ?>
              <option value="<?= esc($chave) ?>" <?= $filtros['modulo'] === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
            <?php endforeach; ?>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
        </div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_registro" class="label-primary">Registro afetado</label>
        <input type="text" id="f_registro" name="registro" value="<?= esc((string) $filtros['registro']) ?>" placeholder="Protocolo, nome ou nº" class="input-primary">
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_ip" class="label-primary">IP</label>
        <input type="text" id="f_ip" name="ip" value="<?= esc((string) $filtros['ip']) ?>" placeholder="Ex.: 127.0.0.1" class="input-primary">
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
      <a href="<?= base_url('sistema/auditoria') ?>" class="btn-secondary text-[13px] px-4 py-2 h-11">Limpar</a>
    </div>
  </form>

  <!-- LISTAGEM -->
  <div class="w-full bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl overflow-hidden">
    <div class="w-full overflow-x-auto">
      <table class="w-full text-[13px]">
        <thead>
          <tr class="bg-gray-50 border-b border-gray-200">
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Data e hora</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Usuário</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Ação</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Módulo</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Registro</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">IP</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (! empty($registros)): ?>
            <?php foreach ($registros as $r): ?>
              <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
                <td class="py-2.5 px-3 text-gray-600 whitespace-nowrap"><?= ! empty($r['created_at']) ? date('d/m/Y H:i:s', strtotime($r['created_at'])) : '—' ?></td>
                <td class="py-2.5 px-3 text-gray-600">
                  <?= esc($r['usuario_nome'] ?? 'Sistema') ?>
                </td>
                <td class="py-2.5 px-3">
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium border <?= $acaoCor[$r['acao']] ?? 'bg-gray-50 border-gray-200 text-gray-600' ?>">
                    <?= esc($acoes[$r['acao']] ?? $r['acao']) ?>
                  </span>
                </td>
                <td class="py-2.5 px-3 text-gray-600"><?= esc($modulos[$r['modulo']] ?? $r['modulo']) ?></td>
                <td class="py-2.5 px-3">
                  <?php if (! empty($r['registro_tipo']) || ! empty($r['registro_ref'])): ?>
                    <span class="text-gray-600 font-medium"><?= esc(ucfirst((string) $r['registro_tipo'])) ?></span>
                    <span class="text-gray-400"><?= ! empty($r['registro_ref']) ? '· ' . esc($r['registro_ref']) : '' ?></span>
                  <?php else: ?>
                    <span class="text-gray-400">—</span>
                  <?php endif; ?>
                </td>
                <td class="py-2.5 px-3 font-mono text-[12px] text-gray-400"><?= esc($r['ip'] ?? '—') ?></td>
                <td class="py-2.5 px-3">
                  <div class="flex items-center gap-1.5">
                    <a href="<?= base_url('sistema/auditoria/' . (int) $r['id']) ?>" title="Ver detalhes" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition">
                      <i data-lucide="eye" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="py-6 px-3 text-center text-gray-400">
                Nenhum registro de auditoria<?= $temFiltros ? ' com os critérios informados.' : ' registrado ainda.' ?>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

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
})();
</script>
<?= $this->endSection() ?>