<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex items-center justify-between">
    <div class="flex items-center gap-2">
      <a href="<?= base_url('criancas') ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 transition" title="Voltar">
        <i data-lucide="arrow-left" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
      </a>
      <h1 class="text-gray-600 text-[15px] font-medium"><?= esc($crianca['nome']) ?></h1>
    </div>
    <a href="<?= base_url('criancas/editar/' . $crianca['id']) ?>" class="btn-secondary text-[13px] px-3 py-2">
      <i data-lucide="pencil" class="w-4 h-4"></i>
      Editar
    </a>
  </header>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
      <p class="text-gray-600 text-[13px] font-medium leading-4">Dados Pessoais</p>
      <div class="flex flex-col divide-y divide-gray-100">
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Data de Nascimento</span>
          <span class="text-gray-600 text-[12.5px]"><?= $crianca['data_nascimento'] ? date('d/m/Y', strtotime($crianca['data_nascimento'])) : '—' ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Sexo</span>
          <span class="text-gray-600 text-[12.5px]"><?= $crianca['sexo'] === 'M' ? 'Masculino' : ($crianca['sexo'] === 'F' ? 'Feminino' : '—') ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">RG / CPF</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($crianca['rg'] ?? '—') ?> / <?= esc($crianca['cpf'] ?? '—') ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Matrícula</span>
          <span class="text-gray-600 text-[12.5px]"><?= $crianca['data_matricula'] ? date('d/m/Y', strtotime($crianca['data_matricula'])) : '—' ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Mãe</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($crianca['nome_mae'] ?? '—') ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Pai</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($crianca['nome_pai'] ?? '—') ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Telefone</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($crianca['telefone'] ?? '—') ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Endereço</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($crianca['endereco'] ?? '—') ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Cidade / UF</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($crianca['cidade'] ?? '—') ?>/<?= esc($crianca['uf'] ?? '—') ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Escola / Série</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($crianca['escola'] ?? '—') ?> / <?= esc($crianca['serie'] ?? '—') ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Status</span>
          <span>
            <?php if ($crianca['status'] === 'ativo'): ?>
              <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-green-50 border border-green-200 text-green-700">Ativo</span>
            <?php else: ?>
              <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-50 border border-gray-200 text-gray-600">Inativo</span>
            <?php endif; ?>
          </span>
        </div>
        <?php if (! empty($crianca['observacoes'])): ?>
          <div class="py-2">
            <span class="text-gray-400 text-[12px]">Observações</span>
            <p class="text-gray-600 text-[12.5px] mt-1"><?= nl2br(esc($crianca['observacoes'])) ?></p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
      <div class="flex justify-between items-center">
        <p class="text-gray-600 text-[13px] font-medium leading-4">Responsáveis</p>
        <button type="button" onclick="openModal('formResponsavel')" class="btn-primary text-[12px] text-white px-2.5 py-1.5">
          <i data-lucide="plus" class="w-3.5 h-3.5"></i>
          Adicionar
        </button>
      </div>

      <div id="formResponsavel" class="hidden">
        <form action="<?= base_url('criancas/' . $crianca['id'] . '/responsaveis') ?>" method="POST" class="border border-gray-200 rounded-xl p-3 bg-gray-50 flex flex-col gap-2.5">
          <div class="flex flex-col gap-1.5">
            <label for="resp_nome" class="label-primary">Nome *</label>
            <input type="text" id="resp_nome" name="nome" required class="input-primary">
          </div>
          <div class="grid grid-cols-2 gap-2.5">
            <div class="flex flex-col gap-1.5">
              <label for="resp_parentesco" class="label-primary">Parentesco</label>
              <input type="text" id="resp_parentesco" name="parentesco" placeholder="Mãe, pai, tia..." class="input-primary">
            </div>
            <div class="flex flex-col gap-1.5">
              <label for="resp_telefone" class="label-primary">Telefone</label>
              <input type="text" id="resp_telefone" name="telefone" class="input-primary">
            </div>
            <div class="flex flex-col gap-1.5">
              <label for="resp_cpf" class="label-primary">CPF</label>
              <input type="text" id="resp_cpf" name="cpf" class="input-primary">
            </div>
            <div class="flex flex-col gap-1.5">
              <label for="resp_endereco" class="label-primary">Endereço</label>
              <input type="text" id="resp_endereco" name="endereco" class="input-primary">
            </div>
          </div>
          <div class="flex items-center gap-2">
            <button type="submit" class="btn-primary text-[12px] text-white px-3 py-1.5">
              <i data-lucide="check" class="w-3.5 h-3.5"></i>
              Salvar
            </button>
            <button type="button" onclick="closeModal('formResponsavel')" class="btn-secondary text-[12px] px-3 py-1.5">Cancelar</button>
          </div>
        </form>
      </div>

      <div class="flex flex-col divide-y divide-gray-100">
        <?php foreach ($responsaveis as $r): ?>
          <div class="flex items-center justify-between py-2.5">
            <div class="flex flex-col">
              <p class="text-gray-600 text-[13px] font-medium leading-4"><?= esc($r['nome']) ?></p>
              <p class="text-gray-400 text-[12px] leading-4"><?= esc($r['parentesco'] ?? '—') ?><?= $r['telefone'] ? ' • ' . esc($r['telefone']) : '' ?></p>
            </div>
            <a href="<?= base_url('responsaveis/excluir/' . $r['id']) ?>" title="Remover" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:border-red-300 hover:text-red-600 hover:bg-red-50 transition">
              <i data-lucide="trash-2" class="w-4 h-4 stroke-[1.5]"></i>
            </a>
          </div>
        <?php endforeach; ?>
        <?php if (empty($responsaveis)): ?>
          <p class="text-gray-400 text-[13px] py-3">Nenhum responsável cadastrado.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?= $this->endSection() ?>
