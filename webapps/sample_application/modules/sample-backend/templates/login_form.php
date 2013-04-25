<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="Content-Style-Type" content="text/css" />
    <meta http-equiv="Content-Script-Type" content="text/javascript" />
    <title>管理者認証</title>
    <?php echo $html->includeCSS('/assets/base/delta/css/base.css') ?>
  </head>
  <body>
    <header>
      <?php $html->includeTemplate('includes/header'); ?>
      <h1>管理者認証</h1>
    </header>
    <div id="contents">
      <p class="right"><?php echo $html->link('スタート画面に戻る', array('router' => 'moduleEntry', 'module' => 'sample-frontend')) ?></p>
      <?php echo $form->start('Login') ?>
        <p>管理者アカウントの ID/PW は admin/admin です。さっそくログインしてみましょう。</p>
        <?php echo $html->errors(FALSE) ?>
        <?php echo $form->inputTextAlphabet('loginId', array('size' => 20), array('label' => 'ログイン ID')) ?>
        <?php echo $form->inputPassword('loginPassword', array('size' => 20), array('label' => 'パスワード')) ?>
        <p><?php echo $form->inputSubmit('ログイン', array('class' => 'btn')) ?></p>
      <?php echo $form->close() ?>
    </div>
    <footer>
      <?php $html->includeTemplate('includes/footer') ?>
    </footer>
  </body>
</html>
