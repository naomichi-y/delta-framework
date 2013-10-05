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
 * ファイルアップロード機能を提供するユーティリティです。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util
 */
class Delta_FileUploader extends Delta_Object
{
  /**
   * アップロードされたファイルの情報。
   * @var array
   */
  private $_fileInfo;

  /**
   * コンストラクタ。
   *
   * @param string $name ファイルのフィールド名。'.' (ピリオド) を含む名前は連想配列名として扱われる。
   * @throws Delta_DataNotFoundException アップロードファイルが見つからない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($name)
  {
    $fileInfo = Delta_ArrayUtils::find($_FILES, $name);

    if ($fileInfo !== NULL) {
      $this->_fileInfo = $fileInfo;

    } else {
      $message = sprintf('File is not up-loaded. [%s]', $name);
      throw new Delta_DataNotFoundException($message);
    }
  }

  /**
   * ファイルが正常にアップロードされているかどうかチェックします。
   *
   * @return bool ファイルが正常にアップロードされている場合は TRUE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isUpload()
  {
    if ($this->_fileInfo['error'] === UPLOAD_ERR_OK) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * アップロードされたファイルの名前を取得します。
   *
   * @param bool $extension ファイル名に拡張子を含める場合は TRUE を指定。
   * @return string アップロードされたファイルの名前を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getFileName($extension = TRUE)
  {
    $fileName = $this->_fileInfo['name'];

    if (!$extension && ($pos = strpos($fileName, '.')) !== FALSE) {
      $fileName = substr($fileName, 0, $pos);
    }

    return $fileName;
  }

  /**
   * アップロードされたファイルの拡張子を取得します。
   *
   * @return string アップロードされたファイルの拡張子を返します。ファイルに拡張子が含まれない場合は NULL を返します。
   * @deprecated {@link getFileExtension()} に置き換えて下さい。1.15.0 で破棄されます。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getFileSuffix()
  {
    $fileName = $this->_fileInfo['name'];

    if (($pos = strpos($fileName, '.')) !== FALSE) {
      return substr($fileName, $pos);
    }

    return NULL;
  }

  /**
   * アップロードされたファイルの拡張子を取得します。
   *
   * @return string アップロードされたファイルの拡張子を返します。ファイルに拡張子が含まれない場合は NULL を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getFileExtension()
  {
    $fileName = $this->_fileInfo['name'];

    if (($pos = strpos($fileName, '.')) !== FALSE) {
      return substr($fileName, $pos);
    }

    return NULL;
  }

  /**
   * アップロードされたファイルの一時ファイル名を取得します。
   *
   * @return string アップロードされたファイルの一時ファイル名を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getTemporaryFilePath()
  {
    return $this->_fileInfo['tmp_name'];
  }

  /**
   * アップロードされたファイルの MIME タイプを取得します。
   * MIME タイプはブラウザによって同一ファイルに対しても異なる値を返す可能性がある点に注意して下さい。
   *
   * @return string アップロードされたファイルの MIME タイプを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getMIMEType()
  {
    return $this->_fileInfo['type'];
  }

  /**
   * アップロードされたファイルのステータスコードを取得します。
   *
   * @return int ステータスコード値として UPLOAD_ERR_* 定数を返します。
   * @link http://www.php.net/manual/features.file-upload.errors.php Error Messages Explained
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getStatus()
  {
    return $this->_fileInfo['error'];
  }

  /**
   * アップロードされたファイルの容量を取得します。
   *
   * @return string アップロードされたファイルの容量を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getFileSize()
  {
    return $this->_fileInfo['size'];
  }

  /**
   * アップロードされたファイルを保存します。
   *
   * @param string $savePath ファイルを保存するパス。APP_ROOT_DIR からの相対パス、あるいは絶対パスが有効。
   *   パスに含まれるディレクトリが存在しない場合は自動的に生成されます。
   * @return bool ファイルの保存に成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function deploy($savePath)
  {
    if (!Delta_FileUtils::isAbsolutePath($savePath)) {
      $savePath = APP_ROOT_DIR . DIRECTORY_SEPARATOR . $savePath;
    }

    $baseDirectory = dirname($savePath);

    if (!is_dir($baseDirectory)) {
      Delta_FileUtils::createDirectory($baseDirectory);
    }

    if (move_uploaded_file($this->getTemporaryFilePath(), $savePath)) {
      return TRUE;
    }

    return FALSE;
  }
}
