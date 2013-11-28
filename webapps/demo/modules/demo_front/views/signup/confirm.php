<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta charset="UTF-8" />
    <title>登録内容の確認</title>
    <?php echo $html->includeCSS('/assets/base/delta/css/base.css') ?>
    <!--[if lt IE 9]>
    <script src="/assets/js/html5shiv.js"></script>
    <![endif]-->
  </head>
  <body>
    <header>
      <?php $html->includeView('/includes/header'); ?>
      <h1>登録内容の確認</h1>
    </header>
    <div id="contents">
      <?php echo $form->start('dispatch') ?>
        <h2>アカウント情報</h2>
        <dl>
        <dt><?php echo $form->labelText('mail_address') ?></dt>
          <dd><p><?php echo $form->get('mail_address') ?></p></dd>
          <dt>パスワード</dt>
          <dd><p><?php echo $loginPasswordMask ?></p></dd>
        </dl>

        <h2>プロフィール</h2>
        <dl>
          <dt><?php echo $form->labelText('nickname') ?></dt>
          <dd><p><?php echo $form->get('nickname') ?></p></dd>
          <dt>生年月日</dt>
          <dd>
            <p>
              <?php echo $form->get('birth_year') ?> 年
              <?php echo $form->get('birth_month') ?> 月
              <?php echo $form->get('birth_day') ?> 日
              (<?php echo Delta_DateUtils::age($form->get('birth_year') . $form->get('birth_month') . $form->get('birth_day')) ?> 歳)
            </p>
          </dd>
          <dt><?php echo $form->labelText('blood') ?></dt>
          <dd><p><?php echo $html->getBloodName($form->get('blood')) ?></p></dd>
          <dt><?php echo $form->labelText('hobbies') ?></dt>
          <dd><p><?php echo $html->getHobbyNameList($form->get('hobbies')) ?></p></dd>
          <dt><?php echo $form->labelText('message') ?></dt>
          <dd><p><?php echo nl2br($form->get('message')) ?></p></dd>
          <dt>アイコン</dt>
          <dd>
            <?php if ($hasUpload): ?>
              <p><?php echo $html->image(array('action' => 'previewAvatar'), array('alt' => 'アイコン')) ?></p>
            <?php else: ?>
              <p>アップロードされていません。</p>
            <?php endif ?>
          </dd>
        </dl>
        <p class="center">
          <?php echo $form->inputSubmit('修正', array('name' => 'dispatchForm', 'class' => 'btn')) ?>
          <?php echo $form->inputSubmit('登録', array('name' => 'dispatchRegister', 'class' => 'btn')) ?>
        </p>
        <?php echo $form->inputHIdden('mail_address') ?>
        <?php echo $form->inputHIdden('login_password') ?>
        <?php echo $form->inputHIdden('login_password_verify') ?>
        <?php echo $form->inputHIdden('nickname') ?>
        <?php echo $form->inputHIdden('birth_year') ?>
        <?php echo $form->inputHIdden('birth_month') ?>
        <?php echo $form->inputHIdden('birth_day') ?>
        <?php echo $form->inputHIdden('blood') ?>
        <?php echo $form->inputHIddenCheckboxes('hobbies') ?>
        <?php echo $form->inputHIdden('message') ?>
      <?php echo $form->close() ?>
    </div>
    <footer>
      <?php $html->includeView('/includes/footer') ?>
    </footer>
  </body>
</html>
