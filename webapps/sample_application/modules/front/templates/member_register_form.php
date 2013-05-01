<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>会員登録フォーム</title>
    <?php echo $html->includeCSS('/assets/base/delta/css/base.css') ?>
  </head>
  <body>
    <header>
      <?php $html->includeTemplate('includes/header'); ?>
      <h1>会員登録フォーム</h1>
    </header>
    <div id="contents">
      <?php echo $form->startMultipart('MemberRegisterConfirm') ?>
        <?php echo $html->errors(FALSE) ?>
        <?php echo $form->containFieldErrors() ?>

        <h2>アカウント情報</h2>
        <?php echo $form->inputTextAlphabet('mailAddress', array('size' => 30), array('label' => 'メールアドレス', 'required' => TRUE)) ?>
        <?php echo $form->inputPassword('loginPassword', array('size' => 10), array('label' => 'パスワード (英数字 4～8 文字)', 'required' => TRUE)) ?>
        <?php echo $form->inputPassword('loginPasswordVerify', array('size' => 10), array('label' => 'パスワード (確認)', 'required' => TRUE)) ?>

        <h2>プロフィール</h2>
        <?php echo $form->inputText('nickname', array('size' => 30), array('label' => '名前', 'required' => TRUE)); ?>
        <?php echo $form->selectDate(array('fieldPrefix' => 'birth', 'separator' => array('年', '月', '日')), array(), array('label' => '誕生日', 'required' => TRUE)) ?>
        <?php echo $form->inputRadios('blood', $site->get('blood'), NULL, array('label' => '血液型', 'required' => TRUE)); ?>
        <?php echo $form->inputCheckboxes('hobbies', $site->get('hobby'), NULL, array('label' => '趣味', 'required' => TRUE)); ?>
        <?php echo $form->textarea('message', array('rows' => 4, 'cols' => 60), array('label' => '自己紹介', 'required' => TRUE)); ?>
        <?php echo $form->inputFile('icon', array('size' => 40), array('label' => 'アイコン')) ?>
        <p><?php echo $form->inputSubmit('確認', array('class' => 'btn')) ?></p>
      <?php echo $form->close() ?>
    </div>
    <footer>
      <?php $html->includeTemplate('includes/footer') ?>
    </footer>
  </body>
</html>
