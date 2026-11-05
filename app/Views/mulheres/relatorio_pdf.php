<?php
/**
 * Template do relatório individual da mulher (PDF, Dompdf).
 *
 * Dados reais preparados pelo controller (ficha cadastral, resumo e histórico
 * consolidado por módulo — via HistoricoMulherModel). Estilo próprio para PDF,
 * com tabelas e blocos sem flexbox, para compatibilidade com o Dompdf.
 *
 * Variáveis esperadas: titulo, mulher, ficha, resumo, secoes, totalRegistros,
 * semRegistros, config, logo, usuarioNome, usuarioPerfil, geradoEm.
 */

$corPrimaria = '#a179c3';
$corEscura   = '#5c3c7f';
$corTexto    = '#3f3f46';
$corSuave    = '#6b7280';

$config = $config ?? [];
$mulher = $mulher ?? [];

$nomeProcuradoria = (string) ($config['nome_procuradoria'] ?? 'Procuradoria da Mulher');
$nomeSistema      = (string) ($config['nome_sistema'] ?? 'Sistema de Gestão');
$contato          = trim((string) ($config['municipio'] ?? '') . (trim((string) ($config['estado'] ?? '')) !== '' ? '/' . (string) $config['estado'] : ''));

$dataHoraBr = static function (?string $valor): string {
    $texto = trim((string) $valor);
    if ($texto === '') {
        return '—';
    }

    $ts = strtotime($texto);

    return $ts === false ? $texto : date('d/m/Y H:i', $ts);
};

$valorOuVazio = static function ($valor): string {
    $texto = trim((string) $valor);

    return $texto === '' ? '<span class="nao-info">Não informado</span>' : esc($texto);
};

$nome = trim((string) ($mulher['nome_completo'] ?? '')) !== ''
    ? (string) $mulher['nome_completo']
    : 'Mulher sem nome registrado';

$perfilRotulo = [
    'admin'       => 'Administração',
    'funcionario' => 'Equipe',
][(string) ($usuarioPerfil ?? '')] ?? (string) ($usuarioPerfil ?? '');

