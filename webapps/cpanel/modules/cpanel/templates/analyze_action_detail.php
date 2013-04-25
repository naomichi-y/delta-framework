<?php echo $html->includeJS('/assets/base/jquery.md5.js') ?>
<?php echo $html->includeJS('/assets/base/delta/js/jquery_setup.js') ?>
<style type="text/css">
  div.request-path-list {
    max-height: 160px;
    overflow: auto;
  }

  div.latest-statement-list {
    max-height: 160px;
    overflow: auto;
  }
</style>
<?php echo $html->includeJS('/assets/base/delta/js/analyze.js') ?>
<script type="text/javascript">
$().ready( function () {
  $('#tablesorter_action_<?php echo $hash ?>').tablesorter({
    sortList:[[2,1]],
    widgets: ['zebra'],
    headers: {
      9: {sorter:false}
    }
  });

  $('#tablesorter_action_statement_<?php echo $hash ?>').tablesorter({
    sortList:[[1,1]],
    widgets: ['zebra'],
    headers: {
      3: {sorter:false}
    }
  });
});
</script>
<?php if ($slowRequests->count()): ?>
  <?php echo $form->start() ?>
    <h2>リクエスト一覧</h2>
    <div class="request-path-list">
      <table id="tablesorter_action_<?php echo $hash ?>" class="tablesorter">
        <colgroup>
          <col width="32%" />
          <col width="6%" />
          <col width="6%" />
          <col width="6%" />
          <col width="6%" />
          <col width="6%" />
          <col width="6%" />
          <col width="10%" />
          <col width="16%" />
          <col width="6%" />
        </colgroup>
        <thead>
          <tr>
            <th>リクエストパス</th>
            <th>回数</th>
            <th>AS</th>
            <th>AI</th>
            <th>AU</th>
            <th>AD</th>
            <th>AO</th>
            <th>平均時間</th>
            <th>最終アクセス時刻</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($slowRequests as $current): ?>
          <tr>
            <td><?php echo $html->link($current->request_path, $current->request_path) ?></td>
            <td class="right"><?php echo number_format($current->request_count) ?></td>
            <td class="right"><?php echo number_format($current->average_select_count) ?></td>
            <td class="right"><?php echo number_format($current->average_insert_count) ?></td>
            <td class="right"><?php echo number_format($current->average_update_count) ?></td>
            <td class="right"><?php echo number_format($current->average_delete_count) ?></td>
            <td class="right"><?php echo number_format($current->average_other_count) ?></td>
            <td class="right"><?php echo $current->average_process_time ?> sec</td>
            <td class="center"><?php echo $current->last_access_date ?></td>
            <td class="center"><?php echo $form->inputButton('詳細', array('class' => 'detail_action_statement btn', 'id' => 'path_' . $current->action_request_id . '_' . $current->request_path)) ?></td>
          </tr>
        <?php endforeach ?>
        </tbody>
      </table>
    </div>
    <div class="note right">
      ※AS: Avg SELECT / AI: Avg INSERT / AU: Avg UPDATE / AD: Avg DELETE / AO: Avg: OTHER
    </div>
    <h2>遅いステートメント</h2>
    <div class="latest-statement-list">
    <?php if ($slowStatements->count()): ?>
      <table id="tablesorter_action_statement_<?php echo $hash ?>" class="tablesorter">
        <colgroup>
          <col width="67%" />
          <col width="10%" />
          <col width="15%" />
          <col width="8%" />
        </colgroup>
        <thead>
          <tr>
            <th>ステートメント</th>
            <th>実行時間</th>
            <th>実行日時</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($slowStatements as $current): ?>
          <tr>
            <td><?php echo Delta_StringUtils::truncate($current->statement, 255) ?></td>
            <td class="right"><?php echo $current->process_time ?> sec</td>
            <td class="center"><?php echo $current->register_date ?></td>
            <td class="center">
              <?php echo $form->inputButton('詳細', array('class' => 'detail_statement btn', 'id' => 'sqlRequestId_' . $current->sql_request_id)) ?>
            </td>
          </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>発行されたステートメントはありません。</p>
    <?php endif ?>
    </div>
  <?php $form->close() ?>
  <div id="dynamic_dialog"></div>
<?php else: ?>
  <p>データがありません。</p>
<?php endif ?>
