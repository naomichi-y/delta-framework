<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta charset="UTF-8" />
    <title>DCP : パフォーマンスアナライザ</title>
    <?php echo $html->includeCSS('/assets/base/jquery-ui-1.8.16.custom/css/smoothness/jquery-ui-1.8.16.custom.css') ?>
    <?php echo $html->includeCSS('/assets/base/tablesorter/style.css') ?>
    <?php echo $html->includeCSS('/assets/base/delta/css/base.css') ?>
    <?php echo $html->includeCSS('/assets/base/delta/css/jquery_setup.css') ?>
    <?php echo $html->includeJS('/assets/base/jquery-ui-1.8.16.custom/js/jquery-1.6.2.min.js') ?>
    <?php echo $html->includeJS('/assets/base/jquery-ui-1.8.16.custom/js/jquery-ui-1.8.16.custom.min.js') ?>
    <?php echo $html->includeJS('/assets/base/jquery-ui-1.8.16.custom/development-bundle/external/jquery.cookie.js') ?>
    <?php echo $html->includeJS('/assets/base/tablesorter/jquery.tablesorter.min.js') ?>
    <?php echo $html->includeJS('/assets/base/delta/js/analyze.js') ?>
    <!--[if lt IE 9]>
    <script src="/assets/js/html5shiv.js"></script>
    <![endif]-->
  </head>

  <body>
    <header>
      <?php $html->includeTemplate('includes/header'); ?>
      <h1>delta control panel : パフォーマンスアナライザ</h1>
    </header>
    <div id="contents">
      <p class="right"><?php echo $html->link('戻る', 'Home') ?></p>
      <?php echo $form->start() ?>
        <div class="search">
          <?php echo $form->select('module', $modules, NULL, array('fieldTag' => '\1')) ?>
          <?php echo $form->inputText('from', array('size' => 10), array('fieldTag' => '\1')) ?>
          ～
          <?php echo $form->inputText('to', array('size' => 10), array('fieldTag' => '\1')) ?>
          <?php echo $form->inputSubmit('検索', array('name' => 'search', 'class' => 'btn')) ?>
        </div>
      <?php echo $form->close() ?>
      <div id="tabs">
        <ul>
          <li><?php echo $html->link('アクションの解析', array('action' => 'AnalyzeAction'), NULL, array('query' => array('target' => $form->get('module'), 'from' => $form->get('from'), 'to' => $form->get('to')))) ?></li>
          <li><?php echo $html->link('SQL の解析', array('action' => 'AnalyzeSQL'), NULL, array('query' => array('target' => $form->get('module'), 'type' => 'default', 'from' => $form->get('from'), 'to' => $form->get('to')))) ?></li>
          <li><?php echo $html->link('SQL の解析 (プリペアードステートメント)', array('action' => 'AnalyzeSQL'), NULL, array('query' => array('target' => $form->get('module'), 'type' => 'prepared', 'from' => $form->get('from'), 'to' => $form->get('to')))) ?></li>
          <li><?php echo $html->link('SQL レポート', array('action' => 'AnalyzeSQLReport'), NULL, array('query' => array('target' => $form->get('module'), 'from' => $form->get('from'), 'to' => $form->get('to')))) ?></li>
          <li><?php echo $html->link('設定', 'AnalyzeSettingForm') ?></li>
        </ul>
      </div>
    </div>
    <footer>
      <?php $html->includeTemplate('includes/footer') ?>
    </footer>
  </body>
</html>
