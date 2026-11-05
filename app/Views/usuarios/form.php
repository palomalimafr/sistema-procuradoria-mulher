<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php $u = $usuario ?? []; ?>

<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex items-center gap-2">
    <a href="<?= base_url('usuarios') ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 transition" title="Voltar">
      <i data-lucide="arrow-left" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
    </a>
    <h1 class="text-gray-600 text-[15px] font-medium"><?= esc($titulo) ?></h1>
  </header>

  <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl">
    <form action="<?= ! empty($u) ? base_url('usuarios/atualizar/' . $u['id']) : base_url('usuarios') ?>" method="POST">
      <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
        <div class="flex flex-col gap-1.5">
          <label for="nome" class="label-primary">Nome *</label>
          <input type="text" id="nome" name="nome" value="<?= esc($u['nome'] ?? old('nome')) ?>" required class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="email" class="label-primary">E-mail *</label>
          <input type="email" id="email" name="email" value="<?= esc($u['email'] ?? old('email')) ?>" required class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="senha" class="label-primary">Senha <?= ! empty($u) ? '(deixe vazio para manter)' : '*' ?></label>
          <input type="password" id="senha" name="senha" class="input-primary" <?= empty($u) ? 'required' : '' ?>>
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="perfil" class="label-primary">Perfil</label>
          <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
            <select id="perfil" name="perfil" class="select-primary">
              <option value="funcionario" <?= ($u['perfil'] ?? '') === 'funcionario' ? 'selected' : '' ?>>Funcionário</option>
              <option value="admin" <?= ($u['perfil'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrador</option>
            </select>
            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
          </div>
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="ativo" class="label-primary">Status</label>
          <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
            <select id="ativo" name="ativo" class="select-primary">
              <option value="1" <?= ($u['ativo'] ?? 1) == 1 ? 'selected' : '' ?>>Ativo</option>
              <option value="0" <?= ($u['ativo'] ?? '') === '0' ? 'selected' : '' ?>>Inativo</option>
            </select>
            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-2 pt-4 mt-4 border-t border-gray-100">
        <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2">
          <i data-lucide="check" class="w-4 h-4"></i>
          Salvar
        </button>
        <a href="<?= base_url('usuarios') ?>" class="btn-secondary text-[13px] px-4 py-2">Cancelar</a>
      </div>
    </form>
  </div>
</section>
<?= $this->endSection() ?>
