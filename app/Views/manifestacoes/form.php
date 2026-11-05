<?= $this->extend('layouts/admin') ?>

<?php
$manifestacao = $manifestacao ?? null;
$mulherPrese = $mulherPreselecionada ?? null;
$valor = static function (string $campo, string $padrao = '') use ($manifestacao) {
    if ($manifestacao !== null) {
        $v = (string) ($manifestacao[$campo] ?? '');
        return $v !== '' ? $v : $padrao;
    }

    $v = (string) old($campo, '');
    return $v !== '' ? $v : $padrao;
};
$anonimo = $manifestacao !== null ? (int) ($manifestacao['anonimo'] ?? 0) === 1 : (int) old('anonimo', 0) === 1;
$rootVoltar = $manifestacao !== null
    ? base_url('manifestacoes/' . (int) $manifestacao['id'])
    : base_url('manifestacoes');
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

  <form method="POST" action="<?= $editando ? base_url('manifestacoes/atualizar/' . (int) $manifestacao['id']) : base_url('manifestacoes') ?>" class="flex flex-col gap-3">
    <?= $this->include('mulheres/_contexto', ['contexto' => $contexto ?? null]) ?>

    <!-- IDENTIFICAÇÃO / ANONIMATO -->
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-slate-50 border border-slate-200 flex items-center justify-center rounded-lg">
          <i data-lucide="user" class="w-4 h-4 stroke-slate-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Identificação</p>
        <span class="text-gray-400 text-[11.5px] ml-auto">Os dados abaixo são opcionais.</span>
      </div>

      <label class="flex items-start gap-2.5 cursor-pointer select-none">
        <input type="checkbox" name="anonimo" id="anonimo" value="1" <?= $anonimo ? 'checked' : '' ?> class="w-4 h-4 mt-0.5 accent-[#a179c3]">
        <span class="flex flex-col">
          <span class="text-gray-700 text-[13px] font-medium">Manifestação anônima</span>
          <span class="text-gray-400 text-[12px] leading-4">Não registrar a identificação da pessoa. A manifestação ainda poderá ser vinculada a uma mulher já cadastrada.</span>
        </span>
      </label>

      <div id="campos-pessoa" class="grid grid-cols-1 lg:grid-cols-2 gap-3">
        <div class="flex flex-col gap-1.5">
          <label for="pessoa_nome" class="label-primary">Nome</label>
          <input type="text" id="pessoa_nome" name="pessoa_nome" value="<?= esc($valor('pessoa_nome')) ?>" autocomplete="off" class="input-primary">
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="pessoa_cpf" class="label-primary">CPF</label>
          <input type="text" id="pessoa_cpf" name="pessoa_cpf" value="<?= esc($valor('pessoa_cpf')) ?>" autocomplete="off" placeholder="Apenas números" class="input-primary">
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="pessoa_telefone" class="label-primary">Telefone</label>
          <input type="text" id="pessoa_telefone" name="pessoa_telefone" value="<?= esc($valor('pessoa_telefone')) ?>" autocomplete="off" class="input-primary">
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="pessoa_email" class="label-primary">E-mail</label>
          <input type="email" id="pessoa_email" name="pessoa_email" value="<?= esc($valor('pessoa_email')) ?>" autocomplete="off" class="input-primary">
        </div>
      </div>
    </div>

    <!-- MANIFESTAÇÃO -->
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-blue-50 border border-blue-200 flex items-center justify-center rounded-lg">
          <i data-lucide="megaphone" class="w-4 h-4 stroke-blue-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Manifestação</p>
        <?php if (!$editando): ?>
          <span class="ml-auto text-gray-400 text-[11.5px]">Protocolo gerado automaticamente.</span>
        <?php else: ?>
          <span class="ml-auto inline-flex items-center gap-1 font-mono text-[11.5px] text-gray-500 bg-gray-50 border border-gray-200 rounded-full px-2.5 py-1">
            <i data-lucide="hash" class="w-3.5 h-3.5"></i>
            <?= esc($manifestacao['protocolo'] ?? '') ?>
          </span>
        <?php endif; ?>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
        <div class="flex flex-col gap-1.5">
          <label for="tipo" class="label-primary">Tipo</label>
          <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
            <select id="tipo" name="tipo" class="select-primary">
              <?php foreach ($tipos as $chave => $rotulo): ?>
                <option value="<?= esc($chave) ?>" <?= $valor('tipo', 'denuncia') === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
              <?php endforeach; ?>
            </select>
            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
          </div>
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="origem" class="label-primary">Origem</label>
          <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
            <select id="origem" name="origem" class="select-primary">
              <?php foreach ($origens as $chave => $rotulo): ?>
                <option value="<?= esc($chave) ?>" <?= $valor('origem', 'presencial') === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
              <?php endforeach; ?>
            </select>
            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
          </div>
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="prioridade" class="label-primary">Prioridade</label>
          <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
            <select id="prioridade" name="prioridade" class="select-primary">
              <?php foreach ($prioridades as $chave => $rotulo): ?>
                <option value="<?= esc($chave) ?>" <?= $valor('prioridade', 'media') === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
              <?php endforeach; ?>
            </select>
            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
          </div>
        </div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="responsavel_id" class="label-primary">Responsável</label>
        <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
          <select id="responsavel_id" name="responsavel_id" class="select-primary">
            <?php foreach ($equipe as $u): ?>
              <?php $sel = $manifestacao !== null
                  ? ($manifestacao['responsavel_id'] ?? '') == $u['id']
                  : ((int) old('responsavel_id', $usuarioId) === (int) $u['id']); ?>
              <option value="<?= (int) $u['id'] ?>" <?= $sel ? 'selected' : '' ?>><?= esc($u['nome']) ?></option>
            <?php endforeach; ?>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
        </div>
        <p class="text-gray-400 text-[11.5px] leading-3.5 pl-0.5">
          <?= $editando ? 'O responsável atual é salvo no histórico da manifestação.' : 'Preenchido automaticamente com o usuário logado.' ?>
        </p>
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="busca_mulher" class="label-primary">Mulher vinculada <span class="text-gray-400 font-normal">(opcional)</span></label>
        <input type="hidden" name="mulher_id" id="mulher_id" value="<?= esc((string) ($mulherPrese['id'] ?? old('mulher_id', ''))) ?>">
        <div class="relative">
          <i data-lucide="search" class="input-icon"></i>
          <input type="text" id="busca_mulher" autocomplete="off" placeholder="Nome ou CPF da mulher..." class="input-primary has-icon"
                 value="<?= esc((string) ($mulherPrese['nome'] ?? '')) ?>" <?= ! empty($mulherPrese['nome']) ? 'readonly' : '' ?>>
        </div>
        <div id="resultado-mulheres" class="hidden w-full bg-white border border-gray-200 rounded-xl shadow-md p-1 flex flex-col gap-0.5"></div>
        <div id="mulher-selecionada" class="hidden"></div>
        <p class="text-gray-400 text-[11.5px] leading-3.5 pl-0.5">
          Busque uma mulher já cadastrada. Nenhum cadastro novo é criado nesta tela.
        </p>
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="descricao" class="label-primary">Relato da manifestação *</label>
        <textarea id="descricao" name="descricao" class="textarea-primary" rows="6"><?= esc($valor('descricao')) ?></textarea>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
        <div class="flex flex-col gap-1.5">
          <label for="local_relacionado" class="label-primary">Local relacionado</label>
          <input type="text" id="local_relacionado" name="local_relacionado" value="<?= esc($valor('local_relacionado')) ?>" placeholder="Onde ocorreu o fato, se souber..." class="input-primary">
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="observacoes" class="label-primary">Observações internas</label>
          <input type="text" id="observacoes" name="observacoes" value="<?= esc($valor('observacoes')) ?>" class="input-primary">
          <p class="text-gray-400 text-[11.5px] leading-3.5 pl-0.5">Anotações de uso interno da equipe.</p>
        </div>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2">
        <i data-lucide="check" class="w-4 h-4"></i>
        <?= $editando ? 'Salvar alterações' : 'Registrar manifestação' ?>
      </button>
      <a href="<?= $rootVoltar ?>" class="btn-secondary text-[13px] px-4 py-2">Cancelar</a>
    </div>
  </form>
