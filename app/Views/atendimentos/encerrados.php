<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="w-full flex-1 flex flex-col gap-3">
  <header class="flex justify-between items-center">
    <h1 class="text-gray-600 text-[15px] font-medium">Atendimentos Encerrados</h1>
    <span class="text-gray-400 text-[12px] leading-4"><?= date('d/m/Y') ?></span>
  </header>

  <div class="w-full bg-white border border-gray-200 shadow-[0px_1px_1px_0px_rgba(31,41,55,0.06)] rounded-xl overflow-hidden">
    <div class="w-full overflow-x-auto">
      <table class="w-full text-[13px]">
        <thead>
          <tr class="bg-gray-50 border-b border-gray-200">
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Protocolo</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Mulher</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Data</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Profissional</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Prioridade</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Encerrado em</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Motivo do encerramento</th>
            <th class="text-left py-2.5 px-3 text-gray-500 font-medium text-[12px]">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (! empty($atendimentos)): ?>
            <?php foreach ($atendimentos as $a): ?>
              <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
                <td class="py-2.5 px-3 text-gray-600 font-medium">
                  <a href="<?= base_url('atendimentos/' . (int) $a['id']) ?>" class="text-primary hover:underline">#<?= esc($a['protocolo']) ?></a>
                </td>
                <td class="py-2.5 px-3 text-gray-600"><?= esc($a['mulher_nome']) ?></td>
                <td class="py-2.5 px-3 text-gray-600"><?= date('d/m/Y H:i', strtotime($a['data_hora'])) ?></td>
                <td class="py-2.5 px-3 text-gray-600"><?= esc($a['profissional_nome'] ?? '—') ?></td>
                <td class="py-2.5 px-3">
                  <?php if ($a['prioridade'] === 'emergencia'): ?>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-red-50 border border-red-200 text-red-700">Emergência</span>
                  <?php elseif ($a['prioridade'] === 'alta'): ?>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-amber-50 border border-amber-200 text-amber-700">Alta</span>
                  <?php elseif ($a['prioridade'] === 'media'): ?>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-blue-50 border border-blue-200 text-blue-700">Média</span>
                  <?php else: ?>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-50 border border-gray-200 text-gray-600">Baixa</span>
                  <?php endif; ?>
                </td>
                <td class="py-2.5 px-3 text-gray-600"><?= $a['data_encerramento'] ? date('d/m/Y H:i', strtotime($a['data_encerramento'])) : '—' ?></td>
                <td class="py-2.5 px-3">
                  <?php if (! empty($a['motivo_encerramento'])): ?>
                    <span class="text-gray-600">
                      <?= esc($motivos[$a['motivo_encerramento']] ?? ucfirst(str_replace('_', ' ', $a['motivo_encerramento']))) ?>
                      <?php if (! empty($a['motivo_encerramento_outra'])): ?>
                        <span class="text-gray-400"> — <?= esc($a['motivo_encerramento_outra']) ?></span>
                      <?php endif; ?>
                    </span>
                  <?php else: ?>
                    <span class="text-gray-400">—</span>
                  <?php endif; ?>
                </td>
                <td class="py-2.5 px-3">
                  <a href="<?= base_url('atendimentos/' . (int) $a['id']) ?>" title="Abrir atendimento" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition">
                    <i data-lucide="eye" class="w-4 h-4 stroke-gray-500 stroke-[1.5]"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="py-6 px-3 text-center text-gray-400">Nenhum atendimento encerrado.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if (! empty($pager) && $pager->getPageCount() > 1): ?>
    <div class="flex justify-end">
      <?= $pager->links() ?>
    </div>
  <?php endif; ?>
</section>
<?= $this->endSection() ?>