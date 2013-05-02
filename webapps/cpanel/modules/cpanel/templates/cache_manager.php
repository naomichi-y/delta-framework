<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>DCP : キャッシュ管理</title>
    <?php echo $html->includeCSS('/assets/base/delta/css/base.css') ?>
    <!--[if lt IE 9]>
    <script src="/assets/js/html5shiv.js"></script>
    <![endif]-->
  </head>
  <body>
    <header>
      <?php $html->includeTemplate('includes/header'); ?>
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
            <th class="left">テンプレートキャッシュ</th>
            <td class="right"><?php echo $templatesCacheSize ?> KB</td>
            <td><?php echo $form->inputSubmit('削除', array('name' => 'dispatchClearTemplatesCache', 'class' => 'btn')) ?></td>
          </tr>
          <tr>
            <th class="left">コンフィグレーションキャッシュ</th>
            <td class="right"><?php echo $yamlCacheSize ?> KB</td>
            <td><?php echo $form->inputSubmit('削除', array('name' => 'dispatchClearYamlTemplatesCache', 'class' => 'btn')) ?></td>
          </tr>
          <tr>
            <th class="left"></th>
            <td class="right"><?php echo $fileCacheSize + $templatesCacheSize + $yamlCacheSize ?> KB</td>
            <td><?php echo $form->inputSubmit('全て削除', array('name' => 'dispatchClearAllCache', 'class' => 'btn')) ?></td>
          </tr>
        </table>
      <?php echo $form->close() ?>
    </div>
    <footer>
      <?php $html->includeTemplate('includes/footer') ?>
    </footer>
  </body>
</html>
