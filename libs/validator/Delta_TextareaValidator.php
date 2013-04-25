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
 * フォームから送信されたテキストエリアの行数を検証します。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * validate:
 *   {validator_id}:
 *     # バリデータクラス名。
 *     class: Delta_TextareaValidator
 *
 *     # 入力が必要な最小行数。
 *     minRowSize:
 *
 *     # 入力された行数が 'minRowSize' 未満の場合に通知するエラーメッセージ。
 *     minRowSizeError: {default_message}
 *
 *     # 入力が必要な最大行数。
 *     maxRowSize:
 *
 *     # 入力された行数が 'maxRowSize' を超えている場合に通知するエラーメッセージ。
 *     maxRowSizeError: {default_message}
 *
 *     # 入力が必要な行数。
 *     matchRowSize:
 *
 *     # 入力された行数が 'matchRowSize' と一致しない場合に通知するエラーメッセージ。
 *     matchRowSizeError: {default_message}
 *
 *     # ホワイトスペースで構成された行をカウントする場合は TRUE を指定。
 *     ignoreBlankLine: FALSE
 * </code>
 * ※: 'minRowSize'、'maxRowSize'、'matchRowSize' のいずれかの指定は必須です。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 */
class Delta_TextareaValidator extends Delta_Validator
{
  /**
   * @throws Delta_ConfigurationException 必須属性がビヘイビアに定義されていない場合に発生。
   * @see Delta_Validator::validate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate($fieldName, $value, array $variables = array())
  {
    $holder = $this->buildParameterHolder($variables);

    if (strlen($value) == 0) {
      return TRUE;
    }

    $values = explode("\n", $value);

    if ($holder->getBoolean('ignoreBlankLine')) {
      $rowSize = sizeof($values);

    } else {
      $rowSize = 0;

      foreach ($values as $line) {
        if (strlen($line)) {
          $rowSize++;
        }
      }
    }

    $message = NULL;

    if ($holder->hasName('matchRowSize')) {
      $matchRowSize = $holder->getInt('matchRowSize');

      if ($rowSize != $matchRowSize) {
        $message = $holder->getString('matchRowSizeError');

        if ($message === NULL) {
          $message = sprintf('Value elements is illegal. [%s]', $fieldName);
        }
      }

    } else {
      if ($holder->hasName('minRowSize')) {
        $minRowSize = $holder->getInt('minRowSize');

        if ($rowSize < $minRowSize) {
          $message = $holder->getString('minRowSizeError');

          if ($message === NULL) {
            $message = sprintf('Value elements is short. [%s]', $fieldName);
          }
        }
      }

      if ($holder->hasName('maxRowSize')) {
        $maxRowSize = $holder->getInt('maxRowSize');

        if ($rowSize > $maxRowSize) {
          $message = $holder->getString('maxRowSizeError');

          if ($message === NULL) {
            $message = sprintf('Value elements is long. [%s]', $fieldName);
          }
        }

      } else {
        $message = sprintf('\'minRowSize\' or \'maxRowSize\' or \'matchRowSize\' validator attribute is undefined.');
        throw new Delta_ConfigurationException($message);
      }
    }

    if ($message !== NULL) {
      $this->sendError($fieldName, $message);

      return FALSE;
    }

    return TRUE;
  }
}
