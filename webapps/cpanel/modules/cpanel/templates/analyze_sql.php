<?php echo $html->includeJS('/assets/base/delta/js/analyze.js') ?>
<?php echo $html->includeJS('/assets/base/delta/js/jquery_setup.js') ?>
<script type="text/javascript">
$().ready( function () {
  $('#tablesorter_sql_default, #tablesorter_sql_prepared').tablesorter({
    sortList:[[<?php echo $orderIndex ?>,1]],
    widgets: ['zebra'],
    headers: {
      2: {sorter: 'number'},
      5: {sorter: false}
    }
  });
});
</script>

<?php if ($slowQueries->count()): ?>
  <?php echo $form->start() ?>
    <div class="right"><?php echo $form->select($orderName, array('3' => '平均実行時間が長い', '2' => '実行回数が多い')) ?></p>
    <?php echo $form->inputHidden('type', array('value' => $request->get('type'))) ?>
    <table id="tablesorter_sql_<?php echo $request->get('type') ?>" class="tablesorter">
      <colgroup>
        <col width="8%" />
        <col width="44%" />
        <col width="10%" />
        <col width="10%" />
        <col width="12%" />
        <col width="14%" />
      </colgroup>
      <thead>
        <tr>
          <th>ランク</th>
          <th>
            <?php if ($request->get('type') === 'default'): ?>
              ステートメント
            <?php else: ?>
              プリペアードステートメント
            <?php endif ?>
          </th>
          <th>実行回数</th>
          <th>平均時間</th>
          <th>最終アクセス時刻</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php $i = 0; ?>
      <?php foreach ($slowQueries as $current): ?>
      <?php $i++ ?>
        <tr id="row_sql_<?php echo $request->get('type') ?>_<?php echo $i ?>">
          <td class="center"><div id="col_rank_<?php echo $i ?>"><?php echo $i ?></div></td>
          <td><div id="col_statement_<?php echo $i ?>"><?php echo Delta_StringUtils::truncate($current->statement, 255) ?></div></td>
          <td class="right"><div id="col_request_count_<?php echo $i ?>"><?php echo number_format($current->request_count) ?></div></td>
          <td class="right"><div id="col_average_process_time_<?php echo $i ?>"><?php echo $current->average_process_time ?> sec</div></td>
          <td class="center"><div id="col_last_access_date_<?php echo $i ?>"><?php echo $current->last_access_date ?></div></td>
          <td class="center">
            <div id="col_action_<?php echo $i ?>">
              <?php echo $form->inputButton('詳細', array('class' => 'detail_statement btn', 'id' => 'hash_' . $i . '_' . $current->statement_hash)) ?>
              <?php echo $form->inputButton('削除', array('class' => 'delete_statement btn', 'id' => 'delete_' . $i . '_' . $current->statement_hash)) ?>
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
