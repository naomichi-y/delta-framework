<style type="text/css">
div#explain {
  overflow: auto;
  max-height: 200px;
}
</style>
<?php echo $html->includeJS('/assets/base/zeroclipboard-1.0.7/ZeroClipboard.js') ?>
<?php echo $html->includeJS('/assets/base/wordbreak.js') ?>
<?php echo $html->includeJS('/assets/base/delta/js/jquery_setup.js') ?>
<script type="text/javascript">
$(document).ready(function(){
  $(".wordbreak").wordbreak();

  var hash = '<?php echo $request->get('hash') ?>';

  ZeroClipboard.setMoviePath('/assets/base/zeroclipboard-1.0.7/ZeroClipboard.swf');
  ZeroClipboard.nextId = <?php echo time() ?>;

  var clipStatement = new ZeroClipboard.Client();
  clipStatement.setHandCursor(true);
  clipStatement.setText($('#statement_' + hash).val());
  clipStatement.glue('copy_statement_' + hash, 'copy_container_statement_' + hash);

  $('#copy_container_statement_' + hash).click(function () {
    var copyDone = $('#copy_statement_done_' + hash);

    copyDone.toggleClass('success', true);
    copyDone.hide();
    copyDone.text('クリップボードにコピーしました。');
    copyDone.show('slow');
  });

  var clipFilepath = new ZeroClipboard.Client();
  clipFilepath.setHandCursor(true);
  clipFilepath.setText($('#filepath_' + hash).text());
  clipFilepath.glue('copy_filepath_' + hash, 'copy_container_filepath_' + hash);

  $('#copy_container_filepath_' + hash).click(function () {
    var copyDone = $('#copy_filepath_done_' + hash);

    copyDone.toggleClass('success', true);
    copyDone.hide();
    copyDone.text('クリップボードにコピーしました。');
    copyDone.show('slow');
  });
});
</script>
<?php $form->start() ?>
  <?php if (isset($statementInfo)): ?>
    <h2>ステートメント情報</h2>
    <table>
      <tr>
        <th>
          <?php if ($request->get('type') === 'prepared'): ?>
          最も遅いステートメント
          <?php else: ?>
          ステートメント
          <?php endif ?>
        </th>
        <td>
          <?php echo $form->textarea('statement_' . $request->get('hash'), array('value' => $statementInfo->statement, 'readonly' => 'readonly', 'cols' => 100, 'rows' => 5)) ?>
          <span id="copy_container_statement_<?php echo $request->get('hash') ?>" style="position: relative">
            <?php echo $form->inputButton('ステートメントのコピー', array('name' => 'copy_statement_' . $request->get('hash'), 'class' => 'btn')) ?>
          </span>
          <span id="copy_statement_done_<?php echo $request->get('hash') ?>"></span>
        </td>
      </tr>
      <tr>
        <th>最も遅い実行時間</th>
        <td><?php echo $statementInfo->most_slow_process_time ?> sec</td>
      </tr>
      <tr>
        <th>ファイルパス</th>
        <td>
          <span id="filepath_<?php echo $request->get('hash') ?>"><?php echo $statementInfo->file_path ?></span><br />
          <span id="copy_container_filepath_<?php echo $request->get('hash') ?>" style="position: relative">
            <?php echo $form->inputButton('ファイルパスのコピー', array('name' => 'copy_filepath_' . $request->get('hash'), 'class' => 'btn')) ?>
          </span>
          <span id="copy_filepath_done_<?php echo $request->get('hash') ?>"></span>
        </td>
      </tr>
      <tr>
        <th>実行メソッド</th>
        <td><?php echo $statementInfo->class_name ?>::<?php echo $statementInfo->method_name ?>() (Line: <?php echo $statementInfo->line ?>)</td>
      </tr>
    </table>
    <?php if (isset($explainColumnNames)): ?>
    <h2>実行計画</h2>
    <div id="explain">
      <table>
        <tr>
        <?php foreach ($explainColumnNames as $columnName): ?>
          <th><?php echo $columnName ?></th>
        <?php endforeach ?>
        </tr>
        <?php foreach ($explainRecords as $record): ?>
          <tr>
            <?php foreach ($record as $current): ?>
              <td class="wordbreak"><?php echo $current ?></td>
            <?php endforeach ?>
          </tr>
        <?php endforeach ?>
      </table>
    </div>
    <?php endif ?>
  <?php else: ?>
    <p>データがありません。</p>
  <?php endif ?>
<?php $form->close() ?>

