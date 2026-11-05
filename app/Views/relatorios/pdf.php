<?php
/**
 * Template de relatório institucional em PDF (Dompdf).
 *
 * Dados reais vindos do controller (mesmos filtros e consultas da tela).
 * Estilo em HTML/CSS próprio para PDF: tabelas, blocos e barras horizontais
 * com largura porcentual — sem flexbox, para máxima compatibilidade com o
 * motor de renderização do Dompdf.
 *
 * Variáveis esperadas: config, filtros, dados, nomesEquipe, nomesInstituicoes,
 * mapas, faixas, fatores, logo, usuarioNome, usuarioPerfil, geradoEm.
 */

$formata = static fn ($v): string => number_format((int) $v, 0, ',', '.');

$dataBR = static function (string $d): string {
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) === 1) {
        return (string) preg_replace('~^(\d{4})-(\d{2})-(\d{2})$~', '$3/$2/$1', $d);
    }

    return '—';
};

$mapas = $mapas ?? [];

$rotulo = static function ($chave, $mapa) {
    if ($chave === null || $chave === '') {
        return 'Sem informação';
    }

    return (string) ($mapa[$chave] ?? $chave);
};

$presets = [
    'hoje'         => 'Hoje',
    '7_dias'       => 'Últimos 7 dias',
    '30_dias'      => 'Últimos 30 dias',
    'mes_atual'    => 'Este mês',
    'mes_anterior' => 'Mês anterior',
    'ano_atual'    => 'Este ano',
];

// Cores definidas localmente (acerter unificação com a identidade visual:
// lavanda/roxo sobre branco).
$corPrimaria = '#a179c3';
$corEscura   = '#5c3c7f';
$corTexto    = '#3f3f46';
$corSuave    = '#6b7280';

// Filtros aplicados (legenda do documento).
$filtrosAplicados = [];
$p = $filtros ?? [];
if (! empty($p['rapido']) && isset($presets[$p['rapido']])) {
    $filtrosAplicados[] = 'Período rápido: ' . $presets[$p['rapido']];
}
if (! empty($p['situacao']) && isset($mapas['situacao'])) {
    $filtrosAplicados[] = 'Situação do atendimento: ' . $rotulo($p['situacao'], $mapas['situacao']);
}
if (! empty($p['prioridade']) && isset($mapas['prioridade'])) {
    $filtrosAplicados[] = 'Prioridade: ' . $rotulo($p['prioridade'], $mapas['prioridade']);
}
if (! empty($p['forma']) && isset($mapas['forma'])) {
    $filtrosAplicados[] = 'Forma de atendimento: ' . $rotulo($p['forma'], $mapas['forma']);
}
if (! empty($p['origem']) && isset($mapas['origem'])) {
    $filtrosAplicados[] = 'Origem do atendimento: ' . $rotulo($p['origem'], $mapas['origem']);
}
if (! empty($p['municipio'])) {
    $filtrosAplicados[] = 'Município: ' . (string) $p['municipio'];
}
if (! empty($p['profissional']) && isset($nomesEquipe[(int) $p['profissional']])) {
    $filtrosAplicados[] = 'Responsável/profissional: ' . $nomesEquipe[(int) $p['profissional']];
}
if (! empty($p['instituicao']) && isset($nomesInstituicoes[(int) $p['instituicao']])) {
    $filtrosAplicados[] = 'Instituição da rede: ' . $nomesInstituicoes[(int) $p['instituicao']];
}
if (! empty($p['orgao']) && isset($mapas['orgao'])) {
    $filtrosAplicados[] = 'Órgão do encaminhamento: ' . $rotulo($p['orgao'], $mapas['orgao']);
}
if (! empty($p['faixa']) && isset($faixas[$p['faixa']])) {
    $filtrosAplicados[] = 'Faixa etária: ' . (string) ($faixas[$p['faixa']][2] ?? $p['faixa']);
}
if (! empty($p['fator_violencia']) && isset($fatores[$p['fator_violencia']])) {
    $filtrosAplicados[] = 'Fator de risco: ' . (string) $fatores[$p['fator_violencia']];
}

/**
 * Tabela de distribuição com barras horizontais (largura proporcional).
 *
 * @param array<int, array<string, mixed>> $itens
 * @param array<string, string>|null        $mapa
 */
