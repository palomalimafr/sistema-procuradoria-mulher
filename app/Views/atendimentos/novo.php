<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex items-center gap-2">
    <a href="<?= base_url('atendimentos/fila') ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 transition" title="Voltar">
      <i data-lucide="arrow-left" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
    </a>
    <h1 class="text-gray-600 text-[15px] font-medium">Novo Atendimento</h1>
  </header>

  <form method="POST" action="<?= base_url('atendimentos') ?>" class="flex flex-col gap-3">
    <input type="hidden" name="mulher_id" id="mulher_id" value="<?= esc((string) old('mulher_id', (string) ($mulherPreselecionada['id'] ?? ''))) ?>">
    <input type="hidden" name="mulher_nome_exibicao" id="mulher_nome_exibicao" value="<?= esc((string) old('mulher_nome_exibicao', (string) ($mulherPreselecionada['nome'] ?? ''))) ?>">
    <input type="hidden" name="pedido_sos_id" id="pedido_sos_id" value="<?= esc((string) old('pedido_sos_id', (string) ($pedidoSos['id'] ?? ''))) ?>">

    <?php if (! empty($pedidoSos)): ?>
      <div class="w-full p-3 bg-red-50 border border-red-200 rounded-xl flex items-center gap-2">
        <i data-lucide="siren" class="w-4 h-4 stroke-red-600 stroke-[1.5] shrink-0"></i>
        <p class="text-red-700 text-[12.5px] leading-4">
          Atendimento originado do <strong>Pedido SOS #<?= (int) $pedidoSos['id'] ?></strong><?= ! empty($pedidoSos['data_hora']) ? ' de ' . date('d/m/Y H:i', strtotime($pedidoSos['data_hora'])) : '' ?>.
          A mulher foi preenchida automaticamente; complete os demais dados e salve para vincular o atendimento ao SOS.
        </p>
      </div>
    <?php endif; ?>

    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-blue-50 border border-blue-200 flex items-center justify-center rounded-lg">
          <i data-lucide="user" class="w-4 h-4 stroke-blue-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Mulher atendida</p>
      </div>

      <div class="flex flex-col gap-1.5">
        <label class="label-primary">Buscar mulher cadastrada *</label>
        <div class="relative">
          <i data-lucide="search" class="input-icon"></i>
          <input type="text" id="busca_mulher" autocomplete="off" placeholder="Nome, CPF, telefone ou e-mail..." class="input-primary has-icon">
        </div>
        <div id="resultado-busca" class="hidden w-full bg-white border border-gray-200 rounded-xl shadow-md p-1 flex flex-col gap-0.5"></div>
        <div id="mulher-selecionada" class="hidden"></div>
        <p class="text-gray-400 text-[12px] leading-4">
          Somente mulheres já cadastradas. Não encontrou? Use o cadastro existente em
          <a href="<?= base_url('mulheres/cadastro') ?>" class="text-primary hover:underline">Mulheres › Cadastro</a> e retorne a esta tela.
        </p>
      </div>
    </div>

    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-gray-100 border border-gray-200 flex items-center justify-center rounded-lg">
          <i data-lucide="briefcase" class="w-4 h-4 stroke-gray-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Dados do atendimento</p>
      </div>

      <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
        <div class="flex flex-col gap-1.5">
          <label for="protocolo_previsto" class="label-primary">Número / protocolo</label>
          <div class="relative">
            <input type="text" id="protocolo_previsto" value="<?= esc($protocoloPrevisto) ?>" readonly disabled class="input-primary text-gray-400">
            <span class="sr-only">Protocolo gerado automaticamente</span>
          </div>
          <p class="text-gray-400 text-[11.5px] leading-3.5 pl-0.5 -mt-1">Gerado automaticamente.</p>
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="data_hora" class="label-primary">Data e hora *</label>
          <input type="datetime-local" id="data_hora" name="data_hora" value="<?= esc((string) old('data_hora', date('Y-m-d\TH:i'))) ?>" class="input-primary">
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="profissional_id" class="label-primary">Profissional responsável *</label>
          <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
            <select id="profissional_id" name="profissional_id" class="select-primary">
              <?php foreach ($equipe as $u): ?>
                <option value="<?= (int) $u['id'] ?>" <?= ((int) old('profissional_id', session()->get('usuario_id'))) === (int) $u['id'] ? 'selected' : '' ?>><?= esc($u['nome']) ?> (<?= $u['perfil'] === 'admin' ? 'Administrador' : 'Funcionário' ?>)</option>
              <?php endforeach; ?>
            </select>
            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
          </div>
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="unidade_setor" class="label-primary">Unidade / setor</label>
          <input type="text" id="unidade_setor" name="unidade_setor" value="<?= esc((string) old('unidade_setor')) ?>" placeholder="Ex.: Procuradoria / Plantão" class="input-primary">
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="forma_atendimento" class="label-primary">Forma de atendimento *</label>
          <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
            <select id="forma_atendimento" name="forma_atendimento" class="select-primary">
              <?php foreach ($formas as $chave => $rotulo): ?>
                <option value="<?= esc($chave) ?>" <?= old('forma_atendimento', 'presencial') === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
              <?php endforeach; ?>
            </select>
            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
          </div>
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="origem" class="label-primary">Origem *</label>
          <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
            <select id="origem" name="origem" class="select-primary">
              <?php foreach ($origens as $chave => $rotulo): ?>
                <option value="<?= esc($chave) ?>" <?= old('origem', 'demanda_espontanea') === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
              <?php endforeach; ?>
            </select>
            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
          </div>
        </div>

        <div id="campo_origem_outra" class="flex flex-col gap-1.5 hidden">
          <label for="origem_outra" class="label-primary">Qual origem? *</label>
          <input type="text" id="origem_outra" name="origem_outra" value="<?= esc((string) old('origem_outra')) ?>" maxlength="150" class="input-primary">
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="motivo_atendimento" class="label-primary">Motivo do atendimento</label>
          <input type="text" id="motivo_atendimento" name="motivo_atendimento" value="<?= esc((string) old('motivo_atendimento')) ?>" maxlength="200" placeholder="Ex.: Pedido de orientação" class="input-primary">
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="classificacao" class="label-primary">Classificação</label>
          <input type="text" id="classificacao" name="classificacao" value="<?= esc((string) old('classificacao')) ?>" maxlength="100" class="input-primary">
          <p class="text-gray-400 text-[11.5px] leading-3.5 pl-0.5 -mt-1">Pode ser complementada na avaliação do caso.</p>
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="prioridade" class="label-primary">Prioridade *</label>
          <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
            <select id="prioridade" name="prioridade" class="select-primary">
              <?php foreach ($prioridades as $chave => $rotulo): ?>
                <option value="<?= esc($chave) ?>" <?= old('prioridade', 'media') === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
              <?php endforeach; ?>
            </select>
            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
          </div>
          <p id="ajuda_prioridade" class="text-gray-400 text-[11.5px] leading-3.5 pl-0.5 -mt-1"></p>
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="situacao" class="label-primary">Situação</label>
          <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
            <select id="situacao" name="situacao" disabled class="select-primary">
              <option value="novo" selected>Novo</option>
            </select>
            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
          </div>
          <p class="text-gray-400 text-[11.5px] leading-3.5 pl-0.5 -mt-1">Inicia como "Novo" e entra na fila.</p>
        </div>

        <div class="flex flex-col gap-1.5 col-span-2 lg:col-span-3">
          <label for="descricao_caso" class="label-primary">Descrição do caso</label>
          <textarea id="descricao_caso" name="descricao_caso" class="textarea-primary" rows="4"><?= esc((string) old('descricao_caso')) ?></textarea>
        </div>

        <div class="flex flex-col gap-1.5 col-span-2 lg:col-span-3">
          <label for="observacoes" class="label-primary">Observações</label>
          <textarea id="observacoes" name="observacoes" class="textarea-primary" rows="3"><?= esc((string) old('observacoes')) ?></textarea>
        </div>
      </div>

      <div class="flex items-center gap-2 pt-4 mt-1 border-t border-gray-100">
        <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2">
          <i data-lucide="check" class="w-4 h-4"></i>
          Abrir atendimento
        </button>
        <a href="<?= base_url('atendimentos/fila') ?>" class="btn-secondary text-[13px] px-4 py-2">Cancelar</a>
      </div>
    </div>
  </form>
