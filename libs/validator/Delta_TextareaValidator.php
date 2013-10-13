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
 *     # ホワイトスペースで構成される行をカウントしない場合は TRUE を指定。
 *     ignoreBlankLine: FALSE
 * </code>
 * ※: 'minRowSize'、'maxRowSize'、'matchRowSize' のいずれかの指定は必須です。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 * @todo 2.0 ドキュメント更新
 */
class Delta_TextareaValidator extends Delta_Validator
{
  /**
   * @var string
   */
  protected $_validatorId = 'textarea';

  /**
   * @throws Delta_ConfigurationException 必須属性が未指定の場合に発生。
   * @see Delta_Validator::validate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate()
  {
    $result = TRUE;

    if (strlen($this->_fieldValue)) {
      $fieldValue = Delta_StringUtils::replaceLinefeed($this->_fieldValue, "\n");
      $lines = explode("\n", $this->_fieldValue);
      $rowSize = 0;

      if ($this->_conditions->getBoolean('ignoreBlankLine')) {
        foreach ($lines as $line) {
          if (strlen($line)) {
            $rowSize++;
          }
        }

      } else {
        $rowSize = sizeof($lines);
      }

      // 必要行数にマッチしているか
      if ($this->_conditions->hasName('matchRowSize')) {
        $matchRowSize = $this->_conditions->getInt('matchRowSize');

        if ($rowSize != $matchRowSize) {
          $this->setError('matchRowSizeError');
          $result = FALSE;
        }

      // 必要行数以上を満たしているか
      } else if ($this->_conditions->hasName('minRowSize')) {
        $minRowSize = $this->_conditions->getInt('minRowSize');

        if ($rowSize < $minRowSize) {
          $this->setError('minRowSizeError');
          $result = FALSE;
        }

      // 必要行数以下を満たしているか
      } else if ($this->_conditions->hasName('maxRowSize')) {
        $maxRowSize = $this->_conditions->getInt('maxRowSize');

        if ($rowSize > $maxRowSize) {
          $this->setError('maxRowSizeError');
          $result = FALSE;
        }

      } else {
        $message = sprintf('Validate condition is undefined. [matchRowSize, minRowSize, maxRowSize]');
        throw new Delta_ConfigurationException($message);
      }
    }

    return $result;
  }
}
