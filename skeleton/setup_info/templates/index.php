<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="Content-Style-Type" content="text/css" />
    <meta http-equiv="Content-Script-Type" content="text/javascript" />
    <title>設定情報の確認</title>
    <?php echo $html->includeCSS('/assets/base/delta/css/base.css') ?>
  </head>
  <body>
    <header>
      <div class="row">
        <?php echo $html->link($html->image('/assets/base/delta/images/logo.png', array('alt' => 'delta')), 'Start', array(), array('escape' => FALSE)) ?>
      </div>
      <h1>設定情報の確認</h1>
    </header>
    <div id="contents">
      <article>
        <p>プロジェクトのインストールが成功しました。設定情報に問題がないか確認して下さい。確認を終えた後は当アクション及びテンプレートは削除しておくことをお勧めします。</p>
        <ul class="data">
          <li>
            <div class="data_label">PHP のバージョン</div>
              <div class="data_content">
              <?php if (!$html->hasError('php')): ?>
              <p>条件を満たしています。</p>
              <?php else: ?>
              <p><?php echo $html->error('php') ?></p>
              <?php endif ?>
            </div>
          </li>
          <li>
            <div class="data_label">デバッグモード</div>
              <div class="data_content">
              <?php if (Delta_DebugUtils::isDebug()): ?>
                <p>有効状態です。</p>
              <?php else: ?>
                <p>無効状態です。</p>
              <?php endif; ?>
              <p class="note">プロダクション環境では必ず設定を無効にして下さい。デバッグモードの設定は config/application.yml の 'debug.output' 属性で変更可能です。</p>
            </div>
          </li>
          <li>
            <div class="data_label">ディレクトリ権限</div>
            <div class="data_content">
              <?php if (!$html->hasError('permission')): ?>
                <p>問題ありません。</p>
              <?php else: ?>
                <p><?php echo $html->error('permission') ?></p>
              <?php endif; ?>
            </div>
          </li>
          <li>
            <div class="data_label">ルーティング</div>
            <div class="data_content">
              <?php if (!$html->hasError('route')): ?>
                <p>問題ありません。</p>
              <?php else: ?>
                <p><?php echo $html->error('route') ?></p>
              <?php endif; ?>
            </div>
          </li>
          <li>
            <div class="data_label">データベース接続</div>
            <div class="data_content">
              <?php if (!$html->hasError('database')): ?>
                <p>接続可能な状態です。</p>
              <?php else: ?>
                <p><?php echo $html->error('database') ?></p>
                <p class="note">接続に失敗した場合は、config/application.yml の 'database' 属性を見なおして下さい。</p>
              <?php endif ?>
            </div>
          </li>
          <li>
            <div class="data_label">コントロールパネル</div>
            <div class="data_content">
              <?php if (!$html->hasError('cpanel')): ?>
                <?php echo $form->start(array('router' => 'moduleEntry', 'module' => 'cpanel', 'action' => 'LoginForm')) ?>
                  <p><?php echo $form->inputSubmit('起動', array('class' => 'btn')) ?></p>
                  <p class="note">ログインパスワードは config/application.yml の 'module.entries.cpanel.password' 属性を参照して下さい。</p>
                <?php echo $form->close() ?>
              <?php else: ?>
                <p><?php echo $html->link($html->error('cpanel'), '/cpanel/', array(), array('escape' => FALSE)) ?></p>
              <?php endif; ?>
            </div>
          </li>
          <li>
            <div class="data_label">サンプルアプリケーション</div>
            <div class="data_content">
              <?php if (isset($hasSampleApp)): ?>
                <?php if (!$html->hasError('sample')): ?>
                  <?php echo $form->start(array('router' => 'moduleEntry', 'module' => 'front', 'action' => 'Start')) ?>
                    <p><?php echo $form->inputSubmit('スタート', array('class' => 'btn')) ?></p>
                  <?php echo $form->close() ?>
                <?php else: ?>
                <p><?php echo $html->error('sample') ?></p>
                <?php endif ?>
              <?php else: ?>
                <p>サンプルアプリケーションがインストールされていません。サンプルアプリケーションを起動するには、'delta install-sample' コマンドでインストールを行なう必要があります。</p>
              <?php endif ?>
            </div>
          </li>
        </ul>
      </article>
    </div>
    <footer>
      <p>Copyright &copy; delta framework project.</p>
    </footer>
  </body>
</html>
