<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>管理画面</title>
    <?php echo $html->includeCSS('/assets/base/delta/css/base.css') ?>
  </head>
  <body>
    <header>
      <?php $html->includeTemplate('includes/header'); ?>
      <h1>管理画面</h1>
    </header>
    <div id="contents">
      <p class="right"><?php echo $html->link('ログアウト', 'Logout') ?></p>
      <ul>
        <li><?php echo $html->link('会員一覧', 'MemberList') ?></li>
      </ul>
    </div>
    <footer>
      <?php $html->includeTemplate('includes/footer') ?>
    </footer>
  </body>
</html>
