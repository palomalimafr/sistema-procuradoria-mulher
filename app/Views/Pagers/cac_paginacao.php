<?php
use CodeIgniter\Pager\PagerRenderer;

/** @var PagerRenderer $pager */
$pager->setSurroundCount(2);
?>
<nav class="flex items-center gap-1">
  <?php if ($pager->hasPrevious()): ?>
    <a href="<?= esc($pager->getPrevious()) ?>" title="Anterior" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 transition">
      <i data-lucide="chevron-left" class="w-4 h-4 stroke-[1.5]"></i>
    </a>
  <?php endif; ?>

  <?php foreach ($pager->links() as $link): ?>
    <a href="<?= esc($link['uri']) ?>" class="min-w-8 h-8 px-2 flex items-center justify-center rounded-lg text-[12.5px] font-medium transition <?= $link['active'] ? 'bg-primary text-white' : 'border border-gray-200 text-gray-500 hover:bg-gray-50' ?>">
      <?= (int) $link['title'] ?>
    </a>
  <?php endforeach; ?>

  <?php if ($pager->hasNext()): ?>
    <a href="<?= esc($pager->getNext()) ?>" title="Próxima" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 transition">
      <i data-lucide="chevron-right" class="w-4 h-4 stroke-[1.5]"></i>
    </a>
  <?php endif; ?>
</nav>
