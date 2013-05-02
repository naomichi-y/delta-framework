<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>DCP : パフォーマンスアナライザのインストール</title>
    <?php echo $html->includeCSS('/assets/base/delta/css/base.css') ?>
    <!--[if lt IE 9]>
    <script src="/assets/js/html5shiv.js"></script>
    <![endif]-->
  </head>
  <body>
    <header>
      <?php $html->includeTemplate('includes/header'); ?>
      <h1>delta control panel : パフォーマンスアナライザのインストール</h1>
    </header>
    <div id="contents">
      <p class="right"><?php echo $html->link('戻る', 'Home') ?></p>
      <h2>インストール結果</h2>
      <?php echo $html->messages() ?>
      <?php echo $html->errors() ?>
      <?php if (!$html->hasError()): ?>
        <h2>インストール後の設定</h2>
        <p>config/application.yml に以下のコードを追加して下さい。</p>
        <div class="lang_yaml">
          <code>
          observer:<br />
          &nbsp;&nbsp;listeners:<br />
          &nbsp;&nbsp;&nbsp;&nbsp;- class: Delta_PerformanceListener
          </code>
        </div>
      <?php endif; ?>
      <?php echo $form->start('PerformanceAnalyzer') ?>
        <p class="center"><?php echo $form->inputSubmit('利用を開始する', array('class' => 'btn')) ?></p>
      <?php echo $form->close() ?>
    </div>
    <footer>
      <?php $html->includeTemplate('includes/footer') ?>
    </footer>
  </body>
</html>
