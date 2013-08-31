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
      <?php $html->includeView('/includes/header'); ?>
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
      <p>DPA を利用するにはデータベース (MySQL) の設定が必要となります。以下のコードを参考にパフォーマンスアナライザに必要な属性を config/application.yml に追記して下さい。</p>
      <div class="lang-yaml">
        <code>
        # データベース接続情報<br />
        database:<br />
        &nbsp;&nbsp;default:<br />
        &nbsp;&nbsp;&nbsp;&nbsp;dsn: "mysql:host=localhost; dbname={DB_NAME}; port={PORT}"<br />
        &nbsp;&nbsp;&nbsp;&nbsp;user: "{DB_USER}"<br />
        &nbsp;&nbsp;&nbsp;&nbsp;password: "{DB_PASSWORD}"<br /><br />
        # パフォーマンスアナライザの設定<br />
        observer:<br />
        &nbsp;&nbsp;listeners:<br />
        &nbsp;&nbsp;&nbsp;&nbsp;# リスナー ID (固定)<br />
        &nbsp;&nbsp;&nbsp;&nbsp;performanceListener:<br />
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;class: Delta_PerformanceListener<br />
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;dataSource: default
        </code>
      </div>
      <p>ファイル更新後、「DPA をインストールする」ボタンを押してインストールを完了させましょう。</p>
      <?php echo $form->start('PerformanceAnalyzerInstall') ?>
        <p class="center"><?php echo $form->inputSubmit('DPA をインストールする', array('class' => 'btn')) ?></p>
      <?php echo $form->close() ?>
    </div>
    <footer>
      <?php $html->includeView('/includes/footer') ?>
    </footer>
  </body>
</html>
