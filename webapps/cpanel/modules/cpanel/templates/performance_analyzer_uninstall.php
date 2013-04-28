<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="Content-Style-Type" content="text/css" />
    <meta http-equiv="Content-Script-Type" content="text/javascript" />
    <title>DCP : パフォーマンスアナライザ</title>
    <?php echo $html->includeCSS('/assets/base/delta/css/base.css') ?>
  </head>
  <body>
    <header>
      <?php $html->includeTemplate('includes/header'); ?>
      <h1>delta control panel : パフォーマンスアナライザ</h1>
    </header>
    <div id="contents">
      <p class="right"><?php echo $html->link('戻る', 'Home') ?></p>
      <p>パフォーマンスアナライザのアンインストールが成功しました。</p>
    </div>
    <footer>
      <?php $html->includeTemplate('includes/footer') ?>
    </footer>
  </body>
</html>
