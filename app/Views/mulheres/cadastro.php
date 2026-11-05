<?= $this->extend('layouts/admin') ?>

<?php $m = $mulher; ?>

<?= $this->section('content') ?>

<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex items-center gap-2">
    <a href="<?= base_url(! empty($m['id']) ? 'mulheres/detalhes/' . (int) $m['id'] : 'mulheres/cadastro') ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 transition" title="Voltar">
      <i data-lucide="arrow-left" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
    </a>
    <h1 class="text-gray-600 text-[15px] font-medium"><?= esc($titulo) ?></h1>
  </header>

  <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl">
    <form action="<?= base_url(! empty($m['id']) ? 'mulheres/atualizar/' . (int) $m['id'] : 'mulheres/cadastro/salvar') ?>" method="POST">
      <?= csrf_field() ?>

      <!-- Dados pessoais -->
      <h2 class="text-[13px] font-medium text-gray-700 mb-3 flex items-center gap-1.5">
        <i data-lucide="user" class="w-4 h-4 stroke-gray-400"></i> Dados pessoais
      </h2>
      <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
        <div class="flex flex-col gap-1.5">
          <label for="nome_completo" class="label-primary">Nome completo *</label>
          <input type="text" id="nome_completo" name="nome_completo" value="<?= esc(old('nome_completo', $m['nome_completo'] ?? '')) ?>" required class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="nome_social" class="label-primary">Nome social</label>
          <input type="text" id="nome_social" name="nome_social" value="<?= esc(old('nome_social', $m['nome_social'] ?? '')) ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="cpf" class="label-primary">CPF</label>
          <input type="text" id="cpf" name="cpf" value="<?= esc(old('cpf', $m['cpf'] ?? '')) ?>" class="input-primary" placeholder="000.000.000-00" data-mascara="cpf">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="rg" class="label-primary">RG</label>
          <input type="text" id="rg" name="rg" value="<?= esc(old('rg', $m['rg'] ?? '')) ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="data_nascimento" class="label-primary">Data de nascimento</label>
          <input type="date" id="data_nascimento" name="data_nascimento" value="<?= esc(old('data_nascimento', $m['data_nascimento'] ?? '')) ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="label-primary">Idade</label>
          <div class="h-11 flex items-center px-3 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-500">
            <span id="idade_calculada">—</span>
          </div>
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="sexo" class="label-primary">Sexo</label>
          <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
            <select id="sexo" name="sexo" class="select-primary">
              <option value="">Selecione</option>
              <?php foreach ($sexos as $valor => $rotulo): ?>
                <option value="<?= $valor ?>" <?= old('sexo', $m['sexo'] ?? '') === $valor ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
              <?php endforeach; ?>
            </select>
            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
          </div>
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="estado_civil" class="label-primary">Estado civil</label>
          <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
            <select id="estado_civil" name="estado_civil" class="select-primary">
              <option value="">Selecione</option>
              <?php foreach ($estadosCivis as $valor => $rotulo): ?>
                <option value="<?= $valor ?>" <?= old('estado_civil', $m['estado_civil'] ?? '') === $valor ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
              <?php endforeach; ?>
            </select>
            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
          </div>
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="escolaridade" class="label-primary">Escolaridade</label>
          <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
            <select id="escolaridade" name="escolaridade" class="select-primary">
              <option value="">Selecione</option>
              <?php foreach ($escolaridades as $valor => $rotulo): ?>
                <option value="<?= $valor ?>" <?= old('escolaridade', $m['escolaridade'] ?? '') === $valor ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
              <?php endforeach; ?>
            </select>
            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
          </div>
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="profissao" class="label-primary">Profissão</label>
          <input type="text" id="profissao" name="profissao" value="<?= esc(old('profissao', $m['profissao'] ?? '')) ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="ocupacao" class="label-primary">Ocupação</label>
          <input type="text" id="ocupacao" name="ocupacao" value="<?= esc(old('ocupacao', $m['ocupacao'] ?? '')) ?>" class="input-primary">
        </div>
      </div>

      <!-- Contato -->
      <h2 class="text-[13px] font-medium text-gray-700 mb-3 mt-5 flex items-center gap-1.5">
        <i data-lucide="phone" class="w-4 h-4 stroke-gray-400"></i> Contato
      </h2>
      <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
        <div class="flex flex-col gap-1.5">
          <label for="telefone" class="label-primary">Telefone</label>
          <input type="text" id="telefone" name="telefone" value="<?= esc(old('telefone', $m['telefone'] ?? '')) ?>" class="input-primary" placeholder="(00) 0000-0000" data-mascara="telefone_fixo">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="whatsapp" class="label-primary">WhatsApp</label>
          <input type="text" id="whatsapp" name="whatsapp" value="<?= esc(old('whatsapp', $m['whatsapp'] ?? '')) ?>" class="input-primary" placeholder="(00) 00000-0000" data-mascara="telefone_celular">
        </div>
        <div class="flex flex-col gap-1.5 col-span-2 lg:col-span-1">
          <label for="email" class="label-primary">E-mail *</label>
          <input type="email" id="email" name="email" value="<?= esc(old('email', $m['email'] ?? '')) ?>" class="input-primary" required>
          <p class="text-[11px] text-gray-500"><?= empty($m['id'])
              ? 'Obrigatório: enviaremos o convite para a mulher criar a senha de acesso.'
              : 'Em branco mantém o e-mail já cadastrado. Alterar invalida o convite pendente.' ?></p>
        </div>
      </div>

      <!-- Endereço -->
      <h2 class="text-[13px] font-medium text-gray-700 mb-3 mt-5 flex items-center gap-1.5">
        <i data-lucide="map-pin" class="w-4 h-4 stroke-gray-400"></i> Endereço
      </h2>
      <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
        <div class="flex flex-col gap-1.5">
          <label for="cep" class="label-primary">CEP</label>
          <input type="text" id="cep" name="cep" value="<?= esc(old('cep', $m['cep'] ?? '')) ?>" class="input-primary" placeholder="00000-000" data-mascara="cep">
        </div>
        <div class="flex flex-col gap-1.5 lg:col-span-2">
          <label for="logradouro" class="label-primary">Logradouro</label>
          <input type="text" id="logradouro" name="logradouro" value="<?= esc(old('logradouro', $m['logradouro'] ?? '')) ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="numero" class="label-primary">Número</label>
          <input type="text" id="numero" name="numero" value="<?= esc(old('numero', $m['numero'] ?? '')) ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="complemento" class="label-primary">Complemento</label>
          <input type="text" id="complemento" name="complemento" value="<?= esc(old('complemento', $m['complemento'] ?? '')) ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="bairro" class="label-primary">Bairro</label>
          <input type="text" id="bairro" name="bairro" value="<?= esc(old('bairro', $m['bairro'] ?? '')) ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="municipio" class="label-primary">Município</label>
          <input type="text" id="municipio" name="municipio" value="<?= esc(old('municipio', $m['municipio'] ?? '')) ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="estado" class="label-primary">Estado</label>
          <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
            <select id="estado" name="estado" class="select-primary">
              <option value="">UF</option>
              <?php foreach ($ufs as $uf): ?>
                <option value="<?= $uf ?>" <?= strtoupper((string) old('estado', $m['estado'] ?? '')) === $uf ? 'selected' : '' ?>><?= $uf ?></option>
              <?php endforeach; ?>
            </select>
            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
          </div>
        </div>
        <div class="flex flex-col gap-1.5 lg:col-span-2">
          <label for="ponto_referencia" class="label-primary">Ponto de referência</label>
          <input type="text" id="ponto_referencia" name="ponto_referencia" value="<?= esc(old('ponto_referencia', $m['ponto_referencia'] ?? '')) ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="zona" class="label-primary">Zona</label>
          <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
            <select id="zona" name="zona" class="select-primary">
              <option value="">Selecione</option>
              <?php foreach ($zonas as $valor => $rotulo): ?>
                <option value="<?= $valor ?>" <?= old('zona', $m['zona'] ?? '') === $valor ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
              <?php endforeach; ?>
            </select>
            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
          </div>
        </div>
      </div>

      <!-- Informações complementares -->
      <h2 class="text-[13px] font-medium text-gray-700 mb-3 mt-5 flex items-center gap-1.5">
        <i data-lucide="info" class="w-4 h-4 stroke-gray-400"></i> Informações complementares
      </h2>
      <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
        <div class="flex flex-col gap-1.5">
          <label class="label-primary flex items-center gap-2 cursor-pointer">
            <input type="checkbox" id="possui_filhos" name="possui_filhos" value="1" <?= old('possui_filhos', $m['possui_filhos'] ?? 0) ? 'checked' : '' ?> class="w-4 h-4 rounded">
            Possui filhos?
          </label>
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="quantidade_filhos" class="label-primary">Quantidade de filhos</label>
          <input type="number" id="quantidade_filhos" name="quantidade_filhos" min="0" value="<?= esc(old('quantidade_filhos', $m['quantidade_filhos'] ?? '')) ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="quantidade_dependentes" class="label-primary">Pessoas dependentes</label>
          <input type="number" id="quantidade_dependentes" name="quantidade_dependentes" min="0" value="<?= esc(old('quantidade_dependentes', $m['quantidade_dependentes'] ?? '')) ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5 col-span-2 lg:col-span-3">
          <div class="flex flex-wrap gap-3 text-sm text-gray-700">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" name="esta_gravida" value="1" <?= old('esta_gravida', $m['esta_gravida'] ?? 0) ? 'checked' : '' ?> class="w-4 h-4 rounded">
              Gestante
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" name="possui_deficiencia" value="1" <?= old('possui_deficiencia', $m['possui_deficiencia'] ?? 0) ? 'checked' : '' ?> class="w-4 h-4 rounded">
              Pessoa com deficiência
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" name="e_idosa" value="1" <?= old('e_idosa', $m['e_idosa'] ?? 0) ? 'checked' : '' ?> class="w-4 h-4 rounded">
              Idosa
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" name="situacao_vulnerabilidade" value="1" <?= old('situacao_vulnerabilidade', $m['situacao_vulnerabilidade'] ?? 0) ? 'checked' : '' ?> class="w-4 h-4 rounded">
              Situação de vulnerabilidade
            </label>
          </div>
        </div>
        <div class="flex flex-col gap-1.5 col-span-2 lg:col-span-3">
          <label for="observacoes" class="label-primary">Observações gerais</label>
          <textarea id="observacoes" name="observacoes" class="textarea-primary" rows="3"><?= esc(old('observacoes', $m['observacoes'] ?? '')) ?></textarea>
        </div>
      </div>

      <div class="flex items-center gap-2 pt-4 mt-5 border-t border-gray-100">
        <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2">
          <i data-lucide="check" class="w-4 h-4"></i>
          <?= ! empty($m['id']) ? 'Salvar alterações' : 'Salvar' ?>
        </button>
        <a href="<?= base_url(! empty($m['id']) ? 'mulheres/detalhes/' . (int) $m['id'] : 'dashboard') ?>" class="btn-secondary text-[13px] px-4 py-2">Cancelar</a>
      </div>
    </form>
  </div>
