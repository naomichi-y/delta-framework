<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller.action
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * 実行するアクションをスタック構造で管理します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller.action
 */
class Delta_ActionStack extends Delta_Object
{
  /**
   * アクションスタックリスト。
   * @var array
   */
  private $_actionStack = array();

  /**
   * プライベートコンストラクタ。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function __construct()
  {}

  /**
   * Delta_ActionStack のインスタンスを取得します。
   *
   * @return Delta_ActionStack Delta_ActionStack のインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getInstance()
  {
    static $instance;

    if ($instance === NULL) {
      $instance = new Delta_ActionStack();
    }

    return $instance;
  }

  /**
   * アクションをスタックに追加します。
   *
   * @param Delta_Action $actionObject スタックに追加するアクション。
   * @throws OverflowException アクションスタックがオーバーフローする可能性がある場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addEntry(Delta_Action $actionObject)
  {
    if (sizeof($this->_actionStack) > 16) {
      $message = 'Cancel processing so that stack overflow may occur.';
      throw new OverflowException($message);
    }

    $this->_actionStack[] = $actionObject;
  }

  /**
   * 最後にエントリしたアクションオブジェクトを取得します。
   *
   * @return Delta_Action 最後にエントリしたアクションのインスタンスを返します。
   * @throws RuntimeException アクションスタックが空の場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getLastEntry()
  {
    $size = sizeof($this->_actionStack);

    if ($size == 0) {
      throw new RuntimeException('Action stack is empty.');
    }

    $lastEntry = $this->_actionStack[$size - 1];

    return $lastEntry;
  }

  /**
   * スタック上に存在するアクションオブジェクトのサイズを取得します。
   *
   * @return int スタック上に存在するアクションオブジェクトのサイズを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getSize()
  {
    return sizeof($this->_actionStack);
  }

  /**
   * フォワード元の (1 つ手前に登録された) アクションオブジェクトを取得します。
   *
   * @return Delta_Action フォワード元となるアクションのインスタンスを返します。
   *   フォワード元が存在しない場合は NULL を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getPreviousEntry()
  {
    $size = $this->getSize();

    if ($size < 2) {
      return NULL;
    }

    $previousEntry = $this->_actionStack[$size - 2];

    return $previousEntry;
  }

  /**
   * スタック上に存在する全てのアクションエントリを取得します。
   *
   * @return array アクションのエントリリストを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getEntries()
  {
    return $this->_actionStack;
  }

  /**
   * 指定されたアクションが既にロード済みであるか (インスタンスとしてスタック上に登録されているか) どうかチェックします。
   *
   * @param string $actionName チェック対象のアクション名。(プレフィックス 'Action' は不要)
   * @return bool アクションがロード済みの場合は TRUE、未ロードの場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasLoadedEntry($actionName)
  {
    foreach ($this->_actionStack as $action) {
      if (strcmp($action->getActionName(), $actionName) == 0) {
        return TRUE;
      }
    }

    return FALSE;
  }
}
