<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package net.mail
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * メール送信機能を提供します。
 *
 * <code>
 * {config/application.yml}
 * mail:
 *   # 送信手段。
 *   #   - smtp: SMTP プロトコルによるメール送信を行なう。({@link Delta_MailSender::BACKEND_TYPE_SMTP})
 *   #   - sendmail: sendmail プログラムによるメール送信を行なう。({@link Delta_MailSender::BACKEND_TYPE_SENDMAIL})
 *   #   - mail: PHP の mail() 関数によるメール送信を行なう。({@link Delta_MailSender::BACKEND_TYPE_MAIL})
 *   type: smtp
 *
 *   # 件名や本文、添付ファイル名に含まれる半角カナを全角カナに変換する。
 *   hanToZen: TRUE
 *
 *   # 添付ファイル名のエンコード形式。Delta_MailSender::ATTACHMENT_FILENAME_FORMAT_* 定数を指定。
 *   fileNameFormat: <?php echo Delta_MailSender::ATTACHMENT_FILENAME_FORMAT_RFC2231 ?>
 *
 *   # 改行コード。既定値は BACKEND_TYPE_* により異なる。
 *   linefeed:
 *
 *   # MIME エンコーディング。既定値は application.yml の 'charset:mime' で指定した値。
 *   encoding:
 *
 *   ############################################################
 *   # type が smtp の場合に指定可能なオプション
 *   ############################################################
 *
 *   # SMTP サーバのホスト名、または IP アドレス。
 *   host: localhost
 *
 *   # SMTP サーバの接続先ポート番号。
 *   port: 25
 *
 *   # SMTP サーバに接続するまでのタイムアウト秒。
 *   timeout: 30
 *
 *   # SMTP サーバとの接続を永続化するかどうか。
 *   persist: TRUE
 *
 *   ############################################################
 *   # type が sendmail の場合に指定可能なオプション
 *   ############################################################
 *
 *   # sendmail コマンドのパス。
 *   path: /usr/sbin/sendmail
 *
 *   # sendmail コマンドに渡すパラメータ。
 *   arguments: -i
 *
 *   ############################################################
 *   # type が mail の場合に指定可能なオプション
 *   ############################################################
 *
 *   # mail() 関数の第 5 引数に渡すパラメータ。詳しくは {@link mail()} 関数のマニュアルを参照。
 *   parameters:
 * </code>
 * - メールの送信形式は複数用意されていますが、大量のメールをループ処理などで送信したい場合は 'smtp' 形式を利用するべきです。
 *   'mail' 形式などは毎回 SMTP ソケットを開くため非効率な処理となります。
 * - 'smtp' 形式の送信ではインスタンスにおける初回送信時に SMTP ソケットを開き、2 通目以降は既に開いてるポートを利用して送信を行います。
 *   全ての送信を終えた後は必ず {@link close()} メソッドをコールしてソケットを閉じて下さい。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package net.mail
 *
 * @todo SMTP-AUTH 対応
*/

class Delta_MailSender extends Delta_Object {
  /**
   * バックエンド smtp 定数。SMTP プロトコルを介して直接メールを送信します。
   */
  const BACKEND_TYPE_SMTP = 'smtp';

  /**
   * バックエンド sendmail 定数。sendmail コマンドを経由してメールを送信します。
   */
  const BACKEND_TYPE_SENDMAIL = 'sendmail';

  /**
   * バックエンド mail 定数。mail() 関数を利用してメールを送信します。
   */
  const BACKEND_TYPE_MAIL = 'mail';

  /**
   * 宛て先タイプ。(TO)
   */
  const RECIPIENT_TYPE_TO = 'To';

  /**
   * 宛て先タイプ。(CC)
   */
  const RECIPIENT_TYPE_CC = 'Cc';

  /**
   * 宛て先タイプ。(BCC)
   */
  const RECIPIENT_TYPE_BCC = 'Bcc';

  /**
   * メールの重要度。(高)
   */
  const PRIORITY_URGENT = 'urgent';

  /**
   * メールの重要度。(中)
   */
  const PRIORITY_NORMAL = 'normal';

  /**
   * メールの重要度。(低)
   */
  const PRIORITY_NON_URGENT = 'non-urgent';

  /**
   * 添付ファイル名を RFC2231 に従ってエンコード。
   */
  const ATTACHMENT_FILENAME_FORMAT_RFC2231 = 1;

  /**
   * 添付ファイル名を base64 に従ってエンコード。(非推奨)
   */
  const ATTACHMENT_FILENAME_FORMAT_BASE64 = 2;

