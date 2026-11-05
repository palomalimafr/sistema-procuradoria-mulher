<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php $d = $dadosMulher ?? []; ?>

<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex items-center gap-2">
    <a href="<?= base_url('mulher/dashboard') ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 transition" title="Voltar">
      <i data-lucide="arrow-left" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
    </a>
    <h1 class="text-gray-600 text-[15px] font-medium">Meus Dados</h1>
  </header>

  <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
      <div class="flex flex-col gap-0.5">
        <p class="text-gray-400 text-[11px] leading-3 uppercase tracking-wider">Nome</p>
        <p class="text-gray-600 text-[13px] leading-4"><?= esc($d['nome_completo'] ?? '—') ?></p>
      </div>
      <div class="flex flex-col gap-0.5">
        <p class="text-gray-400 text-[11px] leading-3 uppercase tracking-wider">Nome social</p>
        <p class="text-gray-600 text-[13px] leading-4"><?= esc($d['nome_social'] ?? '—') ?></p>
      </div>
      <div class="flex flex-col gap-0.5">
        <p class="text-gray-400 text-[11px] leading-3 uppercase tracking-wider">E-mail</p>
        <p class="text-gray-600 text-[13px] leading-4"><?= esc(session()->get('usuario_email')) ?></p>
      </div>
      <div class="flex flex-col gap-0.5">
        <p class="text-gray-400 text-[11px] leading-3 uppercase tracking-wider">CPF</p>
        <p class="text-gray-600 text-[13px] leading-4"><?= esc($d['cpf'] ?? '—') ?></p>
      </div>
      <div class="flex flex-col gap-0.5">
        <p class="text-gray-400 text-[11px] leading-3 uppercase tracking-wider">Data de nascimento</p>
        <p class="text-gray-600 text-[13px] leading-4"><?= !empty($d['data_nascimento']) ? date('d/m/Y', strtotime($d['data_nascimento'])) : '—' ?></p>
      </div>
      <div class="flex flex-col gap-0.5">
        <p class="text-gray-400 text-[11px] leading-3 uppercase tracking-wider">Estado civil</p>
        <p class="text-gray-600 text-[13px] leading-4"><?= esc(ucfirst(str_replace('_', ' ', $d['estado_civil'] ?? '—'))) ?></p>
      </div>
    </div>

    <hr class="w-full bg-gray-200 border-0 my-1">

    <form action="<?= base_url('mulher/perfil/atualizar') ?>" method="POST">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <div class="flex flex-col gap-1.5">
          <label for="nome_social" class="label-primary">Nome social</label>
          <input type="text" id="nome_social" name="nome_social" value="<?= esc($d['nome_social'] ?? '') ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="telefone" class="label-primary">Telefone</label>
          <input type="text" id="telefone" name="telefone" value="<?= esc($d['telefone'] ?? '') ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="whatsapp" class="label-primary">WhatsApp</label>
          <input type="text" id="whatsapp" name="whatsapp" value="<?= esc($d['whatsapp'] ?? '') ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="cep" class="label-primary">CEP</label>
          <input type="text" id="cep" name="cep" value="<?= esc($d['cep'] ?? '') ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="logradouro" class="label-primary">Logradouro</label>
          <input type="text" id="logradouro" name="logradouro" value="<?= esc($d['logradouro'] ?? '') ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="numero" class="label-primary">Número</label>
          <input type="text" id="numero" name="numero" value="<?= esc($d['numero'] ?? '') ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="complemento" class="label-primary">Complemento</label>
          <input type="text" id="complemento" name="complemento" value="<?= esc($d['complemento'] ?? '') ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="bairro" class="label-primary">Bairro</label>
          <input type="text" id="bairro" name="bairro" value="<?= esc($d['bairro'] ?? '') ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="municipio" class="label-primary">Município</label>
          <input type="text" id="municipio" name="municipio" value="<?= esc($d['municipio'] ?? '') ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="estado" class="label-primary">Estado (UF)</label>
          <input type="text" id="estado" name="estado" value="<?= esc($d['estado'] ?? '') ?>" maxlength="2" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="ponto_referencia" class="label-primary">Ponto de referência</label>
          <input type="text" id="ponto_referencia" name="ponto_referencia" value="<?= esc($d['ponto_referencia'] ?? '') ?>" class="input-primary">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="zona" class="label-primary">Zona</label>
          <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
            <select id="zona" name="zona" class="select-primary">
              <option value="">Selecione</option>
              <option value="urbana" <?= ($d['zona'] ?? '') === 'urbana' ? 'selected' : '' ?>>Urbana</option>
              <option value="rural" <?= ($d['zona'] ?? '') === 'rural' ? 'selected' : '' ?>>Rural</option>
            </select>
            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
          </div>
        </div>
        <div class="flex flex-col gap-1.5 sm:col-span-2 lg:col-span-3">
          <label for="observacoes" class="label-primary">Observações gerais</label>
          <textarea id="observacoes" name="observacoes" rows="3" class="textarea-primary"><?= esc($d['observacoes'] ?? '') ?></textarea>
        </div>
      </div>

      <div class="flex items-center gap-2 pt-4 mt-4 border-t border-gray-100">
        <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2">
          <i data-lucide="check" class="w-4 h-4"></i>
          Salvar
        </button>
        <a href="<?= base_url('mulher/dashboard') ?>" class="btn-secondary text-[13px] px-4 py-2">Cancelar</a>
      </div>
    </form>
  </div>
</section>
<?= $this->endSection() ?>
