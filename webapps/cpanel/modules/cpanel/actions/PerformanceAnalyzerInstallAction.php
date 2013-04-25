<?php
/**
 * @package actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class PerformanceAnalyzerInstallAction extends Delta_Action
{
  public function execute()
  {
    $path = DELTA_ROOT_DIR . '/skeleton/database/performance_analyzer/ddl.yml';
    $data = Delta_Config::getCustomFile($path)->toArray();

    try {
      $command = $this->getDatabase()->getConnection()->getCommand();

      foreach ($data['tables'] as $table) {
        $command->createTable($table);
      }

    } catch (PDOException $e) {
      $message = sprintf('解析ログテーブルの作成に失敗しました。[%s]', $e->getMessage());
      $this->getMessages()->addError($message);

      return Delta_View::ERROR;
    }

    $this->getMessages()->add('インストールに成功しました。');

    return Delta_View::SUCCESS;
  }
}
