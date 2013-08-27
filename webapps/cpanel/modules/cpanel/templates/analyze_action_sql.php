<style type="text/css">
  div.action-sql-list {
    max-height: 300px;
    overflow: auto;
  }
</style>
<?php echo $html->includeJS('/assets/base/delta/js/jquery_setup.js') ?>
<?php echo $html->includeJS('/assets/base/delta/js/analyze.js') ?>
<script type="text/javascript">
$().ready( function () {
  $('#tablesorter_action_sql_<?php echo $request->get('actionRequestId') ?>').tablesorter({
    sortList:[[0,0]],
    widgets: ['zebra'],
    headers: {
      2: {sorter:'number'},
      4: {sorter:false}
    }
  });

  $('[id^=action-sql-]').click(function() {
    var prefix = 'action-sql-';
    var buffer = this.id.substring(prefix.length);
    var pos = buffer.indexOf('-');
    var actionRequestId = buffer.substring(0, pos);
    var statementType = buffer.substring(pos + 1);
    var id = '#tablesorter_action_sql_' + actionRequestId;
    var rowDisplay = null;

    switch (statementType) {
      case 'all':
        break;

      case 'select':
        rowDisplay = 1;
        break;

      case 'insert':
        rowDisplay = 2;
        break;

      case 'update':
        rowDisplay = 3;
        break;

      case 'delete':
        rowDisplay = 4;
        break;

      case 'other':
        rowDisplay = 127;
        break;
    }

    var array = Array(1, 2, 3, 4, 127);

    for (var i = 0; i < array.length; i++) {
      var target = id + ' tr.statement-type-' + array[i];

      if (rowDisplay == null || rowDisplay == array[i]) {
        $(target).css('display', 'table-row');
      } else {
        $(target).css('display', 'none');
      }
    }
  });
});
</script>
<?php if ($sqlRequests->count()): ?>
  <?php echo $form->start(array('action' => 'AnalyzeActionSQLDownload', 'actionRequestId' => $request->get('actionRequestId'))) ?>
    <h2>実行された SQL の一覧</h2>
    <p class="right">
      <?php echo $html->link('ALL', '#', array('id' => 'action-sql-' . $request->get('actionRequestId') . '-all')) ?> |
      <?php echo $html->link('SELECT', '#', array('id' => 'action-sql-' . $request->get('actionRequestId') . '-select')) ?> |
      <?php echo $html->link('INSERT', '#', array('id' => 'action-sql-' . $request->get('actionRequestId') . '-insert')) ?> |
      <?php echo $html->link('UPDATE', '#', array('id' => 'action-sql-' . $request->get('actionRequestId') . '-update')) ?> |
      <?php echo $html->link('DELETE', '#', array('id' => 'action-sql-' . $request->get('actionRequestId') . '-delete')) ?> |
      <?php echo $html->link('OTHER', '#', array('id' => 'action-sql-' . $request->get('actionRequestId') . '-other')) ?> |
      <?php echo $form->inputSubmit('CSV 形式でダウンロード') ?>
    </p>
  <?php echo $form->close() ?>
  <?php echo $form->start() ?>
    <div class="action-sql-list">
      <table id="tablesorter_action_sql_<?php echo $request->get('actionRequestId') ?>" class="tablesorter">
        <colgroup>
          <col width="10%" />
          <col width="52%" />
          <col width="10%" />
          <col width="20%" />
          <col width="8%" />
        </colgroup>
        <thead>
          <tr>
            <th>実行順序</th>
            <th>ステートメント</th>
            <th>実行時間</th>
            <th>実行メソッド</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php $i = 1; ?>
        <?php foreach ($sqlRequests as $current): ?>
          <tr class="statement-type-<?php echo $current->statement_type ?>">
            <td class="center"><?php echo $i++ ?></td>
            <td>
              <?php if ($current->prepared_statement): ?>
                <?php echo Delta_StringUtils::truncate($current->prepared_statement, 255) ?>
              <?php else: ?>
                <?php echo Delta_StringUtils::truncate($current->statement, 255) ?>
              <?php endif ?>
            </td>
            <td class="right"><?php echo $current->process_time ?> sec</td>
            <td><?php echo sprintf('%s::%s()', $current->class_name, $current->method_name) ?></td>
            <td class="center">
              <?php echo $form->inputButton('詳細', array('class' => 'detail_statement btn', 'id' => 'sqlRequestId_' . $current->sql_request_id)) ?>
            </td>
          </tr>
        <?php endforeach ?>
        </tbody>
      </table>
    </div>
    <div class="note right">
      実行結果は最後に発生したリクエストを対象としています。
    </div>
  <?php $form->close() ?>
  <div id="dynamic_dialog"></div>
<?php else: ?>
  <p>データがありません。</p>
<?php endif ?>
