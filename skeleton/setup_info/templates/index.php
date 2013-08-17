<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta charset="UTF-8" />
    <title>設定情報の確認</title>
    <?php echo $html->includeCSS('/assets/base/delta/css/base.css') ?>
    <!--[if lt IE 9]>
    <script src="/assets/js/html5shiv.js"></script>
    <![endif]-->
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
            <div class="data-label">PHP のバージョン</div>
              <div class="data-content">
              <?php if (!$html->hasError('php')): ?>
              <p>条件を満たしています。(<?php echo phpversion() ?> &gt;= 5.3)</p>
              <?php else: ?>
              <p><?php echo $html->error('php') ?></p>
              <?php endif ?>
            </div>
          </li>
          <li>
            <div class="data-label">デバッグモード</div>
              <div class="data-content">
              <?php if (Delta_DebugUtils::isDebug()): ?>
                <p>有効な状態です。</p>
              <?php else: ?>
                <p>無効な状態です。</p>
              <?php endif; ?>
              <p class="note">プロダクション環境では必ず設定を無効にして下さい。デバッグモードの設定は config/application.yml の 'debug.output' 属性で変更可能です。</p>
            </div>
          </li>
          <li>
            <div class="data-label">ディレクトリ権限</div>
            <div class="data-content">
              <?php if (!$html->hasError('permission')): ?>
                <p>問題ありません。</p>
              <?php else: ?>
                <p><?php echo $html->error('permission') ?></p>
              <?php endif; ?>
            </div>
          </li>
          <li>
            <div class="data-label">ルーティング</div>
            <div class="data-content">
              <?php if (!$html->hasError('route')): ?>
                <p>問題ありません。</p>
              <?php else: ?>
                <p><?php echo $html->error('route') ?></p>
              <?php endif; ?>
            </div>
          </li>
          <li>
            <div class="data-label">データベース接続</div>
            <div class="data-content">
              <?php if (!$html->hasError('database')): ?>
                <p>接続可能な状態です。</p>
              <?php else: ?>
                <p><?php echo $html->error('database') ?></p>
                <p class="note">接続に失敗した場合は、config/application.yml の 'database' 属性を見直して下さい。</p>
              <?php endif ?>
            </div>
          </li>
          <li>
            <div class="data-label">コントロールパネル</div>
            <div class="data-content">
              <?php if (!$html->hasError('cpanel')): ?>
                <?php echo $form->start(array('route' => 'moduleRoute', 'module' => 'cpanel', 'action' => 'LoginForm')) ?>
                  <p><?php echo $form->inputSubmit('起動', array('class' => 'btn')) ?></p>
                  <p class="note">ログインパスワードは config/application.yml の 'cpanel.password' 属性を参照して下さい。</p>
                <?php echo $form->close() ?>
              <?php else: ?>
                <p><?php echo $html->link($html->error('cpanel'), '/cpanel/', array(), array('escape' => FALSE)) ?></p>
              <?php endif; ?>
            </div>
          </li>
          <li>
            <div class="data-label">デモアプリケーション</div>
            <div class="data-content">
              <?php if (isset($hasDemoApp)): ?>
                <?php if (!$html->hasError('demo')): ?>
                  <?php echo $form->start(array('route' => 'moduleRoute', 'module' => 'demo-front', 'action' => 'Start')) ?>
                    <p><?php echo $form->inputSubmit('スタート', array('class' => 'btn')) ?></p>
                  <?php echo $form->close() ?>
                <?php else: ?>
                <p><?php echo $html->error('demo') ?></p>
                <?php endif ?>
              <?php else: ?>
                <p>デモアプリケーションがインストールされていません。デモアプリケーションを起動するには、'delta install-demo-app' コマンドでインストールを行なう必要があります。</p>
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
