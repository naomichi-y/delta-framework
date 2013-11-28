<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta charset="UTF-8" />
    <title>管理者認証</title>
    <?php echo $html->includeCSS('/assets/base/delta/css/base.css') ?>
    <!--[if lt IE 9]>
    <script src="/assets/js/html5shiv.js"></script>
    <![endif]-->
  </head>
  <body>
    <header>
      <?php $html->includeView('/includes/header'); ?>
      <h1>管理者認証</h1>
    </header>
    <div id="contents">
      <?php echo $form->start('login') ?>
        <p>管理者アカウントの ID/PW は admin/admin です。さっそくログインしてみましょう。</p>
        <?php echo $form->logicError() ?>
        <?php echo $form->label('login_id') ?>
        <?php echo $form->inputTextAlphabet('login_id', array('size' => 20)) ?>
        <?php echo $form->label('login_password') ?>
        <?php echo $form->inputPassword('login_password', array('size' => 20)) ?>
        <p><?php echo $form->inputSubmit('ログイン', array('class' => 'btn')) ?></p>
      <?php echo $form->close() ?>
    </div>
    <footer>
      <?php $html->includeView('/includes/footer') ?>
    </footer>
  </body>
</html>
