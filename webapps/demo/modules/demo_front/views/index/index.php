<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta charset="UTF-8" />
    <title>デモンストレーションを始めましょう!</title>
    <?php echo $html->includeCSS('/assets/base/delta/css/base.css') ?>
    <!--[if lt IE 9]>
    <script src="/assets/js/html5shiv.js"></script>
    <![endif]-->
  </head>
  <body>
    <header>
      <?php $html->includeView('/includes/header'); ?>
      <h1>デモンストレーションを始めましょう!</h1>
    </header>
    <div id="contents">
      <?php echo $html->messages() ?>
      <ul>
        <li><?php echo $html->link('会員登録を行う', array('route' => 'actionRoute', 'controller' => 'Signup', 'action' => 'form')) ?></li>
        <li><?php echo $html->link('管理者画面にログインする', array('route' => 'controllerRoute', 'module' => 'demo_admin', 'action' => 'loginForm')) ?></li>
      </ul>
    </div>
    <footer>
      <?php $html->includeView('/includes/footer') ?>
    </footer>
  </body>
</html>
