<?= $this->extend('layouts/admin') ?>

<?php
$processo = $processo ?? null;
$mulherPrese = $mulherPreselecionada ?? null;
$atendPre = $atendimentoPreselecionado ?? null;
$medidaPre = $medidaPreselecionada ?? null;
$valor = static function (string $campo) use ($processo) {
    if ($processo !== null) {
        return (string) ($processo[$campo] ?? '');
    }

    return (string) old($campo, '');
};
$rootVoltar = $processo !== null
    ? base_url('processos/' . (int) $processo['id'])
    : base_url('processos');
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

  <form method="POST" action="<?= $editando ? base_url('processos/atualizar/' . (int) $processo['id']) : base_url('processos') ?>" class="flex flex-col gap-3">
    <!-- PROCESSO -->
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-blue-50 border border-blue-200 flex items-center justify-center rounded-lg">
          <i data-lucide="scale" class="w-4 h-4 stroke-blue-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Processo</p>
      </div>

      <div class="flex flex-col gap-3">
        <div class="flex flex-col gap-1.5">
          <label for="numero_processo" class="label-primary">Número do processo</label>
          <input type="text" id="numero_processo" name="numero_processo" value="<?= esc($valor('numero_processo')) ?>" placeholder="Nº conforme informado pelo órgão" class="input-primary">
          <p class="text-gray-400 text-[11.5px] leading-3.5 pl-0.5">O número é registrado exatamente como informado.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-3">
          <div class="flex flex-col gap-1.5">
            <label for="tipo" class="label-primary">Tipo</label>
            <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
              <select id="tipo" name="tipo" class="select-primary">
                <?php foreach ($tipos as $chave => $rotulo): ?>
                  <option value="<?= esc($chave) ?>" <?= $valor('tipo') === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
                <?php endforeach; ?>
              </select>
              <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
            </div>
          </div>

          <div class="flex flex-col gap-1.5">
            <label for="data" class="label-primary">Data</label>
            <input type="date" id="data" name="data" value="<?= esc($valor('data')) ?>" class="input-primary">
          </div>

          <div class="flex flex-col gap-1.5">
            <label for="situacao" class="label-primary">Situação</label>
            <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
              <select id="situacao" name="situacao" class="select-primary">
                <?php foreach ($situacoes as $chave => $rotulo): ?>
                  <option value="<?= esc($chave) ?>" <?= $valor('situacao') === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
                <?php endforeach; ?>
              </select>
              <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
            </div>
          </div>

          <div class="flex flex-col gap-1.5">
            <label for="responsavel_id" class="label-primary">Responsável *</label>
            <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
              <select id="responsavel_id" name="responsavel_id" class="select-primary">
                <?php foreach ($equipe as $u): ?>
                  <option value="<?= (int) $u['id'] ?>" <?= (string) $valor('responsavel_id') === (string) $u['id'] ? 'selected' : '' ?><?= $valor('responsavel_id') === '' && (int) $u['id'] === $usuarioId ? 'selected' : '' ?>><?= esc($u['nome']) ?></option>
                <?php endforeach; ?>
              </select>
              <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
          <div class="flex flex-col gap-1.5">
            <label for="orgao" class="label-primary">Órgão</label>
            <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
              <select id="orgao" name="orgao" class="select-primary">
                <option value="">—</option>
                <?php foreach ($orgaos as $chave => $rotulo): ?>
                  <option value="<?= esc($chave) ?>" <?= $valor('orgao') === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
                <?php endforeach; ?>
              </select>
              <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
            </div>
          </div>

          <div class="flex flex-col gap-1.5">
            <label for="vara" class="label-primary">Vara</label>
            <input type="text" id="vara" name="vara" value="<?= esc($valor('vara')) ?>" placeholder="Vara" class="input-primary">
          </div>

          <div class="flex flex-col gap-1.5">
            <label for="comarca" class="label-primary">Comarca</label>
            <input type="text" id="comarca" name="comarca" value="<?= esc($valor('comarca')) ?>" placeholder="Comarca" class="input-primary">
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
        <p class="text-gray-600 text-[13px] font-medium leading-4">Vínculos (opcionais)</p>
      </div>

      <?= $this->include('mulheres/_contexto', ['contexto' => $contexto ?? null]) ?>

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

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
          <div class="flex flex-col gap-1.5">
            <label for="busca_atendimento" class="label-primary">Atendimento</label>
            <input type="hidden" name="atendimento_id" id="atendimento_id"
                   value="<?= esc((string) ($atendPre['id'] ?? old('atendimento_id', ''))) ?>">
            <input type="text" id="busca_atendimento" autocomplete="off"
                   placeholder="Protocolo ou nome..." class="input-primary"
                   value="<?= esc((string) ($atendPre['protocolo'] ?? '')) ?>" <?= ! empty($atendPre['protocolo']) ? 'readonly' : '' ?>>
            <div id="resultado-atendimentos" class="hidden w-full bg-white border border-gray-200 rounded-xl shadow-md p-1 flex flex-col gap-0.5"></div>
            <div id="atendimento-selecionado" class="hidden"></div>
          </div>

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
        </div>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2">
        <i data-lucide="check" class="w-4 h-4"></i>
        <?= $editando ? 'Salvar alterações' : 'Registrar processo' ?>
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
    chip.className = 'flex items-center gap-2 bg-blue-50 border border-blue-200 rounded-lg px-3 py-1.5 self-start';
    var txt = document.createElement('span');
    txt.className = 'text-gray-700 text-[13px] font-medium';
    txt.textContent = nome;
    chip.appendChild(txt);
    mulherSel.appendChild(chip);
    mulherSel.classList.remove('hidden');
  }

<?php if (! empty($mulherPrese)): ?>
  selecionarMulherAutomatica('<?= (int) $mulherPrese['id'] ?>', '<?= esc($mulherPrese['nome'], 'js') ?>');
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
    emSelecionar: function (btn, id) {
      var nomeMulher = btn.getAttribute('data-mulher-nome');
      selecionarMulherAutomatica(btn.getAttribute('data-mulher-id'), nomeMulher);
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
    emSelecionar: function (btn, id) {
      var nomeMulher = btn.getAttribute('data-mulher-nome');
      selecionarMulherAutomatica(btn.getAttribute('data-mulher-id'), nomeMulher);
    }
  });

  if (window.lucide) window.lucide.createIcons();
})();
</script>
<?= $this->endSection() ?>