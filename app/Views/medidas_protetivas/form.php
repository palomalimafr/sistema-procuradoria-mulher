<?= $this->extend('layouts/admin') ?>

<?php
$editando    = (bool) ($editando ?? false);
$medida      = $medida ?? null;
$mulherPre   = $mulherPreselecionada ?? null;
$atendimentoPre = $atendimentoPreselecionado ?? null;

$valor = static function (string $campo) use ($medida) {
    if ($medida !== null) {
        return (string) ($medida[$campo] ?? '');
    }

    return (string) old($campo, '');
};
$marcado     = static function (string $campo) use ($medida) {
    if ($medida !== null) {
        return ! empty($medida[$campo]);
    }

    return (bool) old($campo);
};

/**
 * Identidade dos vínculos na criação: o valor persists do POST inválido e,
 * não havendo POST, cai no vínculo já trazido pelo controller (?mulher_id= /
 * ?atendimento_id= vindo da ficha ou de outra tela).
 */
$vinculoId = static function (string $campo, ?array $pre) use ($medida): string {
    if ($medida !== null) {
        return (string) ($medida[$campo] ?? '');
    }

    $post = old($campo);

    return ($post !== null && $post !== '') ? (string) $post : (string) ($pre['id'] ?? '');
};

$buscaMulherValor = static function () use ($medida, $mulherPre): string {
    if ($medida !== null) {
        return '';
    }

    $post = old('busca_mulher');

    return ($post !== null && $post !== '') ? (string) $post : (string) ($mulherPre['nome'] ?? '');
};

$buscaAtendimentoValor = static function () use ($medida, $atendimentoPre): string {
    if ($medida !== null) {
        return '';
    }

    $post = old('busca_atendimento');

    return ($post !== null && $post !== '') ? (string) $post : (string) ($atendimentoPre !== null ? '#' . $atendimentoPre['protocolo'] : '');
};

