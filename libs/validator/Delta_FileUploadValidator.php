<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * フォームから送信されたアップロードファイルのデータを検証します。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * validate:
 *   {validator_id}:
 *     # バリデータクラス名。
 *     class: Delta_FileUploadValidator
 *
 *     # ファイルのアップロードを必須とする場合は TRUE を指定。
 *     required: FALSE
 *
 *     # ファイルがアップロードされていない場合に通知するエラーメッセージ。
 *     requiredError: {default_message}
 *
 *     # フォームデータが multipart/form-data 形式でエンコードされていない場合に通知するメッセージ。
 *     encodingError: {default_message}
 *
 *     # ファイルが POST 形式でアップロードされていない場合に通知するメッセージ。
 *     postError: {default_message}
 *
 *     # アップロードを許可する MIME タイプを配列形式で指定。未指定の場合は全ての MIME タイプが許可される。
 *     mimeTypes:
 *
 *     # 許可されていない MIME タイプのファイルがアップロードされた場合に通知するエラーメッセージ。
 *     mimeTypeError: {default_message}
 *
 *     # アップロードを許可するファイル拡張子を配列形式で指定。未指定の場合は全ての拡張子が許可される。
 *     extensions:
 *
 *     # 許可されていない拡張子のファイルがアップロードされた場合に通知するメッセージ。
 *     extensionError: {default_message}
 *
 *     # アップロード可能な最大ファイルサイズ。
 *     # 数値形式によるバイト数、または '128KB'、'32MB' のような文字列指定も可能。
 *     maxSize:
 *
 *     # アップロード可能なファイルサイズの上限を超えている場合に通知するエラーメッセージ。
 *     # ({@link http://php.net/manual/features.file-upload.errors.php UPLOAD_ERR_INI_SIZE、UPLOAD_ERR_FORM_SIZE も通知対象となる})
 *     maxSizeError: {default_message}
 *
 *     # アップロードされたファイルが一部欠損している場合に通知するエラーメッセージ。
 *     partialError: {default_message}
 *
 *     # アップロード先となる一時ディレクトリが存在しない場合に通知するエラーメッセージ。
 *     temporaryError: {default_message}
 *
 *     # ディスクへの書き込みが失敗した場合に通知するエラーメッセージ。
 *     writeError: {default_message}
 *
 *     # ファイルのアップロードが拡張モジュールによって停止した場合に通知するメッセージ。
 *     extensionModuleError: {default_message}
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 * @todo 2.0 ドキュメント更新
 */

class Delta_FileUploadValidator extends Delta_Validator
{
  /**
   * @var string
   */
  protected $_validatorId = 'fileUpload';

  /**
   * @see Delta_Validator::validate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate()
  {
    $result = TRUE;

    $fileInfo = Delta_ArrayUtils::find($_FILES, $this->_fieldName);
    $required = $this->_conditions->getBoolean('required');

    // アップロードが必須、または必須ではないがアップロードが行われた
    if ($required || !$required && $fileInfo && $fileInfo['error'] != UPLOAD_ERR_NO_FILE) {
      // アップロードが必須、かつアップロードファイルが見つからない
      if ($required && !$fileInfo || $required && $fileInfo['error'] == UPLOAD_ERR_NO_FILE) {
        $this->setError('unuploadError');
        $result = FALSE;

      } else {
        // ファイルが POST 送信されているかチェック
        if (!is_uploaded_file($fileInfo['tmp_name'])) {
          $this->setError('encodeError');
          $result = FALSE;
        }

        if ($this->validateErrorType($fileInfo['error'])) {
          // ファイルサイズの下限サイズチェック
          $minSize = $this->_conditions->getString('minSize');

          if ($minSize) {
            $minSize = Delta_NumberUtils::realBytes($minSize);

            if ($minSize && $fileInfo['size'] < $minSize) {
              $this->setError('minSizeError');
              $result = FALSE;
            }
          }

          // ファイルサイズの上限チェック
          $maxSize = $this->_conditions->getString('maxSize');

          if ($maxSize) {
            $maxSize = Delta_NumberUtils::realBytes($maxSize);

            if ($maxSize && $fileInfo['size'] > $maxSize) {
              $this->setError('maxSizeError');
              $result = FALSE;
            }
          }

          if ($result) {
            // MIME タイプのチェック
            $mimeTypes = $this->_conditions->getArray('mimeTypes');

            if ($mimeTypes && !in_array($fileInfo['type'], $mimeTypes)) {
              $this->setError('mimeTypeError');
              $result = FALSE;
            }
          }

          if ($result) {
            $extensions = $this->_conditions->getArray('extensions');
            $pathInfo = pathinfo($fileInfo['name']);

            if ($extensions && !in_array($pathInfo['extension'], $extensions)) {
              $this->setError('extensionError');
              $result = FALSE;
            }
          }

        } // end if
      } // end if
    } // end if

    return $result;
  }

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function validateErrorType($errorType)
  {
    $result = TRUE;

    switch ($errorType) {
      case UPLOAD_ERR_OK:
        break;

      case UPLOAD_ERR_INI_SIZE:
        $this->_conditions->set('maxSize', ini_get('upload_max_filesize'));
        $this->setError('maxSizeError');
        $result = FALSE;

        break;

      case UPLOAD_ERR_FORM_SIZE:
        $request = Delta_FrontController::getInstance()->getRequest();
        $maxSize = $request->getPost('MAX_FILE_SIZE');

        $this->_conditions->set('maxSize', $maxSize);
        $this->setError('maxSizeError');
        $result = FALSE;

        break;

      case UPLOAD_ERR_PARTIAL:
        $this->setError('partialError');
        $result = FALSE;

        break;

      case UPLOAD_ERR_NO_TMP_DIR:
        $this->setError('temporaryError');
        $result = FALSE;

        break;

      case UPLOAD_ERR_CANT_WRITE:
        $this->setError('writeError');
        $result = FALSE;

        break;

      case UPLOAD_ERR_EXTENSION:
        $this->setError('extensionModuleError');
        $result = FALSE;

        break;

    }

    return $result;
  }
}
