<?= $this->extend('layouts/admin') ?>

<?php
$documento = $documento ?? null;
$mulherPrese = $mulherPreselecionada ?? null;
$atendPre = $atendimentoPreselecionado ?? null;
$procPre = $processoPreselecionado ?? null;
$medidaPre = $medidaPreselecionada ?? null;
$encPre = $encaminhamentoPreselecionado ?? null;
$manifestPre = $manifestacaoPreselecionada ?? null;
$valor = static function (string $campo, string $padrao = '') use ($documento) {
    if ($documento !== null) {
        $v = (string) ($documento[$campo] ?? '');
        return $v !== '' ? $v : $padrao;
    }

    $v = (string) old($campo, '');
    return $v !== '' ? $v : $padrao;
};
$tamanhoMaxMB = max(1, (int) round($tamanhoMax / 1048576, 0));
$formatarTamanho = static function ($bytes) {
    $b = (int) $bytes;
    if ($b >= 1048576) {
        return number_format($b / 1048576, 2, ',', '.') . ' MB';
    }
    if ($b >= 1024) {
        return number_format($b / 1024, 1, ',', '.') . ' KB';
    }
    return $b . ' B';
};
$rootVoltar = $documento !== null
    ? base_url('documentos/' . (int) $documento['id'])
    : base_url('documentos');
