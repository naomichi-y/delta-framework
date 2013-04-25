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
 * ファイルサイズによるローテートを処理します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package logger.rotator.executor
 */
class Delta_LogRotateSizeBasedExecutor extends Delta_LogRotateExecutor
{
  /**
   * @see Delta_LogRotatePolicy::isRotateRequired()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isRotateRequired()
  {
    clearstatcache(FALSE, $this->_path);
    $maxSize = $this->_logRotatePolicy->getMaxSize();

    if (is_file($this->_path) && filesize($this->_path) >= $maxSize) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * @see Delta_LogRotatePolicy::rotate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function rotate()
  {
    $logs = $this->getRotateLogs();
    $generation = $this->_logRotatePolicy->getGeneration();

    if ($j = sizeof($logs)) {
      for ($i = $j; $i > 0; $i--) {
        $oldPath = $logs[$i - 1];

        if (!is_file($oldPath)) {
          continue;
        }

        $oldIndex = substr($oldPath, strrpos($oldPath, '.') + 1);

        if ($generation != Delta_LogRotatePolicy::GENERATION_UNLIMITED && $generation - 1 <= $oldIndex) {
          unlink($oldPath);

        } else {
          $newPath = sprintf('%s.%d', $this->_path, $oldIndex + 1);
          rename($oldPath, $newPath);
        }
      }
    }

    rename($this->_path, sprintf('%s.1', $this->_path));
  }
}
