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
      <?php $html->includeTemplate('includes/header'); ?>
      <h1>登録内容の確認</h1>
    </header>
    <div id="contents">
      <?php echo $form->start('MemberRegisterDispatch') ?>
        <h2>アカウント情報</h2>
        <dl>
          <dt>メールアドレス</dt>
          <dd><p><?php echo $form->get('mailAddress') ?></p></dd>
          <dt>パスワード</dt>
          <dd><p><?php echo $loginPasswordMask ?></p></dd>
        </dl>

        <h2>プロフィール</h2>
        <dl>
          <dt>名前</dt>
          <dd><p><?php echo $form->get('nickname') ?></p></dd>
          <dt>誕生日</dt>
          <dd>
            <p>
              <?php echo $form->get('birth.year') ?> 年
              <?php echo $form->get('birth.month') ?> 月
              <?php echo $form->get('birth.day') ?> 日
              (<?php echo Delta_DateUtils::age($form->get('birth.year') . $form->get('birth.month') . $form->get('birth.day')) ?> 歳)
            </p>
          </dd>
          <dt>血液型</dt>
          <dd><p><?php echo $html->getBloodName($form->get('blood')) ?></p></dd>
          <dt>趣味</dt>
          <dd><p><?php echo $html->getHobbyNameList($form->get('hobbies')) ?></p></dd>
          <dt>自己紹介</dt>
          <dd><p><?php echo nl2br($form->get('message')) ?></p></dd>
          <dt>アイコン</dt>
          <dd>
            <?php if ($hasUpload): ?>
              <p><?php echo $html->image(array('action' => 'ShowPreviewImage'), array('class' => 'border', 'alt' => 'アイコン')) ?></p>
            <?php else: ?>
              <p>アップロードされていません。</p>
            <?php endif ?>
          </dd>
        </dl>
        <p class="center">
          <?php echo $form->inputSubmit('修正', array('name' => 'dispatchMemberRegisterForm', 'class' => 'btn')) ?>
          <?php echo $form->inputSubmit('登録', array('name' => 'dispatchMemberRegister', 'class' => 'btn')) ?>
        </p>
        <?php echo $form->requestDataToInputHiddens() ?>
      <?php echo $form->close() ?>
    </div>
    <footer>
      <?php $html->includeTemplate('includes/footer') ?>
    </footer>
  </body>
</html>
