<?= $this->extend('layouts/admin') ?>

<?php $a = $acolhimento; ?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex items-center gap-2">
    <a href="<?= base_url('acolhimentos/' . (int) $a['id']) ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 transition" title="Voltar">
      <i data-lucide="arrow-left" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
    </a>
    <h1 class="text-gray-600 text-[15px] font-medium">Avaliação de risco</h1>
  </header>

  <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 bg-blue-50 border border-blue-200 flex items-center justify-center rounded-lg">
        <i data-lucide="heart-handshake" class="w-4 h-4 stroke-blue-600 stroke-[1.5]"></i>
      </div>
      <p class="text-gray-600 text-[13px] font-medium leading-4">Acolhimento vinculado</p>
    </div>

    <div class="flex flex-col divide-y divide-gray-100">
      <div class="flex justify-between py-2">
        <span class="text-gray-400 text-[12px]">Acolhimento</span>
        <span class="text-gray-600 text-[12.5px]">#<?= (int) $a['id'] ?></span>
      </div>
      <div class="flex justify-between py-2">
        <span class="text-gray-400 text-[12px]">Atendimento</span>
        <a href="<?= base_url('atendimentos/' . (int) $a['atendimento_id']) ?>" class="text-primary text-[12.5px] hover:underline font-mono">#<?= esc($a['atendimento_protocolo']) ?></a>
      </div>
      <div class="flex justify-between py-2">
        <span class="text-gray-400 text-[12px]">Mulher</span>
        <span class="text-gray-600 text-[12.5px]"><?= esc($a['mulher_nome']) ?></span>
      </div>
      <div class="flex justify-between py-2">
        <span class="text-gray-400 text-[12px]">Acolhido em</span>
        <span class="text-gray-600 text-[12.5px]"><?= date('d/m/Y H:i', strtotime($a['data_hora'])) ?></span>
      </div>
    </div>
  </div>

  <form method="POST" action="<?= base_url('avaliacoes') ?>" class="flex flex-col gap-3">
    <input type="hidden" name="acolhimento_id" value="<?= (int) $a['id'] ?>">

    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-red-50 border border-red-200 flex items-center justify-center rounded-lg">
          <i data-lucide="shield-alert" class="w-4 h-4 stroke-red-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Questionário</p>
        <p class="text-gray-400 text-[12px] ml-auto">Profissional: <?= esc($usuarioNome) ?></p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-2">
        <?php foreach ($perguntas as $campo => $pergunta): ?>
          <label class="flex items-start gap-2 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 cursor-pointer select-none hover:border-gray-300 transition">
            <input type="checkbox" name="<?= esc($campo) ?>" id="<?= esc($campo) ?>" value="1" <?= old($campo) ? 'checked' : '' ?> class="w-4 h-4 mt-0.5 accent-[#a179c3]">
            <span class="text-gray-600 text-[13px] leading-4"><?= esc($pergunta) ?></span>
          </label>
        <?php endforeach; ?>
      </div>

      <div id="sugestao-area" class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-[13px] text-blue-800 hidden">
        <strong>Sugestão automática:</strong> <span id="sugestao-label">—</span>
        <span class="text-blue-600">(apenas um auxílio — confirme ou ajuste abaixo conforme sua avaliação profissional)</span>
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="observacoes_profissionais" class="label-primary">Observações profissionais</label>
        <textarea id="observacoes_profissionais" name="observacoes_profissionais" class="textarea-primary" rows="4"><?= esc((string) old('observacoes_profissionais')) ?></textarea>
      </div>
    </div>

    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-purple-50 border border-purple-200 flex items-center justify-center rounded-lg">
          <i data-lucide="gauge" class="w-4 h-4 stroke-purple-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Classificação de risco (profissional) *</p>
      </div>

      <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
        <?php foreach ($riscos as $chave => $rotulo): ?>
          <label class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg px-3 py-3 cursor-pointer select-none hover:border-gray-300 transition">
            <input type="radio" name="classificacao" value="<?= esc($chave) ?>" class="w-4 h-4 accent-[#a179c3]">
            <span class="text-gray-600 text-[13px] font-medium"><?= esc($rotulo) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2">
        <i data-lucide="check" class="w-4 h-4"></i>
        Registrar avaliação
      </button>
      <a href="<?= base_url('acolhimentos/' . (int) $a['id']) ?>" class="btn-secondary text-[13px] px-4 py-2">Cancelar</a>
    </div>
  </form>
</section>

<script>
(function () {
  var RISCOS = <?= json_encode($riscos) ?>;
  var PERGUNTAS = <?= json_encode(array_keys($perguntas)) ?>;
  var criticos = ['ameaca_morte', 'estrangulamento', 'agressor_arma', 'ameaca_com_arma', 'descumpriu_medida'];
  var moderados = ['agressao_fisica', 'violencia_aumentando', 'tentou_separar', 'controlador', 'perseguicao'];

  function marcado(campo) {
    var el = document.getElementById(campo);
    return el ? el.checked : false;
  }

  function sugestao() {
    for (var i = 0; i < criticos.length; i++) {
      if (marcado(criticos[i])) return 'extremo';
    }
    var n = 0;
    for (var j = 0; j < moderados.length; j++) {
      if (marcado(moderados[j])) n++;
    }
    if (n >= 3) return 'alto';
    if (n >= 2) return 'medio';
    if (n === 1 && !marcado('local_seguro')) return 'medio';
    return 'baixo';
  }

  var area = document.getElementById('sugestao-area');
  var label = document.getElementById('sugestao-label');
  var radios = document.querySelectorAll('input[name=classificacao]');

  function atualizar() {
    var s = sugestao();
    label.textContent = RISCOS[s] || s;
    area.classList.remove('hidden');
    var jaEscolhido = false;
    radios.forEach(function (r) { if (r.checked) jaEscolhido = true; });
    if (!jaEscolhido) {
      radios.forEach(function (r) {
        if (r.value === s) r.checked = true;
      });
    }
  }

  PERGUNTAS.forEach(function (campo) {
    var el = document.getElementById(campo);
    if (el) el.addEventListener('change', atualizar);
  });

  if (Object.keys(RISCOS).length) atualizar();
})();
</script>
<?= $this->endSection() ?>