<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta charset="UTF-8" />
    <title>DCP : キャッシュ管理</title>
    <?php echo $html->includeCSS('/assets/base/delta/css/base.css') ?>
    <!--[if lt IE 9]>
    <script src="/assets/js/html5shiv.js"></script>
    <![endif]-->
  </head>
  <body>
    <header>
      <?php $html->includeView('/includes/header'); ?>
      <h1>delta control panel : キャッシュ管理</h1>
    </header>
    <div id="contents">
      <p class="right"><?php echo $html->link('戻る', 'Home') ?></p>
      <?php echo $form->start('CacheClearDispatcher') ?>
        <?php echo $html->messages() ?>
        <table>
          <colgroup>
            <col width="30%">
            <col width="10%">
            <col width="60%">
          </colgroup>
          <tr>
            <th class="left">クラスロードキャッシュ</th>
            <td class="right"><?php echo $fileCacheSize ?> KB</td>
            <td><?php echo $form->inputSubmit('削除', array('name' => 'dispatchClearFileCache', 'class' => 'btn')) ?></td>
          </tr>
          <tr>
            <th class="left">ビューキャッシュ</th>
            <td class="right"><?php echo $viewsCacheSize ?> KB</td>
            <td><?php echo $form->inputSubmit('削除', array('name' => 'dispatchClearViewsCache', 'class' => 'btn')) ?></td>
          </tr>
          <tr>
            <th class="left">コンフィグレーションキャッシュ</th>
            <td class="right"><?php echo $yamlCacheSize ?> KB</td>
            <td><?php echo $form->inputSubmit('削除', array('name' => 'dispatchClearYamlViewsCache', 'class' => 'btn')) ?></td>
          </tr>
          <tr>
            <th class="left"></th>
            <td class="right"><?php echo $fileCacheSize + $viewsCacheSize + $yamlCacheSize ?> KB</td>
            <td><?php echo $form->inputSubmit('全て削除', array('name' => 'dispatchClearAllCache', 'class' => 'btn')) ?></td>
          </tr>
        </table>
      <?php echo $form->close() ?>
    </div>
    <footer>
      <?php $html->includeView('/includes/footer') ?>
    </footer>
  </body>
</html>