$mulherIdValor        = $vinculoId('mulher_id', $mulherPre);
$atendimentoIdValor   = $vinculoId('atendimento_id', $atendimentoPre);
?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex items-center justify-between">
    <div class="flex items-center gap-2">
      <a href="<?= base_url($editando ? 'medidas-protetivas/' . (int) $medida['id'] : 'medidas-protetivas') ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 transition" title="Voltar">
        <i data-lucide="arrow-left" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
      </a>
      <h1 class="text-gray-600 text-[15px] font-medium"><?= esc($titulo) ?></h1>
    </div>
    <?php if (! $editando): ?>
      <a href="<?= base_url('medidas-protetivas') ?>" class="text-gray-500 text-[12px] hover:text-primary transition">
        Ver medidas protetivas registradas â€º
      </a>
    <?php endif; ?>
  </header>

  <form method="POST" action="<?= base_url($editando ? 'medidas-protetivas/atualizar/' . (int) $medida['id'] : 'medidas-protetivas') ?>" class="flex flex-col gap-3">
    <input type="hidden" name="mulher_id" id="mulher_id" value="<?= esc($mulherIdValor) ?>">
    <input type="hidden" name="atendimento_id" id="atendimento_id" value="<?= esc($atendimentoIdValor) ?>">

    <?php if (! $editando): ?>
      <?= $this->include('mulheres/_contexto', ['contexto' => $contexto ?? null]) ?>
    <?php endif; ?>

    <!-- VÍNCULOS -->
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-blue-50 border border-blue-200 flex items-center justify-center rounded-lg">
          <i data-lucide="link" class="w-4 h-4 stroke-blue-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Vínculos da medida</p>
      </div>

      <?php if ($editando): ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-2.5">
          <div class="flex flex-col gap-1.5">
            <label class="label-primary">Mulher titular</label>
            <div class="min-h-11 flex items-center px-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 text-[13px]">
              <?= esc($mulherPre['nome'] ?? '—') ?>
            </div>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="label-primary">Atendimento de origem</label>
            <div class="min-h-11 flex items-center gap-2 px-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 text-[13px]">
              <?php if ($atendimentoPre !== null): ?>
                <span class="font-mono font-medium">#<?= esc($atendimentoPre['protocolo']) ?></span>
                <span class="text-gray-400 text-[12px]">·</span>
                <span class="text-gray-500 text-[12px]"><?= esc($atendimentoPre['data_hora_formatted']) ?></span>
              <?php else: ?>
                <span class="text-gray-400">Não vinculado</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-2.5">
          <div class="flex flex-col gap-1.5">
            <label for="busca_mulher" class="label-primary">Buscar mulher *</label>
            <div class="relative">
              <i data-lucide="search" class="input-icon"></i>
              <input type="text" id="busca_mulher" autocomplete="off" placeholder="Nome, CPF ou telefone..." class="input-primary has-icon"
                     value="<?= esc($buscaMulherValor()) ?>">
            </div>
            <div id="resultado-mulheres" class="hidden w-full bg-white border border-gray-200 rounded-xl shadow-md p-1 flex flex-col gap-0.5"></div>
            <div id="mulher-selecionada"></div>
            <p class="text-gray-400 text-[11.5px] leading-3.5 pl-0.5">A medida é vinculada à titular de um atendimento já registrado na casa.</p>
          </div>

          <div class="flex flex-col gap-1.5">
            <label for="busca_atendimento" class="label-primary">Atendimento de origem *</label>
            <div class="relative">
              <i data-lucide="search" class="input-icon"></i>
              <input type="text" id="busca_atendimento" autocomplete="off" placeholder="Protocolo (AT-…), nome ou CPF..." class="input-primary has-icon"
                     value="<?= esc($buscaAtendimentoValor()) ?>">
            </div>
            <div id="resultado-atendimentos" class="hidden w-full bg-white border border-gray-200 rounded-xl shadow-md p-1 flex flex-col gap-0.5"></div>
            <div id="atendimento-selecionado"></div>
            <p id="dica-atendimento" class="text-gray-400 text-[11.5px] leading-3.5 pl-0.5">
              Selecione a mulher primeiro para listar os atendimentos dela.
            </p>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- MEDIDA -->
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-green-50 border border-green-200 flex items-center justify-center rounded-lg">
          <i data-lucide="shield-check" class="w-4 h-4 stroke-green-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Medida protetiva</p>
      </div>

      <div class="flex items-center gap-3">
        <label class="flex items-center gap-2 cursor-pointer select-none">
          <input type="checkbox" name="possui_medida" value="1" <?= $marcado('possui_medida') ? 'checked' : '' ?> class="w-4 h-4 accent-[#a179c3]">
          <span class="text-gray-600 text-[13px]">Possui medida vigente</span>
        </label>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-2.5">
        <div class="flex flex-col gap-1.5">
          <label for="numero" class="label-primary">Número</label>
          <input type="text" id="numero" name="numero" value="<?= esc($valor('numero')) ?>" placeholder="Nº da medida / protocolo" class="input-primary">
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="data_solicitacao" class="label-primary">Data de solicitação</label>
          <input type="date" id="data_solicitacao" name="data_solicitacao" value="<?= esc($valor('data_solicitacao') ?: date('Y-m-d')) ?>" class="input-primary">
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="data_concessao" class="label-primary">Data de concessão</label>
          <input type="date" id="data_concessao" name="data_concessao" value="<?= esc($valor('data_concessao')) ?>" class="input-primary">
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="orgao" class="label-primary">Órgão expedidor</label>
          <div class="relative">
            <select id="orgao" name="orgao" class="select-primary">
              <option value="">Selecione o órgão...</option>
              <?php foreach ($orgaos as $chave => $rotulo): ?>
                <option value="<?= esc($chave) ?>" <?= $valor('orgao') === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
              <?php endforeach; ?>
            </select>
            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
          </div>
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="processo" class="label-primary">Processo</label>
          <input type="text" id="processo" name="processo" value="<?= esc($valor('processo')) ?>" placeholder="Nº do processo judicial" class="input-primary">
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="data_validade" class="label-primary">Data de validade</label>
          <input type="date" id="data_validade" name="data_validade" value="<?= esc($valor('data_validade')) ?>" class="input-primary">
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-2.5">
        <div class="flex flex-col gap-1.5">
          <label for="situacao" class="label-primary">Situação</label>
          <div class="relative">
            <select id="situacao" name="situacao" class="select-primary">
              <?php foreach ($situacoes as $chave => $rotulo): ?>
                <option value="<?= esc($chave) ?>" <?= $valor('situacao') === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
              <?php endforeach; ?>
            </select>
            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
          </div>
        </div>

        <div class="flex flex-col gap-1.5 lg:col-span-2">
          <label for="observacoes" class="label-primary">Observações</label>
          <textarea id="observacoes" name="observacoes" class="textarea-primary" rows="2"><?= esc($valor('observacoes')) ?></textarea>
        </div>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2">
        <i data-lucide="check" class="w-4 h-4"></i>
        <?= $editando ? 'Salvar alterações' : 'Registrar medida protetiva' ?>
      </button>
      <a href="<?= base_url($editando ? 'medidas-protetivas/' . (int) $medida['id'] : 'medidas-protetivas') ?>" class="btn-secondary text-[13px] px-4 py-2">Cancelar</a>
    </div>
  </form>