$barras = static function (array $itens, string $coluna, ?array $mapa = null, ?string $corChave = null) use ($formata, $rotulo, $corPrimaria): string {
    $total = array_sum(array_column($itens, 'total'));
    if ($total <= 0) {
        return '<p class="vazio">Sem registros no período.</p>';
    }

    $max = max(array_column($itens, 'total'));
    $html = '<table class="tbl barras"><thead><tr>'
        . '<th class="al" style="width:38%">' . $coluna . '</th>'
        . '<th class="al" style="width:44%">Distribuição</th>'
        . '<th class="ar" style="width:18%">Total</th>'
        . '</tr></thead><tbody>';

    foreach (array_slice($itens, 0, 15) as $linha) {
        $chave = (string) ($linha['rotulo'] ?? '');
        $label = $mapa !== null ? $rotulo($linha['rotulo'] ?? null, $mapa) : (string) ($linha['rotulo'] ?? 'Sem informação');
        $valor = (int) $linha['total'];
        $pct   = $max > 0 ? (int) round($valor / $max * 100) : 0;
        $pctExib = $pct > 0 ? max(2, $pct) : 1;
        $cor   = $corChave !== null && $chave === $corChave ? '#b23a48' : $corPrimaria;

        $html .= '<tr>'
            . '<td class="al rotulo">' . esc($label) . '</td>'
            . '<td class="track"><div class="fill" style="width:' . $pctExib . '%;background:' . $cor . '"></div></td>'
            . '<td class="ar valor">' . $formata($valor) . '</td>'
            . '</tr>';
    }

    return $html . '</tbody></table>';
};

/**
 * Tabela simples com cabeçalho repetido entre páginas.
 *
 * @param array<int, string>                      $cabecalho
 * @param array<int, array<int|string, mixed>>    $linhas
 */
$tabela = static function (array $cabecalho, array $linhas) use ($formata): string {
    if ($linhas === []) {
        return '<p class="vazio">Sem registros no período.</p>';
    }

    $html = '<table class="tbl simples"><thead><tr>';
    foreach ($cabecalho as $th) {
        $html .= '<th class="al">' . esc($th) . '</th>';
    }
    $html .= '</tr></thead><tbody>';
    foreach ($linhas as $linha) {
        $html .= '<tr>';
        foreach ($linha as $celula) {
            $html .= '<td class="al">' . esc((string) $celula) . '</td>';
        }
        $html .= '</tr>';
    }

    return $html . '</tbody></table>';
};

$d = $dados ?? [];
$ind  = $d['indicadores'] ?? [];
$periodo = $d['periodo'] ?? ['de' => '', 'ate' => ''];

$tituloSistema   = (string) ($config['nome_sistema'] ?? 'Sistema de Gestão');
$nomeProcuradoria = (string) ($config['nome_procuradoria'] ?? 'Procuradoria da Mulher');
$instituicao     = array_values(array_filter([
    (string) ($config['endereco'] ?? ''), (string) ($config['municipio'] ?? ''),
]));
$contato = implode(' · ', $instituicao);

$perfilRotulo = $usuarioPerfil === 'admin' || $usuarioPerfil === 'funcionario'
    ? ($usuarioPerfil === 'admin' ? 'Administrador(a)' : 'Funcionário(a)')
    : (string) $usuarioPerfil;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title><?= esc($titulo ?? 'Relatório') ?> — <?= esc($nomeProcuradoria) ?></title>
