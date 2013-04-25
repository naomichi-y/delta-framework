<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="Content-Style-Type" content="text/css" />
    <meta http-equiv="Content-Script-Type" content="text/javascript" />
    <title>MCP : DAO ジェネレータ</title>
    <?php echo $html->includeCSS('/assets/base/delta/css/base.css') ?>
  </head>
  <body>
    <header>
      <?php $html->includeTemplate('includes/header'); ?>
      <h1>delta control panel : DAO ジェネレータ</h1>
    </header>
    <div id="contents">
      <p class="right"><?php echo $html->link('戻る', 'GenerateDAOForm') ?></p>
      <h2>スケルトン生成完了</h2>
      <p>
        スケルトンファイルを一時ディレクトリ下に生成しました。
        生成されたファイルを libs 下に手動でコピーするか、もしくは画面下のデプロイボタンを押下して下さい。
        デプロイボタンを押下した場合、エンティティに関しては全て上書きされますが、DAO に関しては既に存在するファイルを上書きしません。
        またデプロイ完了後は一時ファイルを自動的に削除します。
      </p>
      <?php echo $form->start('DAODeploy') ?>
        <dl>
          <?php if ($entities->count()): ?>
            <dt>Entity</dt>
            <dd>
              <ul>
                <?php foreach ($entities as $current): ?>
                  <li>
                    <?php echo $current['relative'] ?>
                    <?php echo $form->inputHidden('entities[]', array('value' => $current['file'])) ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            </dd>
          <?php endif; ?>
          <?php if ($dataAccessObjects->count()): ?>
            <dt>DAO</dt>
            <dd>
              <ul>
                <?php foreach ($dataAccessObjects as $current): ?>
                  <li>
                    <?php echo $current['relative'] ?>
                    <?php echo $form->inputHidden('dataAccessObjects[]', array('value' => $current['file'])) ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            </dd>
          <?php endif; ?>
        </dl>
        <p class="center"><?php echo $form->inputSubmit('ファイルをデプロイする') ?></p>
      <?php echo $form->close() ?>
    </div>
    <footer>
      <?php $html->includeTemplate('includes/footer') ?>
    </footer>
  </body>
</html>