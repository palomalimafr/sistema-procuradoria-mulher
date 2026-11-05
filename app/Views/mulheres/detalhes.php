<?= $this->extend('layouts/admin') ?>

<?php $m = $mulher; ?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex items-center justify-between">
    <div class="flex items-center gap-2">
      <a href="<?= base_url('mulheres/pesquisa') ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 transition" title="Voltar">
        <i data-lucide="arrow-left" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
      </a>
      <div class="flex flex-col">
        <h1 class="text-gray-600 text-[15px] font-medium"><?= esc($m['nome_completo']) ?></h1>
        <?php if (! empty($m['nome_social'])): ?>
          <span class="text-gray-400 text-[12px] leading-4">Nome social: <?= esc($m['nome_social']) ?></span>
        <?php endif; ?>
      </div>
    </div>
    <div class="flex items-center gap-2">
      <?php if (! empty($conta)): ?>
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-green-50 border border-green-200 text-green-700">
          <i data-lucide="check" class="w-3.5 h-3.5"></i>
          Com conta
        </span>
      <?php else: ?>
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-gray-50 border border-gray-200 text-gray-500">
          <i data-lucide="user-x" class="w-3.5 h-3.5"></i>
          Sem conta
        </span>
      <?php endif; ?>
    </div>
  </header>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-blue-50 border border-blue-200 flex items-center justify-center rounded-lg">
          <i data-lucide="user" class="w-4 h-4 stroke-blue-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Dados pessoais</p>
      </div>

      <div class="flex flex-col divide-y divide-gray-100">
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Nome</span>
          <span class="text-gray-600 text-[12.5px] text-right"><?= esc($m['nome_completo']) ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Nome social</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($m['nome_social'] ?: '—') ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">CPF</span>
          <span class="text-gray-600 text-[12.5px] font-mono"><?= esc($m['cpf'] ?: '—') ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">RG</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($m['rg'] ?: '—') ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Nascimento</span>
          <span class="text-gray-600 text-[12.5px]">
            <?= $m['data_nascimento'] ? date('d/m/Y', strtotime($m['data_nascimento'])) : '—' ?>
            <?php if ($idade = \App\Models\MulherModel::calcularIdade($m['data_nascimento'])): ?>
              <span class="text-gray-400">(<?= $idade ?> anos)</span>
            <?php endif; ?>
          </span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Sexo</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($sexos[$m['sexo']] ?? ($m['sexo'] ?: '—')) ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Estado civil</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($estadosCivis[$m['estado_civil']] ?? ($m['estado_civil'] ?: '—')) ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Escolaridade</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($escolaridades[$m['escolaridade']] ?? ($m['escolaridade'] ?: '—')) ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Profissão</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($m['profissao'] ?: '—') ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Ocupação</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($m['ocupacao'] ?: '—') ?></span>
        </div>
      </div>
    </div>

    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-gray-100 border border-gray-200 flex items-center justify-center rounded-lg">
          <i data-lucide="phone" class="w-4 h-4 stroke-gray-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Contato</p>
      </div>

      <div class="flex flex-col divide-y divide-gray-100">
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Telefone</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($m['telefone'] ?: '—') ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">WhatsApp</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($m['whatsapp'] ?: '—') ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">E-mail</span>
          <span class="text-gray-600 text-[12.5px] text-right"><?= esc($m['email'] ?: '—') ?></span>
        </div>
      </div>
    </div>

    <?php
    $acesso = $statusAcesso ?? ['estado' => 'sem_conta', 'rotulo' => 'Sem conta', 'descricao' => '', 'convite' => null];
    $estiloAcesso = [
        'criada'    => ['bg-emerald-50', 'text-emerald-700', 'border-emerald-200', 'user-check'],
        'enviado'   => ['bg-amber-50', 'text-amber-700', 'border-amber-200', 'mail-check'],
        'sem_conta' => ['bg-gray-50', 'text-gray-600', 'border-gray-200', 'user-x'],
    ][$acesso['estado']] ?? ['bg-gray-50', 'text-gray-600', 'border-gray-200', 'user-x'];
    $conviteAberto = $acesso['estado'] !== 'criada' && $acesso['estado'] !== 'enviado';
    ?>
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-gray-100 border border-gray-200 flex items-center justify-center rounded-lg">
          <i data-lucide="key-round" class="w-4 h-4 stroke-gray-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Acesso</p>
      </div>

      <div class="flex items-center justify-between gap-2">
        <span class="text-[11px] px-2 py-1 rounded-lg border inline-flex items-center gap-1.5 <?= esc($estiloAcesso[0]) ?> <?= esc($estiloAcesso[1]) ?> <?= esc($estiloAcesso[2]) ?>">
          <i data-lucide="<?= esc($estiloAcesso[3]) ?>" class="w-3.5 h-3.5"></i>
          <?= esc($acesso['rotulo']) ?>
        </span>

        <?php if ($conviteAberto && ! empty($m['email'])): ?>
          <form action="<?= base_url('mulheres/convite/' . $m['id']) ?>" method="POST" class="inline">
            <button type="submit" class="text-[13px] inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50 transition">
              <i data-lucide="send" class="w-4 h-4"></i>
              Enviar convite
            </button>
          </form>
        <?php elseif ($acesso['estado'] === 'enviado'): ?>
          <form action="<?= base_url('mulheres/convite/' . $m['id']) ?>" method="POST" class="inline">
            <button type="submit" class="text-[13px] inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50 transition">
              <i data-lucide="refresh-cw" class="w-4 h-4"></i>
              Reenviar
            </button>
          </form>
        <?php endif; ?>
      </div>

      <?php if (! empty($acesso['descricao'])): ?>
        <p class="text-[12px] text-gray-500 leading-4"><?= esc($acesso['descricao']) ?></p>
      <?php endif; ?>

      <?php if (! empty($acesso['convite']['expira_em']) && $acesso['estado'] === 'enviado'): ?>
        <p class="text-[11px] text-gray-400">Convite válido até <?= esc(date('d/m/Y', strtotime((string) $acesso['convite']['expira_em']))) ?> às <?= esc(date('H:i', strtotime((string) $acesso['convite']['expira_em']))) ?>.</p>
      <?php endif; ?>

      <?php if ($conviteAberto && empty($m['email'])): ?>
        <p class="text-[12px] text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-2.5 py-2">
          Cadastre um e-mail na ficha para poder enviar o convite de primeiro acesso.
        </p>
      <?php endif; ?>
    </div>

    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-gray-100 border border-gray-200 flex items-center justify-center rounded-lg">
          <i data-lucide="map-pin" class="w-4 h-4 stroke-gray-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Endereço</p>
      </div>

      <div class="flex flex-col divide-y divide-gray-100">
        <div class="flex justify-between py-2 gap-3">
          <span class="text-gray-400 text-[12px]">Logradouro</span>
          <span class="text-gray-600 text-[12.5px] text-right"><?= esc(trim(($m['logradouro'] ?? '') . ($m['numero'] ? ', ' . $m['numero'] : '')) ?: '—') ?></span>
        </div>
        <div class="flex justify-between py-2 gap-3">
          <span class="text-gray-400 text-[12px]">Complemento</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($m['complemento'] ?: '—') ?></span>
        </div>
        <div class="flex justify-between py-2 gap-3">
          <span class="text-gray-400 text-[12px]">Bairro</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($m['bairro'] ?: '—') ?></span>
        </div>
        <div class="flex justify-between py-2 gap-3">
          <span class="text-gray-400 text-[12px]">Município / UF</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc(trim(($m['municipio'] ?? '') . ($m['estado'] ? '/' . $m['estado'] : '')) ?: '—') ?></span>
        </div>
        <div class="flex justify-between py-2 gap-3">
          <span class="text-gray-400 text-[12px]">CEP</span>
          <span class="text-gray-600 text-[12.5px] font-mono"><?= esc($m['cep'] ?: '—') ?></span>
        </div>
        <div class="flex justify-between py-2 gap-3">
          <span class="text-gray-400 text-[12px]">Ponto de referência</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($m['ponto_referencia'] ?: '—') ?></span>
        </div>
        <div class="flex justify-between py-2 gap-3">
          <span class="text-gray-400 text-[12px]">Zona</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($zonas[$m['zona']] ?? ($m['zona'] ?: '—')) ?></span>
        </div>
      </div>
    </div>

    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-gray-100 border border-gray-200 flex items-center justify-center rounded-lg">
          <i data-lucide="clipboard-list" class="w-4 h-4 stroke-gray-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Informações complementares</p>
      </div>

      <div class="flex flex-col divide-y divide-gray-100">
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Possui filhos</span>
          <span class="text-gray-600 text-[12.5px]"><?= $m['possui_filhos'] ? 'Sim' : 'Não' ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Quantidade de filhos</span>
          <span class="text-gray-600 text-[12.5px]"><?= $m['quantidade_filhos'] !== null ? (int) $m['quantidade_filhos'] : '—' ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Quantidade de dependentes</span>
          <span class="text-gray-600 text-[12.5px]"><?= $m['quantidade_dependentes'] !== null ? (int) $m['quantidade_dependentes'] : '—' ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Gestante</span>
          <span class="text-gray-600 text-[12.5px]"><?= $m['esta_gravida'] ? 'Sim' : 'Não' ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Possui deficiência</span>
          <span class="text-gray-600 text-[12.5px]"><?= $m['possui_deficiencia'] ? 'Sim' : 'Não' ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Idosa</span>
          <span class="text-gray-600 text-[12.5px]"><?= $m['e_idosa'] ? 'Sim' : 'Não' ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Situação de vulnerabilidade</span>
          <span class="text-gray-600 text-[12.5px]"><?= $m['situacao_vulnerabilidade'] ? 'Sim' : 'Não' ?></span>
        </div>
      </div>
    </div>
  </div>

  <?php if (! empty($m['observacoes'])): ?>
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-1">
      <p class="text-gray-500 text-[12px] font-medium uppercase tracking-wider">Observações</p>
      <p class="text-gray-600 text-[13px] leading-5 whitespace-pre-line"><?= esc($m['observacoes']) ?></p>
    </div>
  <?php endif; ?>

  <div class="flex items-center gap-2">
    <a href="<?= base_url('mulheres/editar/' . (int) $m['id']) ?>" class="btn-primary text-[13px] text-white px-4 py-2">
      <i data-lucide="pencil" class="w-4 h-4"></i>
      Editar
    </a>
    <a href="<?= base_url('mulheres/historico/' . (int) $m['id']) ?>" class="btn-secondary text-[13px] px-4 py-2">
      <i data-lucide="history" class="w-4 h-4"></i>
      Ver histórico
    </a>
    <a href="<?= base_url('atendimentos/novo?mulher_id=' . (int) $m['id']) ?>" class="btn-secondary text-[13px] px-4 py-2">
      <i data-lucide="clipboard-plus" class="w-4 h-4"></i>
      Novo atendimento
    </a>
  </div>

  <?php
  $atalhosRegistro = [
      ['acolhimentos/novo',       'Acolhimento',      'heart-handshake'],
      ['casos/novo',              'Caso',             'folder-plus'],
      ['encaminhamentos/novo',    'Encaminhamento',   'share'],
      ['tarefas/nova',            'Tarefa',           'list-checks'],
      ['agenda/novo',             'Compromisso',      'calendar-plus'],
      ['medidas-protetivas/nova', 'Medida protetiva', 'shield-check'],
      ['processos/nova',          'Processo',         'folder-open'],
      ['documentos/novo',         'Documento',        'file-plus'],
      ['manifestacoes/nova',      'Manifestação',     'message-square-text'],
  ];
  ?>
  <div class="flex flex-wrap items-center gap-2 border-t border-gray-100 pt-3">
    <span class="text-gray-400 text-[11px] uppercase tracking-wide mr-1">Registrar</span>
    <?php foreach ($atalhosRegistro as [$rota, $rotulo, $icone]): ?>
      <a href="<?= base_url($rota . '?mulher_id=' . (int) $m['id']) ?>"
         class="inline-flex items-center gap-1.5 text-gray-500 text-[12px] hover:text-primary transition"
         title="Registrar <?= esc($rotulo) ?> para esta mulher — a ficha e o histórico já vêm carregados">
        <i data-lucide="<?= esc($icone) ?>" class="w-3.5 h-3.5 stroke-gray-400 stroke-[1.5]"></i>
        <?= esc($rotulo) ?>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?= $this->endSection() ?>