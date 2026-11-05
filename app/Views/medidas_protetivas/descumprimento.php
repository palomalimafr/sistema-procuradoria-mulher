<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex items-center justify-between">
    <div class="flex items-center gap-2">
      <a href="<?= base_url('medidas-protetivas/' . (int) $medida['id']) ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 transition" title="Voltar">
        <i data-lucide="arrow-left" class="w-4 h-4 stroke-gray-400 stroke-[1.5]"></i>
      </a>
      <h1 class="text-gray-600 text-[15px] font-medium"><?= esc($titulo) ?></h1>
    </div>
  </header>

  <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex items-center gap-3">
    <div class="w-9 h-9 bg-red-50 border border-red-200 flex items-center justify-center rounded-lg">
      <i data-lucide="shield-off" class="w-4 h-4 stroke-red-600 stroke-[1.5]"></i>
    </div>
    <div class="flex flex-col leading-4">
      <p class="text-gray-600 text-[13.5px] font-medium">Medida ##<?= (int) $medida['id'] ?></p>
      <p class="text-gray-400 text-[12px]">Mulher: <?= esc($medida['mulher_nome'] ?? '') ?></p>
    </div>
    <span class="ml-auto px-2.5 py-1 rounded-full text-[11px] font-medium border <?= ($medida['possui_medida'] ?? 0) ? 'bg-red-50 border-red-200 text-red-600' : 'bg-amber-50 border-amber-200 text-amber-600' ?>">
      <?= ($medida['possui_medida'] ?? 0) ? 'Possui medida protetiva' : 'Nǡo possui medida' ?>
    </span>
  </div>

  <form method="POST" action="<?= base_url('medidas-protetivas/' . (int) $medida['id'] . '/descumprimentos') ?>" class="w-full flex flex-col gap-3">
    <input type="hidden" name="medida_id" value="<?= (int) $medida['id'] ?>">

    <div class="w-full p-4 bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl flex flex-col gap-3">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
        <div class="flex flex-col gap-1.5">
          <label for="data_ocorrencia" class="label-primary">Data da ocorrência *</label>
          <input type="date" id="data_ocorrencia" name="data_ocorrencia" value="<?= esc((string) old('data_ocorrencia', $medida['data_ocorrencia'] ?? $hoje)) ?>" class="input-primary">
        </div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="descricao" class="label-primary">Descrição *</label>
        <textarea id="descricao" name="descricao" class="textarea-primary" rows="3"><?= esc((string) old('descricao')) ?></textarea>
        <p class="text-gray-400 text-[11.5px] leading-3.5">Relate o que ocorreu: agressor, ameaças, violência ou outro comportamento que caracterize o descumprimento.</p>
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="observacoes" class="label-primary">Observações</label>
        <textarea id="observacoes" name="observacoes" class="textarea-primary" rows="2"><?= esc((string) old('observacoes')) ?></textarea>
      </div>

      <div class="flex flex-col gap-1.5">
        <label for="historico" class="label-primary">Decisão da equipe / próximos passos</label>
        <textarea id="historico" name="historico" class="textarea-primary" rows="2"><?= esc((string) old('historico')) ?></textarea>
        <p class="text-gray-400 text-[11.5px] leading-3.5">Ficará registrado no histórico da medida.</p>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <button type="submit" class="btn-primary text-[13px] text-white px-4 py-2">
        <i data-lucide="shield-off" class="w-4 h-4"></i>
        Registrar descumprimento
      </button>
      <a href="<?= base_url('medidas-protetivas/' . (int) $medida['id']) ?>" class="btn-secondary text-[13px] px-4 py-2">Cancelar</a>
    </div>
  </form>
</section>

<?= $this->endSection() ?>
