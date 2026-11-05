<?= $this->extend('layouts/admin') ?>

<?php
$preservar = static function (array $extra = []) use ($filtros): string {
    $base = [];
    foreach (['situacao', 'prioridade', 'forma', 'origem', 'municipio', 'faixa', 'fator_violencia', 'orgao'] as $chave) {
        $valor = (string) ($filtros[$chave] ?? '');
        if ($valor !== '') {
            $base[$chave] = $valor;
        }
    }
    foreach (['profissional', 'instituicao'] as $chave) {
        $valor = (int) ($filtros[$chave] ?? 0);
        if ($valor > 0) {
            $base[$chave] = $valor;
        }
    }
    foreach ($extra as $k => $v) {
        if ($v === '' || $v === 0) {
            unset($base[$k]);
        } else {
            $base[$k] = $v;
        }
    }

    return $base === [] ? '' : '?' . http_build_query($base);
};

$rotulo = static fn ($chave, $mapa): string => $chave === null || $chave === ''
    ? 'Sem informação'
    : (string) ($mapa[$chave] ?? $chave);

$formata = static fn ($v): string => number_format((int) $v, 0, ',', '.');

$temFiltros = $filtros['situacao'] !== '' || $filtros['prioridade'] !== ''
    || $filtros['forma'] !== '' || $filtros['origem'] !== ''
    || $filtros['municipio'] !== '' || $filtros['profissional'] > 0
    || $filtros['instituicao'] > 0 || $filtros['orgao'] !== ''
    || $filtros['faixa'] !== '' || $filtros['fator_violencia'] !== '';

$presets = [
    'hoje'       => 'Hoje',
    '7_dias'     => 'Últimos 7 dias',
    '30_dias'    => 'Últimos 30 dias',
    'mes_atual'  => 'Este mês',
    'mes_anterior' => 'Mês anterior',
    'ano_atual'  => 'Este ano',
];

$corBarra = [
    'primary' => 'bg-[#a179c3]',
    'red'     => 'bg-[#dc2626]',
    'amber'   => 'bg-[#d97706]',
    'green'   => 'bg-[#059669]',
    'violet'  => 'bg-[#7c3aed]',
    'sky'     => 'bg-[#0284c7]',
    'gray'    => 'bg-[#9ca3af]',
];

