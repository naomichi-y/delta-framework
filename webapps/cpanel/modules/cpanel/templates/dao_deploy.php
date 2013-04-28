<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="Content-Style-Type" content="text/css" />
    <meta http-equiv="Content-Script-Type" content="text/javascript" />
    <title>DCP : DAO ジェネレータ</title>
    <?php echo $html->includeCSS('/assets/base/delta/css/base.css') ?>
  </head>
  <body>
    <header>
      <?php $html->includeTemplate('includes/header'); ?>
      <h1>delta control panel : DAO ジェネレータ</h1>
    </header>
    <div id="contents">
      <p class="right"><?php echo $html->link('戻る', 'GenerateDAOForm') ?></p>
      <h2>スケルトンファイルのデプロイ完了</h2>
      <p>デプロイが正常に完了しました。</p>
      <dl>
        <dt>エンティティ</dt>
        <dd>
          <?php if ($entities->count()): ?>
            <?php echo $html->ul($entities) ?>
          <?php else: ?>
            更新対象ファイルがありません。
          <?php endif; ?>
        </dd>
        <dt>DAO</dt>
        <dd>
          <?php if ($dataAccessObjects->count()): ?>
            <?php echo $html->ul($dataAccessObjects) ?>
          <?php else: ?>
            更新対象ファイルがありません。
          <?php endif; ?>
        </dd>
      </dl>
    </div>
    <footer>
      <?php $html->includeTemplate('includes/footer') ?>
    </footer>
  </body>
</html>
