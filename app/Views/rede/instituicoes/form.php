<?= $this->extend('layouts/admin') ?>

<?php
$instituicao = $instituicao ?? null;
$servicosAtuais = $servicos ?? [];
$servicosPost = (array) old('servicos', []);
$servicosVal = [];
foreach ($servicosPost as $nome) {
    if (is_string($nome) && trim($nome) !== '') {
        $servicosVal[] = trim($nome);
    }
}
if (empty($servicosVal)) {
    $servicosVal = $servicosAtuais;
}
$valor = static function (string $campo) use ($instituicao) {
    if ($instituicao !== null) {
        return (string) ($instituicao[$campo] ?? '');
    }
    return (string) old($campo, '');
};
$estaAtiva = $instituicao === null ? 1 : (int) ($instituicao['ativo'] ?? 1);
?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex items-center justify-between">
    <div class="flex items-center gap-2">
      <a href="<?= base_url('rede/instituicoes') ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 transition" title="Voltar">
        <i data-lucide="arrow-left" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
      </a>
      <h1 class="text-gray-600 text-[15px] font-medium"><?= $instituicao === null ? 'Nova Instituição' : 'Editar Instituição' ?></h1>
    </div>
  </header>

  <form method="POST" action="<?= $instituicao === null ? base_url('rede/instituicoes') : base_url('rede/instituicoes/atualizar/' . (int) $instituicao['id']) ?>" class="flex flex-col gap-3">
    <!-- DADOS GERAIS -->
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-blue-50 border border-blue-200 flex items-center justify-center rounded-lg">
          <i data-lucide="building-2" class="w-4 h-4 stroke-blue-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Dados da instituição</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
        <div class="flex flex-col gap-1.5">
          <label for="nome" class="label-primary">Nome *</label>
          <input type="text" id="nome" name="nome" value="<?= esc($valor('nome')) ?>" placeholder="Ex.: CREAS Centro" class="input-primary">
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="tipo" class="label-primary">Tipo *</label>
          <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
            <select id="tipo" name="tipo" class="select-primary">
              <option value="">Selecione…</option>
              <?php foreach ($tipos as $chave => $rotulo): ?>
                <option value="<?= esc($chave) ?>" <?= $valor('tipo') === $chave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
              <?php endforeach; ?>
            </select>
            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
          </div>
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="endereco" class="label-primary">Endereço</label>
          <input type="text" id="endereco" name="endereco" value="<?= esc($valor('endereco')) ?>" placeholder="Rua, número, bairro" class="input-primary">
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="municipio" class="label-primary">Município</label>
          <input type="text" id="municipio" name="municipio" value="<?= esc($valor('municipio')) ?>" placeholder="Município" class="input-primary">
        </div>
      </div>
    </div>

    <!-- CONTATO -->
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-amber-50 border border-amber-200 flex items-center justify-center rounded-lg">
          <i data-lucide="phone" class="w-4 h-4 stroke-amber-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Contato</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
        <div class="flex flex-col gap-1.5">
          <label for="telefone" class="label-primary">Telefone</label>
          <input type="text" id="telefone" name="telefone" value="<?= esc($valor('telefone')) ?>" placeholder="(22) 0000-0000" class="input-primary">
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="whatsapp" class="label-primary">WhatsApp</label>
          <input type="text" id="whatsapp" name="whatsapp" value="<?= esc($valor('whatsapp')) ?>" placeholder="(22) 90000-0000" class="input-primary">
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="email" class="label-primary">E-mail</label>
          <input type="email" id="email" name="email" value="<?= esc($valor('email')) ?>" placeholder="contato@instituicao.gov.br" class="input-primary">
        </div>
      </div>
    </div>

    <!-- ATENDIMENTO -->
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-purple-50 border border-purple-200 flex items-center justify-center rounded-lg">
          <i data-lucide="user-check" class="w-4 h-4 stroke-purple-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Atendimento</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
        <div class="flex flex-col gap-1.5">
          <label for="responsavel" class="label-primary">Responsável</label>
          <input type="text" id="responsavel" name="responsavel" value="<?= esc($valor('responsavel')) ?>" placeholder="Nome do responsável" class="input-primary">
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="horario_atendimento" class="label-primary">Horário de atendimento</label>
          <input type="text" id="horario_atendimento" name="horario_atendimento" value="<?= esc($valor('horario_atendimento')) ?>" placeholder="Ex.: Segunda a sexta, 8h às 17h" class="input-primary">
        </div>
      </div>
    </div>

    <!-- SERVIÇOS -->
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-green-50 border border-green-200 flex items-center justify-center rounded-lg">
          <i data-lucide="list-plus" class="w-4 h-4 stroke-green-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Serviços oferecidos</p>
      </div>

      <div id="servicos-lista" class="flex flex-col gap-2">
        <?php foreach ($servicosVal as $nome): ?>
          <div class="servico-linha flex items-center gap-2">
            <div class="flex-1 relative">
              <input type="text" name="servicos[]" value="<?= esc((string) $nome) ?>" placeholder="Ex.: Atendimento social" class="input-primary">
            </div>
            <button type="button" class="remover-servico w-9 h-11 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:text-red-600 hover:bg-red-50 transition cursor-pointer" title="Remover serviço">
              <i data-lucide="trash-2" class="w-4 h-4"></i>
            </button>
          </div>
        <?php endforeach; ?>
      </div>

      <div>
        <button type="button" id="adicionar-servico" class="btn-secondary text-[13px] px-3 py-2">
          <i data-lucide="plus" class="w-4 h-4"></i>
          Adicionar serviço
        </button>
      </div>
    </div>

    <!-- OBSERVAÇÕES -->
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-gray-100 border border-gray-200 flex items-center justify-center rounded-lg">
          <i data-lucide="file-text" class="w-4 h-4 stroke-gray-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Observações</p>
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="observacoes" class="label-primary">Observações</label>
        <textarea id="observacoes" name="observacoes" class="textarea-primary" rows="3" placeholder="Informações complementares"><?= esc($valor('observacoes')) ?></textarea>
      </div>
    </div>

    <!-- SITUAÇÃO -->
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-red-50 border border-red-200 flex items-center justify-center rounded-lg">
          <i data-lucide="toggle-right" class="w-4 h-4 stroke-red-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Situação</p>
      </div>

      <div class="flex items-center gap-4">
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="radio" name="ativo" value="1" <?= $estaAtiva === 1 ? 'checked' : '' ?> class="accent-blue-600 w-4 h-4">
          <span class="text-gray-600 text-[13px]">Ativa</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="radio" name="ativo" value="0" <?= $estaAtiva === 0 ? 'checked' : '' ?> class="accent-blue-600 w-4 h-4">
          <span class="text-gray-600 text-[13px]">Inativa</span>
        </label>
      </div>
      <p class="text-gray-400 text-[11.5px] leading-3.5 pl-0.5 -mt-1">Instituições inativas não aparecem como opção para novos encaminhamentos, mas o histórico é preservado.</p>
    </div>

    <div class="flex items-center gap-2">
      <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2">
        <i data-lucide="check" class="w-4 h-4"></i>
        <?= $instituicao === null ? 'Cadastrar instituição' : 'Salvar alterações' ?>
      </button>
      <a href="<?= base_url('rede/instituicoes') ?>" class="btn-secondary text-[13px] px-4 py-2">Cancelar</a>
    </div>
  </form>
