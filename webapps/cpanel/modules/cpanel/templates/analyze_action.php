<?php echo $html->includeJS('/assets/base/delta/js/analyze.js') ?>
<?php echo $html->includeJS('/assets/base/delta/js/jquery_setup.js') ?>
<script type="text/javascript">
$().ready( function () {
  $('#tablesorter_action').tablesorter({
    sortList:[[<?php echo $orderIndex ?>,1]],
    widgets: ['zebra'],
    headers: {
      4: {sorter:'number'},
      5: {sorter:'number'},
      7: {sorter:false}
    }
  });
});
</script>
<?php if ($slowActions->count()): ?>
  <?php echo $form->start() ?>
    <div class="right"><?php echo $form->select('orderByAction', array('4' => '平均実行時間が長い', '3' => '表示回数が多い', '5' => '実行時間が長い')) ?></p>
    <table id="tablesorter_action" class="tablesorter">
      <colgroup>
        <col width="8%" />
        <col width="13%" />
        <col width="20%" />
        <col width="10%" />
        <col width="10%" />
        <col width="10%" />
        <col width="17%" />
        <col width="12%" />
      </colgroup>
      <thead>
        <tr>
          <th>ランク</th>
          <th>モジュール名</th>
          <th>アクション名</th>
          <th>表示回数</th>
          <th>平均時間</th>
          <th>最遅時間</th>
          <th>最終アクセス時刻</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php $i = 0; ?>
      <?php foreach ($slowActions as $current): ?>
      <?php $i++ ?>
        <tr id="row_action_<?php echo $i ?>">
          <td class="center"><div id="col_rank_<?php echo $i ?>"><?php echo $i ?></div></td>
          <td><div id="col_module_name_<?php echo $i ?>"><?php echo $current->module_name ?></div></td>
          <td><div id="col_action_name_<?php echo $i ?>"><?php echo $current->action_name ?></div></td>
          <td class="right"><div id="col_request_count_<?php echo $i ?>"><?php echo number_format($current->request_count) ?></div></td>
          <td class="right"><div id="col_average_process_time_<?php echo $i ?>"><?php echo $current->average_process_time ?> sec</div></td>
          <td class="right"><div id="col_max_time_<?php echo $i ?>"><?php echo $current->max_process_time ?> sec</div></td>
          <td class="center"><div id="col_last_access_date_<?php echo $i ?>"><?php echo $current->last_access_date ?></div></td>
          <td class="center">
            <div id="col_action_<?php echo $i ?>">
              <?php echo $form->inputButton('詳細', array('class' => 'detail_action btn', 'id' => 'detail_' . $i . '_' . $current->module_name . '::' . $current->action_name)) ?>
              <?php echo $form->inputButton('削除', array('class' => 'delete_action btn', 'id' => 'delete_' . $i . '_' . $current->module_name . '::' . $current->action_name)) ?>
            </div>
          </td>
        </tr>
      <?php endforeach ?>
      </tbody>
    </table>
  <?php $form->close() ?>
  <div id="dynamic_dialog"></div>
<?php else: ?>
  <p>データがありません。</p>
<?php endif ?>
