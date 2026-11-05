<?= $this->extend('layouts/admin') ?>

<?php
$r = $registro;
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
?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex items-center justify-between">
    <div class="flex items-center gap-2">
      <a href="<?= base_url('sistema/auditoria') ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 transition" title="Voltar">
        <i data-lucide="arrow-left" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
      </a>
      <div class="flex items-center gap-2">
        <h1 class="text-gray-600 text-[15px] font-medium"><?= esc($titulo) ?> #<?= (int) $r['id'] ?></h1>
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium border <?= $acaoCor[$r['acao']] ?? 'bg-gray-50 border-gray-200 text-gray-700' ?>">
          <?= esc($acaoRotulo) ?>
        </span>
      </div>
    </div>
  </header>

  <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 bg-blue-50 border border-blue-200 flex items-center justify-center rounded-lg">
        <i data-lucide="scroll-text" class="w-4 h-4 stroke-blue-600 stroke-[1.5]"></i>
      </div>
      <p class="text-gray-600 text-[13px] font-medium leading-4">Registro de auditoria</p>
    </div>

    <div class="flex flex-col divide-y divide-gray-100">
      <div class="flex justify-between py-2 gap-3">
        <span class="text-gray-400 text-[12px] shrink-0">Data e hora</span>
        <span class="text-gray-600 text-[12.5px]"><?= ! empty($r['created_at']) ? date('d/m/Y H:i:s', strtotime($r['created_at'])) : '—' ?></span>
      </div>
      <div class="flex justify-between py-2 gap-3">
        <span class="text-gray-400 text-[12px] shrink-0">Usuário</span>
        <span class="text-gray-600 text-[12.5px]"><?= esc($r['usuario_nome'] ?? 'Sistema') ?></span>
      </div>
      <?php if (! empty($r['usuario_email'])): ?>
        <div class="flex justify-between py-2 gap-3">
          <span class="text-gray-400 text-[12px] shrink-0">E-mail</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($r['usuario_email']) ?></span>
        </div>
      <?php endif; ?>
      <div class="flex justify-between py-2 gap-3">
        <span class="text-gray-400 text-[12px] shrink-0">Ação</span>
        <span class="text-gray-600 text-[12.5px]"><?= esc($acaoRotulo) ?></span>
      </div>
      <div class="flex justify-between py-2 gap-3">
        <span class="text-gray-400 text-[12px] shrink-0">Módulo</span>
        <span class="text-gray-600 text-[12.5px]"><?= esc($moduloRotulo) ?></span>
      </div>
      <div class="flex justify-between py-2 gap-3">
        <span class="text-gray-400 text-[12px] shrink-0">Registro afetado</span>
        <span class="text-gray-600 text-[12.5px]">
          <?= ! empty($r['registro_tipo']) ? esc(ucfirst((string) $r['registro_tipo'])) . ($r['registro_id'] ? ' #' . (int) $r['registro_id'] : '') : '—' ?>
        </span>
      </div>
      <?php if (! empty($r['registro_ref'])): ?>
        <div class="flex justify-between py-2 gap-3">
          <span class="text-gray-400 text-[12px] shrink-0">Referência</span>
          <span class="text-gray-600 text-[12.5px] break-all"><?= esc($r['registro_ref']) ?></span>
        </div>
      <?php endif; ?>
      <div class="flex justify-between py-2 gap-3">
        <span class="text-gray-400 text-[12px] shrink-0">IP</span>
        <span class="text-gray-600 text-[12.5px] font-mono text-[12px]"><?= esc($r['ip'] ?? '—') ?></span>
      </div>
      <div class="flex justify-between py-2 gap-3">
        <span class="text-gray-400 text-[12px] shrink-0">Rota</span>
        <span class="text-gray-600 text-[12.5px] break-all font-mono text-[12px]"><?= esc($r['rota'] ?? '—') ?></span>
      </div>
      <div class="flex justify-between py-2 gap-3">
        <span class="text-gray-400 text-[12px] shrink-0">Método HTTP</span>
        <span class="text-gray-600 text-[12.5px]"><?= esc($r['metodo'] ?? '—') ?></span>
      </div>
    </div>
  </div>

  <?php if (! empty($detalhes)): ?>
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-amber-50 border border-amber-200 flex items-center justify-center rounded-lg">
          <i data-lucide="list-checks" class="w-4 h-4 stroke-amber-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Detalhes do contexto</p>
      </div>

      <div class="flex flex-col divide-y divide-gray-100">
        <?php foreach ($detalhes as $chave => $valor): ?>
          <div class="flex justify-between py-2 gap-3">
            <span class="text-gray-400 text-[12px] shrink-0"><?= esc(ucwords(str_replace('_', ' ', (string) $chave))) ?></span>
            <span class="text-gray-600 text-[12.5px] text-right break-all">
              <?= is_bool($valor) ? ($valor ? 'Sim' : 'Não') : (($valor === null || $valor === '') ? '—' : esc((string) $valor)) ?>
            </span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</section>
<?= $this->endSection() ?>