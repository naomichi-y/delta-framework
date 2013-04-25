<?php
/**
 * @package modules.entry.actions
 */
class IndexAction extends Delta_Action
{
  public function execute()
  {
    $request = $this->getRequest();
    $messages = $this->getMessages();

    // PHP のバージョンチェック
    $requireVersion = '5.3.0';
    $currentVersion = phpversion();

    if (version_compare($requireVersion, $currentVersion, '>')) {
      $message = sprintf('PHP のバージョンは %s 以上である必要があります。インストールされているバージョンは %s です。',
        $requireVersion,
        $currentVersion);
      $messages->addError($message, 'php');
    }

    $failureDirectory = array();

    // cache ディレクトリの権限チェック
    if (Delta_FileUtils::getMode('cache') < 775) {
      $failureDirectory[] = 'cache';
    }

    // logs ディレクトリの権限チェック
    if (Delta_FileUtils::getMode('logs') < 775) {
      $failureDirectory[] = 'logs';
    }

    // tmp ディレクトリの権限チェック
    if (Delta_FileUtils::getMode('tmp') < 775) {
      $failureDirectory[] = 'tmp';
    }

    if (sizeof($failureDirectory)) {
      $message = sprintf('権限が不足しています。次のディレクトリの権限を 0775 に設定して下さい。[%s]', implode(', ', $failureDirectory));
      $messages->addError($message, 'permission');
    }

    // mod_rewrite の動作チェック
    if ($request->getPathInfo('check') === 'rewrite') {
      $this->getResponse()->write('SUCCESS');

      return Delta_View::NONE;

    } else {
      $path = array('action' => $this->getActionName(), 'check' => 'rewrite');
      $requestUrl = Delta_Router::getInstance()->buildRequestPath($path, array(), TRUE);

      try {
        if (file_get_contents($requestUrl) !== 'SUCCESS') {
          throw new Delta_RequestException();
        }

      } catch (Delta_RequestException $e) {
        $messages->addError('mod_rewrite が正常に動作していない可能性があります。モジュールの設定を見直して下さい。', 'route');
      }
    }

    // データベースへの接続チェック
    try {
      $this->getDatabase()->getConnection();

    } catch (PDOException $e) {
      $messages->addError($e->getMessage(), 'database');
    }

    // cpanel の動作チェック
    if (!$request->getParameter('check')) {
      // cpanel のパスは固定なので Delta_Router::buildRequestPath() 経由でパスを算出しない
      $requestUrl = 'http://' . $request->getEnvironment('HTTP_HOST') . '/cpanel/loginForm.do/check/available';

      try {
        if (file_get_contents($requestUrl) !== 'SUCCESS') {
          throw new Exception();
        }

      } catch (Exception $e) {
        $messages->addError('コントロールパネルが表示できない可能性があります。', 'cpanel');
      }
    }

    // サンプルアプリケーションがインストールされているかチェック
    if (in_array('sample-frontend', Delta_CoreUtils::getModuleNames())) {
      $this->getView()->setAttribute('hasSampleApp', TRUE);

      if ($messages->hasError('database')) {
        $messages->addError('データベースに接続できないため、サンプルアプリケーションを起動できません。', 'sample');
      }
    }

    return Delta_View::SUCCESS;
  }
}
