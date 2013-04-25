<style type="text/css">
div#graph_report {
  height: 180px;
}
</style>
<?php echo $html->includeJS('/assets/base/highcharts-2.1.9/highcharts.js') ?>
<?php echo $html->includeJS('/assets/base/delta/js/jquery_setup.js') ?>
<?php echo $html->includeJS('/assets/base/delta/js/analyze.js') ?>
<?php echo $html->includeJS('/assets/base/delta/js/analyze_highcharts.js') ?>
<script type="text/javascript">
$(document).ready(function(){
  $('#tablesorter_sql_report').tablesorter({
    sortList:[[0,1]],
    widgets: ['zebra'],
    headers: {
      1: {sorter:'number'},
      2: {sorter:'number'},
      3: {sorter:'number'},
      4: {sorter:'number'},
      5: {sorter:'number'},
      6: {sorter:'number'},
      7: {sorter:'number'}
    }
  });
});
</script>
<?php if (sizeof($dailySummary)): ?>
  <div id="graph_report"></div>
  <table id="tablesorter_sql_report" class="tablesorter">
    <colgroup>
      <col width="16%" />
      <col width="12%" />
      <col width="12%" />
      <col width="12%" />
      <col width="12%" />
      <col width="12%" />
      <col width="12%" />
      <col width="12%" />
    </colgroup>
    <thead>
      <tr>
        <th>対象日</th>
        <th>SELECT</th>
        <th>INSERT</th>
        <th>UPDATE</th>
        <th>DELETE</th>
        <th>OTHER</th>
        <th>総実行回数</th>
        <th>総実行時間</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($dailySummary as $date => $current): ?>
      <tr>
        <td class="center"><?php echo $date ?></td>
        <td class="right"><?php echo number_format($current['select']['execute_count']) ?></td>
        <td class="right"><?php echo number_format($current['insert']['execute_count']) ?></td>
        <td class="right"><?php echo number_format($current['update']['execute_count']) ?></td>
        <td class="right"><?php echo number_format($current['delete']['execute_count']) ?></td>
        <td class="right"><?php echo number_format($current['other']['execute_count']) ?></td>
        <td class="right"><?php echo number_format($current['select']['execute_count'] + $current['insert']['execute_count'] + $current['update']['execute_count'] + $current['delete']['execute_count'] + $current['other']['execute_count']) ?></td>
        <td class="right"><?php echo ($current['select']['total_process_time'] + $current['insert']['total_process_time'] + $current['update']['total_process_time'] + $current['delete']['total_process_time'] + $current['other']['total_process_time']) ?> sec</td>
      </tr>
    <?php endforeach ?>
    </tbody>
  </table>
<?php else: ?>
  <p>データがありません。</p>
<?php endif ?>
