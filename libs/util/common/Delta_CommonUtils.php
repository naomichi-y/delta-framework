<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.common
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * 操作する汎用的なユーティリティメソッドを提供します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.common
 */
class Delta_CommonUtils
{
  /**
   * ベンダーライブラリを読み込みます。
   *
   * @param string $path ライブラリが存在するパスの指定。
   *   絶対パス、あるいは {APP_ROOT_DIR}/vendors、{DELTA_ROOT_DIR}/vendors からの相対パスを指定可能。
   * @throws Delta_IOException ファイルの読み込みに失敗した際に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function loadVendorLibrary($path)
  {
    if (Delta_FileUtils::isAbsolutePath($path)) {
      $absolutePath = $path;

    } else {
      $absolutePath = APP_ROOT_DIR . '/vendors/' . $path;

      if (is_file($absolutePath)) {
        require_once $absolutePath;
        return;

      } else {
        $absolutePath = DELTA_ROOT_DIR . '/vendors/' . $path;
      }
    }

    if (is_file($absolutePath)) {
      require_once $absolutePath;

    } else {
      $message = sprintf('File path does not exist. [%s]', $absolutePath);
      throw new Delta_IOException($message);
    }
  }

  /**
   * 指定した変数を文字列型の表記形式に変換します。
   *
   * @param mixed $variable 変換対象の変数。
   * @return string 文字列型の表記形式を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function convertVariableToString($variable)
  {
    $type = gettype($variable);

    switch($type) {
      case 'boolean':
        if ($variable) {
          $string = 'TRUE';
        } else {
          $string = 'FALSE';
        }

        break;

      case 'object':
      case 'array':
        $string = str_replace('  ', ' ', trim(print_r($variable, TRUE)));
        break;

      default:
        $string = (string) $variable;
    }

    return $string;
  }

  /**
   * 変数 value の型を type に設定します。
   * この関数は {@link settype()} 関数と異なり、変更後の値を戻り値として返します。
   *
   * @param mixed $value 変換する変数。
   * @param string $type 変換する型。指定可能な値は {@link settype()} 関数を参照して下さい。
   * @return mixed 型変換後の値を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function setConvertType($value, $type = 'integer')
  {
    settype($value, $type);

    return $value;
  }
}