<style>
  @page { size: A4 portrait; margin: 15mm 14mm 20mm 14mm; }

  html, body { font-family: "DejaVu Sans", sans-serif; }
  body { margin:0; color:<?= $corTexto ?>; font-size:9pt; line-height:1.45; }
  p { margin:0; }

  /* ===== Cabeçalho institucional (página 1) ===== */
  .header { border-bottom:1.6pt solid <?= $corPrimaria ?>; padding-bottom:3.5mm; margin-bottom:4.5mm; }
  .header table { width:100%; border-collapse:collapse; }
  .header td { vertical-align:middle; }
  .header .logo { width:26mm; padding-right:4mm; }
  .header .logo img { width:100%; height:13mm; object-fit:contain; }
  .header .marca { font-size:8pt; color:#8a6bb5; letter-spacing:0.06em; text-transform:uppercase; }
  .header h1 { font-size:15pt; margin:1mm 0 0.8mm; color:<?= $corEscura ?>; }
  .header .sub { font-size:8.5pt; color:<?= $corSuave ?>; }

  /* ===== Bloco de metadados ===== */
  .meta { background:#f9f5fd; border:0.4pt solid #e6d8f0; border-left:2mm solid <?= $corPrimaria ?>; padding:2.6mm 3mm; margin-bottom:4.5mm; }
  .meta .linha { font-size:8.5pt; color:#4b5563; padding:0.4mm 0; }
  .meta .linha b { color:<?= $corEscura ?>; }
  .meta .filtros { margin-top:1.2mm; }

  /* ===== Indicadores principais ===== */
  table.kpis { width:100%; border-collapse:separate; border-spacing:1.4mm 1.6mm; margin:-1.6mm -1.4mm; }
  td.kpi { border:0.5pt solid #e6d8f0; background:#fdfbff; padding:2.4mm 2.6mm; vertical-align:top; width:25%; }
  .kpi-num { font-size:16pt; font-weight:bold; color:<?= $corEscura ?>; line-height:1.1; }
  .kpi-rot { font-size:8.5pt; font-weight:bold; color:#374151; margin-top:1.2mm; }
  .kpi-leg { font-size:7.5pt; color:<?= $corSuave ?>; margin-top:0.6mm; }

  /* ===== Seções ===== */
  .sec { margin-top:5mm; }
  .sec-tit { font-size:11pt; font-weight:bold; color:<?= $corEscura ?>; border-bottom:0.8pt solid <?= $corPrimaria ?>; padding-bottom:1mm; margin-bottom:2mm; page-break-after:avoid; }
  .sub-tit { font-size:9.5pt; font-weight:bold; color:#4b5563; margin:3.2mm 0 1.6mm; page-break-after:avoid; }
  .bloco { margin-bottom:1mm; }
  .bloco.encolhe { page-break-inside:avoid; }

  /* ===== Tabelas ===== */
  table.tbl { width:100%; border-collapse:collapse; }
  table.tbl th, table.tbl td { padding:1.6mm 2mm; font-size:8.4pt; border-bottom:0.4pt solid #e6d8f0; vertical-align:middle; }
  table.tbl thead th { background:#f2ebf9; color:<?= $corEscura ?>; text-align:left; font-size:8pt; border-top:0.8pt solid <?= $corPrimaria ?>; border-bottom:0.8pt solid <?= $corPrimaria ?>; }
  table.tbl td.al { text-align:left; }
  table.tbl th.al { text-align:left; }
  table.tbl td.ar { text-align:right; }
  table.tbl th.ar { text-align:right; }
  table.tbl td.rotulo { color:#374151; }
  table.tbl td.valor { font-weight:bold; color:#374151; }
  table.tbl td.track { padding:1.2mm 2mm; }
  table.tbl .fill { height:3.6mm; min-width:1mm; }

  .vazio { color:<?= $corSuave ?>; font-size:8.5pt; padding:2mm 0; font-style:italic; }

  /* ===== Nota e validação ===== */
  .nota { margin-top:6mm; font-size:7.5pt; color:<?= $corSuave ?>; border-top:0.5pt solid #e6d8f0; padding-top:2mm; }
  .assinatura { margin-top:9mm; page-break-inside:avoid; }
  .assinatura .linha-ass { width:70mm; border-top:0.6pt solid #4b5563; margin-bottom:1.4mm; }
  .assinatura .resp { font-size:9pt; font-weight:bold; color:#374151; }
  .assinatura .cargo { font-size:8pt; color:<?= $corSuave ?>; }
</style>
</head>
<body>

<div class="header">
  <table>
    <tr>
      <?php if (! empty($logo)): ?>
        <td class="logo"><img src="<?= $logo ?>" alt="Logo"></td>
      <?php endif; ?>
      <td>
        <div class="marca"><?= esc(strtoupper($nomeProcuradoria)) ?></div>
        <h1><?= esc((string) ($titulo ?? 'Relatório Estatístico')) ?></h1>
        <div class="sub">Resumo estatístico com dados reais do sistema<?= $contato !== '' ? ' · ' . esc($contato) : '' ?></div>
      </td>
    </tr>
  </table>
</div>

<div class="meta">
  <div class="linha"><b>Período consultado:</b> <?= esc($dataBR((string) $periodo['de'])) ?> a <?= esc($dataBR((string) $periodo['ate'])) ?></div>
  <div class="linha"><b>Emitido em:</b> <?= esc($geradoEm ?? '') ?> <?= $usuarioNome !== '' ? 'por <b>' . esc($usuarioNome) . '</b> (' . esc($perfilRotulo) . ')' : '' ?></div>
  <?php if ($filtrosAplicados !== []): ?>
    <div class="linha filtros"><b>Filtros aplicados:</b> <?= esc(implode(' · ', $filtrosAplicados)) ?></div>
  <?php else: ?>
    <div class="linha filtros"><b>Filtros aplicados:</b> nenhum além do período (dados completos).</div>
  <?php endif; ?>
</div>

<!-- ================= INDICADORES PRINCIPAIS ================= -->
<div class="sec">
  <div class="sec-tit">1. Indicadores principais</div>
  <div class="bloco encolhe">
    <?php
    $cards = array_filter([
        ['mulheres',  'Mulheres atendidas',  'mulheres distintas no período'],
        ['atendimentos', 'Atendimentos registrados', 'total no período'],
        ['novos',     'Novos atendimentos',  'situação "Novo"'],
        ['em_andamento', 'Em andamento',      'aguardando/em andamento'],
        ['encerrados','Encerrados',          'situação "Encerrado"'],
        ['urgentes',  'Atendimentos urgentes','prioridade emergência/alta'],
        ['riscos',    'Avaliações de risco', 'casos avaliados (violência)'],
        ['encaminhamentos', 'Encaminhamentos', 'total no período'],
        ['encaminhamentos_pendentes', 'Encaminhamentos pendentes', 'em acompanhamento'],
        ['retornos',  'Retornos agendados',  'agendados/confirmados'],
        ['medidas',   'Medidas protetivas',  'registradas no período'],
        ['processos', 'Processos',           'registrados no período'],
        ['manifestacoes', 'Denúncias/Ouvidoria', 'manifestações no período'],
    ], static fn (array $c): bool => isset($ind[$c[0]]));
    ?>
    <table class="kpis">
      <?php foreach (array_chunk($cards, 4) as $linhaCards): ?>
        <tr>
          <?php foreach ($linhaCards as [$chave, $rotuloCard, $legenda]): ?>
            <td class="kpi">
              <div class="kpi-num"><?= $formata((int) ($ind[$chave] ?? 0)) ?></div>
              <div class="kpi-rot"><?= esc($rotuloCard) ?></div>
              <div class="kpi-leg"><?= esc($legenda) ?></div>
            </td>
          <?php endforeach; ?>
          <?php for ($i = count($linhaCards); $i < 4; $i++): ?>
            <td class="kpi"></td>
          <?php endfor; ?>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>

<!-- ================= ATENDIMENTOS ================= -->
<div class="sec">
  <div class="sec-tit">2. Atendimentos</div>

  <div class="sub-tit">2.1 Evolução mensal de atendimentos</div>
  <div class="bloco">
    <?= $barras($d['atendimentos_por_mes'] ?? [], 'Mês') ?>
  </div>

  <div class="sub-tit">2.2 Distribuições dos atendimentos</div>
  <div class="bloco encolhe">
    <?= $barras($d['atendimentos_por_situacao'] ?? [], 'Situação', $mapas['situacao'] ?? null, 'urgente') ?>
  </div>
  <div class="bloco encolhe">
    <?= $barras($d['atendimentos_por_prioridade'] ?? [], 'Prioridade', $mapas['prioridade'] ?? null, 'emergencia') ?>
  </div>
  <div class="bloco encolhe">
    <?= $barras($d['atendimentos_por_forma'] ?? [], 'Forma de atendimento', $mapas['forma'] ?? null) ?>
  </div>
  <div class="bloco encolhe">
    <?= $barras($d['atendimentos_por_origem'] ?? [], 'Origem do atendimento', $mapas['origem'] ?? null) ?>
  </div>

  <div class="sub-tit">2.3 Origem/localidade e faixa etária das mulheres atendidas</div>
  <div class="bloco encolhe">
    <?= $barras($d['atendimentos_por_municipio'] ?? [], 'Município') ?>
  </div>
  <div class="bloco encolhe">
    <?php
    $faixaLinhas = [];
    foreach ($d['faixa_etaria'] ?? [] as $linhaFaixa) {
        $faixaLinhas[] = [$linhaFaixa['faixa'], $formata($linhaFaixa['total'])];
    }
    ?>
    <?= $tabela(['Faixa etária', 'Mulheres'], $faixaLinhas) ?>
  </div>
</div>

<!-- ================= AVALIAÇÃO DE RISCO ================= -->
<div class="sec">
  <div class="sec-tit">3. Avaliação de risco (violência)</div>

  <div class="sub-tit">3.1 Classificação de risco</div>
  <div class="bloco encolhe">
    <?= $barras($d['risco_por_classificacao'] ?? [], 'Classificação', $mapas['riscos'] ?? null, 'extremo') ?>
  </div>

  <div class="sub-tit">3.2 Fatores de risco mais registrados</div>
  <div class="bloco encolhe">
    <?= $barras($d['risco_fatores'] ?? [], 'Fator de risco', $mapas['fatores'] ?? null) ?>
  </div>

  <div class="sub-tit">3.3 Evolução mensal das avaliações</div>
  <div class="bloco">
    <?= $barras($d['risco_por_mes'] ?? [], 'Mês') ?>
  </div>
</div>

<!-- ================= ENCAMINHAMENTOS ================= -->
<div class="sec">
  <div class="sec-tit">4. Encaminhamentos</div>

  <div class="sub-tit">4.1 Encaminhamentos por órgão</div>
  <div class="bloco encolhe">
    <?= $barras($d['encaminhamentos_por_orgao'] ?? [], 'Órgão', $mapas['orgao'] ?? null) ?>
  </div>

  <div class="sub-tit">4.2 Encaminhamentos por situação</div>
  <div class="bloco encolhe">
    <?= $barras($d['encaminhamentos_por_situacao'] ?? [], 'Situação', $mapas['statusEnc'] ?? null) ?>
  </div>

  <div class="sub-tit">4.3 Instituições da rede mais acionadas</div>
  <div class="bloco encolhe">
    <?= $barras($d['encaminhamentos_por_instituicao'] ?? [], 'Instituição') ?>
  </div>
</div>

<!-- ================= MEDIDAS, PROCESSOS E MANIFESTAÇÕES ================= -->
<div class="sec">
  <div class="sec-tit">5. Medidas protetivas, processos e manifestações</div>

  <div class="sub-tit">5.1 Medidas protetivas por situação</div>
  <div class="bloco encolhe">
    <?= $barras($d['medidas_por_situacao'] ?? [], 'Situação', $mapas['situacaoMedida'] ?? null) ?>
  </div>

  <div class="sub-tit">5.2 Processos por situação</div>
  <div class="bloco encolhe">
    <?= $barras($d['processos_por_situacao'] ?? [], 'Situação', $mapas['situacaoProcesso'] ?? null) ?>
  </div>

  <div class="sub-tit">5.3 Manifestações por situação</div>
  <div class="bloco encolhe">
    <?= $barras($d['manifestacoes_por_situacao'] ?? [], 'Situação', $mapas['situacaoManifest'] ?? null) ?>
  </div>

  <div class="sub-tit">5.4 Manifestações por tipo</div>
  <div class="bloco encolhe">
    <?= $barras($d['manifestacoes_por_tipo'] ?? [], 'Tipo', $mapas['tipoManifest'] ?? null) ?>
  </div>
</div>

<div class="nota">
  Todos os números deste relatório provêm de consultas diretas ao banco de dados do sistema,
  limitadas ao período e aos filtros indicados. Os percentuais das barras são proporcionais ao
  maior valor de cada bloco. Valores zerados ou blocos sem registros são exibidos como
  "Sem registros no período".
</div>

<div class="assinatura">
  <div class="linha-ass"></div>
  <?php if ($usuarioNome !== ''): ?>
    <div class="resp"><?= esc($usuarioNome) ?></div>
    <div class="cargo"><?= esc($perfilRotulo) ?> · <?= esc($tituloSistema) ?> · <?= esc($nomeProcuradoria) ?></div>
  <?php endif; ?>
</div>

</body>
</html>