$resumo   = $resumo ?? ['itens' => [], 'total' => 0, 'ultimaAvaliacao' => null];
$secoes   = $secoes ?? [];
$cards    = array_values(array_filter($resumo['itens'] ?? [], static fn (array $i): bool => (int) $i['total'] > 0));
$numero   = 1;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title><?= esc($titulo ?? 'Relatório Individual') ?> — <?= esc($nome) ?></title>
<style>
  @page { size: A4 portrait; margin: 15mm 14mm 20mm 14mm; }

  html, body { font-family: "DejaVu Sans", sans-serif; }
  body { margin:0; color:<?= $corTexto ?>; font-size:9pt; line-height:1.45; }
  p { margin:0; }

  .header { border-bottom:1.6pt solid <?= $corPrimaria ?>; padding-bottom:3.5mm; margin-bottom:4.5mm; }
  .header table { width:100%; border-collapse:collapse; }
  .header td { vertical-align:middle; }
  .header .logo { width:26mm; padding-right:4mm; }
  .header .logo img { width:100%; height:13mm; object-fit:contain; }
  .header .marca { font-size:8pt; color:#8a6bb5; letter-spacing:0.06em; text-transform:uppercase; }
  .header h1 { font-size:15pt; margin:1mm 0 0.8mm; color:<?= $corEscura ?>; }
  .header .sub { font-size:8.5pt; color:<?= $corSuave ?>; }

  .meta { background:#f9f5fd; border:0.4pt solid #e6d8f0; border-left:2mm solid <?= $corPrimaria ?>; padding:2.6mm 3mm; margin-bottom:4.5mm; }
  .meta .linha { font-size:8.5pt; color:#4b5563; padding:0.4mm 0; }
  .meta .linha b { color:<?= $corEscura ?>; }

  table.kpis { width:100%; border-collapse:separate; border-spacing:1.4mm 1.6mm; margin:-1.6mm -1.4mm; }
  td.kpi { border:0.5pt solid #e6d8f0; background:#fdfbff; padding:2.4mm 2.6mm; vertical-align:top; width:25%; }
  .kpi-num { font-size:15pt; font-weight:bold; color:<?= $corEscura ?>; line-height:1.1; }
  .kpi-rot { font-size:8.2pt; font-weight:bold; color:#374151; margin-top:1.2mm; }

  .sec { margin-top:5mm; }
  .sec-tit { font-size:11pt; font-weight:bold; color:<?= $corEscura ?>; border-bottom:0.8pt solid <?= $corPrimaria ?>; padding-bottom:1mm; margin-bottom:2mm; page-break-after:avoid; }
  .sub-tit { font-size:9.5pt; font-weight:bold; color:#4b5563; margin:3.2mm 0 1.4mm; page-break-after:avoid; }
  .bloco { margin-bottom:1mm; }
  .bloco.encolhe { page-break-inside:avoid; }

  table.tbl { width:100%; border-collapse:collapse; }
  table.tbl th, table.tbl td { padding:1.6mm 2mm; font-size:8.4pt; border-bottom:0.4pt solid #e6d8f0; vertical-align:top; }
  table.tbl thead th { background:#f2ebf9; color:<?= $corEscura ?>; text-align:left; font-size:8pt; border-top:0.8pt solid <?= $corPrimaria ?>; border-bottom:0.8pt solid <?= $corPrimaria ?>; }
  table.ficha td.rot { width:34%; color:<?= $corEscura ?>; font-weight:bold; }
  table.registros td.data { width:20%; white-space:nowrap; }
  table.tbl td.rotulo { color:#374151; }
  table.tbl td.sit { color:#374151; }

  .det { font-size:7.8pt; color:<?= $corSuave ?>; line-height:1.4; }
  .det b { color:#4b5563; }
  .vazio { color:<?= $corSuave ?>; font-size:8.5pt; padding:2mm 0; font-style:italic; }
  .nao-info { color:#9ca3af; font-style:italic; }

  .nota { margin-top:6mm; font-size:7.5pt; color:<?= $corSuave ?>; border-top:0.5pt solid #e6d8f0; padding-top:2mm; }
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
        <h1><?= esc((string) ($titulo ?? 'Relatório Individual')) ?></h1>
        <div class="sub">Ficha e histórico consolidado da mulher atendida<?= $contato !== '' ? ' · ' . esc($contato) : '' ?></div>
      </td>
    </tr>
  </table>
</div>

<div class="meta">
  <div class="linha"><b>Mulher:</b> <?= esc($nome) ?></div>
  <div class="linha"><b>Registro:</b> #<?= (int) ($mulher['id'] ?? 0) ?></div>
  <?php if (! empty($mulher['created_at'])): ?>
    <div class="linha"><b>Cadastrada em:</b> <?= esc((string) $mulher['created_at']) ?></div>
  <?php endif; ?>
  <div class="linha"><b>Emitido em:</b> <?= esc($geradoEm ?? '') ?><?= $usuarioNome !== '' ? ' por <b>' . esc($usuarioNome) . '</b>' . ($usuarioPerfil !== '' ? ' (' . esc($usuarioPerfil) . ')' : '') : '' ?></div>
</div>

<!-- ================= 1. IDENTIFICAÇÃO ================= -->
<div class="sec">
  <div class="sec-tit"><?= $numero++ ?>. Identificação</div>
  <?php foreach (($ficha ?? []) as $bloco): ?>
    <div class="sub-tit"><?= esc((string) $bloco['titulo']) ?></div>
    <div class="bloco">
      <table class="tbl ficha">
        <tbody>
          <?php foreach ($bloco['itens'] as $item): ?>
            <tr>
              <td class="rotulo"><?= esc((string) $item['rotulo']) ?></td>
              <td><?= trim((string) $item['valor']) === '' ? '<span class="nao-info">Não informado</span>' : esc((string) $item['valor']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endforeach; ?>
</div>

<!-- ================= 2. RESUMO DOS REGISTROS ================= -->
<div class="sec">
  <div class="sec-tit"><?= $numero++ ?>. Resumo dos registros</div>

  <?php if ($semRegistros || $cards === []): ?>
    <p class="vazio">Nenhum registro vinculado foi encontrado nos módulos do sistema.</p>
  <?php else: ?>
    <div class="bloco">
      <table class="kpis">
        <?php foreach (array_chunk($cards, 4) as $linhaCards): ?>
          <tr>
            <?php foreach ($linhaCards as $card): ?>
              <td class="kpi">
                <div class="kpi-num"><?= (int) $card['total'] ?></div>
                <div class="kpi-rot"><?= esc((string) $card['rotulo']) ?></div>
              </td>
            <?php endforeach; ?>
            <?php for ($i = count($linhaCards); $i < 4; $i++): ?>
              <td class="kpi"></td>
            <?php endfor; ?>
          </tr>
        <?php endforeach; ?>
        <tr>
          <td class="kpi">
            <div class="kpi-num"><?= (int) ($resumo['total'] ?? 0) ?></div>
            <div class="kpi-rot">Total de registros</div>
          </td>
          <?php if (! empty($resumo['ultimaAvaliacao'])): ?>
            <td class="kpi">
              <div class="kpi-num" style="font-size:11pt"><?= esc((string) $resumo['ultimaAvaliacao']['classificacao']) ?></div>
              <div class="kpi-rot">Risco mais recente<?= $resumo['ultimaAvaliacao']['data'] !== '' ? '<br>' . esc((string) $resumo['ultimaAvaliacao']['data']) : '' ?></div>
            </td>
          <?php endif; ?>
          <td class="kpi"></td>
          <td class="kpi"></td>
        </tr>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- ================= HISTÓRICO POR MÓDULO ================= -->
<?php foreach ($secoes as $secao): ?>
  <div class="sec">
    <div class="sec-tit"><?= $numero++ ?>. <?= esc((string) $secao['rotulo']) ?></div>
    <div class="bloco">
      <table class="tbl">
        <thead>
          <tr>
            <th style="width:20%">Data</th>
            <th style="width:36%">Registro</th>
            <th style="width:20%">Situação</th>
            <th style="width:24%">Detalhes</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($secao['registros'] as $registro): ?>
            <tr>
              <td><?= esc((string) $registro['data']) !== '' ? esc((string) $registro['data']) : '<span class="nao-info">Sem data</span>' ?></td>
              <td class="rotulo"><?= esc((string) $registro['titulo']) ?></td>
              <td class="sit"><?= trim((string) $registro['situacao']) === '' ? '—' : esc((string) $registro['situacao']) ?></td>
              <td>
                <?php
                $detalhes = array_values(array_filter(
                    $registro['detalhes'] ?? [],
                    static fn ($d): bool => trim((string) ($d[1] ?? '')) !== '',
                ));
                ?>
                <?php if ($detalhes === []): ?>
                  <span class="nao-info">—</span>
                <?php else: ?>
                  <?php foreach ($detalhes as $detalhe): ?>
                    <div class="det"><b><?= esc((string) $detalhe[0]) ?>:</b> <?= esc((string) $detalhe[1]) ?></div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endforeach; ?>

<div class="nota">
  Documento gerado por <b><?= esc($nomeSistema) ?></b> em <?= esc($geradoEm ?? '') ?>.
  Total de registros vinculados: <b><?= (int) ($totalRegistros ?? 0) ?></b>.
  Relatório de uso interno da equipe da Procuradoria da Mulher.
</div>

</body>
</html>