?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex items-center justify-between">
    <div class="flex items-center gap-2">
      <a href="<?= $rootVoltar ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 transition" title="Voltar">
        <i data-lucide="arrow-left" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
      </a>
      <h1 class="text-gray-600 text-[15px] font-medium"><?= esc($titulo) ?></h1>
    </div>
  </header>

  <form method="POST" action="<?= $editando ? base_url('documentos/atualizar/' . (int) $documento['id']) : base_url('documentos') ?>" enctype="multipart/form-data" class="flex flex-col gap-3">
    <?= $this->include('mulheres/_contexto', ['contexto' => $contexto ?? null]) ?>

    <!-- DOCUMENTO -->
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-blue-50 border border-blue-200 flex items-center justify-center rounded-lg">
          <i data-lucide="file-up" class="w-4 h-4 stroke-blue-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Documento</p>
        <?php if ($editando && $documento !== null): ?>
          <span class="ml-auto inline-flex items-center gap-1 text-[11.5px] text-gray-500 bg-gray-50 border border-gray-200 rounded-full px-2.5 py-1">
            <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
            <?= esc($documento['arquivo_original'] ?? '') ?> · <?= esc($formatarTamanho($documento['tamanho_bytes'] ?? 0)) ?>
          </span>
        <?php endif; ?>
      </div>

      <div class="flex flex-col gap-3">
        <div class="flex flex-col gap-1.5">
          <label for="arquivo" class="label-primary">Arquivo<?= $editando ? '' : ' *' ?></label>
          <input type="file" id="arquivo" name="arquivo" accept=".pdf,.png,.jpg,.jpeg,.gif,.webp,.doc,.docx,.odt,.txt,.csv,.xls,.xlsx,.pptx,.rtf" class="input-primary file:mr-3 file:rounded-lg file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-[12.5px] file:text-gray-600 file:font-medium hover:file:bg-gray-200 transition">
          <p class="text-gray-400 text-[11.5px] leading-3.5 pl-0.5">
            PDF, imagens, texto, planilhas e documentos (Word/ODT) — até <?= $tamanhoMaxMB ?> MB.
            <?= $editando ? 'Anexe um novo arquivo apenas se desejar substituir o atual.' : 'Formatos executáveis não são permitidos.' ?>
          </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
          <div class="flex flex-col gap-1.5">
            <label for="tipo" class="label-primary">Tipo</label>
            <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
              <select id="tipo" name="tipo" class="select-primary">
                <?php foreach ($tipos as $chave => $rotulo): ?>
                  <option value="<?= esc($chave) ?>" <?= $valor('tipo', 'outros') === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
                <?php endforeach; ?>
              </select>
              <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
            </div>
          </div>

          <div class="flex flex-col gap-1.5">
            <label for="sigilo" class="label-primary">Classificação de sigilo</label>
            <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
              <select id="sigilo" name="sigilo" class="select-primary">
                <?php foreach ($sigilos as $chave => $rotulo): ?>
                  <option value="<?= esc($chave) ?>" <?= $valor('sigilo', 'restrito') === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
                <?php endforeach; ?>
              </select>
              <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
            </div>
            <p class="text-gray-400 text-[11.5px] leading-3.5 pl-0.5">
              Recomendado: <strong>Restrito</strong> para documentos internos; <strong>Sigiloso</strong> para casos sensíveis.
            </p>
          </div>
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="observacoes" class="label-primary">Observações</label>
          <textarea id="observacoes" name="observacoes" class="textarea-primary" rows="3"><?= esc($valor('observacoes')) ?></textarea>
        </div>
      </div>
    </div>

    <!-- VÍNCULOS -->
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-purple-50 border border-purple-200 flex items-center justify-center rounded-lg">
          <i data-lucide="link" class="w-4 h-4 stroke-purple-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Vínculos</p>
        <span class="text-gray-400 text-[11.5px] ml-auto">Todos os vínculos devem pertencer à mesma mulher.</span>
      </div>

      <div class="flex flex-col gap-3">
        <div class="flex flex-col gap-1.5">
          <input type="hidden" name="mulher_id" id="mulher_id"
                 value="<?= esc((string) ($mulherPrese['id'] ?? old('mulher_id', ''))) ?>">
          <label for="busca_mulher" class="label-primary">Mulher *</label>
          <div class="relative">
            <i data-lucide="search" class="input-icon"></i>
            <input type="text" id="busca_mulher" autocomplete="off" placeholder="Nome ou CPF da mulher..." class="input-primary has-icon"
                   value="<?= esc((string) ($mulherPrese['nome'] ?? '')) ?>" <?= ! empty($mulherPrese['nome']) ? 'readonly' : '' ?>>
          </div>
          <div id="resultado-mulheres" class="hidden w-full bg-white border border-gray-200 rounded-xl shadow-md p-1 flex flex-col gap-0.5"></div>
          <div id="mulher-selecionada" class="hidden"></div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
          <div class="flex flex-col gap-1.5">
            <label for="busca_atendimento" class="label-primary">Atendimento</label>
            <input type="hidden" name="atendimento_id" id="atendimento_id"
                   value="<?= esc((string) ($atendPre['id'] ?? old('atendimento_id', ''))) ?>">
            <input type="text" id="busca_atendimento" autocomplete="off"
                   placeholder="Protocolo ou nome..." class="input-primary"
                   value="<?= esc((string) ($atendPre['protocolo'] ?? '')) ?>" <?= ! empty($atendPre['id']) ? 'readonly' : '' ?>>
            <div id="resultado-atendimentos" class="hidden w-full bg-white border border-gray-200 rounded-xl shadow-md p-1 flex flex-col gap-0.5"></div>
            <div id="atendimento-selecionado" class="hidden"></div>
          </div>

          <div class="flex flex-col gap-1.5">
            <label for="busca_processo" class="label-primary">Processo</label>
            <input type="hidden" name="processo_id" id="processo_id"
                   value="<?= esc((string) ($procPre['id'] ?? old('processo_id', ''))) ?>">
            <input type="text" id="busca_processo" autocomplete="off"
                   placeholder="Nº do processo ou nome..." class="input-primary"
                   value="<?= esc((string) ($procPre['titulo'] ?? '')) ?>" <?= ! empty($procPre['id']) ? 'readonly' : '' ?>>
            <div id="resultado-processos" class="hidden w-full bg-white border border-gray-200 rounded-xl shadow-md p-1 flex flex-col gap-0.5"></div>
            <div id="processo-selecionado" class="hidden"></div>
          </div>

          <div class="flex flex-col gap-1.5">
            <label for="busca_manifestacao" class="label-primary">Manifestação</label>
            <input type="hidden" name="manifestacao_id" id="manifestacao_id"
                   value="<?= esc((string) ($manifestPre['id'] ?? old('manifestacao_id', ''))) ?>">
            <input type="text" id="busca_manifestacao" autocomplete="off"
                   placeholder="Protocolo ou nome..." class="input-primary"
                   value="<?= esc((string) ($manifestPre['protocolo'] ?? '')) ?>" <?= ! empty($manifestPre['id']) ? 'readonly' : '' ?>>
            <div id="resultado-manifestacoes" class="hidden w-full bg-white border border-gray-200 rounded-xl shadow-md p-1 flex flex-col gap-0.5"></div>
            <div id="manifestacao-selecionada" class="hidden"></div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
          <div class="flex flex-col gap-1.5">
            <label for="busca_medida" class="label-primary">Medida protetiva</label>
            <input type="hidden" name="medida_protetiva_id" id="medida_protetiva_id"
                   value="<?= esc((string) ($medidaPre['id'] ?? old('medida_protetiva_id', ''))) ?>">
            <input type="text" id="busca_medida" autocomplete="off"
                   placeholder="Número da medida ou nome..." class="input-primary"
                   value="<?= esc((string) ($medidaPre['titulo'] ?? '')) ?>" <?= ! empty($medidaPre['id']) ? 'readonly' : '' ?>>
            <div id="resultado-medidas" class="hidden w-full bg-white border border-gray-200 rounded-xl shadow-md p-1 flex flex-col gap-0.5"></div>
            <div id="medida-selecionada" class="hidden"></div>
          </div>

          <div class="flex flex-col gap-1.5">
            <label for="busca_encaminhamento" class="label-primary">Encaminhamento</label>
            <input type="hidden" name="encaminhamento_id" id="encaminhamento_id"
                   value="<?= esc((string) ($encPre['id'] ?? old('encaminhamento_id', ''))) ?>">
            <input type="text" id="busca_encaminhamento" autocomplete="off"
                   placeholder="Nº do encaminhamento ou nome..." class="input-primary"
                   value="<?= esc((string) ($encPre['titulo'] ?? '')) ?>" <?= ! empty($encPre['id']) ? 'readonly' : '' ?>>
            <div id="resultado-encaminhamentos" class="hidden w-full bg-white border border-gray-200 rounded-xl shadow-md p-1 flex flex-col gap-0.5"></div>
            <div id="encaminhamento-selecionado" class="hidden"></div>
          </div>
        </div>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2">
        <i data-lucide="check" class="w-4 h-4"></i>
        <?= $editando ? 'Salvar alterações' : 'Enviar documento' ?>
      </button>
      <a href="<?= $rootVoltar ?>" class="btn-secondary text-[13px] px-4 py-2">Cancelar</a>
    </div>
  </form>
