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
 *     # 許可されていないMIME タイプのファイルがアップロードされた場合に通知するエラーメッセージ。
 *     mimeTypeError: {default_message}
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
 *     extensionError: {default_message}
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 */

class Delta_FileUploadValidator extends Delta_Validator
{
  protected $_validatorId = 'fileUpload';

  /**
   * @see Delta_Validator::validate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate()
  {
    exit;
    $holder = $this->buildParameterHolder($variables);
    $hasUpload = Delta_FileUploader::hasUpload($fieldName);

    if (!$holder->getBoolean('required') && !$hasUpload) {
      return TRUE;
    }

    // ファイルがアップロードされているか検証
    if (is_array($value) && $value['error'] == UPLOAD_ERR_NO_FILE) {
      $message = $holder->getString('requiredError');

      if ($message === NULL) {
        $message = sprintf('The file is not up-loaded. [%s]', $fieldName);
      }

      $this->sendError($fieldName, $message);

      return FALSE;
    }

    // エンコーディング形式の検証
    if (!is_array($value)) {
      $message = $holder->getString('encodingError');

      if ($message === NULL) {
        $message = 'Form data is not encoded by multipart/form-data.';
      }

      $this->sendError($fieldName, $message);

      return FALSE;
    }

    // アップロードに関する基本的なエラーチェックを行う
    $message = NULL;

    switch ($value['error']) {
      case UPLOAD_ERR_PARTIAL:
        $message = $holder->getString('partialError');

        if ($message === NULL) {
          $message = sprintf('The uploaded file was only partially uploaded. [%s]', $fieldName);
        }

        break;

      case UPLOAD_ERR_NO_TMP_DIR:
        $message = $holder->getString('temporaryError');

        if ($message === NULL) {
          $message = sprintf('Missing a temporary directory. [%s]', $fieldName);
        }

        break;

      case UPLOAD_ERR_CANT_WRITE:
        $message = $holder->getString('writeError');

        if ($message === NULL) {
          $message = sprintf('Failed to write file to disk. [%s]', $fieldName);
        }

        break;

      case UPLOAD_ERR_EXTENSION:
        $message = $holder->getString('extensionError');

        if ($message === NULL) {
          $message = sprintf('Upload file was stopped with an expansion module. [%s]', $fieldName);
        }

        break;
    }

    if ($message) {
      $this->sendError($fieldName, $message);

      return FALSE;
    }

    // ファイルサイズの検証
    $fileSizeError = FALSE;
    $maxSize = $holder->getString('maxSize');

    if ($maxSize) {
      $maxSize = Delta_NumberUtils::realBytes($maxSize);

      if ($maxSize && $value['size'] > $maxSize) {
        $fileSizeError = TRUE;
      }
    }

    // PHP (upload_max_filesize) が許容するアップロードサイズを超えた場合
    if ($value['error'] == UPLOAD_ERR_INI_SIZE) {
      $message = sprintf('Upload limit is %s. '
        .'Please rewrite the value of \'upload_max_filesize\' in php.ini.', ini_get('upload_max_filesize'));
      throw new RuntimeException($message);
    }

    if ($fileSizeError || $value['error'] == UPLOAD_ERR_FORM_SIZE) {
      $message = $holder->getString('maxSizeError');

      if ($message === NULL) {
        $message = sprintf('File size exceeds it. [%s]', $fieldName);
      }

      $this->sendError($fieldName, $message);

      return FALSE;
    }

    // データが POST 形式でリクエストされているか検証
    if (!is_uploaded_file($value['tmp_name'])) {
      $message = $holder->getString('postError');

      if ($message === NULL) {
        $message = 'Upload file format is wrong.';
      }

      $this->sendError($fieldName, $message);

      return FALSE;
    }

    // MIME タイプの検証
    $mimeTypes = $holder->getArray('mimeTypes');

    if (sizeof($mimeTypes)) {
      if (!in_array($value['type'], $mimeTypes)) {
        $message = $holder->getString('mimeTypeError');

        if ($message === NULL) {
          $message = sprintf('MIME-Type format is illegal. [%s]', $fieldName);
        }

        $this->sendError($fieldName, $message);

        return FALSE;
      }
    }

    return TRUE;
  }
}
