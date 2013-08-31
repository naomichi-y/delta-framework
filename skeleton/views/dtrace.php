<div class="delta-context">
  <?php foreach ($trace as $number => $current): ?>
    <?php Delta_DebugUtils::output($current) ?>
  <?php endforeach ?>
</div>
