<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="Content-Style-Type" content="text/css" />
    <meta http-equiv="Content-Script-Type" content="text/javascript" />
    <title>MCP : パフォーマンスアナライザのインストール</title>
    <?php echo $html->includeCSS('/assets/base/delta/css/base.css') ?>
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
          controller:<br />
          &nbsp;&nbsp;listener: Delta_PerformanceListener
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
