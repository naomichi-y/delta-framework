<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package logger
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * ログのローテート方法を定義します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package logger.rotator.executor
 */
abstract class Delta_LogRotateExecutor extends Delta_Object
{
  /**
   * @var string
   */
  protected $_path;

  /**
   * @var Delta_LogRotatePolicy
   */
  protected $_logRotatePolicy;

  /**
   * コンストラクタ。
   *
   * @param string $path ログの出力先。絶対パス、または ({APP_ROOT_DIR}/logs からの) 相対パスでの指定が可能。
   * @param Delta_LogRotatePolicy $logRotatePolicy ローテートパターンのインスタンス。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($path, $logRotatePolicy)
  {
    $this->_path = $path;
    $this->_logRotatePolicy = $logRotatePolicy;
  }

  /**
   * ローテートログのファイルリストを取得します。
   *
   * @return array 自然順アルゴリズムでソートされたファイルリストを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getRotateLogs()
  {
    $rotateLogs = glob(sprintf('%s.*', $this->_logRotatePolicy->getBasePath()), GLOB_NOSORT);
    array_multisort($rotateLogs, SORT_NATURAL);

    return $rotateLogs;
  }

  /**
   * ログファイルのローテートが必要な状態にあるかどうか取得します。
   *
   * @return bool ローテートが必要な場合は TRUE、不要な場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function isRotateRequired();

  /**
   * ログのローテートを行います。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function rotate();
}