$distribuicoes = [
    'atendimentos_por_situacao'    => ['situação dos atendimentos', $situacoesAtend, 'primary'],
    'atendimentos_por_prioridade'  => ['prioridade dos atendimentos', $prioridades, 'red'],
    'atendimentos_por_forma'       => ['forma de atendimento', $formas, 'sky'],
    'atendimentos_por_origem'      => ['origem dos atendimentos', $origensAtend, 'violet'],
    'encaminhamentos_por_orgao'    => ['órgãos mais utilizados', $orgaos, 'primary'],
    'encaminhamentos_por_situacao' => ['situação dos encaminhamentos', $statusEnc, 'amber'],
    'medidas_por_situacao'         => ['medidas protetivas por situação', $situacoesMedida, 'green'],
    'processos_por_situacao'       => ['processos por situação', $situacoesProcesso, 'sky'],
    'manifestacoes_por_situacao'   => ['manifestações por situação', $situacoesManifest, 'violet'],
    'manifestacoes_por_tipo'       => ['manifestações por tipo', $tiposManifest, 'red'],
];
?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex justify-between items-center gap-3 flex-wrap">
    <div class="flex flex-col gap-0.5">
      <div class="flex items-center gap-2">
        <h1 class="text-gray-600 text-[15px] font-medium">Relatórios</h1>
        <?php if ($temFiltros): ?>
          <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 border border-gray-200 text-gray-600">filtros ativos</span>
        <?php endif; ?>
      </div>
      <p class="text-gray-500 text-[12px] leading-4">Indicadores da Procuradoria da Mulher</p>
    </div>
    <div class="flex items-center gap-2">
      <a href="<?= base_url('relatorios/pdf' . $preservar()) ?>"
         class="btn-secondary text-[13px] px-3 py-2 inline-flex items-center gap-1.5"
         title="Gerar o relatório em PDF com os filtros atuais (para visualizar ou imprimir)">
        <i data-lucide="file-text" class="w-4 h-4"></i>
        Relatório PDF
      </a>
    </div>
  </header>

  <!-- ATALHOS DE PERÍODO -->
  <div class="flex items-center gap-1.5 flex-wrap">
    <span class="text-gray-500 text-[12px] font-medium">Período:</span>
    <?php foreach ($presets as $chave => $rotuloAtalho): ?>
      <a href="<?= base_url('relatorios' . $preservar(['rapido' => $chave, 'data_de' => '', 'data_ate' => ''])) ?>"
         class="px-3 py-1.5 rounded-lg text-[12px] font-medium border transition
                <?= $filtros['rapido'] === $chave
                    ? 'bg-primary border-primary text-white'
                    : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' ?>">
        <?= esc($rotuloAtalho) ?>
      </a>
    <?php endforeach; ?>
    <?php if (! empty($filtros['data_de']) && ! empty($filtros['data_ate'])): ?>
      <span class="text-gray-400 text-[12px]"><?= esc($filtros['data_de']) ?> a <?= esc($filtros['data_ate']) ?></span>
    <?php endif; ?>
  </div>

  <!-- FILTROS COMPACTOS -->
  <button type="button" id="btn-filtros" class="btn-secondary text-[13px] px-3 py-2 w-fit" aria-expanded="false" aria-controls="painel-filtros">
    <i data-lucide="filter" class="w-4 h-4"></i>
    Filtros
  </button>

  <form method="GET" action="<?= base_url('relatorios') ?>" id="painel-filtros" class="hidden w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex-col gap-3">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 bg-gray-100 border border-gray-200 flex items-center justify-center rounded-lg">
        <i data-lucide="filter" class="w-4 h-4 stroke-gray-600 stroke-[1.5]"></i>
      </div>
      <p class="text-gray-600 text-[13px] font-medium leading-4">Filtros</p>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
      <div class="flex flex-col gap-1.5">
        <label for="f_data_de" class="label-primary">Período inicial</label>
        <input type="date" id="f_data_de" name="data_de" value="<?= esc((string) $filtros['data_de']) ?>" class="input-primary">
      </div>
      <div class="flex flex-col gap-1.5">
        <label for="f_data_ate" class="label-primary">Período final</label>
        <input type="date" id="f_data_ate" name="data_ate" value="<?= esc((string) $filtros['data_ate']) ?>" class="input-primary">
      </div>
      <div class="flex flex-col gap-1.5">
        <label for="f_situacao" class="label-primary">Situação do atendimento</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="f_situacao" name="situacao" class="select-primary">
            <option value="">Todas</option>
            <?php foreach ($situacoesAtend as $chave => $rotuloOpt): ?>
              <option value="<?= esc($chave) ?>" <?= (string) $filtros['situacao'] === $chave ? 'selected' : '' ?>><?= esc($rotuloOpt) ?></option>
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
            <?php foreach ($prioridades as $chave => $rotuloOpt): ?>
              <option value="<?= esc($chave) ?>" <?= (string) $filtros['prioridade'] === $chave ? 'selected' : '' ?>><?= esc($rotuloOpt) ?></option>
            <?php endforeach; ?>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
        </div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_forma" class="label-primary">Forma de atendimento</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="f_forma" name="forma" class="select-primary">
            <option value="">Todas</option>
            <?php foreach ($formas as $chave => $rotuloOpt): ?>
              <option value="<?= esc($chave) ?>" <?= (string) $filtros['forma'] === $chave ? 'selected' : '' ?>><?= esc($rotuloOpt) ?></option>
            <?php endforeach; ?>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
        </div>
      </div>
      <div class="flex flex-col gap-1.5">
        <label for="f_origem" class="label-primary">Origem do atendimento</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="f_origem" name="origem" class="select-primary">
            <option value="">Todas</option>
            <?php foreach ($origensAtend as $chave => $rotuloOpt): ?>
              <option value="<?= esc($chave) ?>" <?= (string) $filtros['origem'] === $chave ? 'selected' : '' ?>><?= esc($rotuloOpt) ?></option>
            <?php endforeach; ?>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
        </div>
      </div>
      <div class="flex flex-col gap-1.5">
        <label for="f_municipio" class="label-primary">Município</label>
        <input type="text" id="f_municipio" name="municipio" list="lista-municipios" value="<?= esc((string) $filtros['municipio']) ?>" placeholder="Todas as localidades" class="input-primary">
        <datalist id="lista-municipios">
          <?php foreach ($municipios as $municipio): ?>
            <option value="<?= esc($municipio) ?>"></option>
          <?php endforeach; ?>
        </datalist>
      </div>
      <div class="flex flex-col gap-1.5">
        <label for="f_profissional" class="label-primary">Responsável / profissional</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="f_profissional" name="profissional" class="select-primary">
            <option value="">Todos</option>
            <?php foreach ($equipe as $u): ?>
              <option value="<?= (int) $u['id'] ?>" <?= (int) $filtros['profissional'] === (int) $u['id'] ? 'selected' : '' ?>><?= esc($u['nome']) ?></option>
            <?php endforeach; ?>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
        </div>
      </div>
      <div class="flex flex-col gap-1.5">
        <label for="f_faixa" class="label-primary">Faixa etária</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="f_faixa" name="faixa" class="select-primary">
            <option value="">Todas as idades</option>
            <?php foreach ($faixas as $chave => $faixa): ?>
              <option value="<?= esc($chave) ?>" <?= (string) $filtros['faixa'] === $chave ? 'selected' : '' ?>><?= esc($faixa[2]) ?></option>
            <?php endforeach; ?>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
        </div>
      </div>
      <div class="flex flex-col gap-1.5">
        <label for="f_fator" class="label-primary">Fator de risco (violência)</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="f_fator" name="fator_violencia" class="select-primary">
            <option value="">Todos os fatores</option>
            <?php foreach ($fatores as $chave => $rotuloOpt): ?>
              <option value="<?= esc($chave) ?>" <?= (string) $filtros['fator_violencia'] === $chave ? 'selected' : '' ?>><?= esc($rotuloOpt) ?></option>
            <?php endforeach; ?>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
        </div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="f_orgao" class="label-primary">Órgão do encaminhamento</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="f_orgao" name="orgao" class="select-primary">
            <option value="">Todos os órgãos</option>
            <?php foreach ($orgaos as $chave => $rotuloOpt): ?>
              <option value="<?= esc($chave) ?>" <?= (string) $filtros['orgao'] === $chave ? 'selected' : '' ?>><?= esc($rotuloOpt) ?></option>
            <?php endforeach; ?>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
        </div>
      </div>
      <div class="flex flex-col gap-1.5">
        <label for="f_instituicao" class="label-primary">Instituição da rede</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="f_instituicao" name="instituicao" class="select-primary">
            <option value="">Todas</option>
            <?php foreach ($instituicoes as $inst): ?>
              <option value="<?= (int) $inst['id'] ?>" <?= (int) $filtros['instituicao'] === (int) $inst['id'] ? 'selected' : '' ?>><?= esc($inst['nome']) ?></option>
            <?php endforeach; ?>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
        </div>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2 h-11">
        <i data-lucide="filter" class="w-4 h-4"></i>
        Aplicar
      </button>
      <a href="<?= base_url('relatorios') ?>" class="btn-secondary text-[13px] px-4 py-2 h-11">Limpar</a>
    </div>
  </form>

  <!-- INDICADORES PRINCIPAIS -->
  <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
    <?php foreach ([
        ['mulheres', 'user-round-check', 'Mulheres atendidas', 'mulheres distintas no período', 'primary'],
        ['atendimentos', 'clipboard-plus', 'Atendimentos registrados', 'total no período', 'primary'],
        ['novos', 'sparkles', 'Novos atendimentos', 'situação "Novo"', 'sky'],
        ['em_andamento', 'clock-3', 'Em andamento', 'aguardando/em andamento', 'violet'],
        ['encerrados', 'circle-check', 'Encerrados', 'situação "Encerrado"', 'green'],
        ['urgentes', 'octagon-alert', 'Atendimentos urgentes', 'prioridade emergência/alta', 'red'],
        ['riscos', 'shield-alert', 'Avaliações de risco', 'casos avaliados (violência)', 'red'],
        ['encaminhamentos', 'send', 'Encaminhamentos realizados', 'total no período', 'primary'],
        ['encaminhamentos_pendentes', 'clock', 'Encaminhamentos pendentes', 'em acompanhamento', 'amber'],
        ['retornos', 'calendar-clock', 'Retornos agendados', 'agendados/confirmados', 'green'],
        ['medidas', 'shield-check', 'Medidas protetivas', 'registradas no período', 'primary'],
        ['processos', 'scale', 'Processos', 'registrados no período', 'sky'],
        ['manifestacoes', 'megaphone', 'Denúncias/Ouvidoria', 'manifestações no período', 'violet'],
    ] as [$chave, $icone, $rotuloCard, $legenda, $cor]): ?>
      <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-1">
        <div class="flex items-center gap-2 mb-1">
          <div class="w-8 h-8 bg-primary/10 border border-primary/20 flex items-center justify-center rounded-lg">
            <i data-lucide="<?= esc($icone) ?>" class="w-4 h-4 stroke-primary stroke-[1.5]"></i>
          </div>
          <p class="text-gray-500 text-[12px] leading-4"><?= esc($rotuloCard) ?></p>
        </div>
        <p class="text-gray-700 text-[22px] font-semibold leading-5" data-indicador="<?= esc($chave) ?>" data-valor="<?= (int) ($indicadores[$chave] ?? 0) ?>"><?= $formata($indicadores[$chave] ?? 0) ?></p>
        <p class="text-gray-400 text-[11px] leading-3"><?= esc($legenda) ?></p>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- EVOLUÇÃO MENSAL DE ATENDIMENTOS -->
  <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
    <div class="flex items-center justify-between">
      <p class="text-gray-600 text-[13px] font-medium leading-4">Atendimentos por período (evolução mensal)</p>
      <a href="<?= base_url('relatorios/pdf' . $preservar()) ?>" class="text-primary hover:underline text-[12px]">PDF ›</a>
    </div>
    <?php if (! empty($atendimentos_por_mes) && array_sum(array_column($atendimentos_por_mes, 'total')) > 0): ?>
      <?php $maxMes = max(array_column($atendimentos_por_mes, 'total')); ?>
      <div class="w-full h-48 flex items-end gap-1.5 px-0.5">
        <?php foreach ($atendimentos_por_mes as $item): ?>
          <?php $pct = $maxMes > 0 ? max(3, (int) round($item['total'] / $maxMes * 100)) : 3; ?>
          <div class="flex-1 flex flex-col items-center gap-0.5 h-full" title="<?= esc($item['rotulo']) ?>: <?= $formata($item['total']) ?>">
            <span class="text-[9.5px] text-gray-400 leading-3"><?= $formata($item['total']) ?></span>
            <div class="w-full flex-1 flex items-end justify-center min-h-0">
              <div class="w-full max-w-7 rounded-t <?= $item['total'] > 0 ? $corBarra['primary'] : 'bg-gray-200' ?>" style="height: <?= $item['total'] > 0 ? $pct : 2 ?>%"></div>
            </div>
            <span class="text-[9px] text-gray-400 leading-3 truncate w-full text-center"><?= esc($item['rotulo']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="text-gray-400 text-[12.5px]">Sem atendimentos no período.</p>
    <?php endif; ?>
  </div>

  <!-- DISTRIBUIÇÕES DE ATENDIMENTOS -->
  <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-3">
    <?php foreach ($distribuicoes as $chaveLista => [$titulo, $mapa]): ?>
      <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
        <p class="text-gray-600 text-[13px] font-medium leading-4"><?= esc(ucfirst($titulo)) ?></p>
        <?php if (! empty($$chaveLista)): ?>
          <?php $maxDist = max(array_column($$chaveLista, 'total')); ?>
          <div class="flex flex-col gap-2">
            <?php foreach (array_slice($$chaveLista, 0, 8) as $linha): ?>
              <?php $pct = $maxDist > 0 ? (int) round($linha['total'] / $maxDist * 100) : 0; ?>
              <div class="flex items-center gap-2">
                <span class="w-36 truncate text-[11.5px] text-gray-600" title="<?= esc($rotulo($linha['rotulo'], $mapa)) ?>"><?= esc($rotulo($linha['rotulo'], $mapa)) ?></span>
                <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                  <div class="h-full rounded-full <?= $corBarra['primary'] ?>" style="width: <?= $pct ?>%"></div>
                </div>
                <span class="w-10 text-right text-[11.5px] font-medium text-gray-700"><?= $formata($linha['total']) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="text-gray-400 text-[12.5px]">Sem dados no período.</p>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- MUNICÍPIO E FAIXA ETÁRIA -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
      <p class="text-gray-600 text-[13px] font-medium leading-4">Origem/localidade (município)</p>
      <?php if (! empty($atendimentos_por_municipio)): ?>
        <?php $maxMun = max(array_column($atendimentos_por_municipio, 'total')); ?>
        <div class="flex flex-col gap-2">
          <?php foreach ($atendimentos_por_municipio as $linha): ?>
            <div class="flex items-center gap-2">
              <span class="w-36 truncate text-[11.5px] text-gray-600"><?= esc($linha['rotulo']) ?></span>
              <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full rounded-full <?= $corBarra['sky'] ?>" style="width: <?= $maxMun > 0 ? (int) round($linha['total'] / $maxMun * 100) : 0 ?>%"></div>
              </div>
              <span class="w-10 text-right text-[11.5px] font-medium text-gray-700"><?= $formata($linha['total']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="text-gray-400 text-[12.5px]">Sem dados no período.</p>
      <?php endif; ?>
    </div>

    <div class="w-full bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl overflow-hidden">
      <div class="p-4 pb-2">
        <p class="text-gray-600 text-[13px] font-medium leading-4">Faixa etária das mulheres atendidas</p>
      </div>
      <div class="w-full overflow-x-auto">
        <table class="w-full text-[13px]">
          <thead>
            <tr class="bg-gray-50 border-b border-gray-200">
              <th class="text-left py-2 px-3 text-gray-500 font-medium text-[12px]">Faixa</th>
              <th class="text-right py-2 px-3 text-gray-500 font-medium text-[12px]">Mulheres</th>
            </tr>
          </thead>
          <tbody>
            <?php if (! empty($faixa_etaria)): ?>
              <?php foreach ($faixa_etaria as $linha): ?>
                <tr class="border-b border-gray-100 last:border-0">
                  <td class="py-2 px-3 text-gray-600"><?= esc($linha['faixa']) ?></td>
                  <td class="py-2 px-3 text-right font-medium text-gray-700"><?= $formata($linha['total']) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="2" class="py-6 px-3 text-center text-gray-400">Sem dados no período.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- VIOLÊNCIA / AVALIAÇÃO DE RISCO -->
  <div class="w-full flex flex-col gap-3">
    <p class="text-gray-600 text-[13px] font-medium leading-4">Avaliação de risco (violência)</p>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
      <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
        <p class="text-gray-600 text-[13px] font-medium leading-4">Classificação de risco</p>
        <?php if (! empty($risco_por_classificacao)): ?>
          <?php $maxRisco = max(array_column($risco_por_classificacao, 'total')); ?>
          <div class="flex flex-col gap-2">
            <?php foreach ($risco_por_classificacao as $linha): ?>
              <div class="flex items-center gap-2">
                <span class="w-24 truncate text-[11.5px] text-gray-600"><?= esc($rotulo($linha['rotulo'], $riscos)) ?></span>
                <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                  <div class="h-full rounded-full <?= ($linha['rotulo'] ?? '') === 'extremo' ? $corBarra['red'] : $corBarra['amber'] ?>" style="width: <?= $maxRisco > 0 ? (int) round($linha['total'] / $maxRisco * 100) : 0 ?>%"></div>
                </div>
                <span class="w-10 text-right text-[11.5px] font-medium text-gray-700"><?= $formata($linha['total']) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="text-gray-400 text-[12.5px]">Sem avaliações no período.</p>
        <?php endif; ?>
      </div>

      <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
        <p class="text-gray-600 text-[13px] font-medium leading-4">Fatores de risco mais registrados</p>
        <?php if (! empty($risco_fatores)): ?>
          <?php $maxFator = max(array_column($risco_fatores, 'total')); ?>
          <div class="flex flex-col gap-2">
            <?php foreach (array_slice($risco_fatores, 0, 8) as $linha): ?>
              <div class="flex items-center gap-2">
                <span class="w-40 truncate text-[11.5px] text-gray-600" title="<?= esc($linha['rotulo']) ?>"><?= esc($linha['rotulo']) ?></span>
                <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                  <div class="h-full rounded-full <?= $corBarra['red'] ?>" style="width: <?= $maxFator > 0 ? (int) round($linha['total'] / $maxFator * 100) : 0 ?>%"></div>
                </div>
                <span class="w-10 text-right text-[11.5px] font-medium text-gray-700"><?= $formata($linha['total']) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="text-gray-400 text-[12.5px]">Sem fatores registrados no período.</p>
        <?php endif; ?>
      </div>

      <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
        <p class="text-gray-600 text-[13px] font-medium leading-4">Evolução mensal das avaliações</p>
        <?php if (! empty($risco_por_mes) && array_sum(array_column($risco_por_mes, 'total')) > 0): ?>
          <?php $maxRiscoMes = max(array_column($risco_por_mes, 'total')); ?>
          <div class="w-full h-40 flex items-end gap-1.5">
            <?php foreach ($risco_por_mes as $item): ?>
              <div class="flex-1 flex flex-col items-center gap-0.5 h-full" title="<?= esc($item['rotulo']) ?>: <?= $formata($item['total']) ?>">
                <span class="text-[9.5px] text-gray-400 leading-3"><?= $formata($item['total']) ?></span>
                <div class="w-full flex-1 flex items-end justify-center min-h-0">
                  <div class="w-full max-w-7 rounded-t <?= $item['total'] > 0 ? $corBarra['red'] : 'bg-gray-200' ?>" style="height: <?= $maxRiscoMes > 0 && $item['total'] > 0 ? max(3, (int) round($item['total'] / $maxRiscoMes * 100)) : 2 ?>%"></div>
                </div>
                <span class="text-[9px] text-gray-400 leading-3 truncate w-full text-center"><?= esc($item['rotulo']) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="text-gray-400 text-[12.5px]">Sem avaliações no período.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ENCAMINHAMENTOS POR INSTITUIÇÃO -->
  <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
    <p class="text-gray-600 text-[13px] font-medium leading-4">Instituições da rede mais acionadas</p>
    <?php if (! empty($encaminhamentos_por_instituicao)): ?>
      <?php $maxInst = max(array_column($encaminhamentos_por_instituicao, 'total')); ?>
      <div class="flex flex-col gap-2">
        <?php foreach ($encaminhamentos_por_instituicao as $linha): ?>
          <div class="flex items-center gap-2">
            <span class="w-52 truncate text-[11.5px] text-gray-600" title="<?= esc($linha['rotulo']) ?>"><?= esc($linha['rotulo']) ?></span>
            <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
              <div class="h-full rounded-full <?= $corBarra['green'] ?>" style="width: <?= $maxInst > 0 ? (int) round($linha['total'] / $maxInst * 100) : 0 ?>%"></div>
            </div>
            <span class="w-10 text-right text-[11.5px] font-medium text-gray-700"><?= $formata($linha['total']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="text-gray-400 text-[12.5px]">Sem encaminhamentos no período.</p>
    <?php endif; ?>
  </div>

  <p class="text-gray-400 text-[11.5px] leading-4">
    Todos os números provêm de consultas ao banco de dados, restritas ao período e aos filtros aplicáveis a cada bloco.
  </p>
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
    var icone = btn.querySelector('[data-lucide]');
    if (icone) icone.setAttribute('data-lucide', 'filter-x');
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