  /**
   * application.yml 'mail' パラメータ。
   * @var array
   */
  private $_options;

  /**
   * {@link Delta_SMTP} オブジェクト。
   * @var Delta_SMTP
   */
  private $_smtp;

  /**
   * 送信先アドレスリスト。
   * @var array
   */
  private $_envelopeTo = array();

  /**
   * 送信元アドレス。
   * @var string
   */
  private $_envelopeFrom;

  /**
   * 1 行辺りの最大長。
   * @var int
   */
  private $_width = 76;

  /**
   * BCC が含まれているか。
   * @var bool
   */
  private $_hasBcc = FALSE;

  /**
   * HTML メッセージ。
   * @var string
   */
  private $_html;

  /**
   * 最後に送信したメッセージ。
   * @var string
   */
  private $_latestSendData;

  /**
   * {@link Delta_MailPart} オブジェクト。
   * @var Delta_MailPart
   */
  protected $_part;

  /**
   * {@link Delta_MailPart} オブジェクト。(HTML パート)
   * @var Delta_MailPart
   */
  protected $_htmlPart;

  /**
   * {@link Delta_MailPart} オブジェクト。(添付パート)
   * @var Delta_MailPart
   */
  protected $_attachmentPart;

  /**
   * 入力エンコーディング。
   * @var string
   */
  protected $_inputEncoding;