</section>

<script>
(function () {
  var editando = <?= $editando ? 'true' : 'false' ?>;
  if (editando) {
    if (window.lucide) window.lucide.createIcons();
    return;
  }

  var URL_MULHERES    = '<?= base_url('atendimentos/buscar-mulheres') ?>';
  var URL_ATENDIMENTOS = '<?= base_url('atendimentos/buscar') ?>';

  var inMulher   = document.getElementById('busca_mulher');
  var resMulher  = document.getElementById('resultado-mulheres');
  var selMulher  = document.getElementById('mulher-selecionada');
  var hidMulher  = document.getElementById('mulher_id');

  var inAtend    = document.getElementById('busca_atendimento');
  var resAtend   = document.getElementById('resultado-atendimentos');
  var selAtend   = document.getElementById('atendimento-selecionado');
  var hidAtend   = document.getElementById('atendimento_id');
  var dicaAtend  = document.getElementById('dica-atendimento');

  if (!inMulher || !hidMulher || !inAtend || !hidAtend) return;

  var timerMulher = null;
  var timerAtend  = null;

  /**
   * Chip de seleção com botão "X" que libera o campo novamente.
   * O X existe justamente porque a mulher chega travada quando a ação é
   * iniciada pela ficha: sem ele, não haveria como corrigir a seleção.
   */
  function montarChip(container, icone, titulo, subtitulo, onLimpar) {
    var chip = document.createElement('div');
    chip.className = 'flex items-center gap-2 bg-blue-50 border border-blue-200 rounded-lg pl-3 pr-1.5 py-1.5 self-start';

    var i = document.createElement('i');
    i.setAttribute('data-lucide', icone);
    i.className = 'w-4 h-4 stroke-blue-600 stroke-[1.5] shrink-0';

    var txt = document.createElement('span');
    txt.className = 'flex flex-col min-w-0';
    var forte = document.createElement('strong');
    forte.className = 'text-gray-700 text-[13px] font-medium truncate';
    forte.textContent = titulo;
    txt.appendChild(forte);
    if (subtitulo) {
      var pequeno = document.createElement('small');
      pequeno.className = 'text-gray-500 text-[12px] truncate';
      pequeno.textContent = subtitulo;
      txt.appendChild(pequeno);
    }

    var limpar = document.createElement('button');
    limpar.type = 'button';
    limpar.title = 'Remover seleção';
    limpar.className = 'w-6 h-6 flex items-center justify-center rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 transition cursor-pointer';
    limpar.innerHTML = '<i data-lucide="x" class="w-3.5 h-3.5"></i>';
    limpar.addEventListener('click', onLimpar);

    chip.appendChild(i);
    chip.appendChild(txt);
    chip.appendChild(limpar);
    container.innerHTML = '';
    container.appendChild(chip);
    if (window.lucide) window.lucide.createIcons();
  }

  function travar(input, valor) {
    input.value = valor;
    input.setAttribute('readonly', 'readonly');
  }

  function liberar(input) {
    input.value = '';
    input.removeAttribute('readonly');
    input.focus();
  }

  function montarLinha(btn, dados, titulo, extras) {
    btn.type = 'button';
    btn.setAttribute('data-item-id', String(dados.id));
    btn.setAttribute('data-item-titulo', titulo);
    btn.setAttribute('data-item-extras', extras || '');
    btn.className = 'w-full flex items-start justify-between gap-2 px-3 py-2.5 rounded-lg text-left cursor-pointer transition hover:bg-gray-50';

    var info = document.createElement('span');
    info.className = 'flex flex-col gap-0.5 min-w-0';

    var nome = document.createElement('strong');
    nome.className = 'text-gray-700 text-[13px] font-medium truncate';
    nome.textContent = titulo;
    info.appendChild(nome);

    if (extras) {
      var extra = document.createElement('small');
      extra.className = 'text-gray-400 text-[12px] leading-4 truncate';
      extra.textContent = extras;
      info.appendChild(extra);
    }

    var seta = document.createElement('span');
    seta.className = 'w-4 h-4 flex items-center justify-center text-gray-300 shrink-0';
    seta.innerHTML = '<i data-lucide="chevron-right" class="w-4 h-4"></i>';

    btn.appendChild(info);
    btn.appendChild(seta);
    if (window.lucide) window.lucide.createIcons();
    return btn;
  }

  function mostrar(container, lista, vazioTexto, tituloDe, extrasDe) {
    container.innerHTML = '';
    if (!lista.length) {
      var vazio = document.createElement('p');
      vazio.className = 'px-3 py-2.5 text-gray-400 text-[13px]';
      vazio.textContent = vazioTexto;
      container.appendChild(vazio);
    } else {
      lista.forEach(function (item) {
        container.appendChild(montarLinha(document.createElement('button'), item, tituloDe(item), extrasDe(item)));
      });
    }
    container.classList.remove('hidden');
  }

  /* ---------- MULHER ---------- */

  function buscarMulheres(termo) {
    fetch(URL_MULHERES + '?term=' + encodeURIComponent(termo), {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var lista = data.mulheres || [];
        mostrar(
          resMulher,
          lista,
          'Nenhuma mulher encontrada com esse termo.',
          function (m) { return m.nome || ''; },
          function (m) {
            var partes = [];
            if (m.cpf) partes.push(m.cpf);
            if (m.bairro) partes.push(m.bairro);
            if (m.municipio) partes.push(m.municipio);
            return partes.join(' · ');
          }
        );
      })
      .catch(function () { resMulher.classList.add('hidden'); });
  }

  inMulher.addEventListener('input', function () {
    // Digitou de novo: a seleção travada foi desfeita manualmente.
    if (inMulher.hasAttribute('readonly')) return;
    var termo = this.value.trim();
    hidMulher.value = '';
    selMulher.innerHTML = '';
    limparAtendimento();
    if (!termo) { resMulher.classList.add('hidden'); return; }
    clearTimeout(timerMulher);
    timerMulher = setTimeout(function () { buscarMulheres(termo); }, 300);
  });

  /* ---------- ATENDIMENTO ---------- */

  function limparAtendimento() {
    hidAtend.value = '';
    selAtend.innerHTML = '';
    liberar(inAtend);
    resAtend.classList.add('hidden');
    atualizarDica();
  }

  function atualizarDica() {
    if (!dicaAtend) return;
    if (!hidMulher.value) {
      dicaAtend.textContent = 'Selecione a mulher primeiro para listar os atendimentos dela.';
    } else if (hidAtend.value) {
      dicaAtend.textContent = 'Atendimento vinculado à mulher selecionada.';
    } else {
      dicaAtend.textContent = 'Escolha o atendimento que originou a solicitação.';
    }
  }

  function buscarAtendimentos(termo) {
    if (!hidMulher.value) {
      mostrar(resAtend, [], 'Selecione a mulher primeiro.', function (a) { return a.protocolo; }, function () { return ''; });
      return;
    }
    fetch(URL_ATENDIMENTOS + '?term=' + encodeURIComponent(termo) + '&mulher_id=' + encodeURIComponent(hidMulher.value), {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        mostrar(
          resAtend,
          data.atendimentos || [],
          'Nenhum atendimento desta mulher encontrado com esse termo.',
          function (a) { return '#' + a.protocolo; },
          function (a) { return a.data_hora_formatted || ''; }
        );
      })
      .catch(function () { resAtend.classList.add('hidden'); });
  }

  inAtend.addEventListener('input', function () {
    if (inAtend.hasAttribute('readonly')) return;
    var termo = this.value.trim();
    hidAtend.value = '';
    selAtend.innerHTML = '';
    atualizarDica();
    if (!termo) { resAtend.classList.add('hidden'); return; }
    clearTimeout(timerAtend);
    timerAtend = setTimeout(function () { buscarAtendimentos(termo); }, 300);
  });

  /* ---------- CLIQUES ---------- */

  document.addEventListener('click', function (e) {
    var alvo = e.target.closest('[data-item-id]');
    if (alvo && (resMulher.contains(alvo) || resAtend.contains(alvo))) {
      var id       = alvo.getAttribute('data-item-id');
      var titulo   = alvo.getAttribute('data-item-titulo');
      var extras   = alvo.getAttribute('data-item-extras') || '';

      if (resMulher.contains(alvo)) {
        hidMulher.value = id;
        travar(inMulher, titulo);
        montarChip(selMulher, 'user', titulo, extras, function () {
          hidMulher.value = '';
          selMulher.innerHTML = '';
          liberar(inMulher);
          limparAtendimento();
        });
        resMulher.classList.add('hidden');
        // A mulher mudou: o atendimento anterior deixa de valer.
        limparAtendimento();
        inAtend.focus();
      } else {
        hidAtend.value = id;
        travar(inAtend, titulo);
        montarChip(selAtend, 'briefcase', titulo, extras, function () {
          hidAtend.value = '';
          selAtend.innerHTML = '';
          liberar(inAtend);
          atualizarDica();
        });
        resAtend.classList.add('hidden');
      }

      atualizarDica();
      return;
    }

    if (!e.target.closest('#resultado-mulheres') && !e.target.closest('#busca_mulher')) {
      resMulher.classList.add('hidden');
    }
    if (!e.target.closest('#resultado-atendimentos') && !e.target.closest('#busca_atendimento')) {
      resAtend.classList.add('hidden');
    }
  });

  /* ---------- ESTADO INICIAL (vínculo vindo da ficha) ---------- */

  <?php if ($mulherPre !== null && $mulherIdValor !== ''): ?>
    (function iniciarMulher() {
      var extras = [];
      <?php if (! empty($mulherPre['cpf'])): ?>extras.push('<?= esc($mulherPre['cpf']) ?>');<?php endif; ?>
      <?php if (! empty($mulherPre['bairro'])): ?>extras.push('<?= esc($mulherPre['bairro']) ?>');<?php endif; ?>
      <?php if (! empty($mulherPre['municipio'])): ?>extras.push('<?= esc($mulherPre['municipio']) ?>');<?php endif; ?>
      montarChip(selMulher, 'user', '<?= esc($mulherPre['nome']) ?>', extras.join(' · '), function () {
        hidMulher.value = '';
        selMulher.innerHTML = '';
        liberar(inMulher);
        limparAtendimento();
      });
      travar(inMulher, '<?= esc($mulherPre['nome']) ?>');
    })();
  <?php endif; ?>

  <?php if ($atendimentoPre !== null && $atendimentoIdValor !== ''): ?>
    (function iniciarAtendimento() {
      montarChip(selAtend, 'briefcase', '#<?= esc($atendimentoPre['protocolo']) ?>', '<?= esc($atendimentoPre['data_hora_formatted']) ?>', function () {
        hidAtend.value = '';
        selAtend.innerHTML = '';
        liberar(inAtend);
        atualizarDica();
      });
      travar(inAtend, '#<?= esc($atendimentoPre['protocolo']) ?>');
    })();
  <?php endif; ?>

  atualizarDica();

  if (window.lucide) window.lucide.createIcons();
})();
</script>
<?= $this->endSection() ?>