</section>

<script>
(function () {
  var baseURL = '<?= base_url('atendimentos/buscar-mulheres') ?>';
  var input = document.getElementById('busca_mulher');
  var hidden = document.getElementById('mulher_id');
  var hiddenNome = document.getElementById('mulher_nome_exibicao');
  var resultados = document.getElementById('resultado-busca');
  var selecionado = document.getElementById('mulher-selecionada');
  var timer = null;

  var AJUDA = {
    emergencia: 'Emergência: risco iminente à vida, violência em andamento, ameaça grave ou necessidade de intervenção imediata.',
    alta: 'Alta: violência recente, ameaças, medidas protetivas ou situação de risco elevado.',
    media: 'Média: demandas que não configuram urgência imediata.',
    baixa: 'Baixa: casos de esclarecimento e orientação, sem risco aparente.'
  };

  function montarLinha(m) {
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.setAttribute('data-mulher-id', String(m.id));
    btn.setAttribute('data-mulher-nome', m.nome);
    btn.className = 'w-full flex items-start justify-between gap-2 px-3 py-2.5 rounded-lg text-left cursor-pointer transition hover:bg-gray-50';

    var info = document.createElement('span');
    info.className = 'flex flex-col gap-0.5 min-w-0';

    var nome = document.createElement('strong');
    nome.className = 'text-gray-700 text-[13px] leading-4 truncate';
    nome.textContent = m.nome;
    info.appendChild(nome);

    var extras = [];
    if (m.cpf) extras.push('CPF ' + m.cpf);
    if (m.data_nascimento) extras.push('Nasc. ' + formatarData(m.data_nascimento));
    if (m.bairro) extras.push(m.bairro);
    if (m.municipio && m.municipio !== m.bairro) extras.push(m.municipio);
    if (extras.length) {
      var det = document.createElement('small');
      det.className = 'text-gray-400 text-[12px] leading-4 truncate';
      det.textContent = extras.join(' · ');
      info.appendChild(det);
    }

    var seta = document.createElement('span');
    seta.className = 'w-4 h-4 flex items-center justify-center text-gray-300 shrink-0';
    seta.innerHTML = '<i data-lucide="chevron-right" class="w-4 h-4"></i>';

    btn.appendChild(info);
    btn.appendChild(seta);
    if (window.lucide) window.lucide.createIcons();
    return btn;
  }

  function formatarData(data) {
    var partes = String(data).split('-');
    return partes.length === 3 ? partes[2] + '/' + partes[1] + '/' + partes[0] : data;
  }

  function mostrarResultados(lista) {
    resultados.innerHTML = '';
    if (!lista.length) {
      var vazio = document.createElement('p');
      vazio.className = 'px-3 py-2.5 text-gray-400 text-[13px]';
      vazio.textContent = 'Nenhuma mulher encontrada com esse termo.';
      resultados.appendChild(vazio);
      resultados.classList.remove('hidden');
      return;
    }
    lista.forEach(function (m) {
      resultados.appendChild(montarLinha(m));
    });
    resultados.classList.remove('hidden');
  }

  function buscar(termo) {
    fetch(baseURL + '?term=' + encodeURIComponent(termo), {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.json(); })
      .then(function (data) { mostrarResultados(data.mulheres || []); })
      .catch(function () {
        resultados.textContent = 'Erro ao buscar. Tente novamente.';
        resultados.classList.remove('hidden');
      });
  }

  input.addEventListener('input', function () {
    var termo = this.value.trim();
    if (!termo) {
      resultados.classList.add('hidden');
      return;
    }
    clearTimeout(timer);
    timer = setTimeout(function () { buscar(termo); }, 300);
  });

  input.addEventListener('focus', function () {
    if (this.value.trim() && hidden.value === '') {
      var ul = resultados.querySelectorAll('[data-mulher-id]');
      if (ul.length) resultados.classList.remove('hidden');
    }
  });

  function renderSelecionada(nome) {
    var chip = document.createElement('div');
    chip.className = 'flex items-center gap-2 bg-blue-50 border border-blue-200 rounded-lg pl-3 pr-1.5 py-1.5 self-start';

    var icone = document.createElement('i');
    icone.setAttribute('data-lucide', 'user-check');
    icone.className = 'w-4 h-4 stroke-blue-600 stroke-[1.5] shrink-0';

    var txt = document.createElement('span');
    txt.className = 'text-gray-700 text-[13px] font-medium';
    txt.textContent = nome;

    var limpar = document.createElement('button');
    limpar.type = 'button';
    limpar.title = 'Remover seleção';
    limpar.className = 'w-6 h-6 flex items-center justify-center rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 transition cursor-pointer';
    limpar.innerHTML = '<i data-lucide="x" class="w-3.5 h-3.5"></i>';
    limpar.addEventListener('click', function () {
      hidden.value = '';
      hiddenNome.value = '';
      input.value = '';
      input.removeAttribute('readonly');
      selecionado.classList.add('hidden');
      selecionado.innerHTML = '';
      input.focus();
    });

    chip.appendChild(icone);
    chip.appendChild(txt);
    chip.appendChild(limpar);

    selecionado.innerHTML = '';
    selecionado.appendChild(chip);
    selecionado.classList.remove('hidden');
    if (window.lucide) window.lucide.createIcons();
  }

  document.addEventListener('click', function (e) {
    var alvo = e.target.closest('[data-mulher-id]');
    if (alvo) {
      hidden.value = alvo.getAttribute('data-mulher-id');
      hiddenNome.value = alvo.getAttribute('data-mulher-nome');
      input.value = alvo.getAttribute('data-mulher-nome');
      input.setAttribute('readonly', 'readonly');
      renderSelecionada(alvo.getAttribute('data-mulher-nome'));
      resultados.classList.add('hidden');
      return;
    }
    if (!e.target.closest('#resultado-busca') && !e.target.closest('#busca_mulher')) {
      resultados.classList.add('hidden');
    }
  });

  if (hidden.value && hiddenNome.value) {
    input.value = hiddenNome.value;
    input.setAttribute('readonly', 'readonly');
    renderSelecionada(hiddenNome.value);
  }

  var origem = document.getElementById('origem');
  function alternarOrigemOutra() {
    var campo = document.getElementById('campo_origem_outra');
    campo.classList.toggle('hidden', origem.value !== 'outros');
  }
  origem.addEventListener('change', alternarOrigemOutra);
  alternarOrigemOutra();

  var prioridade = document.getElementById('prioridade');
  var ajuda = document.getElementById('ajuda_prioridade');
  function atualizarAjudaPrioridade() {
    ajuda.textContent = AJUDA[prioridade.value] || '';
  }
  prioridade.addEventListener('change', atualizarAjudaPrioridade);
  atualizarAjudaPrioridade();
})();
</script>
<?= $this->endSection() ?>