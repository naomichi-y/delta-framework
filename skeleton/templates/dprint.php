<?php if ($functionCallCount == 1): ?>
  <script type="text/javascript" src="/assets/base/require.js"></script>
  <script type="text/javascript" src="/assets/base/delta/js/code_inspector.js"></script>
<?php endif ?>
<div class="delta_context">
  <div class="delta_dprint">
    <p class="delta_dprint_title">dprint() #<?php echo $functionCallCount ?></p>
    <div class="delta_dprint_box">
      <?php echo $code ?>
      <dl class="delta_dprint_detail">
        <dt>Type:</dt>
        <dd><?php echo $type ?></dd>
        <?php if (isset($length)): ?>
          <dt>Length:</dt>
          <dd><?php echo number_format($length) ?></dd>
        <?php endif ?>
        <dt>Result:</dt>
        <dd><pre><code><?php echo $message ?></code></pre></dd>
      </dl>
    </div>
  </div>
</div>
