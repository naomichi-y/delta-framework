<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta charset="UTF-8" />
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
        <p>以上で設定は完了です。今後はデータベースに送信されたクエリをパフォーマンスアナライザ画面から確認することができるようになります。</p>
        <?php echo $form->start('PerformanceAnalyzer') ?>
          <p class="center"><?php echo $form->inputSubmit('利用を開始する', array('class' => 'btn')) ?></p>
        <?php echo $form->close() ?>
      <?php endif; ?>
    </div>
    <footer>
      <?php $html->includeTemplate('includes/footer') ?>
    </footer>
  </body>
</html>
