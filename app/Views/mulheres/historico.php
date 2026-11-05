<?= $this->extend('layouts/admin') ?>

<?php
$m = $mulher;

/**
 * Cores do selo de situação por módulo, no mesmo formato já usado no histórico
 * anterior para os atendimentos.
 */
$situacaoCor = [
    'novo'                   => 'bg-amber-50 border-amber-200 text-amber-700',
    'aguardando_atendimento' => 'bg-amber-50 border-amber-200 text-amber-700',
    'em_andamento'           => 'bg-blue-50 border-blue-200 text-blue-700',
    'encerrado'              => 'bg-green-50 border-green-200 text-green-700',
    'concluido'              => 'bg-green-50 border-green-200 text-green-700',
    'concluida'              => 'bg-green-50 border-green-200 text-green-700',
    'pendente'               => 'bg-amber-50 border-amber-200 text-amber-700',
    'resolvido'              => 'bg-green-50 border-green-200 text-green-700',
    'resolvida'              => 'bg-green-50 border-green-200 text-green-700',
    'cancelado'              => 'bg-gray-50 border-gray-200 text-gray-600',
    'cancelada'              => 'bg-gray-50 border-gray-200 text-gray-600',
    'arquivado'              => 'bg-gray-50 border-gray-200 text-gray-600',
    'negada'                 => 'bg-gray-50 border-gray-200 text-gray-600',
];

/** Tom do ícone de cada módulo, para dar leitura visual rápida. */
$moduloCor = [
    'caso'           => 'bg-blue-50 border-blue-200 text-blue-600',
    'atendimento'    => 'bg-indigo-50 border-indigo-200 text-indigo-600',
    'sos'            => 'bg-red-50 border-red-200 text-red-600',
    'acolhimento'    => 'bg-cyan-50 border-cyan-200 text-cyan-600',
    'avaliacao'      => 'bg-orange-50 border-orange-200 text-orange-600',
    'encaminhamento' => 'bg-violet-50 border-violet-200 text-violet-600',
    'medida'         => 'bg-emerald-50 border-emerald-200 text-emerald-600',
    'descumprimento' => 'bg-rose-50 border-rose-200 text-rose-600',
    'processo'       => 'bg-slate-100 border-slate-200 text-slate-600',
    'documento'      => 'bg-stone-100 border-stone-200 text-stone-600',
    'compromisso'    => 'bg-teal-50 border-teal-200 text-teal-600',
    'tarefa'         => 'bg-lime-50 border-lime-200 text-lime-600',
    'manifestacao'   => 'bg-fuchsia-50 border-fuchsia-200 text-fuchsia-600',
];

$base = base_url('mulheres/historico/' . (int) $m['id']);
$link = static fn (string $modulo): string => $modulo === ''
    ? $base
    : $base . '?modulo=' . rawurlencode($modulo);
