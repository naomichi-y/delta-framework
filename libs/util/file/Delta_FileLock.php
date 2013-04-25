<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * ファイルを読み書きする際の排他ロック機能を提供します。
 * PHP (flock() 関数) が提供するアドバイザリロックとは異なり、Web アプリケーションにおいて複数のユーザから同時にアクセスされた場合の制御が考慮されています。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.file
 */

class Delta_FileLock extends Delta_Object
{
  /**
   * ロック処理を行なう基底ディレクトリ。
   * @var string
   */
  private $_baseDirectory;

  /**
   * ロック対象ファイルのパス。
   * @var string
   */
  private $_targetPath;

  /**
   * ロックファイルのデフォルトパス。(ロックが無効な状態)
   * @var string
   */
  private $_defaultLockPath;

  /**
   * ロックファイルのアクティブパス。(ロックが有効な状態)
   * @var string
   */
  private $_activeLockPath;

  /**
   * ロック対象ファイル名。
   * @var string
   */
  private $_lockFile;

  /**
   * ロックファイルの有効タイムアウト秒。
   * @var int
   */
  private $_timeout;

  /**
   * ロック試行回数。
   * @var int
   */
  private $_retry;

  /**
   * コンストラクタ。
   *
   * @param string $filePath ロック対象のファイルパス。
   *   APP_ROOT_DIR からの相対パス、あるいは絶対パスが有効。
   * @param int $retry ロック権の取得を試行する回数。失敗した場合は 0.1 秒ごとにリトライを繰り返します。
   * @param int $timeout ロックファイルの有効タイムアウト秒。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  function __construct($filePath, $retry = 10, $timeout = 10)
  {
    if (!Delta_FileUtils::isAbsolutePath($filePath)) {
      $filePath = APP_ROOT_DIR . DIRECTORY_SEPARATOR . $filePath;
    }

    $info = pathinfo($filePath);
    $this->_baseDirectory = $info['dirname'];
    $this->_lockFile = $info['basename'];

    $this->_targetPath = $this->_baseDirectory .  DIRECTORY_SEPARATOR . $this->_lockFile;
    $this->_defaultLockPath = $this->_targetPath . '.lock';

    if (!is_file($this->_targetPath)) {
      if (!is_dir($this->_baseDirectory)) {
        Delta_FileUtils::createDirectoryRecursive($this->_baseDirectory);
      }

      @touch($this->_targetPath);
      @touch($this->_defaultLockPath);
    }

    $this->_timeout = $timeout;
    $this->_retry = $retry;
  }

  /**
   * ファイルをロックした後に内容を読み込みます。
   *
   * @return mixed ファイルの内容を返します。ファイルのロックに失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function lockFileOpen()
  {
    if ($this->lock()) {
      return @file_get_contents($this->_targetPath);
    }

    return FALSE;
  }

  /**
   * ファイルに書き込みを行ないます。
   * このメソッドは、lock() または {@link lockFileOpen()} メソッドでロックをかけた後にコールする必要があります。
   *
   * @param string $contents 書き込む内容。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function lockFileWrite($contents)
  {
    file_put_contents($this->_targetPath, $contents);
  }

  /**
   * ファイルをロックします。
   *
   * @return bool ロックに成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  function lock()
  {
    $retry = $this->_retry;
    $defaultLockPath = $this->_defaultLockPath;

    // ロックファイルが存在する場合は一時的にファイル名を変更する (ロック権の取得)
    for ($i = 0; $i < $retry; $i++) {
      clearstatcache(TRUE, $defaultLockPath);

      if (is_file($defaultLockPath)) {
        $activeLockPath = $defaultLockPath . time();

        // プロセス A とプロセス B が同時にアクセスした場合、rename() が失敗する可能性がある
        if (@rename($defaultLockPath, $activeLockPath)) {
          $this->_activeLockPath = $activeLockPath;

          return TRUE;
        }
      }
      // !is_file($defaultLockPath) はロック対象ファイルがあるが .lock ファイルが取得できない状態
      // つまり他のプロセスが .lock.{time} ファイルを作成してロック権を保持している可能性がある
      // 他のプロセスがロック権を破棄するまで待機 (0.1 秒)
      usleep(10000);
    }

    // ロック権を得られなかった場合、過去に開放されなかったロックファイルが存在しないかチェックする
    $handle = opendir($this->_baseDirectory);
    $pattern = '/^' . $this->_lockFile . '\.lock([0-9]+)$/';
    $result = FALSE;

    while ($oldLockId = readdir($handle)) {
      if (preg_match($pattern, $oldLockId, $lockTime)) {
        if (time() - $lockTime[1] > $this->_timeout) {
          $oldLockPath = $defaultLockPath . $lockTime[1];
          $activeLockPath = $defaultLockPath . time();

          // 対象ファイルを新しいロックファイルとして扱う
          if (@rename($oldLockPath, $activeLockPath)) {
            $this->_activeLockPath = $activeLockPath;
            $result = TRUE;

            break;
          }
        }

        $result = FALSE;
        break;
      }
    }

    closedir($handle);

    return $result;
  }

  /**
   * ロックを開放します。
   *
   * @return bool ロックの開放に成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  function unlock()
  {
    // オリジナルのロックファイル名に戻す
    return @rename($this->_activeLockPath, $this->_defaultLockPath);
  }
}
