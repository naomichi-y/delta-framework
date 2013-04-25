<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package console
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * コンソールアプリケーションで対話式インタフェースを提供します。
 * <code>
 * class HelloWorldCommand extends Delta_ConsoleCommand
 * {
 *   public function execute()
 *   {
 *     $dialog = $this->getInput()->getDialog();
 *     $dialog->{@link setSendFormat}('', ': ');
 *
 *     // コンソール上に 'Your name?: ' を出力。クライアントから入植された値が $response に格納される。
 *     $response = $dialog->{@link send}('Your name?');
 *
 *     // コンソール上に 'Yer or No? (Y/N): ' を出力。'Y' 押下時は TRUE、'N' 押下時は FALSE が返される。
 *     $response = $dialog->{@link sendConfirm}('Yer or No? (Y/N)');
 *   }
 * }
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package console
 */

class Delta_ConsoleDialog extends Delta_Object
{
 /**
   * @var int
   */
  private $_sendIndent = 0;

  /**
   * @var string
   */
  private $_sendSuffix;

  /**
   * @var string
   */
  private $_sendPrefix;

  /**
   a @var int
   */
  private $_sendGraphicMode;

  /**
   * 標準入力 (STDIN) を取得します。
   *
   * @return string 標準入力から取得した一行を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getInputLine()
  {
    return trim(fgets(STDIN));
  }

  /**
   * {@link send()} や {@link sendConfirm()} で出力するメッセージのインデントを設定します。
   *
   * @param int $indent インデント数。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setSendIndent($sendIndent)
  {
    $this->_sendIndent = $sendIndent;
  }

  /**
   * {@link send()} や {@link sendConfirm()} で出力するメッセージの書式を設定します。
   *
   * @param string $sendPrefix メッセージの接頭辞に付ける文字列。
   * @param string $sendPrefix メッセージの接尾辞に付ける文字列。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setSendFormat($sendPrefix, $sendSuffix = NULL)
  {
    $this->_sendPrefix = $sendPrefix;
    $this->_sendSuffix = $sendSuffix;
  }

  /**
   * {@link send()} や {@link sendConfirm()} で出力するメッセージを装飾します。
   *
   * @param int $sendGraphicMode メッセージ装飾子。指定可能なオプションは {@link Delta_ANSIGraphic} クラスを参照。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setSendGraphicMode($sendGraphicMode)
  {
    $this->_sendGraphicMode = $sendGraphicMode;
  }

  /**
   * 送信するメッセージの装飾を行います。
   *
   * @param string $message 送信するメッセージ。
   * @return string 装飾されたメッセージ文字列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function decorateMessage($message)
  {
    if ($this->_sendIndent > 0) {
      $message = str_repeat(' ', $this->_sendIndent) . $message;
    }

    $message = $this->_sendPrefix . $message . $this->_sendSuffix;

    if ($this->_sendGraphicMode !== NULL) {
      $message = Delta_ANSIGraphic::build($message, $this->_sendGraphicMode);
    }

    return $message;
  }

  /**
   * コンソールにメッセージを送信した後に、クライアントから入力された文字列を取得します。
   *
   * @param string $message 送信するメッセージ。
   * @param bool $required 入力を必須とするかどうか。
   *   TRUE 指定時は (ホワイトスペースを除く) 何かしらの文字が入力されるまでメッセージを再送信する。
   * @return string クライアントが入力した文字列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function send($message, $required = FALSE)
  {
    $message = $this->decorateMessage($message);
    $output = new Delta_ConsoleOutput();

    do {
      $output->write($message);
      $response = trim(fgets(STDIN));

      if (!$required) {
        break;
      }

    } while (strlen($response) == 0);

    return $response;
  }

  /**
   * コンソールにメッセージを送信した後に、クライアントから入力された文字列を取得します。
   * 入力が許可される文字は次のリストに限定されます。(大文字・小文字は区別しない)
   *   o yes
   *   o y
   *   o no
   *   o n
   * 上記以外の文字が送信された場合は、正しいパターンが送信されるまでメッセージを再送信します。
   *
   * @param string $message 送信するメッセージ。
   * @return bool クライアントが 'yes' (y) を返した場合は TRUE、'no' (n) を返した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function sendConfirm($message)
  {
    $result = FALSE;
    $response = $this->sendChoice($message, array('y', 'yes', 'n', 'no'), FALSE);

    if (strcasecmp($response, 'y') === 0 || strcasecmp($response, 'yes') === 0) {
      $result = TRUE;
    }

    return $result;
  }

  /**
   * コンソールにメッセージを送信した後に、クライアントから入力された文字列を取得します。
   * 入力が許可される文字は allows リストに指定された文字に限定されます。
   *
   * @param string $message 送信するメッセージ。
   * @param array $allows クライアントからの入力を許可する文字列のリスト。
   *   例えば array('Y', 'N') を設定した場合、入力文字に 'Y' または 'N' が返されるまでメッセージを再送信する。
   * @param bool $strict allows で制限した文字の大文字・小文字を判別するかどうか。
   *   FALSE を設定した場合、大文字・小文字は区別されない。
   * @return string クライアントから入力された文字列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function sendChoice($message, array $allows, $strict = TRUE)
  {
    $message = $this->decorateMessage($message);

    $output = new Delta_ConsoleOutput();
    $size = sizeof($allows);

    if ($size && !$strict) {
      $allows = array_map('strtolower', $allows);
    }

    do {
      $output->write($message);
      $response = trim(fgets(STDIN));

      if ($size == 0) {
        break;
      }

      if ($strict) {
        $search = $response;
      } else {
        $search = strtolower($response);
      }

    } while (!in_array($search, $allows));

    return $response;
  }
}
