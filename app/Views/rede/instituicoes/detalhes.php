<?= $this->extend('layouts/admin') ?>

<?php $i = $instituicao; ?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex items-center justify-between">
    <div class="flex items-center gap-2">
      <a href="<?= base_url('rede/instituicoes') ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 transition" title="Voltar">
        <i data-lucide="arrow-left" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
      </a>
      <h1 class="text-gray-600 text-[15px] font-medium"><?= esc($i['nome']) ?></h1>
    </div>
    <span class="px-2.5 py-1 rounded-full text-[11px] font-medium border <?= (int) $i['ativo'] === 1 ? 'bg-green-50 border-green-200 text-green-700' : 'bg-gray-50 border-gray-200 text-gray-500' ?>">
      <?= (int) $i['ativo'] === 1 ? 'Ativa' : 'Inativa' ?>
    </span>
  </header>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
    <!-- DADOS DA INSTITUIÇÃO -->
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-blue-50 border border-blue-200 flex items-center justify-center rounded-lg">
          <i data-lucide="building-2" class="w-4 h-4 stroke-blue-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Dados da instituição</p>
      </div>

      <div class="flex flex-col divide-y divide-gray-100">
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Nome</span>
          <span class="text-gray-600 text-[12.5px] text-right"><?= esc($i['nome']) ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Tipo</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc($tipos[$i['tipo']] ?? $i['tipo']) ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Endereço</span>
          <span class="text-gray-600 text-[12.5px] text-right"><?= esc(! empty($i['endereco']) ? $i['endereco'] : '—') ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Município</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc(! empty($i['municipio']) ? $i['municipio'] : '—') ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Telefone</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc(! empty($i['telefone']) ? $i['telefone'] : '—') ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">WhatsApp</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc(! empty($i['whatsapp']) ? $i['whatsapp'] : '—') ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">E-mail</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc(! empty($i['email']) ? $i['email'] : '—') ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Responsável</span>
          <span class="text-gray-600 text-[12.5px]"><?= esc(! empty($i['responsavel']) ? $i['responsavel'] : '—') ?></span>
        </div>
        <div class="flex justify-between py-2">
          <span class="text-gray-400 text-[12px]">Horário de atendimento</span>
          <span class="text-gray-600 text-[12.5px] text-right"><?= esc(! empty($i['horario_atendimento']) ? $i['horario_atendimento'] : '—') ?></span>
        </div>
      </div>
    </div>

    <!-- SERVIÇOS -->
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-green-50 border border-green-200 flex items-center justify-center rounded-lg">
          <i data-lucide="list-plus" class="w-4 h-4 stroke-green-600 stroke-[1.5]"></i>
        </div>
        <p class="text-gray-600 text-[13px] font-medium leading-4">Serviços</p>
      </div>

      <?php if (empty($servicos)): ?>
        <p class="text-gray-400 text-[13px]">Nenhum serviço informado.</p>
      <?php else: ?>
        <div class="flex flex-wrap gap-1.5">
          <?php foreach ($servicos as $nome): ?>
            <span class="px-2 py-1 rounded-full text-[12px] font-medium border bg-blue-50 border-blue-200 text-blue-700"><?= esc($nome) ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if (! empty($i['observacoes'])): ?>
    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-1">
      <p class="text-gray-500 text-[12px] font-medium uppercase tracking-wider">Observações</p>
      <p class="text-gray-600 text-[13px] leading-5 whitespace-pre-line"><?= esc($i['observacoes']) ?></p>
    </div>
  <?php endif; ?>

  <!-- ÁREA PREPARADA PARA OS ENCAMINHAMENTOS ENVIADOS (funcionalidade futura) -->
  <div class="w-full p-4 bg-white border border-dashed border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-2">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 bg-gray-50 border border-gray-200 flex items-center justify-center rounded-lg">
        <i data-lucide="send" class="w-4 h-4 stroke-gray-400 stroke-[1.5]"></i>
      </div>
      <p class="text-gray-600 text-[13px] font-medium leading-4">Encaminhamentos enviados</p>
    </div>
    <p class="text-gray-400 text-[12.5px]">Os encaminhamentos enviados a esta instituição aparecerão aqui.</p>
  </div>

  <div class="flex items-center gap-2">
    <a href="<?= base_url('rede/instituicoes/editar/' . (int) $i['id']) ?>" class="btn-primary text-[13px] text-white px-4 py-2">
      <i data-lucide="pencil" class="w-4 h-4"></i>
      Editar
    </a>
    <form method="POST" action="<?= base_url('rede/instituicoes/' . (int) $i['id'] . '/situacao') ?>" onSubmit="return confirm('<?= (int) $i['ativo'] === 1 ? 'Desativar esta instituição? Ela deixará de aparecer para novos encaminhamentos (histórico preservado).' : 'Reativar esta instituição?' ?>');">
      <button type="submit" class="btn-secondary text-[13px] px-4 py-2">
        <i data-lucide="<?= (int) $i['ativo'] === 1 ? 'toggle-right' : 'toggle-left' ?>" class="w-4 h-4"></i>
        <?= (int) $i['ativo'] === 1 ? 'Desativar' : 'Reativar' ?>
      </button>
    </form>
  </div>
</section>
<?= $this->endSection() ?>