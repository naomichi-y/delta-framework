<?php
/**
 * @package modules.entry.actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */

class StartAction extends Delta_Action
{
  public function execute()
  {
    $conn = $this->getDatabase()->getConnection();
    $command = $conn->getCommand();

    // テーブルの作成
    $path = DELTA_ROOT_DIR . '/skeleton/database/sample_application/ddl.yml';
    $data = Delta_Config::getCustomFile($path)->toArray();
    $isCreate = FALSE;

    foreach ($data['tables'] as $table) {
      if (!$command->existsTable($table['name'])) {
        $command->createTable($table);
        $isCreate = TRUE;
      }
    }

    // データの作成
    if ($isCreate) {
      $path = DELTA_ROOT_DIR . '/skeleton/database/sample_application/data';
      $files = scandir($path);
      $tableNames = array();

      foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
          continue;
        }

        $csvPath = $path . '/' . $file;
        $tableName = substr($file, 0, strpos($file, '.')) ;
        $tableNames[] = $tableName;

        $command->importCSV($tableName, $csvPath);
      }

      $message = sprintf('データベースにサンプルアプリケーション用のテーブルを追加しました。(%s)',
        implode(', ', $tableNames));
      $this->getMessages()->add($message);
    }

    return Delta_View::SUCCESS;
  }
}
