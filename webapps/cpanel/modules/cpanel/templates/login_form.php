<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="Content-Style-Type" content="text/css" />
    <meta http-equiv="Content-Script-Type" content="text/javascript" />
    <title>MCP : ログイン</title>
    <?php echo $html->includeCSS('/assets/base/delta/css/base.css') ?>
  </head>
  <body>
    <header>
      <?php $html->includeTemplate('includes/header'); ?>
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
      <?php $html->includeTemplate('includes/footer') ?>
    </footer>
  </body>
</html>