</section>

<script>
  var MASCARA = {
    cpf: function (v) {
      return v.replace(/\D/g, '').slice(0, 11)
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/(\d{3})\.(\d{3})\.(\d{3})(\d)/, '$1.$2.$3-$4');
    },
    cep: function (v) {
      return v.replace(/\D/g, '').slice(0, 8).replace(/(\d{5})(\d)/, '$1-$2');
    },
    telefone: function (v) {
      var d = v.replace(/\D/g, '').slice(0, 11);
      d = d.replace(/(\d{2})(\d)/, '($1) $2');
      if (d.length > 10) d = d.replace(/(\d{5})(\d)/, '$1-$2');
      else d = d.replace(/(\d{4})(\d)/, '$1-$2');
      return d;
    }
  };

  var campos = document.querySelectorAll('[data-mascara]');
  campos.forEach(function (el) {
    var tipo = el.getAttribute('data-mascara');
    var funcao = tipo === 'telefone_fixo' || tipo === 'telefone_celular' ? MASCARA.telefone : MASCARA[tipo];
    el.value = funcao(String(el.value).replace(/\D/g, ''));
    el.addEventListener('input', function () {
      var v = el.value.replace(/\D/g, '').slice(0, tipo === 'telefone_fixo' ? 10 : (tipo === 'telefone_celular' ? 11 : 14));
      el.value = funcao(v);
    });
  });

  // Idade calculada automaticamente
  const idadeSpan = document.getElementById('idade_calculada');
  const nascInput = document.getElementById('data_nascimento');
  const calcularIdade = () => {
    if (!nascInput.value) { idadeSpan.textContent = '—'; return; }
    const nasc = new Date(nascInput.value + 'T00:00:00');
    const hoje = new Date();
    let anos = hoje.getFullYear() - nasc.getFullYear();
    const mes = hoje.getMonth() - nasc.getMonth();
    if (mes < 0 || (mes === 0 && hoje.getDate() < nasc.getDate())) anos--;
    idadeSpan.textContent = anos >= 0 ? anos + ' anos' : 'Data inválida';
  };
  nascInput.addEventListener('change', calcularIdade);
  calcularIdade();
</script>
<?= $this->endSection() ?>