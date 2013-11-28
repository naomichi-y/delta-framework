<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta charset="UTF-8" />
    <title>管理画面</title>
    <?php echo $html->includeCSS('/assets/base/delta/css/base.css') ?>
    <!--[if lt IE 9]>
    <script src="/assets/js/html5shiv.js"></script>
    <![endif]-->
  </head>
  <body>
    <header>
      <?php $html->includeView('/includes/header'); ?>
      <h1>管理画面</h1>
    </header>
    <div id="contents">
      <p class="right"><?php echo $html->link('ログアウト', array('route' => 'controllerRoute', 'action' => 'logout')) ?></p>
      <ul>
        <li><?php echo $html->link('会員一覧', array('route' => 'actionRoute', 'controller' => 'member', 'action' => 'list')) ?></li>
      </ul>
    </div>
    <footer>
      <?php $html->includeView('/includes/footer') ?>
    </footer>
  </body>
</html>
