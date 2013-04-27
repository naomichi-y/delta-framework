<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="Content-Style-Type" content="text/css" />
    <meta http-equiv="Content-Script-Type" content="text/javascript" />
    <title>デモンストレーションを始めましょう!</title>
    <?php echo $html->includeCSS('/assets/base/delta/css/base.css') ?>
  </head>
  <body>
    <header>
      <?php $html->includeTemplate('includes/header'); ?>
      <h1>デモンストレーションを始めましょう!</h1>
    </header>
    <div id="contents">
      <?php echo $html->messages() ?>
      <ul>
        <li><?php echo $html->link('会員登録を行う', 'MemberRegisterForm') ?></li>
        <li><?php echo $html->link('管理者画面にログインする', array('router' => 'moduleEntry', 'module' => 'admin')) ?></li>
      </ul>
    </div>
    <footer>
      <?php $html->includeTemplate('includes/footer') ?>
    </footer>
  </body>
</html>
