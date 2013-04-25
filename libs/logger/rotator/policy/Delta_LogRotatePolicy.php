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
 * ログのローテートポリシーを定義します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package logger.rotator.policy
 */
abstract class Delta_LogRotatePolicy extends Delta_Object
{
  /**
   * ローテート世代数。(無制限)
   */
  const GENERATION_UNLIMITED = -1;

  /**
   * オリジナルの出力パス。
   * @var string
   */
  protected $_basePath;

  /**
   * ローテート世代数。
   *
   * @var int
   */
  protected $_generation = 4;

  /**
   * コンストラクタ。
   *
   * @param string $basePath 基底のログ出力パス。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($basePath)
  {
    if (!Delta_FileUtils::isAbsolutePath($basePath)) {
      $basePath = sprintf('%s%slogs%s%s',
        APP_ROOT_DIR,
        DIRECTORY_SEPARATOR,
        DIRECTORY_SEPARATOR,
        $basePath);
    }

    $this->_basePath = $basePath;
  }

  /**
   * 基底のログ出力パスを取得します。
   *
   * @return string 基底のログ出力パスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getBasePath()
  {
    return $this->_basePath;
  }

  /**
   * ログの出力パスを取得します。
   *
   * @return string ログの出力パスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getWritePath()
  {
    return $this->_basePath;
  }

  /**
   * ローテートファイルの世代数を設定します。
   * 世代数を超えたログは {@link rotate()} 実行時に削除されます。
   * なお、{@link GENERATION_UNLIMITED} が指定された場合は全てのログを残します。
   *   - {@link Delta_LogRotateSizeBasedPolicy}: ファイル名を 'error' とした場合、書き込みがファイルサイズの上限に達すると 'error.1' にリネームされ、新たな 'error' ファイルが作成されます。
   *     世代数を 3 とすることで、最大で 3 ファイル ('error'、'error.1'、'error.2') のログが作成されることになります。
   *   - {@link Delta_LogRotateDateBasedPolicy}: ファイル名を 'error'、ローテート種別を日次とした場合、'error.1980-08-06'、'error.1980-08-07' といったファイルが作成されます。
   *     世代数を 3 とすることで、最大で 3 ファイル ('error.1980-08-06'、'error.1980-08-07'、'error.1980-08-08') のログが作成されることになります。
   *
   * @param int $generation ローテートファイルの世代数。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setGeneration($generation)
  {
    $this->_generation = $generation;
  }

  /**
   * ローテートファイルの世代数を取得します。
   *
   * @return int ローテートファイルの世代数を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getGeneration()
  {
    return $this->_generation;
  }

  /**
   * ローテートクラスを名を取得します。
   *
   * @return string {@link Delta_LogRotateExecutor} を実装したローテートクラスの名前を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function getExecutorClassName();
}
