<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package logger.appender
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * ログの出力をメールで送信します。
 *
 * application.yml の設定例:
 * <code>
 * logger:
 *   # ログアペンダ ID。
 *   {appender_name}:
 *     # ロガークラス名。
 *     class: Delta_LoggerMailAppender
 *
 *     # メール送信オプション。未指定の場合は application.yml の 'mail' 属性が参照される。
 *     # 設定可能な属性については {@link Delta_MailSender} のクラスリファレンスを参照。
 *     mail:
 *
 *     # 例外オブジェクトをロギングする場合の出力形式。
 *     #  - simple: 例外メッセージをシンプルな文字列形式で出力。
 *     #  - trace: 例外のスタックトレースを出力。
 *     #  - detail: 例外のサマリーに加え、スタックトレースや実行時のスーパーグローバル変数、HTTP リクエスト情報を出力。
 *     exception:
 *
 *     # 'exception: detail' が有効な場合に出力されるスーパーグローバル変数のうち、protectKeywords に列挙したキーは値がマスク表示となる。(配列形式で指定)
 *     # 例えば POST 値に含まれるパスワードやクレジットカード番号といったセキュアな情報をロギングしたくない場合に使うことが推奨される。
 *     # 出力されるスーパーグローバル変数は次の通り。(ただしコンソール下で実行時は $_SERVER、$_ENV のみ有効)
 *     #   - $_SERVER (server)
 *     #   - $_GET (get)
 *     #   - $_POST (post)
 *     #   - $_FILES (files)
 *     #   - $_COOKIE (cookie)
 *     #   - $_SESSION (session)
 *     #   - $_ENV (env)
 *     # 例えば $_SERVER['SERVER_NAME'] の値をマスク表示したい場合は 'server.SERVER_NAME' と指定する。(全ての $_SERVER 変数をマスク表示したい場合は 'server' を指定)
 *     # マスク変換処理は {@link maskProtectKeywords()} メソッドで実行される。
 *     protectKeywords:
 *
 *     # protectKeywords にマッチした値を表示するマスク文字列。
 *     protectMask: ********
 *
 *     # 送信元アドレスの指定。
 *     from:
 *
 *     # 送信者名。
 *     fromName:
 *
 *     # 送信先アドレスの指定。単一、または複数のアドレスが指定可能。
 *     to:
 * </code>
 * <i>その他に指定可能な属性は {@link Delta_LoggerAppender} クラスを参照して下さい。</i>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package logger.appender
 */
