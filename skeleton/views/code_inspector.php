<dl class="delta-stack-traces">
  <?php foreach ($traces as $trace): ?>
    <?php if ($trace['isOutput']): ?>
    <dt id="trace_point_<?php echo $trace['traceId'] ?>">
      <?php echo $trace['title'] ?><br />
      <span class="delta-file-info"><?php echo $trace['file'] ?></span>
    </dt>
    <?php if ($trace['isExpand']): ?>
    <dd id="trace_point_detail_<?php echo $trace['traceId'] ?>" class="delta-code-inspector" style="display: block">
    <?php else: ?>
    <dd id="trace_point_detail_<?php echo $trace['traceId'] ?>" class="delta-code-inspector" style="display: none">
    <?php endif ?>
      <h2>Inspector code</h2>
      <p class="delta-stack-trace lang-php"><?php echo $trace['code'] ?></p>
    </dd>
    <?php endif ?>
  <?php endforeach ?>
</dl>
