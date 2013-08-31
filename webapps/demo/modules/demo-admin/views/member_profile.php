<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta charset="UTF-8" />
    <title>会員プロフィール</title>
    <?php echo $html->includeCSS('/assets/base/delta/css/base.css') ?>
    <!--[if lt IE 9]>
    <script src="/assets/js/html5shiv.js"></script>
    <![endif]-->
  </head>
  <body>
    <header>
      <?php $html->includeTemplate('includes/header'); ?>
      <h1>会員プロフィール</h1>
    </header>
    <div id="contents">
      <p class="right"><?php echo $html->link('戻る', array('action' => 'MemberList', 'page' => $request->get('page'))) ?></p>
      <?php echo $html->memberIconImage($request->get('memberId')) ?>
      <table>
        <colgroup>
          <col width="15%" />
          <col width="35%" />
          <col width="15%" />
          <col width="35%" />
        </colgroup>
        <tr>
          <th class="left">メールアドレス</th>
          <td colspan="3"><?php echo $mailAddress ?></td>
        </tr>
        <tr>
          <th class="left">名前</th>
          <td><?php echo $nickname ?></td>
          <th class="left">誕生日</th>
          <td>
            <?php echo $date->dateFormat($birthDate) ?>
            (<?php echo Delta_DateUtils::age($birthDate) ?> 歳)
          </td>
        </tr>
        <tr>
          <th class="left">血液型</th>
          <td><?php echo $html->getBloodName($blood) ?></td>
          <th class="left">趣味</th>
          <td><?php echo $html->getHobbyNameList($hobbies) ?></td>
        </tr>
        <tr>
          <th class="left">自己紹介</th>
          <td colspan="3"><?php echo nl2br($message) ?></td>
        </tr>
      </table>
    </div>
    <footer>
      <?php $html->includeTemplate('includes/footer') ?>
    </footer>
  </body>
</html>