class Delta_LoggerMailAppender extends Delta_LoggerAppender
{
  /**
   * メールオブジェクトを生成します。
   *
   * @param Delta_ParameterHolder $holder パラメータホルダ。
   * @return Delta_MailSender Delta_MailSender のインスタンスを返します。
   * @throws Delta_ParseException 'from' または 'to' のアドレスが空の場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  protected function getMailSender()
  {
    static $instances = array();

    if (!isset($instances[$this->_appenderId])) {
      $holder = $this->_holder;

      $from = $holder->getString('from');
      $fromName = $holder->getString('fromName');
      $to = $holder->getArray('to');

      if ($from === NULL) {
        throw new Delta_ParseException('Required to send mail "From" header is undefined.');
      }

      if ($to === NULL) {
        throw new Delta_ParseException('Required to send mail "To" header is undefined.');
      }

      $config = $holder->getArray('mail');

      $mail = new Delta_MailSender($config);
      $mail->setFrom($from, $fromName);
      $mail->setRecipients(Delta_MailSender::RECIPIENT_TYPE_TO, $to);

      $instances[$this->_appenderId] = $mail;
    }

    return $instances[$this->_appenderId];
  }

  /**
   * メールの件名に使用するログのフォーマットを取得します。
   *
   * @param string $className ロギング対象のクラス名。
   * @param int $type ログタイプを Delta_Logger::MASK_* 定数で指定。
   * @param string $message 出力する件名。件名が 64 文字以上の場合、末尾は "..." となる。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getSubjectFormat($className, $type, $message)
  {
    $typeName = Delta_Logger::getTypeName($type);

    if ($message instanceof Exception) {
      $format = sprintf("[%s] %s - %s", $typeName, $className, $message->getMessage());
    } else {
      $format = sprintf("[%s] %s", $typeName, $className);
    }

    return Delta_StringUtils::truncate($format, 64);
  }

  /**
   * メールの本文に使用するログのフォーマットを取得します。
   *
   * @param string $className ロギング対象のクラス名。
   * @param int $type ログタイプを Delta_Logger::MASK_* 定数で指定。
   * @param string $message 出力するメッセージ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getBodyFormat($className, $type, $message)
  {
    if ($message instanceof Exception) {
      $exception = $this->_holder->getString('exception');
      $separator = str_repeat('=', 74);

      $skipCount = $this->getSkipCount();

      if ($skipCount) {
        $format = sprintf("%s\nSummary: %s Messages skipped.\n%s\n",
          $separator,
          number_format($skipCount),
          $separator);
      } else {
        $format = sprintf("%s\nSummary:\n%s\n",
          $separator,
          $separator);
      }

      $format .= sprintf("Date: %s\n"
        ."Exception: %s\n"
        ."Message: %s\n"
        ."File: %s\n"
        ."Line: %s\n"
        ."Code: %s\n\n",
        date($this->_holder->getString('dateFormat')),
        $className,
        $message->getMessage(),
        $message->getFile(),
        $message->getLine(),
        $message->getCode());

      if ($exception !== 'simple') {
        $format .= sprintf("%s\nTrace:\n%s\n%s\n\n",
          $separator,
          $separator,
          $message->getTraceAsString());

        if ($exception === 'detail') {
          $holder = $this->getParameterHolder();
          $protectKeywords = $holder->getArray('protectKeywords');
          $protectMask = $holder->getString('protectMask', '********');

          if (Delta_BootLoader::isBootTypeWeb()) {
            $superGlobals = array(
              'server' => $_SERVER,
              'get' => $_GET,
              'post' => $_POST,
              'files' => $_FILES,
              'cookie' => $_COOKIE,
              'session' => $_SESSION,
              'env' => $_ENV
            );

            if (is_array($protectKeywords)) {
              $superGlobals = $this->maskProtectKeywords($superGlobals, $protectKeywords, $protectMask);
            }

            $controller = Delta_FrontController::getInstance()->getRequest();
            $array = array(
              'REQUEST HEADERS' => $controller->getHeaders(),
              'RESPONSE HEADERS' => $controller->getHeaders(),
              '$_SERVER' => $superGlobals['server'],
              '$_GET' => $superGlobals['get'],
              '$_POST' => $superGlobals['post'],
              '$_FILES' => $superGlobals['files'],
              '$_COOKIE' => $superGlobals['cookie'],
              '$_SESSION' => $superGlobals['session'],
              '$_ENV' => $superGlobals['env']
            );

          } else {
            $superGlobals = array(
              'server' => $_SERVER,
              'env' => $_ENV
            );

            if (is_array($protectKeywords)) {
              $superGlobals = $this->maskProtectKeywords($superGlobals, $protectKeywords, $protectMask);
            }

            $array = array(
              'SERVER' => $superGlobals['server'],
              'ENV' => $superGlobals['env']
            );
          }

          foreach ($array as $name => $value) {
            $format .= sprintf("%s\n%s:\n%s\n%s\n\n",
            $separator,
            $name,
            $separator,
            Delta_CommonUtils::convertVariableToString($value));
          }
        }
      }

    } else {
      $date = date($this->_holder->getString('dateFormat'));
      $format = sprintf("%s\n\n(%s)", Delta_CommonUtils::convertVariableToString($message), $date);
    }

    return $format;
  }

  /**
   * メールに含まれるスーパーグローバル変数のうち、keywords にマッチする値をマスクします。
   * このメソッドはログ属性 'exception' が 'detail' の場合に実行されます。
   *
   * @param array $variables スーパーグローバル変数リスト。
   * @param array $keywords マスク対象の識別キー。
   * @param string $protectMask マスクに用いる文字。
   * @return array マスク処理を施したスーパーグローバル変数のリストを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  protected function maskProtectKeywords(array $variables, array $keywords, $protectMask)
  {
    foreach ($keywords as $keyword) {
      Delta_ArrayUtils::replaceValueWithCallback($variables, $keyword, function($value) use ($protectMask) {
        return $protectMask;
      });
    }

    return $variables;
  }

  /**
   * @see Delta_LoggerAppender::write()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function write($className, $type, $message)
  {
    $mail = $this->getMailSender();
    $mail->setSubject($this->getSubjectFormat($className, $type, $message));
    $mail->setBody($this->getBodyFormat($className, $type, $message));
    $mail->send();
  }

  /**
   * デストラクタ。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __destruct()
  {
    try {
      $mail = $this->getMailSender();

      if (is_object($mail)) {
        $mail->close();
      }

    // write() メソッドから getMailSender() が実行された場合の、getMailSender() 内で発生した例外を捕捉する
    // 例外を捕捉しない場合、例外ハンドリング後にデストラクタが起動してしまい、"Fatal error: Exception thrown without a stack frame in Unknown on line 0" エラーが発生してしまう
    } catch (Exception $e) {}
  }
}