  /**
   * コンストラクタ。
   *
   * @param array $options メール送信オプションの指定。
   *   指定可能なオプションは application.yml の 'mail' 属性を参照。
   * <code>
   * $mail = new Delta_MailSender(array('type' => 'smtp', 'timeout' => 3));
   * </code>
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct(array $options = array())
  {
    $config = Delta_Config::getApplication();

    if (sizeof($options) == 0) {
      $options = $config->get('mail', array());
    } else {
      $options = new Delta_ParameterHolder($options, TRUE);
    }

    $type = $options->getString('type', self::BACKEND_TYPE_SMTP);

    if ($type == self::BACKEND_TYPE_SMTP) {
      $options->set('type', $type, FALSE);
      $options->set('host', 'localhost', FALSE);
      $options->set('port', 25, FALSE);
      $options->set('timeout', 30, FALSE);
      $options->set('persist', FALSE, FALSE);
      $options->set('linefeed', "\r\n", FALSE);

    } else if ($type == self::BACKEND_TYPE_SENDMAIL) {
      $options->set('path', '/usr/sbin/sendmail', FALSE);
      $options->set('arguments', 'arguments', '-i', FALSE);
      $options->set('linefeed', "\n", FALSE);

    } else {
      // 本来ヘッダは LF で送るべきだが、MTA によっては正しく表示できないので CRLF で送信する
      $options->set('linefeed', "\n", FALSE);
    }

    $options->set('hanToZen', FALSE, FALSE);
    $options->set('fileNameFormat', self::ATTACHMENT_FILENAME_FORMAT_RFC2231, FALSE);

    // エンコードの設定
    $options->set('encoding', $config->getString('charset.mime'), FALSE);

    $this->_inputEncoding = $config->get('charset.default');
    $this->_options = $options;
    $this->_part = new Delta_MailPart($options->getString('linefeed'));
  }

  /**
   * メールヘッダに日付を設定します。
   * 未指定の場合は {@link http://www.ietf.org/rfc/rfc2822.txt RFC 2822} でフォーマットされた日付が追加されます。
   *
   * @param string $date 日付フォーマット。
   * @return Delta_MailSender Delta_MailSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setDate($date)
  {
    $this->addHeader('Date', $date);

    return $this;
  }

  /**
   * メールヘッダに Return-Path を設定します。
   *
   * @param string $returnPath Return-Path のアドレス。
   * @return Delta_MailSender Delta_MailSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setReturnPath($returnPath)
  {
    $returnPath = $this->sanitizeAddress($returnPath);
    $this->addHeader('Return-Path', $returnPath);

    return $this;
  }

  /**
   * メールヘッダに Reply-To を設定します。
   *
   * @param string $returnPath Reply-To のアドレス。
   * @return Delta_MailSender Delta_MailSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setReplyTo($replyTo)
  {
    $replyTo = $this->sanitizeAddress($replyTo);
    $this->addHeader('Reply-To', $replyTo);

    return $this;
  }

  /**
   * メールアドレスのローカルパートに通常は使用できない ASCII 文字 (":" や ";") が含まれる場合、ローカルパート全体をダブルクォートで括ります。
   *
   * 次のメソッドからは自動的に呼び出されます。
   * - {@link setFrom()}
   * - {@link setRecipients()}
   * - {@link setReturnPath()}
   * - {@link setReplyTo()}
   *
   * @param string $address 対象とするメールアドレス。
   * @return string サニタイズされたアドレスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function sanitizeAddress($address)
  {
    if (($pos = strpos($address, '@')) !== FALSE) {
      $local = substr($address, 0, $pos);
      $domain = substr($address, $pos + 1);

      if (preg_match('/[#\(\)<>\[\]:;@,\.\s]/', $local) && substr($local, 0, 1) !== '"') {
        $address = '"' . $local . '"@' . $domain;
      }
    }

    return $address;
  }

  /**
   * メールヘッダに優先度を設定します。
   * 優先度を設定すると、メールヘッダに X-Priority、X-MsMail-Priority、Priority フィールドが追加されます。
   *
   * @param int $priority PRIORITY_* 定数の指定。
   * @return Delta_MailSender Delta_MailSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setPriority($priority)
  {
    switch ($priority) {
      case self::PRIORITY_URGENT:
        $this->addHeader('X-Priority', 1);
        $this->addHeader('X-MsMail-Priotiry', 1);
        break;

      case self::PRIORITY_NORMAL:
        $this->addHeader('X-Priority', 3);
        $this->addHeader('X-MsMail-Priotiry', 3);
        break;

      case self::PRIORITY_NON_URGENT:
        $this->addHeader('X-Priority', 5);
        $this->addHeader('X-MsMail-Priotiry', 5);
        break;
    }

    $this->addHeader('Priority', $priority);

    return $this;
  }

  /**
   * メールの送信者を設定します。
   * <i>送信者が未指定の場合、現在のホスト名、プログラム実行者名が設定されます。</i>
   *
   * @param string $from 送信者のメールアドレス。
   * @param string $name 送信者名。
   * @return Delta_MailSender Delta_MailSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setFrom($from, $name = NULL)
  {
    $from = $this->sanitizeAddress($from);
    $this->_envelopeFrom = $from;

    if ($name !== NULL) {
      $from = '"' . $this->encodeMIMEHeader($name) . '" <' . $from . '>';
    } else {
      $from = $from;
    }

    $this->addHeader('From', $from);

    return $this;
  }

  /**
   * MIME ヘッダをエンコードします。
   * このメソッドは、{@link mb_encode_mimeheader()} で起きる文字化け不具合を改修するためのラッパーメソッドです。
   *
   * @param string $value 対象となる文字列。
   * @return string エンコード結果の文字列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function encodeMIMEHeader($value)
  {
    $encoding = $this->_options->getString('encoding');
    $internalEncoding = mb_internal_encoding();

    mb_internal_encoding($encoding);

    if ($this->_options->hasName('hanToZen')) {
      $value = mb_convert_kana($value, 'KV', $this->_inputEncoding);
    }

    $value = mb_convert_encoding($value, $encoding, $this->_inputEncoding);
    $value = mb_encode_mimeheader($value, $encoding, 'B', $this->_options->getString('linefeed'));

    mb_internal_encoding($internalEncoding);

    return $value;
  }

  /**
   * 宛先 To を設定します。
   *
   * @param mixed $addresses 送信先のアドレス。単一、または配列形式での複数指定が可能。
   * @return Delta_MailSender Delta_MailSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setTo($addresses)
  {
    $this->setRecipients(self::RECIPIENT_TYPE_TO, $addresses);

    return $this;
  }

  /**
   * 宛先 Cc を設定します。
   *
   * @param mixed $addresses 送信先のアドレス。単一、または配列形式での複数指定が可能。
   * @return Delta_MailSender Delta_MailSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setCc($addresses)
  {
    $this->setRecipients(self::RECIPIENT_TYPE_CC, $addresses);

    return $this;
  }

  /**
   * 宛先 Bcc を設定します。
   *
   * @param mixed $addresses 送信先のアドレス。単一、または配列形式での複数指定が可能。
   * @return Delta_MailSender Delta_MailSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setBcc($addresses)
  {
    $this->setRecipients(self::RECIPIENT_TYPE_BCC, $addresses);

    return $this;
  }

  /**
   * メールの宛先を設定します。
   *
   * @param string $recipientType 宛先タイプ。RECIPIENT_TYPE_* 定数を指定。
   * @param mixed $addresses 送信先のアドレス。単一、または配列形式での複数指定が可能。NULL 指定時は宛先をクリアします。
   * @return Delta_MailSender Delta_MailSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setRecipients($recipientType, $addresses)
  {
    if (is_array($addresses)) {
      $glue = ',' . $this->_options->getString('linefeed') . '  ';
      $recipients = NULL;

      $array = array();

      foreach ($addresses as $address) {
        $sanitize = $this->sanitizeAddress($address);
        $array[] = $sanitize;

        $recipients .= $sanitize . $glue;
      }

      // BACKEND_TYPE_SMTP の場合は BCC ヘッダを残さない
      if ($recipientType == self::RECIPIENT_TYPE_BCC && $this->_options->getString('type') == self::BACKEND_TYPE_SMTP) {
        $this->_hasBcc = TRUE;

      } else {
        $this->_part->addHeader($recipientType, rtrim($recipients, $glue));
      }

      $this->_envelopeTo['rcpt'][$recipientType] = $array;

    } else if (is_string($addresses)) {
      $addresses = $this->sanitizeAddress($addresses);

      if ($recipientType == self::RECIPIENT_TYPE_BCC && $this->_options->getString('type') == self::BACKEND_TYPE_SMTP) {
        $this->_hasBcc = TRUE;

      } else {
        $this->_part->addHeader($recipientType, $addresses);
      }

      $this->_envelopeTo['rcpt'][$recipientType] = array($addresses);

    } else {
      $this->_hasBcc = NULL;
      $this->_part->removeHeader($recipientType);
    }

    return $this;
  }

  /**
   * 任意のメールヘッダを追加します。
   *
   * @param string $name フィールド名。同じ値が再設定された場合、古い値は新しい値で置き換えられます。
   * @param string $value フィールド値。
   * @return Delta_MailSender Delta_MailSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addHeader($name, $value)
  {
    $value = Delta_StringUtils::replaceLinefeed($value, $this->_options->getString('linefeed'));
    $this->_part->addHeader($name, $value);

    return $this;
  }

  /**
   * メールの件名を設定します。
   *
   * @param string $subject メールの件名。改行コードは半角スペースに置換されます。
   * @return Delta_MailSender Delta_MailSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setSubject($subject)
  {
    $subject = Delta_StringUtils::replaceLinefeed($subject, ' ');
    $subject = $this->encodeMIMEHeader($subject);

    $this->addHeader('Subject', $subject);

    return $this;
  }

  /**
   * メールの本文を設定します。
   *
   * @param string $body メールの本文。
   * @return Delta_MailSender Delta_MailSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setBody($body)
  {
    $body = Delta_StringUtils::replaceLinefeed($body, $this->_options->getString('linefeed'));

    if ($this->_options->hasName('hanToZen')) {
      $body = mb_convert_kana($body, 'KV', $this->_inputEncoding);
    }

    $body = mb_convert_encoding($body, $this->_options->getString('encoding'), $this->_inputEncoding);
    $this->_part->setBody($body);

    return $this;
  }

  /**
   * {@link setBody()} のエイリアスメソッドです。
   *
   * @param string $body メールの本文。
   * @return Delta_MailSender Delta_MailSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setText($text)
  {
    $this->setBody($text);

    return $this;
  }

  /**
   * メール送信時に利用するテンプレートを設定します。
   * テンプレート内では PHP タグを使用することができます。(base_dicon.yml の設定による)
   * 一行目が件名となり、改行を 2 つ入れた後から本文と見なされます。
   *
   * <code>
   * Dear <?php echo $nickname ?>!
   *
   * Welcome to my site!
   * First, please open the following address for user authentication.
   * Code: <?php echo $code ?>
   * </code>
   * なお、行末で PHP タグで終わらせる場合、PHP は末尾の改行を除去する点に注意して下さい。
   * この問題は末尾に改行を 2 つ入れるか、もしくは PHP タグの後にスペースを入れることで回避することができます。
   *
   * 作成したテンプレートの名前を thanks.php とした場合、setTemplate() の指定は次のようになります。
   * <code>
   * $parameters = array('nickname' => 'Guest', 'code' => Delta_StringUtils::buildRandomString());
   * $mail->setTemplate('thanks', $parameters);
   * </code>
   *
   * <i>テンプレートが指定された場合、{@link setSubject()}、{@link setBody()} メソッドで指定された内容は破棄されます。</i>
   *
   * @param string $path 現在有効なテンプレートディレクトリからの相対パスで読み込むファイル名を指定。
   *   {@link Delta_AppPathManager::buildAbsolutePath()} も参照。
   * @param array $parameters テンプレートに割り当てる変数のリスト。
   * @return Delta_MailSender Delta_MailSender オブジェクトを返します。
   * @throws Delta_IOException テンプレートが見つからない場合に発生。
   * @see Delta_MailSender::setTemplateData()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setTemplate($path, array $parameters = array())
  {
    $extension = Delta_Config::getApplication()->getString('view.extension');
    $path = sprintf('mail%s%s', DIRECTORY_SEPARATOR, $path);

    $templatesPath = $this->getAppPathManager()->getInstance()->getTemplatesPath();
    $path = Delta_AppPathManager::buildAbsolutePath($templatesPath, $path, $extension);

    if (is_file($path)) {
      $view = new Delta_View();
      $view->setAttributes($parameters, FALSE);
      $view->setTemplatePath($path);

      $linefeed = $this->_options->getString('linefeed');

      $data = $view->fetch();
      $data = Delta_StringUtils::replaceLinefeed($data, $linefeed);

      $lines = explode($linefeed, $data);

      if (isset($lines[1]) && strlen($lines[1])) {
        $subject = array_shift($lines);
        $body = implode($linefeed, $lines);
        $data = $subject . $linefeed . $linefeed . $body;
      }

      $this->setTemplateData($data);

    } else {
      $message = sprintf('Template file does not exist. [%s]', $path);
      throw new Delta_IOException($message);
    }

    return $this;
  }

  /**
   * メール送信時に使用するテンプレートデータを設定します。
   *
   * @param string $data メールテンプレートのデータ。データの書式は {@link setTemplate()} メソッドを確認して下さい。
   * @return Delta_MailSender Delta_MailSender オブジェクトを返します。
   * @throws Delta_ParseException データの書式が不正な場合に発生。
   * @see Delta_MailSender::setTemplate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setTemplateData($data)
  {
    $linefeed = $this->_options->getString('linefeed');
    $data = Delta_StringUtils::replaceLinefeed($data, $linefeed);
    $regexp = sprintf('/([^%s]+)%s%s(.*)/s', $linefeed, $linefeed, $linefeed);

    if (preg_match($regexp, $data, $matches)) {
      $subject = $matches[1];
      $body = $matches[2];

      $this->setSubject($subject);
      $this->setBody($body);

    } else {
      $message = 'Template is malformed.';
      throw new Delta_IOException($message);
    }

    return $this;
  }

  /**
   * メールに HTML パートを設定します。
   * <i>HTML 非対応クライアントで閲覧した場合、全てのタグは除去された状態で表示されます。</i>
   *
   * @param string $htmlMessage HTML テキスト。
   * @return Delta_MailSender Delta_MailSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setHTMLText($htmlText)
  {
    // 改行コードの統一
    $linefeed = $this->_options->getString('linefeed');
    $htmlText = Delta_StringUtils::replaceLinefeed($htmlText, $linefeed);

    // 半角文字を全角文字に変換
    if ($this->_options->hasName('hanToZen')) {
      $htmlText = mb_convert_kana($htmlText, 'KV', $this->_inputEncoding);
    }

    $htmlText = Delta_StringUtils::encodeQuotedPrintable($htmlText, $this->_options->getString('linefeed'), $this->_width);

    // HTML パートの生成
    $encoding = $this->_options->getString('encoding');

    $htmlPart = new Delta_MailPart($linefeed);
    $htmlPart->setCharset($encoding);
    $htmlPart->setContentType('text/html', array('charset' => $encoding), TRUE);
    $htmlPart->setContentTransferEncoding('quoted-printable');
    $htmlPart->setBody($htmlText);

    $this->_html = $htmlText;
    $this->_htmlPart = $htmlPart;

    return $this;
  }

  /**
   * メールにファイルを添付します。
   *
   * @param string $path 添付ファイルのパスを指定。
   * @param string $fileName 添付ファイルをダウンロードする際のファイル名。未指定時は $path のファイル名が使用されます。
   * @param string $contentType コンテンツタイプ。
   * @param string $contentDisposition Content-Disposition ヘッダの値。('inline'、あるいは 'attachment')
   * @return Delta_MailSender Delta_MailSender オブジェクトを返します。
   * @throws exception Delta_DataNotFoundException ファイルパスが見つからない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addAttachmentFile($path,
    $fileName = NULL,
    $contentType = 'application/octet-stream',
    $contentDisposition = 'attachment')
  {
    if (!is_file($path)) {
      $message = sprintf('Attachment file does not exist. [%s]', $path);
      throw new Delta_DataNotFoundException($message);
    }

    if ($fileName === NULL) {
      $fileName = basename($path);
    }

    $data = file_get_contents($path);
    $this->addAttachmentData($data, $fileName, $contentType, $contentDisposition);

    return $this;
  }

  /**
   * メールにデータを添付します。
   *
   * @param string $data 添付するデータ。テキスト、バイナリ形式を指定することが出来ます。
   * @param string $fileName 添付ファイルをダウンロードする際のファイル名。
   * @param string $contentType コンテンツタイプ。
   * @param string $contentDisposition Content-Disposition ヘッダの値。
   * @return Delta_MailSender Delta_MailSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addAttachmentData($data,
    $fileName,
    $contentType = 'application/octet-stream',
    $contentDisposition = 'attachment')
  {
    if ($this->_options->hasName('hanToZen')) {
      $fileName = mb_convert_kana($fileName, 'KV', $this->_inputEncoding);
    }

    $fileName = mb_convert_encoding($fileName,
      $this->_options->getString('encoding'),
      $this->_inputEncoding);

    // Content-Type のオプションパラメータにファイル名を定義
    $contentTypeFileName = $this->encodeMIMEHeader($fileName);
    $parameters = array('name' => $contentTypeFileName);

    $attachmentPart = new Delta_MailAttachment($this->_options->getString('linefeed'));
    $attachmentPart->setContentType($contentType, $parameters, TRUE);
    $attachmentPart->setContentTransferEncoding('base64');

    // Content-Disposition のオプションパラメータにファイル名を定義
    if ($this->_options->getInt('fileNameFormat') == self::ATTACHMENT_FILENAME_FORMAT_RFC2231) {
      $parameters = $this->getFileNameParameter($fileName);
    } else {
      $parameters = array('filename' => $contentTypeFileName);
    }

    $data = base64_encode($data);

    $attachmentPart->setDispositionType($contentDisposition, $parameters, TRUE);
    $attachmentPart->setAttachment($data);

    if ($this->_attachmentPart === NULL) {
      $this->_attachmentPart = new Delta_MailPart($this->_options->getString('linefeed'));
    }

    $this->_attachmentPart->addPart($attachmentPart);

    return $this;
  }

  /**
   * 指定したファイル名を {@link http://www.ietf.org/rfc/rfc2231.txt RFC-2231} 形式の 'Content-Disposition' 書式に変換します。
   *
   * @param string $fileName 変換対象のファイル名。
   * @return array ファイル名パラメータを配列型で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function getFileNameParameter($fileName)
  {
    $buffer = $this->_options->getString('encoding') . '\'\'';
    $width = strlen($buffer);

    $j = strlen($fileName);
    $k = 0;

    $parameters = array();
    $width = $this->_width - 30;
    $length = 0;

    for ($i = 0; $i < $j; $i++) {
      $char = urlencode($fileName[$i]);
      $length += strlen($char);
      $buffer .= $char;

      if ($length > $width) {
        $name = 'filename*' . $k++ . '*';
        $parameters[$name] = $buffer;
        $buffer = NULL;
        $length = 0;
      }
    }

    if (strlen($buffer)) {
      if ($k > 0) {
        $name = 'filename*' . $k . '*';
        $parameters[$name] = $buffer;

      } else {
        $parameters['filename*'] = $buffer;
      }
    }

    return $parameters;
  }

  /**
   * 送信者、宛先、件名、本文をセットした上でメールを送信します。
   *
   * @param string $from 送信者のメールアドレス。
   * @param mixed $to 送信先 (To) アドレス。単一、または配列形式による複数指定が可能。
   * @param string $subject メールの件名。
   * @param string $body メールの本文。
   * @return Delta_MailSender Delta_MailSender オブジェクトを返します。
   * @see {@link Delta_MailSender::setFrom()}
   * @see {@link Delta_MailSender::setRecipients()}
   * @see {@link Delta_MailSender::setBody()}
   * @see {@link Delta_MailSender::send()}
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function buildToSend($from, $to, $subject, $body)
  {
    $this->setFrom($from);
    $this->setRecipients(self::RECIPIENT_TYPE_TO, $to);
    $this->setSubject($subject);
    $this->setBody($body);
    $this->send();

    return $this;
  }

  /**
   * メールを送信します。
   *
   * @return Delta_MailSender Delta_MailSender オブジェクトを返します。
   * @throws Delta_ConnectException SMTP サーバとの通信時に例外が起きた場合に発生。
   * @throws Delta_CommandException SMTP サーバへコマンド送信が失敗した場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function send()
  {
    $part = $this->_part;
    $body = $this->buildBody();

    if (!$part->hasHeader('From')) {
      $this->setFrom(php_uname('n'), get_current_user());
    }

    if ($this->_hasBcc) {
      if (!$part->hasHeader('To') && !$part->hasHeader('Bcc')) {
        throw new Delta_ParseException('To or Bcc header is undefined.');
      }
    }

    // エンベロープ From の設定
    $returnPath = $part->getHeaderValue('Return-Path');

    if ($returnPath !== NULL) {
      $envelopeFrom = $returnPath;
    } else {
      $envelopeFrom = $this->_envelopeFrom;
    }

    if ($this->_options->getString('type') == self::BACKEND_TYPE_SMTP) {
      $header = $this->buildHeader();
      $data = $header . $this->_options->getString('linefeed') . $body;

      if ($this->_smtp === NULL || !$this->_options->getBoolean('persist')) {
        $smtp = new Delta_SMTP($this->_options->getString('host'),
          $this->_options->getInt('port'),
          $this->_options->getInt('timeout'));
        $smtp->sendEhlo();

        $this->_smtp = $smtp;

      } else {
        $smtp = $this->_smtp;
      }

      $smtp->sendMailFrom($envelopeFrom);
      $envelopeToList = $this->buildRCPTToArray();

      foreach ($envelopeToList as $envelopeTo) {
        $smtp->sendRcptTo($envelopeTo);
      }

      $smtp->sendData($data);

      if (!$this->_options->hasName('persist')) {
        $smtp->sendQuit();
        $smtp->close();
      }

      $this->_latestSendData = $data;

    } else if ($this->_options->getString('type') == self::BACKEND_TYPE_SENDMAIL) {
      $header = $this->buildHeader() . $this->_options->getString('linefeed');

      $arguments = ' -f ' . $envelopeFrom;

      $envelopeTo = implode(' ', $this->buildRCPTToArray());
      $command = $this->_options->getString('path')
        .' '
        .$this->_options->getString('arguments')
        .' -- '
        .$envelopeTo;

      $handle = popen($command, 'w');
      fputs($handle, $header);
      fputs($handle, $body);
      pclose($handle);

      $this->_latestSendData = $header . $body;

    } else if ($this->_options->getString('type') == self::BACKEND_TYPE_MAIL) {
      $to = $part->getHeaderValue('To');
      $subject = $part->getHeaderValue('Subject');

      $latestSendData = $this->buildHeader() . $this->_options->getString('linefeed') . $body;

      $part->removeHeader('To');
      $part->removeHeader('Subject');

      $header = $this->buildHeader();

      if ($this->_options->hasName('parameters')) {
        mail($to, $subject, $body, trim($header), $this->_options->getString('parameters'));
      } else {
        mail($to, $subject, $body, trim($header));
      }

      $this->_latestSendData = $latestSendData;
    }

    return $this;
  }

  /**
   * 最後に送信したメールデータを取得します。
   *
   * @return string ヘッダとメッセージ部で構成される送信データを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getSendData()
  {
    return $this->_latestSendData;
  }

  /**
   * メール本文パートを構築します。
   *
   * @return string 本文パートの構成を返します。
   * @throws exception Delta_ParseException メールヘッダが不正な場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function buildBody()
  {
    $part = $this->_part;
    $lineFeed = $this->_options->getString('linefeed');
    $width = $this->_width;

    if (!$part->hasHeader('Date')) {
      $part->addHeader('Date', date('r'));
    }

    $part->addHeader('MIME-Version', '1.0');
    $parameters = array('charset' => $this->_options->getString('encoding'));
    $body = $part->getBody();

    // テキストメールを生成
    if ($this->_htmlPart === NULL && $this->_attachmentPart === NULL) {
      $part->setContentType('text/plain', $parameters, TRUE);
      $part->setContentTransferEncoding('7bit');

    // MIME 形式のメールを生成
    } else {
      if ($this->_attachmentPart === NULL) {
        $contentType = 'multipart/alternative';
      } else {
        $contentType = 'multipart/mixed';
      }

      if ($this->_htmlPart !== NULL) {
        if (strlen($body)) {
          $textPlainBody = $body;
        } else {
          $textPlainBody = strip_tags($this->_html);
        }

      } else {
        $textPlainBody = $part->getBody();
      }

      $boundary = $this->buildBoundary($contentType);
      $part->setContentType($contentType, array('boundary' => $boundary), TRUE);
      $part->setContentTransferEncoding('7bit');

      $body = 'This is a multi-part message in MIME format.' . $lineFeed . $lineFeed
             .'--' . $boundary . $lineFeed;

      // テキストパートの生成
      $plainPart = new Delta_MailPart($this->_options->getString('linefeed'));
      $plainPart->setCharset($this->_options->getString('encoding'));
      $plainPart->setContentType('text/plain', $parameters, TRUE);
      $plainPart->setContentTransferEncoding('7bit');

      $plainPart->setBody($textPlainBody);
      $plain = $plainPart->buildPart($width);

      // 添付ファイルを含む
      if (strcmp($contentType, 'multipart/mixed') == 0) {
        $attachmentParts = $this->_attachmentPart->getParts();

        if ($this->_htmlPart === NULL) {
          $body .= $plain . $lineFeed;

        } else {
          $contentType = 'multipart/alternative';
          $alternativeBoundary = $this->buildBoundary($contentType);

          $alternativePart = new Delta_MailPart($this->_options->getString('linefeed'));
          $alternativePart->setContentType('multipart/alternative', array('boundary' => '--' . $alternativeBoundary), TRUE);
          $alternativePart->setContentTransferEncoding('7bit');

          $body .= $alternativePart->buildPart($width) . $lineFeed . $lineFeed
                  .'----' . $alternativeBoundary . $lineFeed
                  .$plain . $lineFeed
                  .'----' . $alternativeBoundary . $lineFeed
                  .$this->_htmlPart->buildPart($width) . $lineFeed
                  .'----' . $alternativeBoundary . '--' . $lineFeed . $lineFeed;

        }

        foreach ($attachmentParts as $attachmentPart) {
          $body .= '--' . $boundary . $lineFeed
                  .$attachmentPart->buildPart($width) . $lineFeed;
        }

        $body .=  '--' . $boundary . '--' . $lineFeed . $lineFeed;

      } else {
        $body .= $plain. $lineFeed
                .'--' . $boundary . $lineFeed
                .$this->_htmlPart->buildPart($width) . $lineFeed
                .'--' . $boundary . '--' . $lineFeed . $lineFeed;
      }
    }

    return $body;
  }

  /**
   * MIME バウンダリ文字列を生成します。
   *
   * @param string $contentType MIME パートのコンテンツタイプ。
   * @return string MIME バウンダリを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function buildBoundary($contentType)
  {
    $suffix = str_replace('/', '_', strtoupper($contentType));
    $boundary = '----_' . md5(microtime(TRUE)) . '_' . $suffix . '_';

    return $boundary;
  }

  /**
   * To、Cc、Bcc ヘッダから実際にメールを送信する送信先アドレスリストを生成します。
   *
   * @return array エンベロープアドレスリストを返します。
   * @throws Delta_ParseException 宛先が不明な場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function buildRCPTToArray()
  {
    $envelopeTo = $this->_envelopeTo;

    if (empty($envelopeTo['rcpt'][self::RECIPIENT_TYPE_TO]) && empty($envelopeTo['rcpt'][self::RECIPIENT_TYPE_BCC])) {
      throw new Delta_ParseException('To or Bcc header is undefined.');
    }

    $toList = array();

    foreach ($envelopeTo['rcpt'] as $recipientType => $addresses) {
      foreach ($addresses as $index => $address) {
        $toList[] = $address;
      }
    }

    return $toList;
  }

  /**
   * メールヘッダを構築します。
   *
   * @return string ヘッダ構成を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function buildHeader()
  {
    $headers = $this->_part->getHeaders();
    $buildHeader = NULL;

    $width = $this->_width;
    $lineFeed = $this->_options->getString('linefeed');

    foreach ($headers as $name => $value) {
      $header = $name . ': ' . $value;
      $buildHeader .= Delta_MailPart::splitHeader($header, $width, $lineFeed) . $lineFeed;
    }

    return $buildHeader;
  }

  /**
   * メールオブジェクトをクリアします。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   * @return Delta_MailSender Delta_MailSender オブジェクトを返します。
   */
  public function close()
  {
    if ($this->_options->getString('type') == self::BACKEND_TYPE_SMTP && is_object($this->_smtp)) {
      $this->_smtp->close();
    }

    return $this;
  }
}
