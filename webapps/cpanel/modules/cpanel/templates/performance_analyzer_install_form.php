<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta charset="UTF-8" />
    <title>DCP : パフォーマンスアナライザ</title>
    <?php echo $html->includeCSS('/assets/base/delta/css/base.css') ?>
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
      <h2>パフォーマンスアナライザついて</h2>
      <p>
        DPA (delta performance analyzer) はアプリケーション開発者のためのパフォーマンス計測ツールです。
        フレームワークはクライアントからの要求を解析し、開発者が設計したビジネスロジックを処理した後にレスポンスを返します。
        DPA はビジネスロジックで必要とされる実行コストをグラフィカルに確認することができるため、アプリケーションを開発する上でボトルネックとなるロジックを見つけ出す際に非常に役立つでしょう。
      </p>
      <h2>インストール方法</h2>
      <p>DPA を利用するには MySQL のデータベースが必要となります。接続情報を application.yml の 'database.default' に定義定した後、「インストール」ボタンを実行して下さい。</p>
      <?php echo $form->start('PerformanceAnalyzerInstall') ?>
        <p class="center"><?php echo $form->inputSubmit('DPA をインストールする', array('class' => 'btn')) ?></p>
      <?php echo $form->close() ?>
    </div>
    <footer>
      <?php $html->includeTemplate('includes/footer') ?>
    </footer>
  </body>
</html>
