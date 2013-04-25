<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="Content-Style-Type" content="text/css" />
    <meta http-equiv="Content-Script-Type" content="text/javascript" />
    <title>MCP : ホーム</title>
    <?php echo $html->includeCSS('/assets/base/delta/css/base.css') ?>
  </head>
  <body>
    <header>
      <?php $html->includeTemplate('includes/header'); ?>
      <h1>delta control panel : ホーム</h1>
    </header>
    <div id="contents">
      <p class="right"><a href="/cpanel/logout.do">ログアウト</a></p>
      <ul>
        <li><?php echo $html->link('キャッシュ管理', 'CacheManager') ?></li>
        <li><?php echo $html->link('パフォーマンスアナライザ', 'PerformanceAnalyzer') ?></li>
        <li><?php echo $html->link('DAO ジェネレータ', 'GenerateDAOForm') ?></li>
      </ul>
    </div>
    <footer>
      <?php $html->includeTemplate('includes/footer') ?>
    </footer>
  </body>
</html>
