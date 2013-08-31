<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta charset="UTF-8" />
    <title>DCP : ホーム</title>
    <?php echo $html->includeCSS('/assets/base/delta/css/base.css') ?>
    <!--[if lt IE 9]>
    <script src="/assets/js/html5shiv.js"></script>
    <![endif]-->  </head>
  <body>
    <header>
      <?php $html->includeView('/includes/header'); ?>
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
      <?php $html->includeView('/includes/footer') ?>
    </footer>
  </body>
</html>
