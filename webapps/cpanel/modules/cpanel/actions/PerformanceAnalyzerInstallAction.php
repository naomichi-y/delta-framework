<?php
/**
 * @package actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class PerformanceAnalyzerInstallAction extends Delta_Action
{
  public function execute()
  {
    // リスナが宣言されているかチェック
    $databaseNamespace = Delta_PerformanceListener::getDatabaseNamespace();

    if ($databaseNamespace) {
      $key = 'database.' . $databaseNamespace;
      $config = Delta_Config::getApplication();

      if (!$config->hasName($key)) {
        $message = sprintf('config/application.yml にデータベース属性 \'database.%s\' が定義されていません。', $databaseNamespace);
        $this->getMessages()->addError($message);

        return Delta_View::ERROR;
      }

      $path = DELTA_ROOT_DIR . '/skeleton/database/performance_analyzer/ddl.yml';
      $data = Delta_Config::getCustomFile($path)->toArray();

      try {
        $command = $this->getDatabase()->getConnection($databaseNamespace)->getCommand();

        foreach ($data['tables'] as $table) {
          $command->createTable($table);
        }

      } catch (PDOException $e) {
        $message = sprintf('解析ログテーブルの作成に失敗しました。[%s]', $e->getMessage());
        $this->getMessages()->addError($message);

        return Delta_View::ERROR;
      }

      $this->getMessages()->add('インストールに成功しました。');

    } else {
      $message = '設定ファイルにリスナが宣言されていません。';
      $this->getMessages()->addError($message);

      return Delta_View::ERROR;
    }

    return Delta_View::SUCCESS;
  }
}