</section>

<script>
(function () {
  var lista = document.getElementById('servicos-lista');
  var botao = document.getElementById('adicionar-servico');
  if (!lista || !botao) return;

  function criarLinha(valor) {
    var linha = document.createElement('div');
    linha.className = 'servico-linha flex items-center gap-2';

    var wrapper = document.createElement('div');
    wrapper.className = 'flex-1';
    var input = document.createElement('input');
    input.type = 'text';
    input.name = 'servicos[]';
    input.value = valor || '';
    input.placeholder = 'Ex.: Atendimento social';
    input.className = 'input-primary';
    wrapper.appendChild(input);

    var remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'remover-servico w-9 h-11 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:text-red-600 hover:bg-red-50 transition cursor-pointer';
    remove.title = 'Remover serviço';
    remove.innerHTML = '<i data-lucide="trash-2" class="w-4 h-4"></i>';
    remove.addEventListener('click', function () {
      linha.remove();
      if (window.lucide) window.lucide.createIcons();
    });

    linha.appendChild(wrapper);
    linha.appendChild(remove);
    return linha;
  }

  if (lista.children.length === 0) {
    lista.appendChild(criarLinha(''));
  }

  lista.querySelectorAll('.remover-servico').forEach(function (remove) {
    remove.addEventListener('click', function () {
      remove.closest('.servico-linha').remove();
      if (window.lucide) window.lucide.createIcons();
    });
  });

  botao.addEventListener('click', function () {
    lista.appendChild(criarLinha(''));
    if (window.lucide) window.lucide.createIcons();
    var ultimo = lista.lastElementChild.querySelector('input');
    if (ultimo) ultimo.focus();
  });

  if (window.lucide) window.lucide.createIcons();
})();
</script>
<?= $this->endSection() ?>