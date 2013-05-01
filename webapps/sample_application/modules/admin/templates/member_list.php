<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>会員一覧</title>
    <?php echo $html->includeCSS('/assets/base/delta/css/base.css') ?>
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
          <colgroup>
            <col width="10%" />
            <col width="20%" />
            <col width="40%" />
            <col width="15%" />
            <col width="15%" />
          </colgroup>
          <tr>
            <th>アイコン</th>
            <th><?php echo $pager->getSortLabel('名前', 'nickname') ?></th>
            <th><?php echo $pager->getSortLabel('自己紹介', 'message') ?></th>
            <th><?php echo $pager->getSortLabel('登録日', 'register_date') ?></th>
            <th><?php echo $pager->getSortLabel('更新日', 'last_update_date') ?></th>
          </tr>
          <?php while ($current = $pager->next()): ?>
            <tr>
              <td><?php echo $html->memberIconImage($current['member_id']) ?></td>
              <td><?php echo $html->link($current['nickname'], array('action' => 'MemberProfile', 'memberId' => $current['member_id'], 'page' => $request->get('page')), array()) ?></td>
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
