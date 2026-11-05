<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php $c = $configs ?? \App\Models\ConfiguracaoModel::DEFAULTS; ?>
<?php $ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO']; ?>

<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex items-center gap-2">
    <h1 class="text-gray-600 text-[15px] font-medium">Configurações</h1>
  </header>

  <form action="<?= base_url('sistema/configuracoes') ?>" method="POST" enctype="multipart/form-data" class="flex flex-col gap-3">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">

      <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
        <div>
          <h2 class="text-gray-700 text-[14px] font-medium">Informações institucionais</h2>
          <p class="text-gray-500 text-[12px] leading-4 mt-0.5">Identificação do sistema e do órgão exibida para a equipe.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div class="flex flex-col gap-1.5">
            <label for="nome_sistema" class="label-primary">Nome do sistema *</label>
            <input type="text" id="nome_sistema" name="nome_sistema" value="<?= esc(old('nome_sistema') ?? $c['nome_sistema']) ?>" required maxlength="100" class="input-primary">
          </div>
          <div class="flex flex-col gap-1.5">
            <label for="nome_instituicao" class="label-primary">Nome da instituição *</label>
            <input type="text" id="nome_instituicao" name="nome_instituicao" value="<?= esc(old('nome_instituicao') ?? $c['nome_instituicao']) ?>" required maxlength="150" class="input-primary">
          </div>
          <div class="flex flex-col gap-1.5">
            <label for="nome_procuradoria" class="label-primary">Nome da Procuradoria da Mulher *</label>
            <input type="text" id="nome_procuradoria" name="nome_procuradoria" value="<?= esc(old('nome_procuradoria') ?? $c['nome_procuradoria']) ?>" required maxlength="150" class="input-primary">
          </div>
          <div class="flex flex-col gap-1.5">
            <label for="telefone" class="label-primary">Telefone institucional</label>
            <input type="text" id="telefone" name="telefone" value="<?= esc(old('telefone') ?? $c['telefone']) ?>" maxlength="30" class="input-primary" placeholder="(22) 99999-0000">
          </div>
          <div class="flex flex-col gap-1.5">
            <label for="email" class="label-primary">E-mail institucional</label>
            <input type="email" id="email" name="email" value="<?= esc(old('email') ?? $c['email']) ?>" maxlength="150" class="input-primary" placeholder="contato@exemplo.gov.br">
          </div>
          <div class="flex flex-col gap-1.5">
            <label for="endereco" class="label-primary">Endereço institucional</label>
            <input type="text" id="endereco" name="endereco" value="<?= esc(old('endereco') ?? $c['endereco']) ?>" maxlength="255" class="input-primary" placeholder="Rua, número e complemento">
          </div>
          <div class="flex flex-col gap-1.5">
            <label for="municipio" class="label-primary">Município</label>
            <input type="text" id="municipio" name="municipio" value="<?= esc(old('municipio') ?? $c['municipio']) ?>" maxlength="150" class="input-primary">
          </div>
          <div class="flex flex-col gap-1.5">
            <label for="estado" class="label-primary">Estado</label>
            <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
              <select id="estado" name="estado" class="select-primary">
                <option value="">Selecione</option>
                <?php foreach ($ufs as $uf): ?>
                  <option value="<?= esc($uf) ?>" <?= (old('estado') ?? $c['estado']) === $uf ? 'selected' : '' ?>><?= esc($uf) ?></option>
                <?php endforeach; ?>
              </select>
              <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
        <div>
          <h2 class="text-gray-700 text-[14px] font-medium">Logo</h2>
          <p class="text-gray-500 text-[12px] leading-4 mt-0.5">Imagem exibida na área de marca do sistema. Máximo de 2&nbsp;MB (PNG, JPG, GIF ou WEBP).</p>
        </div>

        <div class="flex flex-col gap-2.5">
          <div class="w-full min-h-24 h-32 max-h-40 bg-gray-50 border border-dashed border-gray-300 rounded-xl flex items-center justify-center overflow-hidden">
            <?php if (! empty($c['logo'])): ?>
              <img src="<?= base_url('sistema/logo') ?>" alt="Logo do sistema" class="max-h-full max-w-full object-contain p-2">
            <?php else: ?>
              <div class="flex flex-col items-center gap-1 text-gray-400">
                <i data-lucide="image" class="w-6 h-6 stroke-[1.2]"></i>
                <p class="text-[12px]">Nenhum logo cadastrado</p>
              </div>
            <?php endif; ?>
          </div>

          <div class="flex flex-col gap-1.5">
            <label for="logo" class="label-primary">Substituir logo</label>
            <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/gif,image/webp" class="input-primary">
          </div>

          <?php if (! empty($c['logo'])): ?>
            <div class="flex items-center justify-end pt-1">
              <button type="submit" form="form-remover-logo" class="text-[13px] inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-red-200 text-red-700 hover:bg-red-50 transition">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                Remover logo
              </button>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <div>
        <h2 class="text-gray-700 text-[14px] font-medium">Envio de e-mail (convite de primeiro acesso)</h2>
        <p class="text-gray-500 text-[12px] leading-4 mt-0.5">Usado para enviar o convite de criação de senha à mulher. Sem servidor SMTP configurado, o sistema tenta o transporte nativo do PHP.</p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div class="flex flex-col gap-1.5">
          <label for="email_smtp_host" class="label-primary">Servidor SMTP</label>
          <input type="text" id="email_smtp_host" name="email_smtp_host" value="<?= esc(old('email_smtp_host') ?? $c['email_smtp_host']) ?>" maxlength="255" class="input-primary" placeholder="smtp.exemplo.gov.br">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="email_smtp_port" class="label-primary">Porta</label>
          <input type="number" id="email_smtp_port" name="email_smtp_port" value="<?= esc(old('email_smtp_port') ?? $c['email_smtp_port']) ?>" min="1" max="65535" class="input-primary" placeholder="587">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="email_smtp_user" class="label-primary">Usuário SMTP</label>
          <input type="text" id="email_smtp_user" name="email_smtp_user" value="<?= esc(old('email_smtp_user') ?? $c['email_smtp_user']) ?>" maxlength="150" class="input-primary" autocomplete="off">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="email_smtp_pass" class="label-primary">Senha SMTP</label>
          <input type="password" id="email_smtp_pass" name="email_smtp_pass" value="" maxlength="255" class="input-primary" autocomplete="new-password" placeholder="<?= ! empty($smtpConfigurado) ? 'Cadastrada — deixe em branco para manter' : '' ?>">
          <?php if (! empty($smtpConfigurado)): ?>
            <p class="text-[11px] text-gray-500">A senha já está cadastrada. Deixe em branco para mantê-la.</p>
          <?php endif; ?>
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="email_smtp_crypto" class="label-primary">Criptografia</label>
          <div class="relative h-11 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
            <select id="email_smtp_crypto" name="email_smtp_crypto" class="select-primary">
              <?php $cryptoAtual = old('email_smtp_crypto') ?? $c['email_smtp_crypto']; ?>
              <option value="" <?= $cryptoAtual === '' || $cryptoAtual === null ? 'selected' : '' ?>>Nenhuma</option>
              <option value="tls" <?= $cryptoAtual === 'tls' ? 'selected' : '' ?>>TLS</option>
              <option value="ssl" <?= $cryptoAtual === 'ssl' ? 'selected' : '' ?>>SSL</option>
            </select>
            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 stroke-gray-400 pointer-events-none"></i>
          </div>
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="email_convite_validade_horas" class="label-primary">Validade do convite (horas)</label>
          <input type="number" id="email_convite_validade_horas" name="email_convite_validade_horas" value="<?= esc(old('email_convite_validade_horas') ?? $c['email_convite_validade_horas']) ?>" min="1" max="8760" class="input-primary" placeholder="72">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="email_remetente_nome" class="label-primary">Nome do remetente</label>
          <input type="text" id="email_remetente_nome" name="email_remetente_nome" value="<?= esc(old('email_remetente_nome') ?? $c['email_remetente_nome']) ?>" maxlength="150" class="input-primary" placeholder="Procuradoria da Mulher">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="email_remetente_email" class="label-primary">E-mail do remetente</label>
          <input type="email" id="email_remetente_email" name="email_remetente_email" value="<?= esc(old('email_remetente_email') ?? $c['email_remetente_email']) ?>" maxlength="150" class="input-primary" placeholder="convites@exemplo.gov.br">
        </div>
      </div>
    </div>

    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h2 class="text-gray-700 text-[14px] font-medium">Listas configuráveis</h2>
        <p class="text-gray-500 text-[12px] leading-4 mt-0.5">Administre os itens exibidos nos formulários e filtros dos módulos (tipos, origens, prioridades, status e situações).</p>
      </div>
      <a href="<?= base_url('sistema/configuracoes/listas') ?>" class="btn-primary text-[13px] text-white px-3 py-2 whitespace-nowrap self-start sm:self-auto">
        <i data-lucide="list" class="w-4 h-4"></i>
        Gerenciar listas
      </a>
    </div>

    <div class="flex items-center gap-2">
      <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2">
        <i data-lucide="check" class="w-4 h-4"></i>
        Salvar configurações
      </button>
    </div>
  </form>
</section>

<?php if (! empty($c['logo'])): ?>
  <form id="form-remover-logo" action="<?= base_url('sistema/configuracoes/remover-logo') ?>" method="POST" class="hidden"></form>
<?php endif; ?>

<?= $this->endSection() ?>