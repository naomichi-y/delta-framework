<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package message
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * プログラムの処理完了時やエラー発生時にクライアントへ返すメッセージを一元管理します。
 *
 * このクラスは 'messages' コンポーネントとして DI コンテナに登録されているため、{@link Delta_DIContainer::getComponent()}、あるいは {@link Delta_DIController::getMessages()} からインスタンスを取得することができます。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package message
 */

class Delta_ActionMessages extends Delta_Object
{
  /**
   * メッセージ定数。({@link addError()} メソッドで追加されるタイプのエラー)
   */
  const ERROR_TYPE_DEFAULT = 1;

  /**
   * メッセージ定数。({@link addErrorField()} メソッドで追加されるタイプのエラー)
   */
  const ERROR_TYPE_FIELD = 2;

  /**
   * メッセージリスト。
   * @var array
   */
  private $_messages = array();

  /**
   * メッセージ ID リスト。
   */
  private $_messageIds = array();

  /**
   * エラーメッセージリスト。
   * @var array
   */
  private $_errors = array();

  /**
   * エラーメッセージ ID リスト。
   */
  private $_errorIds = array();

  /**
   * エラーメッセージリスト。(フィールドエラー)
   * @var array
   */
  private $_fieldErrors = array();

  /**
   * コンストラクタ。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct()
  {
    $this->clear();
  }

  /**
   * メッセージオブジェクトにメッセージを追加します。
   * このメソッドは何らかの処理の結果 (会員登録が完了した、アップロード処理が完了した等) をクライアントに通知する際に利用します。
   *
   * @param string $message 追加するメッセージ。
   * @param string $messageId メッセージを紐付ける ID。
   *   {@link hasMessage()} メソッドを利用することで、指定された ID がメッセージオブジェクトに登録されているかチェックすることが可能。
   * @throws RuntimeException messageId で指定された ID が既に登録されている場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function add($message, $messageId = NULL)
  {
    if ($messageId === NULL) {
      $this->_messages[] = $message;

    } else if (!$this->hasMessage($messageId)) {
      $this->_messages[$messageId] = $message;
      $this->_messageIds[] = $messageId;

    } else {
      $message = sprintf('Message has been registered with same ID. [%s]', $messageId);
      throw new RuntimeException($message);
    }
  }

  /**
   * メッセージオブジェクトにエラーメッセージを追加します。
   * このメソッドは何らかの処理の結果、エラーが発生した (会員登録に失敗した、アップロード処理が不正に終了した等) ことをクライアントに通知する際に利用します。
   *
   * @param string $message 追加するエラーメッセージ。
   * @param string $messageId エラーメッセージを紐付ける ID。
   *   {@link hasError()} メソッドを利用することで、指定された ID がメッセージオブジェクトに登録されているかチェックすることが可能。
   * @throws RuntimeException messageId で指定された ID が既に登録されている場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addError($message, $messageId = NULL)
  {
    if ($messageId === NULL) {
      $this->_errors[] = $message;

    } else if (!$this->hasError($messageId)) {
      $this->_errors[$messageId] = $message;
      $this->_errorIds[] = $messageId;

    } else {
      $message = sprintf('Message has been registered with same ID. [%s]', $messageId);
      throw new RuntimeException($message);
    }
  }

  /**
   * 指定したフォームフィールドにエラーメッセージを設定します。
   * 例えば会員登録時に 'loginId' フィールドに入力された ID が登録済みで使用できない場合、addFieldError('loginId', '指定された ID は利用できません。') とすることで対象フィールドにエラーメッセージを設定することができます。
   * フィールドに設定したメッセージは {@link getFieldError()} や、テンプレート上からは {@link Delta_FormHelper::getFieldError()} といったメソッドで取得することができます。
   *
   * @param string $name 対象となるフィールド要素名。
   * @param string $message 追加するエラーメッセージ。
   *   同じ name に対し複数回同じメソッドがコールされた場合、古いメッセージは上書きされます。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addFieldError($name, $message)
  {
    $this->_fieldErrors[$name] = $message;
  }

  /**
   * メッセージオブジェクトに 1 つ以上のメッセージが登録されているかどうかチェックします。
   *
   * @param string $messageId 検索対象のメッセージ ID。
   * @return bool メッセージが登録されている場合は TRUE、登録されていない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasMessage($messageId = NULL)
  {
    if ($messageId !== NULL) {
      return in_array($messageId, $this->_messageIds, TRUE);
    }

    if (sizeof($this->_messages)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * メッセージオブジェクトに 1 つ以上のエラーメッセージが登録されているかどうかチェックします。
   *
   * @param string $messageId 検索対象のエラーメッセージ ID。
   * @return bool エラーメッセージが登録されている場合は TRUE、登録されていない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasError($messageId = NULL)
  {
    if ($messageId !== NULL) {
      return in_array($messageId, $this->_errorIds, TRUE);
    }

    if ($this->getErrorSize(self::ERROR_TYPE_DEFAULT)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * フォームフィールドにエラーメッセージが設定されているかどうかチェックします。
   *
   * @param string $name 対象となるフィールド要素名。
   * @return bool 対象となるフィールドにエラーメッセージが設定されている場合は TRUE、未設定の場合は FALSE を返します。
   *   また name が未指定の場合は、1 つ以上のフィールドエラーが設定されているかどうかをチェックします。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasFieldError($name = NULL)
  {
    if ($name === NULL) {
      if (sizeof($this->_fieldErrors)) {
        return TRUE;
      } else {
        return FALSE;
      }
    }

    return isset($this->_fieldErrors[$name]);
  }

  /**
   * 対象フォームフィールドに設定されているエラーメッセージを取得します。
   *
   * @param string $name 対象となるフィールド要素名。
   * @return mixed 対象フォームフィールドに含まれるエラーメッセージを返します。メッセージが見つからない場合は NULL を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getFieldError($name)
  {
    if (isset($this->_fieldErrors[$name])) {
      return $this->_fieldErrors[$name];
    }

    return NULL;
  }

  /**
   * 対象フォームフィールドに設定されているエラーメッセージを削除します。
   *
   * @param string $name 対象となるフィールド要素名。
   * @return bool エラーメッセージの削除に成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function removeFieldError($name)
  {
    if (isset($this->_fieldErrors[$name])) {
      unset($this->_fieldErrors[$name]);

      return TRUE;
    }

    return FALSE;
  }

  /**
   * メッセージオブジェクトに登録されている総メッセージ数を取得します。
   *
   * @return int メッセージオブジェクトに登録されている総メッセージ数を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getSize()
  {
    return sizeof($this->_messages);
  }

  /**
   * メッセージオブジェクトに設定されている総エラーメッセージ数を取得します。
   *
   * @param int $type Delta_ActionMessages::ERROR_TYPE_* 定数を指定。
   * @return int メッセージオブジェクトに設定されている総エラーメッセージ数を返します。
   *   type が未指定の場合は {@link Delta_ActionMessages::ERROR_TYPE_DEFAULT} + {@link Delta_ActionMessages::ERROR_TYPE_FIELD} の総数を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getErrorSize($type = NULL)
  {
    switch ($type) {
      case self::ERROR_TYPE_DEFAULT:
        $size = sizeof($this->_errors);
        break;

      case self::ERROR_TYPE_FIELD:
        $size = sizeof($this->_fieldErrors);
        break;

      default:
        $size = sizeof($this->_errors) + sizeof($this->_fieldErrors);
        break;
    }

    return $size;
  }

  /**
   * メッセージオブジェクトに設定されている全てのメッセージを取得します。
   *
   * @return array メッセージオブジェクトに設定されている全てのメッセージを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getList()
  {
    return $this->_messages;
  }

  /**
   * メッセージオブジェクトに設定されている全てのエラーメッセージを取得します。
   *
   * @param int $type Delta_ActionMessages::ERROR_TYPE_* 定数を指定。
   * @return array メッセージオブジェクトに設定されている全てのエラーメッセージを返します。
   *   type が未指定の場合は {@link Delta_ActionMessages::ERROR_TYPE_DEFAULT} + {@link Delta_ActionMessages::ERROR_TYPE_FIELD} のリストを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getErrorList($type = NULL)
  {
    switch ($type) {
      case self::ERROR_TYPE_DEFAULT:
        $array = $this->_errors;
        break;

      case self::ERROR_TYPE_FIELD:
        $array = $this->_fieldErrors;
        break;

      default:
        $array = $this->_errors + $this->_fieldErrors;
        break;
    }

    return $array;
  }

  /**
   * メッセージオブジェクトに設定されている全てのメッセージ (エラーメッセージを含む) を削除します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function clear()
  {
    $this->_messages = array();
    $this->_errors = array();
    $this->_fieldErrors = array();

    $this->_messageIds = array();
    $this->_errorIds = array();
  }
}