?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex items-center justify-between">
    <div class="flex items-center gap-2">
      <a href="<?= base_url('mulheres/detalhes/' . (int) $m['id']) ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 transition" title="Voltar">
        <i data-lucide="arrow-left" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
      </a>
      <div class="flex flex-col">
        <h1 class="text-gray-600 text-[15px] font-medium">Histórico — <?= esc($m['nome_completo']) ?></h1>
        <span class="text-gray-400 text-[12px] leading-4">
          Trajetória no sistema · <?= (int) $total ?> <?= (int) $total === 1 ? 'registro' : 'registros' ?>
        </span>
      </div>
    </div>
    <div class="flex items-center gap-2">
      <a href="<?= base_url('mulheres/editar/' . (int) $m['id']) ?>" class="btn-secondary text-[13px] px-3 py-2">
        <i data-lucide="pencil" class="w-4 h-4"></i>
        Editar
      </a>
      <a href="<?= base_url('atendimentos/novo?mulher_id=' . (int) $m['id']) ?>" class="btn-primary text-[13px] text-white px-3 py-2">
        <i data-lucide="clipboard-plus" class="w-4 h-4"></i>
        Novo atendimento
      </a>
    </div>
  </header>

  <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 bg-blue-50 border border-blue-200 flex items-center justify-center rounded-lg">
        <i data-lucide="user" class="w-4 h-4 stroke-blue-600 stroke-[1.5]"></i>
      </div>
      <p class="text-gray-600 text-[13px] font-medium leading-4">Dados do cadastro</p>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-x-3 gap-y-2 pt-2">
      <div class="flex flex-col">
        <span class="text-gray-400 text-[11.5px]">Nome</span>
        <span class="text-gray-600 text-[12.5px]"><?= esc($m['nome_completo']) ?></span>
      </div>
      <div class="flex flex-col">
        <span class="text-gray-400 text-[11.5px]">CPF</span>
        <span class="text-gray-600 text-[12.5px] font-mono"><?= esc($m['cpf'] ?: '—') ?></span>
      </div>
      <div class="flex flex-col">
        <span class="text-gray-400 text-[11.5px]">Nascimento</span>
        <span class="text-gray-600 text-[12.5px]">
          <?= $m['data_nascimento'] ? date('d/m/Y', strtotime($m['data_nascimento'])) : '—' ?>
          <?php if ($idade = \App\Models\MulherModel::calcularIdade($m['data_nascimento'])): ?>
            <span class="text-gray-400">(<?= $idade ?> anos)</span>
          <?php endif; ?>
        </span>
      </div>
      <div class="flex flex-col">
        <span class="text-gray-400 text-[11.5px]">Telefone</span>
        <span class="text-gray-600 text-[12.5px]"><?= esc($m['telefone'] ?: '—') ?></span>
      </div>
      <div class="flex flex-col">
        <span class="text-gray-400 text-[11.5px]">Bairro / Município</span>
        <span class="text-gray-600 text-[12.5px]"><?= esc(trim(($m['bairro'] ?? '') . ($m['municipio'] ? ' · ' . $m['municipio'] : '')) ?: '—') ?></span>
      </div>
    </div>

    <div class="pt-1 flex items-center gap-2">
      <a href="<?= base_url('mulheres/detalhes/' . (int) $m['id']) ?>" class="text-primary text-[12px] hover:underline">Ver cadastro completo</a>
    </div>
  </div>

  <div class="w-full bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl overflow-hidden">
    <div class="flex items-center gap-2 px-4 py-3 border-b border-gray-200">
      <div class="w-8 h-8 bg-gray-100 border border-gray-200 flex items-center justify-center rounded-lg">
        <i data-lucide="history" class="w-4 h-4 stroke-gray-600 stroke-[1.5]"></i>
      </div>
      <p class="text-gray-600 text-[13px] font-medium leading-4">Linha do tempo</p>
    </div>

    <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-2 flex-wrap">
      <a href="<?= $link('') ?>" class="px-2.5 py-1 rounded-full text-[12px] border <?= $modulo === '' ? 'bg-gray-600 border-gray-600 text-white' : 'bg-white border-gray-200 text-gray-500 hover:bg-gray-50' ?> transition">
        Todos (<?= (int) $total ?>)
      </a>
      <?php foreach ($modulos as $chave => $meta): ?>
        <a
          href="<?= $link($chave) ?>"
          data-filtro="<?= esc($chave) ?>"
          class="px-2.5 py-1 rounded-full text-[12px] border <?= $modulo === $chave ? 'bg-gray-600 border-gray-600 text-white' : 'bg-white border-gray-200 text-gray-500 hover:bg-gray-50' ?> transition"
        ><?= esc($meta['chip']) ?> (<?= (int) ($contagem[$chave] ?? 0) ?>)</a>
      <?php endforeach; ?>
    </div>

    <?php if (empty($eventos)): ?>
      <p class="py-6 px-4 text-center text-gray-400 text-[13px]">
        <?php if ($modulo !== ''): ?>
          Nenhum registro em <?= esc($modulos[$modulo]['chip'] ?? 'este módulo') ?> para esta mulher.
        <?php else: ?>
          Nenhum registro no histórico desta mulher.
        <?php endif; ?>
      </p>
    <?php else: ?>
      <div class="divide-y divide-gray-100">
        <?php foreach ($eventos as $e): ?>
          <?php
            $temHora = $e['data'] !== '' && ! str_ends_with($e['data'], '00:00:00');
            $geo     = $e['geo'] ?? null;
            $temGeo  = $geo !== null && $geo['lat'] !== '' && $geo['lon'] !== '';
          ?>
          <div class="px-4 py-3 flex items-start gap-3 hover:bg-gray-50 transition" data-modulo="<?= esc($e['modulo']) ?>">
            <div class="w-8 h-8 shrink-0 border rounded-lg flex items-center justify-center <?= $moduloCor[$e['modulo']] ?? 'bg-gray-50 border-gray-200 text-gray-600' ?>">
              <i data-lucide="<?= esc($e['icone']) ?>" class="w-4 h-4 stroke-[1.5]"></i>
            </div>

            <div class="flex-1 min-w-0 flex flex-col gap-1">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="text-[11px] uppercase tracking-wider text-gray-400"><?= esc($e['rotulo']) ?></span>
                <?php if ($e['situacaoRotulo'] !== ''): ?>
                  <span class="px-2 py-0.5 rounded-full text-[11px] font-medium border <?= $situacaoCor[$e['situacao']] ?? 'bg-gray-50 border-gray-200 text-gray-600' ?>"><?= esc($e['situacaoRotulo']) ?></span>
                <?php endif; ?>
                <?php if ($e['dataCadastro']): ?>
                  <span class="text-[11px] text-gray-400" title="O módulo não tinha data própria; usamos a data de cadastro.">(data de cadastro)</span>
                <?php endif; ?>
              </div>

              <div class="flex items-center gap-2 flex-wrap">
                <a href="<?= base_url($e['url']) ?>" class="text-primary text-[13px] font-medium hover:underline"><?= esc($e['titulo']) ?></a>
                <span class="text-gray-400 text-[11.5px]">·</span>
                <span class="text-gray-500 text-[12px] font-mono"><?= $e['data'] !== '' ? ($temHora ? date('d/m/Y H:i', strtotime($e['data'])) : date('d/m/Y', strtotime($e['data']))) : 'sem data' ?></span>
              </div>

              <?php if ($e['detalhes'] !== []): ?>
                <div class="flex items-center gap-2 flex-wrap pt-0.5">
                  <?php foreach ($e['detalhes'] as $par): ?>
                    <?php
                    [$rotulo, $valor] = $par;
                    if (trim((string) $valor) === '') { continue; }
                    ?>
                    <span class="text-[11.5px] text-gray-400">
                      <?= esc((string) $rotulo) ?>:
                      <span class="text-gray-600"><?= esc((string) $valor) ?></span>
                    </span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <?php if ($temGeo): ?>
                <div class="pt-0.5">
                  <a href="https://www.google.com/maps?q=<?= (float) $geo['lat'] ?>,<?= (float) $geo['lon'] ?>" target="_blank" rel="noopener" class="text-primary text-[12px] font-mono hover:underline">
                    <?= number_format((float) $geo['lat'], 6, '.', '') ?>, <?= number_format((float) $geo['lon'], 6, '.', '') ?>
                  </a>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?= $this->endSection() ?>
