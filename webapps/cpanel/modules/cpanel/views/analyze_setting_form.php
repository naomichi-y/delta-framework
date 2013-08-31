<?php echo $html->includeJS('/assets/base/delta/js/analyze.js') ?>
<?php echo $html->includeJS('/assets/base/delta/js/jquery_setup.js') ?>
<?php echo $form->start() ?>
  <h2>登録されているデータ</h2>
  <?php if ($beginDate): ?>
    <p><?php echo $beginDate ?> ～ <?php echo $endDate ?></p>
  <?php else: ?>
    <p>データがありません。</p>
  <?php endif ?>
  <h2>テーブルサイズ</h2>
  <table>
    <tr>
      <th>テーブル</th>
      <th>レコード数</th>
      <th>データサイズ</td>
    </tr>
    <?php foreach ($dataList as $tableName => $data): ?>
    <tr>
      <td><?php echo $tableName ?></td>
      <td class="right"><?php echo number_format($data['count']) ?></td>
      <td class="right"><?php echo Delta_NumberUtils::formatBytes($data['size']) ?></td>
    </tr>
    <?php endforeach ?>
  </table>
  <h2>アクション</h2>
  <p>
    <?php echo $form->inputButton('解析ログをリセットする', array('id' => 'reset', 'class' => 'btn')) ?>
    <?php echo $form->inputButton('アンインストール', array('id' => 'uninstall', 'class' => 'btn')) ?>
    <br />
    <span id="progress"></span>
    <span id="analyze_data_reset"></span>
  </p>
<?php echo $form->close() ?>
<div id="reset_confirm" title="解析ログ削除の確認" class="ui-helper-hidden">
  <p>解析ログを全て削除します。よろしいですか?</p>
</div>
<div id="uninstall_confirm" title="アンインストールの確認" class="ui-helper-hidden">
  <p>パフォーマンスアナライザをアンインストールします。よろしいですか?</p>
</div>
