<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta charset="UTF-8" />
    <title>DCP : ログイン</title>
    <?php echo $html->includeCSS('/assets/base/delta/css/base.css') ?>
    <!--[if lt IE 9]>
    <script src="/assets/js/html5shiv.js"></script>
    <![endif]-->
  </head>
  <body>
    <header>
      <?php $html->includeView('/includes/header'); ?>
      <h1>delta control panel : ログイン</h1>
    </header>
    <div id="contents">
      <?php echo $form->start('Login') ?>
        <?php echo $html->errors(FALSE) ?>
        <?php echo $form->inputPassword('loginPassword', array('size' => 20), array('label' => 'パスワード')) ?>
        <p><?php echo $form->inputSubmit('ログイン', array('class' => 'btn')) ?></p>
      <?php echo $form->close() ?>
    </div>
    <footer>
      <?php $html->includeView('/includes/footer') ?>
    </footer>
  </body>
</html>
