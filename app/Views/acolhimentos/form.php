<?= $this->extend('layouts/admin') ?>

<?php
$acolhimento = $acolhimento ?? null;
$preselecionado = $atendimentoPreselecionado ?? null;
$valor = static function (string $campo) use ($acolhimento) {
    if ($acolhimento !== null) {
        return (string) ($acolhimento[$campo] ?? '');
    }

    return (string) old($campo, '');
};
$marcado = static function (string $campo) use ($acolhimento) {
    if ($acolhimento !== null) {
        return ! empty($acolhimento[$campo]);
    }

    return (bool) old($campo);
};
?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex items-center justify-between">
    <div class="flex items-center gap-2">
      <a href="<?= base_url($editando ? 'acolhimentos/' . (int) $acolhimento['id'] : 'acolhimentos') ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 transition" title="Voltar">
        <i data-lucide="arrow-left" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
      </a>
      <h1 class="text-gray-600 text-[15px] font-medium"><?= $editando ? 'Editar Acolhimento #' . (int) $acolhimento['id'] : 'Novo Acolhimento' ?></h1>
    </div>
    <?php if (! $editando): ?>
      <a href="<?= base_url('acolhimentos') ?>" class="text-gray-500 text-[12px] hover:text-primary transition">
        Ver acolhimentos registrados ›
      </a>
    <?php endif; ?>
  </header>

  <form method="POST" action="<?= base_url($editando ? 'acolhimentos/atualizar/' . (int) $acolhimento['id'] : 'acolhimentos') ?>" class="flex flex-col gap-3">
    <input type="hidden" name="atendimento_id" id="atendimento_id" value="<?= $editando ? esc((string) $preselecionado['id']) : esc((string) ($preselecionado['id'] ?? old('atendimento_id', ''))) ?>">

    <?php if (! $editando && ! empty($acolhimentosEmAberto)): ?>
      <div class="w-full p-4 bg-amber-50 border border-amber-200 rounded-xl flex flex-col gap-2">
        <div class="flex items-center gap-2">
          <i data-lucide="alert-triangle" class="w-4 h-4 stroke-amber-600 stroke-[1.5]"></i>
          <p class="text-amber-700 text-[13px] font-medium leading-4">Esta mulher já possui <?= count($acolhimentosEmAberto) ?> acolhimento<?= count($acolhimentosEmAberto) > 1 ? 's' : '' ?> em aberto</p>
        </div>
        <ul class="flex flex-col gap-1">
          <?php foreach ($acolhimentosEmAberto as $aberto): ?>
            <li class="text-amber-700 text-[12px]">
              <a href="<?= base_url('acolhimentos/' . (int) $aberto['id']) ?>" class="underline hover:text-amber-700 transition" target="_blank" rel="noopener">
                #<?= (int) $aberto['id'] ?> — <?= esc(date('d/m/Y', strtotime((string) $aberto['data_hora']))) ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
        <p class="text-amber-700 text-[12px] leading-4">Confirme se este novo acolhimento é realmente necessário antes de registrar.</p>
      </div>
    <?php endif; ?>

    <?= $this->include('mulheres/_contexto', ['contexto' => $contexto ?? null]) ?>

    <!-- IDENTIFICAÇÃO -->
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-blue-50 border border-blue-200 flex items-center justify-center rounded-lg">
          <i data-lucide="briefcase" class="w-4 h-4 stroke-blue-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Identificação</p>
      </div>

      <div class="flex flex-col gap-1.5">
        <?php if ($editando): ?>
          <label class="label-primary">Atendimento vinculado</label>
          <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2.5">
            <span class="font-mono text-gray-700 text-[13px] font-medium">#<?= esc($preselecionado['protocolo']) ?></span>
            <span class="text-gray-400 text-[12px]">·</span>
            <span class="text-gray-600 text-[13px]"><?= esc($preselecionado['mulher_nome']) ?></span>
          </div>
        <?php else: ?>
          <label for="busca_atendimento" class="label-primary">Buscar atendimento existente *</label>
          <div class="relative">
            <i data-lucide="search" class="input-icon"></i>
            <input type="text" id="busca_atendimento" autocomplete="off" placeholder="Protocolo (AT-…), nome ou CPF da mulher..." class="input-primary has-icon">
          </div>
          <div id="resultado-busca" class="hidden w-full bg-white border border-gray-200 rounded-xl shadow-md p-1 flex flex-col gap-0.5"></div>
          <div id="atendimento-selecionado" class="hidden"></div>
          <p class="text-gray-400 text-[12px] leading-4">
            O acolhimento sempre parte de um atendimento já aberto. Não encontrou? Registre o
            <a href="<?= base_url('atendimentos/novo') ?>" class="text-primary hover:underline">atendimento</a> primeiro.
          </p>
        <?php endif; ?>
      </div>

      <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
        <div class="flex flex-col gap-1.5">
          <label class="label-primary">Mulher</label>
          <div id="mulher-exibicao" class="h-11 flex items-center px-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 text-[13px] <?= ($preselecionado['mulher_nome'] ?? '') === '' ? 'text-gray-400' : '' ?>">
            <?= esc($preselecionado['mulher_nome'] ?? 'A aguardar seleção do atendimento…') ?>
          </div>
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="data_hora" class="label-primary">Data/hora do acolhimento *</label>
          <input type="datetime-local" id="data_hora" name="data_hora"
                 value="<?= esc($editando ? date('Y-m-d\TH:i', strtotime($acolhimento['data_hora'])) : (string) old('data_hora', $hoje)) ?>"
                 class="input-primary">
        </div>

        <div class="flex flex-col gap-1.5">
          <label class="label-primary">Profissional responsável</label>
          <div class="h-11 flex items-center gap-2 px-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 text-[13px]">
            <i data-lucide="shield-check" class="w-4 h-4 stroke-gray-400 stroke-[1.5]"></i>
            <?= esc($usuarioNome) ?>
          </div>
          <p class="text-gray-400 text-[11.5px] leading-3.5 pl-0.5 -mt-1">Usuário autenticado.</p>
        </div>
      </div>
    </div>

    <!-- SITUAÇÃO -->
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-amber-50 border border-amber-200 flex items-center justify-center rounded-lg">
          <i data-lucide="message-square-text" class="w-4 h-4 stroke-amber-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Situação</p>
      </div>

      <div class="flex flex-col gap-3">
        <div class="flex flex-col gap-1.5">
          <label for="motivo_procura" class="label-primary">Motivo da procura</label>
          <textarea id="motivo_procura" name="motivo_procura" class="textarea-primary" rows="3"><?= esc($valor('motivo_procura')) ?></textarea>
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="situacao_apresentada" class="label-primary">Situação apresentada</label>
          <textarea id="situacao_apresentada" name="situacao_apresentada" class="textarea-primary" rows="3"><?= esc($valor('situacao_apresentada')) ?></textarea>
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="necessidades_imediatas" class="label-primary">Necessidades imediatas</label>
          <textarea id="necessidades_imediatas" name="necessidades_imediatas" class="textarea-primary" rows="2"><?= esc($valor('necessidades_imediatas')) ?></textarea>
        </div>
      </div>
    </div>

    <!-- NECESSIDADES IDENTIFICADAS -->
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-purple-50 border border-purple-200 flex items-center justify-center rounded-lg">
          <i data-lucide="list-checks" class="w-4 h-4 stroke-purple-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Necessidades identificadas</p>
        <p class="text-gray-400 text-[12px] ml-auto">Poderão gerar encaminhamentos e tarefas em etapa futura.</p>
      </div>

      <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
        <?php foreach ($necessidades as $campo => $rotulo): ?>
          <?php if ($campo === 'outras'): ?>
            <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-gray-400 text-[13px] col-span-2 lg:col-span-4">
              Outras necessidades &nbsp;·&nbsp;
              <input type="text" id="outras_necessidades" name="outras_necessidades" maxlength="200" value="<?= esc($valor('outras_necessidades')) ?>" placeholder="Descreva, se houver" class="flex-1 bg-transparent outline-none text-gray-700 text-[13px] placeholder:text-gray-400">
            </div>
          <?php else: ?>
            <label class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 cursor-pointer select-none hover:border-gray-300 transition">
              <input type="checkbox" name="<?= esc($campo) ?>" value="1" <?= $marcado($campo) ? 'checked' : '' ?> class="w-4 h-4 accent-[#a179c3]">
              <span class="text-gray-600 text-[13px]"><?= esc($rotulo) ?></span>
            </label>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- RISCO -->
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-red-50 border border-red-200 flex items-center justify-center rounded-lg">
          <i data-lucide="alert-triangle" class="w-4 h-4 stroke-red-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Risco</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
        <div class="flex flex-col gap-1.5">
          <label for="risco_identificado" class="label-primary">Risco identificado</label>
          <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
            <select id="risco_identificado" name="risco_identificado" class="select-primary">
              <option value="">Não informado</option>
              <?php foreach ($riscos as $chave => $rotulo): ?>
                <option value="<?= esc($chave) ?>" <?= $valor('risco_identificado') === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
              <?php endforeach; ?>
            </select>
            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
          </div>
          <p class="text-gray-400 text-[11.5px] leading-3.5 pl-0.5 -mt-1">Detalhado na avaliação de risco.</p>
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="observacoes" class="label-primary">Observações</label>
          <textarea id="observacoes" name="observacoes" class="textarea-primary" rows="2"><?= esc($valor('observacoes')) ?></textarea>
        </div>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2">
        <i data-lucide="check" class="w-4 h-4"></i>
        <?= $editando ? 'Salvar alterações' : 'Registrar acolhimento' ?>
      </button>
      <a href="<?= base_url($editando ? 'acolhimentos/' . (int) $acolhimento['id'] : 'acolhimentos') ?>" class="btn-secondary text-[13px] px-4 py-2">Cancelar</a>
    </div>
  </form>
