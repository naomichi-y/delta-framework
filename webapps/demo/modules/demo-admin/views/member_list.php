<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta charset="UTF-8" />
    <title>会員一覧</title>
    <?php echo $html->includeCSS('/assets/base/delta/css/base.css') ?>
    <!--[if lt IE 9]>
    <script src="/assets/js/html5shiv.js"></script>
    <![endif]-->
    <style>
      .col-avatar {
        width: 10%;
      }

      .col-nickname {
        width: 20%;
      }

      .col-message {
        width: 40%;
      }

      .col-register-date, .col-last-update-date {
        width: 15%;
      }
    </style>
  </head>
  <body>
    <header>
      <?php $html->includeTemplate('includes/header'); ?>
      <h1>会員一覧</h1>
    </header>
    <div id="contents">
      <p class="right"><?php echo $html->link('戻る', 'Home') ?></p>
      <?php if ($pager->hasRecord()): ?>
        <p class="right"><?php echo $pager->getNavigationLabel() ?></p>
        <table>
          <tr>
            <th class="col-avatar">アイコン</th>
            <th class="col-nickname"><?php echo $pager->getSortLabel('名前', 'nickname') ?></th>
            <th class="col-message"><?php echo $pager->getSortLabel('自己紹介', 'message') ?></th>
            <th class="col-register-date"><?php echo $pager->getSortLabel('登録日', 'register_date') ?></th>
            <th class="col-last-update-date"><?php echo $pager->getSortLabel('更新日', 'last_update_date') ?></th>
          </tr>
          <?php while ($current = $pager->next()): ?>
            <tr>
              <td><?php echo $html->memberIconImage($current['member_id']) ?></td>
              <td><?php echo $html->link($current['nickname'], array('action' => 'MemberProfile'), array(), array('query' => array('memberId' => $current['member_id'], 'page' => $request->get('page')))) ?></td>
              <td><?php echo $current['message'] ?></td>
              <td class="center"><?php echo $date->datetimeFormat($current['register_date']) ?></td>
              <td class="center"><?php echo $date->datetimeFormat($current['last_update_date'], '%Y-%m-%d %H:%M') ?></td>
            </tr>
          <?php endwhile; ?>
        </table>
        <p class="right"><?php echo $pager->getNavigationLabel() ?></p>
      <?php else: ?>
        <p>データがありません。</p>
      <?php endif; ?>
    </div>
    <footer>
      <?php $html->includeTemplate('includes/footer') ?>
    </footer>
  </body>
</html>
