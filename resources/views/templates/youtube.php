<?php
/**
 * Props.
 *
 * @var string|null $aspectClass
 * @var string $id
 * @var (callable(string): void) $e
 */
?><div class="relative <?php $e($aspectClass ?? 'aspect-[calc(16/9)]'); ?>">
    <iframe src="https://www.youtube.com/embed/<?php $e($id); ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen class="absolute inset-0 w-full h-full bg-purple-100"></iframe>
</div>
