<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php $c = $crianca ?? []; ?>

<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex items-center gap-2">
    <a href="<?= base_url('criancas') ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 transition" title="Voltar">
      <i data-lucide="arrow-left" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
    </a>
    <h1 class="text-gray-600 text-[15px] font-medium"><?= esc($titulo) ?></h1>
  </header>

  <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl">
    <form action="<?= ! empty($c) ? base_url('criancas/atualizar/' . $c['id']) : base_url('criancas') ?>" method="POST">
      <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
        <div class="flex flex-col gap-1.5">
          <label for="nome" class="label-primary">Nome *</label>
          <input type="text" id="nome" name="nome" value="<?= esc($c['nome'] ?? old('nome')) ?>" required class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="data_nascimento" class="label-primary">Data de Nascimento</label>
          <input type="date" id="data_nascimento" name="data_nascimento" value="<?= esc($c['data_nascimento'] ?? old('data_nascimento')) ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="sexo" class="label-primary">Sexo</label>
          <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
            <select id="sexo" name="sexo" class="select-primary">
              <option value="">Selecione</option>
              <option value="M" <?= ($c['sexo'] ?? '') === 'M' ? 'selected' : '' ?>>Masculino</option>
              <option value="F" <?= ($c['sexo'] ?? '') === 'F' ? 'selected' : '' ?>>Feminino</option>
            </select>
            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
          </div>
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="rg" class="label-primary">RG</label>
          <input type="text" id="rg" name="rg" value="<?= esc($c['rg'] ?? old('rg')) ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="cpf" class="label-primary">CPF</label>
          <input type="text" id="cpf" name="cpf" value="<?= esc($c['cpf'] ?? old('cpf')) ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="data_matricula" class="label-primary">Data de Matrícula</label>
          <input type="date" id="data_matricula" name="data_matricula" value="<?= esc($c['data_matricula'] ?? old('data_matricula')) ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="telefone" class="label-primary">Telefone</label>
          <input type="text" id="telefone" name="telefone" value="<?= esc($c['telefone'] ?? old('telefone')) ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="nome_mae" class="label-primary">Nome da Mãe</label>
          <input type="text" id="nome_mae" name="nome_mae" value="<?= esc($c['nome_mae'] ?? old('nome_mae')) ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="nome_pai" class="label-primary">Nome do Pai</label>
          <input type="text" id="nome_pai" name="nome_pai" value="<?= esc($c['nome_pai'] ?? old('nome_pai')) ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="endereco" class="label-primary">Endereço</label>
          <input type="text" id="endereco" name="endereco" value="<?= esc($c['endereco'] ?? old('endereco')) ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="cidade" class="label-primary">Cidade</label>
          <input type="text" id="cidade" name="cidade" value="<?= esc($c['cidade'] ?? old('cidade')) ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="uf" class="label-primary">UF</label>
          <input type="text" id="uf" name="uf" maxlength="2" value="<?= esc($c['uf'] ?? old('uf')) ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="escola" class="label-primary">Escola</label>
          <input type="text" id="escola" name="escola" value="<?= esc($c['escola'] ?? old('escola')) ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="serie" class="label-primary">Série</label>
          <input type="text" id="serie" name="serie" value="<?= esc($c['serie'] ?? old('serie')) ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="status" class="label-primary">Status</label>
          <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
            <select id="status" name="status" class="select-primary">
              <option value="ativo" <?= ($c['status'] ?? 'ativo') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
              <option value="inativo" <?= ($c['status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inativo</option>
            </select>
            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
          </div>
        </div>
        <div class="flex flex-col gap-1.5 col-span-2 lg:col-span-3">
          <label for="observacoes" class="label-primary">Observações</label>
          <textarea id="observacoes" name="observacoes" class="textarea-primary" rows="3"><?= esc($c['observacoes'] ?? old('observacoes')) ?></textarea>
        </div>
      </div>

      <div class="flex items-center gap-2 pt-4 mt-4 border-t border-gray-100">
        <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2">
          <i data-lucide="check" class="w-4 h-4"></i>
          Salvar
        </button>
        <a href="<?= base_url('criancas') ?>" class="btn-secondary text-[13px] px-4 py-2">Cancelar</a>
      </div>
    </form>
  </div>
</section>
<?= $this->endSection() ?>