</section>

<script>
(function () {
  function selecionarMulherAutomatica(id, nome) {
    if (!id || !nome) return;
    var mulherInput = document.getElementById('mulher_id');
    var buscaMulher = document.getElementById('busca_mulher');
    var mulherSel = document.getElementById('mulher-selecionada');
    if (!mulherInput || !buscaMulher || !mulherSel) return;
    mulherInput.value = String(id);
    buscaMulher.value = nome;
    buscaMulher.setAttribute('readonly', 'readonly');
    mulherSel.innerHTML = '';
    var chip = document.createElement('div');
    chip.className = 'flex items-center gap-2 bg-blue-50 border border-blue-200 rounded-lg pl-3 pr-1.5 py-1.5 self-start';
    var txt = document.createElement('span');
    txt.className = 'text-gray-700 text-[13px] font-medium';
    txt.textContent = nome;
    // A mulher chega travada quando o documento parte da ficha, mas precisa
    // de uma saída explícita para a profissional corrigir a seleção.
    var limpar = document.createElement('button');
    limpar.type = 'button';
    limpar.title = 'Remover seleção';
    limpar.className = 'w-6 h-6 flex items-center justify-center rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 transition cursor-pointer';
    limpar.innerHTML = '<i data-lucide="x" class="w-3.5 h-3.5"></i>';
    limpar.addEventListener('click', function () {
      mulherInput.value = '';
      buscaMulher.value = '';
      buscaMulher.removeAttribute('readonly');
      mulherSel.classList.add('hidden');
      mulherSel.innerHTML = '';
      buscaMulher.focus();
      if (window.lucide) window.lucide.createIcons();
    });
    chip.appendChild(txt);
    chip.appendChild(limpar);
    mulherSel.appendChild(chip);
    mulherSel.classList.remove('hidden');
    if (window.lucide) window.lucide.createIcons();
  }

<?php if (! empty($mulherPrese)): ?>
  selecionarMulherAutomatica('<?= (int) $mulherPrese['id'] ?>', '<?= esc($mulherPrese['nome'], 'js') ?>');
<?php endif; ?>

  function preselecionar(ids, id, titulo) {
    var hidden = document.getElementById(ids.hidden);
    var busca = document.getElementById(ids.busca);
    var sel = document.getElementById(ids.selecionado);
    if (!hidden || !busca || !sel || !id) return;
    hidden.value = String(id);
    busca.value = titulo || '';
    busca.setAttribute('readonly', 'readonly');
    sel.innerHTML = '';
    var chip = document.createElement('div');
    chip.className = 'flex items-center gap-2 bg-blue-50 border border-blue-200 rounded-lg px-3 py-1.5 self-start';
    var txt = document.createElement('span');
    txt.className = 'text-gray-700 text-[13px] font-medium';
    txt.textContent = titulo || '';
    chip.appendChild(txt);
    var limpar = document.createElement('button');
    limpar.type = 'button';
    limpar.title = 'Remover seleção';
    limpar.className = 'w-6 h-6 flex items-center justify-center rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 transition cursor-pointer';
    limpar.innerHTML = '<i data-lucide="x" class="w-3.5 h-3.5"></i>';
    limpar.addEventListener('click', function () {
      hidden.value = '';
      busca.value = '';
      busca.removeAttribute('readonly');
      sel.classList.add('hidden');
      sel.innerHTML = '';
      busca.focus();
      if (window.lucide) window.lucide.createIcons();
    });
    chip.appendChild(txt);
    chip.appendChild(limpar);
    sel.appendChild(chip);
    sel.classList.remove('hidden');
    if (window.lucide) window.lucide.createIcons();
  }

<?php if (! empty($atendPre)): ?>
  preselecionar(
    { hidden: 'atendimento_id', busca: 'busca_atendimento', selecionado: 'atendimento-selecionado' },
    '<?= (int) $atendPre['id'] ?>',
    '<?= esc((string) ($atendPre['protocolo'] ?? ''), 'js') ?>'
  );
<?php endif; ?>
<?php if (! empty($procPre)): ?>
  preselecionar(
    { hidden: 'processo_id', busca: 'busca_processo', selecionado: 'processo-selecionado' },
    '<?= (int) $procPre['id'] ?>',
    '<?= esc((string) ($procPre['titulo'] ?? ''), 'js') ?>'
  );
<?php endif; ?>
<?php if (! empty($medidaPre)): ?>
  preselecionar(
    { hidden: 'medida_protetiva_id', busca: 'busca_medida', selecionado: 'medida-selecionada' },
    '<?= (int) $medidaPre['id'] ?>',
    '<?= esc((string) ($medidaPre['titulo'] ?? ''), 'js') ?>'
  );
<?php endif; ?>
<?php if (! empty($encPre)): ?>
  preselecionar(
    { hidden: 'encaminhamento_id', busca: 'busca_encaminhamento', selecionado: 'encaminhamento-selecionado' },
    '<?= (int) $encPre['id'] ?>',
    '<?= esc((string) ($encPre['titulo'] ?? ''), 'js') ?>'
  );
<?php endif; ?>
<?php if (! empty($manifestPre)): ?>
  preselecionar(
    { hidden: 'manifestacao_id', busca: 'busca_manifestacao', selecionado: 'manifestacao-selecionada' },
    '<?= (int) $manifestPre['id'] ?>',
    '<?= esc((string) ($manifestPre['protocolo'] ?? ''), 'js') ?>'
  );
<?php endif; ?>

  function montarBusca(opts) {
    var input = document.getElementById(opts.input);
    var hidden = document.getElementById(opts.hidden);
    var resultados = document.getElementById(opts.resultados);
    var selecionado = document.getElementById(opts.selecionado);
    if (!input || !hidden || !resultados || !selecionado) return;
    if (input.hasAttribute('readonly')) return;
    var timer = null;

    function montarLinha(item) {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.setAttribute('data-id', String(item[opts.campoId]));
      btn.setAttribute('data-titulo', item[opts.campoNome] || '');
      if (item.mulher_id) btn.setAttribute('data-mulher-id', String(item.mulher_id));
      if (item.mulher_nome) btn.setAttribute('data-mulher-nome', item.mulher_nome);
      btn.className = 'w-full flex items-start justify-between gap-2 px-3 py-2.5 rounded-lg text-left cursor-pointer transition hover:bg-gray-50';

      var info = document.createElement('span');
      info.className = 'flex flex-col gap-0.5 min-w-0';
      var titulo = document.createElement('strong');
      titulo.className = 'text-gray-700 text-[13px] font-medium';
      titulo.textContent = item[opts.campoNome] || '';
      var sub = document.createElement('small');
      sub.className = 'text-gray-400 text-[12px] leading-4 truncate';
      sub.textContent = opts.linhaExtra ? opts.linhaExtra(item) : '';
      info.appendChild(titulo);
      info.appendChild(sub);

      var seta = document.createElement('span');
      seta.className = 'w-4 h-4 flex items-center justify-center text-gray-300 shrink-0';
      seta.innerHTML = '<i data-lucide="chevron-right" class="w-4 h-4"></i>';

      btn.appendChild(info);
      btn.appendChild(seta);
      if (window.lucide) window.lucide.createIcons();
      return btn;
    }

    function mostrar(lista) {
      resultados.innerHTML = '';
      if (!lista.length) {
        var vazio = document.createElement('p');
        vazio.className = 'px-3 py-2.5 text-gray-400 text-[13px]';
        vazio.textContent = opts.vazio || 'Nenhum resultado encontrado.';
        resultados.appendChild(vazio);
      } else {
        lista.forEach(function (item) { resultados.appendChild(montarLinha(item)); });
      }
      resultados.classList.remove('hidden');
    }

    function renderSelecionado(btn) {
      var id = btn.getAttribute('data-id');
      var titulo = btn.getAttribute('data-titulo');
      hidden.value = id;

      if (opts.emSelecionar) opts.emSelecionar(btn, id);

      selecionado.innerHTML = '';
      var chip = document.createElement('div');
      chip.className = 'flex items-center gap-2 bg-blue-50 border border-blue-200 rounded-lg px-3 py-1.5 self-start';
      var txt = document.createElement('span');
      txt.className = 'text-gray-700 text-[13px] font-medium';
      txt.textContent = titulo;
      var limpar = document.createElement('button');
      limpar.type = 'button';
      limpar.title = 'Remover seleção';
      limpar.className = 'w-6 h-6 flex items-center justify-center rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 transition cursor-pointer';
      limpar.innerHTML = '<i data-lucide="x" class="w-3.5 h-3.5"></i>';
      limpar.addEventListener('click', function () {
        hidden.value = '';
        input.value = '';
        input.removeAttribute('readonly');
        selecionado.classList.add('hidden');
        selecionado.innerHTML = '';
        if (opts.emLimpar) opts.emLimpar();
        input.focus();
        if (window.lucide) window.lucide.createIcons();
      });
      chip.appendChild(txt);
      chip.appendChild(limpar);
      selecionado.appendChild(chip);
      selecionado.classList.remove('hidden');
      input.value = titulo;
      input.setAttribute('readonly', 'readonly');
      if (window.lucide) window.lucide.createIcons();
    }

    resultados.addEventListener('click', function (e) {
      var alvo = e.target.closest('[data-id]');
      if (alvo) {
        renderSelecionado(alvo);
        resultados.classList.add('hidden');
      }
    });

    input.addEventListener('input', function () {
      var termo = this.value.trim();
      if (!termo) { resultados.classList.add('hidden'); return; }
      clearTimeout(timer);
      timer = setTimeout(function () {
        var url = opts.url + '?term=' + encodeURIComponent(termo);
        if (opts.comMulher) {
          var mulherId = document.getElementById(opts.mulherInput || 'mulher_id');
          if (mulherId && mulherId.value) {
            url += '&mulher_id=' + encodeURIComponent(mulherId.value);
          }
        }
        fetch(url, {
          credentials: 'same-origin',
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
          .then(function (r) { return r.json(); })
          .then(function (data) { mostrar(opts.resolver(data)); })
          .catch(function () {
            resultados.textContent = 'Erro ao buscar. Tente novamente.';
            resultados.classList.remove('hidden');
          });
      }, 300);
    });

    document.addEventListener('click', function (e) {
      if (!e.target.closest('#' + resultados.id) && !e.target.closest('#' + input.id)) {
        resultados.classList.add('hidden');
      }
    });
  }

  montarBusca({
    input: 'busca_mulher',
    hidden: 'mulher_id',
    resultados: 'resultado-mulheres',
    selecionado: 'mulher-selecionada',
    url: '<?= base_url('atendimentos/buscar-mulheres') ?>',
    campoId: 'id',
    campoNome: 'nome',
    resolver: function (data) { return data.mulheres || []; },
    linhaExtra: function (m) {
      var partes = [];
      if (m.bairro) partes.push(m.bairro);
      if (m.municipio) partes.push(m.municipio);
      if (m.cpf) partes.push(m.cpf);
      return partes.join(' · ');
    },
    vazio: 'Nenhuma mulher encontrada com esse termo.'
  });

  montarBusca({
    input: 'busca_atendimento',
    hidden: 'atendimento_id',
    resultados: 'resultado-atendimentos',
    selecionado: 'atendimento-selecionado',
    url: '<?= base_url('acolhimentos/buscar-atendimentos') ?>',
    campoId: 'id',
    campoNome: 'protocolo',
    resolver: function (data) { return data.atendimentos || []; },
    linhaExtra: function (a) { return '#' + a.protocolo + ' · ' + (a.mulher_nome || ''); },
    vazio: 'Nenhum atendimento encontrado com esse termo.',
    emSelecionar: function (btn) {
      selecionarMulherAutomatica(btn.getAttribute('data-mulher-id'), btn.getAttribute('data-mulher-nome'));
    }
  });

  montarBusca({
    input: 'busca_processo',
    hidden: 'processo_id',
    resultados: 'resultado-processos',
    selecionado: 'processo-selecionado',
    url: '<?= base_url('documentos/buscar-processos') ?>',
    comMulher: true,
    campoId: 'id',
    campoNome: 'titulo',
    resolver: function (data) { return data.processos || []; },
    linhaExtra: function (p) {
      var partes = [];
      if (p.numero_processo) partes.push(p.numero_processo);
      if (p.mulher_nome) partes.push(p.mulher_nome);
      return partes.join(' · ');
    },
    vazio: 'Nenhum processo encontrado com esse termo.',
    emSelecionar: function (btn) {
      selecionarMulherAutomatica(btn.getAttribute('data-mulher-id'), btn.getAttribute('data-mulher-nome'));
    }
  });

  montarBusca({
    input: 'busca_medida',
    hidden: 'medida_protetiva_id',
    resultados: 'resultado-medidas',
    selecionado: 'medida-selecionada',
    url: '<?= base_url('processos/buscar-medidas') ?>',
    comMulher: true,
    campoId: 'id',
    campoNome: 'titulo',
    resolver: function (data) { return data.medidas || []; },
    linhaExtra: function (m) {
      var partes = [];
      if (m.situacao) partes.push(m.situacao);
      if (m.mulher_nome) partes.push(m.mulher_nome);
      return partes.join(' · ');
    },
    vazio: 'Nenhuma medida protetiva encontrada com esse termo.',
    emSelecionar: function (btn) {
      selecionarMulherAutomatica(btn.getAttribute('data-mulher-id'), btn.getAttribute('data-mulher-nome'));
    }
  });

  montarBusca({
    input: 'busca_encaminhamento',
    hidden: 'encaminhamento_id',
    resultados: 'resultado-encaminhamentos',
    selecionado: 'encaminhamento-selecionado',
    url: '<?= base_url('agenda/buscar-encaminhamentos') ?>',
    comMulher: true,
    campoId: 'id',
    campoNome: 'titulo',
    resolver: function (data) { return data.encaminhamentos || []; },
    linhaExtra: function (e) {
      var partes = [];
      if (e.orgao) partes.push(e.orgao);
      if (e.mulher_nome) partes.push(e.mulher_nome);
      return partes.join(' · ');
    },
    vazio: 'Nenhum encaminhamento encontrado com esse termo.',
    emSelecionar: function (btn) {
      selecionarMulherAutomatica(btn.getAttribute('data-mulher-id'), btn.getAttribute('data-mulher-nome'));
    }
  });

  montarBusca({
    input: 'busca_manifestacao',
    hidden: 'manifestacao_id',
    resultados: 'resultado-manifestacoes',
    selecionado: 'manifestacao-selecionada',
    url: '<?= base_url('documentos/buscar-manifestacoes') ?>',
    comMulher: true,
    campoId: 'id',
    campoNome: 'protocolo',
    resolver: function (data) { return data.manifestacoes || []; },
    linhaExtra: function (ma) {
      var partes = [];
      if (ma.anonimo) partes.push('Anônima');
      if (ma.mulher_nome) partes.push(ma.mulher_nome);
      if (ma.situacao) partes.push(ma.situacao);
      return partes.join(' · ');
    },
    vazio: 'Nenhuma manifestação encontrada com esse termo.',
    emSelecionar: function (btn) {
      if (btn.getAttribute('data-mulher-id')) {
        selecionarMulherAutomatica(btn.getAttribute('data-mulher-id'), btn.getAttribute('data-mulher-nome'));
      }
    }
  });

  if (window.lucide) window.lucide.createIcons();
})();
</script>
<?= $this->endSection() ?>