</section>

<?php if (! $editando): ?>
<script>
(function () {
  var baseURL = '<?= base_url('acolhimentos/buscar-atendimentos') ?>';
  var input = document.getElementById('busca_atendimento');
  if (!input) return;
  var hidden = document.getElementById('atendimento_id');
  var resultados = document.getElementById('resultado-busca');
  var selecionado = document.getElementById('atendimento-selecionado');
  var mulherExibicao = document.getElementById('mulher-exibicao');
  var timer = null;

  function montarLinha(a) {
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.setAttribute('data-atendimento-id', String(a.id));
    btn.setAttribute('data-protocolo', a.protocolo);
    btn.setAttribute('data-mulher', a.mulher_nome);
    btn.className = 'w-full flex items-start justify-between gap-2 px-3 py-2.5 rounded-lg text-left cursor-pointer transition hover:bg-gray-50';

    var info = document.createElement('span');
    info.className = 'flex flex-col gap-0.5 min-w-0';

    var topo = document.createElement('span');
    topo.className = 'flex items-center gap-2 min-w-0';
    var proto = document.createElement('strong');
    proto.className = 'font-mono text-primary text-[13px] font-medium';
    proto.textContent = '#' + a.protocolo;
    topo.appendChild(proto);
    var quando = document.createElement('small');
    quando.className = 'text-gray-400 text-[12px]';
    quando.textContent = a.data_hora_formatted || '';
    topo.appendChild(quando);
    info.appendChild(topo);

    var extras = document.createElement('small');
    extras.className = 'text-gray-400 text-[12px] leading-4 truncate';
    extras.textContent = a.mulher_nome || '';
    info.appendChild(extras);

    var seta = document.createElement('span');
    seta.className = 'w-4 h-4 flex items-center justify-center text-gray-300 shrink-0';
    seta.innerHTML = '<i data-lucide="chevron-right" class="w-4 h-4"></i>';

    btn.appendChild(info);
    btn.appendChild(seta);
    if (window.lucide) window.lucide.createIcons();
    return btn;
  }

  function mostrarResultados(lista) {
    resultados.innerHTML = '';
    if (!lista.length) {
      var vazio = document.createElement('p');
      vazio.className = 'px-3 py-2.5 text-gray-400 text-[13px]';
      vazio.textContent = 'Nenhum atendimento encontrado com esse termo.';
      resultados.appendChild(vazio);
    } else {
      lista.forEach(function (a) { resultados.appendChild(montarLinha(a)); });
    }
    resultados.classList.remove('hidden');
  }

  function buscar(termo) {
    fetch(baseURL + '?term=' + encodeURIComponent(termo), {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.json(); })
      .then(function (data) { mostrarResultados(data.atendimentos || []); })
      .catch(function () {
        resultados.textContent = 'Erro ao buscar. Tente novamente.';
        resultados.classList.remove('hidden');
      });
  }

  input.addEventListener('input', function () {
    var termo = this.value.trim();
    if (!termo) { resultados.classList.add('hidden'); return; }
    clearTimeout(timer);
    timer = setTimeout(function () { buscar(termo); }, 300);
  });

  function renderSelecionado(proto, mulher) {
    var chip = document.createElement('div');
    chip.className = 'flex items-center gap-2 bg-blue-50 border border-blue-200 rounded-lg pl-3 pr-1.5 py-1.5 self-start';

    var i = document.createElement('i');
    i.setAttribute('data-lucide', 'briefcase');
    i.className = 'w-4 h-4 stroke-blue-600 stroke-[1.5] shrink-0';

    var txt = document.createElement('span');
    txt.className = 'flex flex-col';
    var strong = document.createElement('strong');
    strong.className = 'font-mono text-gray-700 text-[13px] font-medium';
    strong.textContent = '#' + proto;
    var mul = document.createElement('small');
    mul.className = 'text-gray-500 text-[12px]';
    mul.textContent = mulher;
    txt.appendChild(strong);
    txt.appendChild(mul);

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
      mulherExibicao.textContent = 'A aguardar seleção do atendimento…';
      mulherExibicao.classList.add('text-gray-400');
      input.focus();
    });

    chip.appendChild(i);
    chip.appendChild(txt);
    chip.appendChild(limpar);

    selecionado.innerHTML = '';
    selecionado.appendChild(chip);
    selecionado.classList.remove('hidden');
    mulherExibicao.textContent = mulher;
    mulherExibicao.classList.remove('text-gray-400');
    if (window.lucide) window.lucide.createIcons();
  }

  document.addEventListener('click', function (e) {
    var alvo = e.target.closest('[data-atendimento-id]');
    if (alvo) {
      hidden.value = alvo.getAttribute('data-atendimento-id');
      input.value = alvo.getAttribute('data-protocolo');
      input.setAttribute('readonly', 'readonly');
      renderSelecionado(alvo.getAttribute('data-protocolo'), alvo.getAttribute('data-mulher'));
      resultados.classList.add('hidden');
      return;
    }
    if (!e.target.closest('#resultado-busca') && !e.target.closest('#busca_atendimento')) {
      resultados.classList.add('hidden');
    }
  });

  if (hidden.value && !hidden.readOnly) {
    input.removeAttribute('readonly');
  }
})();
</script>
<?php endif; ?>
<?= $this->endSection() ?>