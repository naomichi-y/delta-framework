<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.config
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * カスタム設定ファイルのパラメータを管理します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.config
 */
class Delta_ConfigCustomHolder extends Delta_ParameterHolder
{
  /**
   * @var string
   */
  private $_path;

  /**
   * コンストラクタ。
   *
   * @param string $path ファイルパス。
   * @param array $config パラメータデータ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($path, array $config = array())
  {
    $this->_path = $path;

    parent::__construct($config, TRUE);
  }

  /**
   * 設定ファイルを更新します。
   * このメソッドは配列データを元に YAML フォーマットを生成します。
   * ファイルに含まれるコメントは全て除去される点に注意して下さい。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function update()
  {
    $directory = dirname($this->_path);

    if (!is_dir($directory)) {
      Delta_FileUtils::createDirectoryRecursive($directory);
    }

    Delta_CommonUtils::loadVendorLibrary('spyc/spyc.php');

    $array = $this->toArray();

     // 配列を YAML 形式に変換
    $data = Spyc::YAMLDump($array, 2, 76);

    // サニタイズ処理
    $data = preg_replace('/^([a-zA-Z0-9]+)/m', "\n" . '\1', $data);
    $data = trim($data, "---\n") . "\n";

    Delta_FileUtils::writeFile($this->_path, $data, LOCK_EX);
  }

  /**
   * 設定ファイルを削除します。
   *
   * @return bool ファイルの削除に成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function delete()
  {
    $result = FALSE;

    if (is_file($this->_path)) {
      $result = Delta_FileUtils::deleteFile($this->_path);
    }

    return $result;
  }
}