</section>

<script>
(function () {
  var cbAnonimo = document.getElementById('anonimo');
  var camposPessoa = document.getElementById('campos-pessoa');

  function aplicarAnonimo() {
    if (!cbAnonimo || !camposPessoa) return;
    if (cbAnonimo.checked) {
      camposPessoa.classList.add('opacity-40', 'pointer-events-none');
    } else {
      camposPessoa.classList.remove('opacity-40', 'pointer-events-none');
    }
  }
  if (cbAnonimo) {
    cbAnonimo.addEventListener('change', aplicarAnonimo);
    aplicarAnonimo();
  }

  function selecionarMulher(id, nome) {
    if (!id || !nome) return;
    var mulherInput = document.getElementById('mulher_id');
    var busca = document.getElementById('busca_mulher');
    var sel = document.getElementById('mulher-selecionada');
    if (!mulherInput || !busca || !sel) return;
    mulherInput.value = String(id);
    busca.value = nome;
    busca.setAttribute('readonly', 'readonly');
    sel.innerHTML = '';
    var chip = document.createElement('div');
    chip.className = 'flex items-center gap-2 bg-blue-50 border border-blue-200 rounded-lg px-3 py-1.5 self-start';
    var txt = document.createElement('span');
    txt.className = 'text-gray-700 text-[13px] font-medium';
    txt.textContent = nome;
    chip.appendChild(txt);
    sel.appendChild(chip);
    sel.classList.remove('hidden');
  }

<?php if (! empty($mulherPrese)): ?>
  selecionarMulher('<?= (int) $mulherPrese['id'] ?>', '<?= esc($mulherPrese['nome'], 'js') ?>');
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
        fetch(opts.url + '?term=' + encodeURIComponent(termo), {
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

  if (window.lucide) window.lucide.createIcons();
})();
</script>
<?= $this->endSection() ?>