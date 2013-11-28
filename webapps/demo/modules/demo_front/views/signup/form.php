<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta charset="UTF-8" />
    <title>会員登録フォーム</title>
    <?php echo $html->includeCSS('/assets/base/delta/css/base.css') ?>
    <!--[if lt IE 9]>
    <script src="/assets/js/html5shiv.js"></script>
    <![endif]-->
  </head>
  <body>
    <header>
      <?php $html->includeView('/includes/header'); ?>
      <h1>会員登録フォーム</h1>
    </header>
    <div id="contents">
      <?php echo $form->startMultipart('confirm') ?>
        <?php echo $form->logicError() ?>
        <?php echo $form->errorFIelds() ?>

        <h2>アカウント情報</h2>
        <?php echo $form->label('mail_address') ?>
        <?php echo $form->inputText('mail_address', array('size' => 30)) ?>
        <?php echo $form->label('login_password') ?>
        <?php echo $form->inputPassword('login_password', array('size' => 10)) ?>
        <?php echo $form->label('login_password_verify') ?>
        <?php echo $form->inputPassword('login_password_verify', array('size' => 10)) ?>

        <h2>プロフィール</h2>
        <?php echo $form->label('nickname') ?>
        <?php echo $form->inputText('nickname', array('size' => 30)); ?>
        <span>生年月日</span>
        <?php echo $form->selectDate(array('fieldPrefix' => 'birth_', 'separator' => array('年', '月', '日'))) ?>
        <?php echo $form->label('blood') ?>
        <?php echo $form->inputRadios('blood', $site->get('blood')); ?>
        <?php echo $form->label('hobbies') ?>
        <?php echo $form->inputCheckboxes('hobbies', $site->get('hobby')); ?>
        <?php echo $form->label('message') ?>
        <?php echo $form->textarea('message', array('rows' => 4, 'cols' => 60)); ?>
        <?php echo $form->label('avatar') ?>
        <?php echo $form->inputFile('avatar', array('size' => 40)) ?>
        <p><?php echo $form->inputSubmit('確認', array('class' => 'btn')) ?></p>
      <?php echo $form->close() ?>
    </div>
    <footer>
      <?php $html->includeView('/includes/footer') ?>
    </footer>
  </body>
</html>
