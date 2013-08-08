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
 * デバッグに関する汎用的なユーティリティメソッドを提供します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.common
 */
class Delta_DebugUtils
{
  /**
   * PHP 構文をハイライト表示形式に変換します。
   * この関数は標準の {@link highlight_string()} に比べ、以下のような特徴があります。
   *   - 行番号の出力をサポート
   *   - 任意のコード範囲を出力可能
   *   - コードの表示形式を拡張するスタイルシート属性をサポート
   *
   * @param string $source 解析する PHP コード。
   * @param array $options 出力形式のオプション。
   *   <ul>
   *     <li>
   *       display: 表示オプション
   *       <ul>
   *         <li>numbers: {TRUE} 行番号を出力</li>
   *       </ul>
   *     </li>
   *     <li>
   *       format: 出力形式
   *       <ul>
   *         <li>
   *           type: {all} 'all' (全て出力)、'active' (指定行を中心に前後行を出力)、'range' (指定範囲を出力) のいずれかを指定
   *         </li>
   *         <li>
   *           'type' が 'active' の時に指定可能な属性
   *           <ul>
   *             <li>before: {3} 'line' より前のコードの出力行数</li>
   *             <li>after: {3} 'line' より後のコードの出力行数</li>
   *             <li>target: {1} 出力の中心となる行数</li>
   *           </ul>
   *         </li>
   *         <li>
   *           'type' が 'range' の時に指定可能な属性
   *           <ul>
   *             <li>start: {1} 開始行</li>
   *             <li>end: 終了行。未指定時は最終行まで出力</li>
   *           </ul>
   *         </li>
   *       </ul>
   *     </li>
   *     <li>
   *       styles:
   *       <ul>
   *         <li>active: {#CCC} 'line' 行の背景色 ('format.type' が 'active' の場合に有効)</li>
   *         <li>number: {#000} 行番号の色 ('display.numbers' が TRUE の場合に有効)</li>
   *       </ul>
   *     </li>
   *   </ul>
   *   <code>
   *     // 30 行目を中心に前後 10 行 (合計 20 行) を表示
   *     $options = array(
   *       'format' => array(
   *         'type' => 'active',
   *         'before' => 10,
   *         'after' => 10,
   *         'target' => 30
   *       )
   *     );
   *
   *     Delta_DebugUtils::syntaxHighlight($source, $options);
   *   </code>
   * @return string ハイライトされたコードを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function syntaxHighlight($source, array $options = array())
  {
    $content = highlight_string($source, TRUE);
    $pos = strpos($content, "\n");

    $header = substr($content, 0, $pos);
    $content = substr($content, $pos + 1,  -15);
    $footer = "</span>\n</code>";
    $lines = explode('<br />', $content);

    $maxLine = sizeof($lines);

    if ($lines[$maxLine - 1] === '') {
      $maxLine--;
    }

    $format = Delta_ArrayUtils::find($options, 'format', array());
    $type = Delta_ArrayUtils::find($format, 'type', 'active');
    $target = NULL;

    switch ($type) {
      case 'active':
        $target = Delta_ArrayUtils::find($format, 'target', 1);
        $before = Delta_ArrayUtils::find($format, 'before', 3);
        $after = Delta_ArrayUtils::find($format, 'after', 3);

        // 出力開始行の取得
        if ($before >= $target) {
          $start = 1;
        } else {
          $start = $target - $before;
        }

        // 出力終了行の取得
        if ($after >= $maxLine) {
          $end = $maxLine;

        } else {
          $end = $target + $after;

          if ($end > $maxLine) {
            $end = $maxLine;
          }
        }

        break;

      case 'range':
        $start = Delta_ArrayUtils::find($format, 'start', 1);
        $end = Delta_ArrayUtils::find($format, 'end', $maxLine);

        break;

      case 'all':
      default:
        $start = 1;
        $end = $maxLine;
        break;
    }

    $buffer = NULL;
    $spanStyle = NULL;

    $current = 1;
    $padding = strlen($end);

    $numbers = Delta_ArrayUtils::find($options, 'display.numbers', TRUE);
    $activeBackgroundColor = Delta_ArrayUtils::find($options, 'styles.active', '#CCC');
    $numberColor = Delta_ArrayUtils::find($options, 'styles.number', '#000');

    foreach ($lines as $line) {
      $isActiveLine = FALSE;

      // 出力対象行であれば $isOutputLine を TRUE に設定
      if ($current >= $start && $current <= $end) {
        $isOutputLine = TRUE;
      } else {
        $isOutputLine = FALSE;
      }

      // 一行以上前のコードから <span> タグによる色指定が設定されており、かつ終了タグを見つけた場合
      if ($spanStyle && strpos($line, '</span>') === 0) {
        $spanStyle = NULL;
        $line = substr($line, 7);
      }

      // 出力対象行
      if ($isOutputLine) {
        if ($target && $target == $current) {
          $isActiveLine = TRUE;
          $buffer .= sprintf('<span style="background-color: %s; display: block">', $activeBackgroundColor);
        }

        if ($numbers) {
          $buffer .= sprintf('<span style="color: %s">%s: </span>',
            $numberColor,
            str_pad($current, $padding, '0', STR_PAD_LEFT));
        }

        if ($spanStyle) {
          $buffer .= sprintf('<span style="%s">', $spanStyle);
        }
      }

      // 現在の行に <span> タグが含まれるかチェック
      if (preg_match_all('/<span style="([^"]+)">/', $line, $matches, PREG_OFFSET_CAPTURE)) {
        $i = sizeof($matches[1]);

        // <span> タグが閉じられている
        if (preg_match_all('/<\/span>/', $line, $matches2)) {
          if (sizeof($matches[0]) == sizeof($matches2[0])) {
            if ($isOutputLine) {
              $buffer .= sprintf('%s</span>', $line);
            }

          } else {
            // <span> タグが次の行に続いている
            if ($isOutputLine) {
              $buffer .= sprintf("%s</span>", $line);
            }
          }

        } else {
          if ($isOutputLine) {
            $buffer .= sprintf("%s</span>", $line);
          }
        }

        $spanStyle = $matches[1][$i - 1][0];

      // 現在の行に <span> タグが含まれていない
      } else if ($isOutputLine) {
        if ($spanStyle) {
          $buffer .= sprintf("%s</span>", $line);
        } else {
          $buffer .= $line;
        }
      }

      if ($isOutputLine) {
        if ($isActiveLine) {
          // <span style="background-color: {...}; display: block"> タグを閉じる
          $buffer .= '</span>';

        } else {
          $buffer .= "<br />\n";
        }
      }

      $current++;
      $isActiveLine = FALSE;
    }

    if ($buffer !== NULL) {
      $buffer = preg_replace("/<br \/>\n$/", '', $buffer);
      $buffer = preg_replace('/<span style=\"([^"]+)\"><\/span>/', '', $buffer);
      $buffer = preg_replace('/<span style="[^"]+">(?:((&nbsp;)+))<\/span>/', '${1}', $buffer);

      $buffer = sprintf("%s\n%s\n%s", $header, $buffer, $footer);
    }

    return $buffer;
  }

  /**
   * デバッグ出力モードが有効な場合に message を出力します。
   *
   * @param mixed $message 出力するメッセージ。
   *   配列やオブジェクトを指定した場合は、内容を展開して出力します。
   * @param bool $flush TRUE を指定すると、直前までの出力バッファをクリアします。
   * @param bool $force 出力の制御。TRUE を指定した場合、デバッグ出力モード ('debug.output' 属性) の状態に関わらず、'debug.allows' で指定した IP アドレスからのリクエストであれば message を出力します。CLI から実行した場合は常に message を出力します。
   * @param bool $decoration 出力形式を装飾するかどうか。
   *   - TRUE: デバッグコードを見やすい表示で出力します。
   *   - FALSE: 引数に渡された内容をそのまま出力します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function output($message, $flush = FALSE, $force = FALSE, $decoration = TRUE)
  {
    if ($force) {
      $config = Delta_Config::getApplication();
      $debug = $config->getBoolean('debug');

      if (Delta_BootLoader::isBootTypeWeb() && !$debug['output']) {
        if (isset($debug['allows'])) {
          $address = $container->getComponent('request')->getRemoteAddress();
          $output = Delta_NetworkUtils::hasContainNetwork($debug['allows'], $address);

        } else {
          $output = FALSE;
        }

      } else {
        $output = TRUE;
      }

    } else {
      $output = self::isDebug();
    }

    if (!$output) {
      return;
    }

    if ($flush && ob_get_length()) {
      ob_clean();
    }

    if ($decoration) {
      $messageIsObjectOrArray = FALSE;

      $type = gettype($message);
      $isScalar = is_scalar($message);
      $message = Delta_CommonUtils::convertVariableToString($message);

      switch ($type) {
        case 'array':
        case 'object':
          $messageIsObjectOrArray = TRUE;
          break;
      }

      $trace = debug_backtrace();

      $file = $trace[0]['file'];
      $line = $trace[0]['line'];

      if (empty($retain[$file][$line])) {
        $retain[$file][$line] = 1;
      } else {
        $retain[$file][$line]++;
      }

      if (Delta_BootLoader::isBootTypeWeb()) {
        $subDescriptionFormat = sprintf('in \4 [Line: \5] (retain: %s)', $retain[$file][$line]);

        $inspector = new Delta_CodeInspector();
        $inspector->setVisibleMode(Delta_CodeInspector::CODE_NAME_FILTER);
        $inspector->addFunction('dprint');
        $inspector->setSubDescriptionFormat($subDescriptionFormat);

        if ($flush) {
          $inspector->resetRetainCount();
        }

        $code = $inspector->buildFromBacktrace();
        $path = DELTA_ROOT_DIR . '/skeleton/templates/dprint.php';

        static $functionCallCount = 0;
        $functionCallCount++;

        $view = new Delta_View(new Delta_BaseRenderer());
        $view->setAttribute('code', $code, FALSE);
        $view->setAttribute('type', $type);

        if ($isScalar) {
          $encoding = $config->get('charset.default');
          $length = mb_strlen($message, $encoding);
          $view->setAttribute('length', $length);
        }

        $view->setAttribute('message', $message);
        $view->setAttribute('functionCallCount', $functionCallCount);
        $view->setTemplatePath($path);
        $view->importHelpers();
        $view->execute();

      } else {
        $message = sprintf(" type: %s\n result: %s", $type, $message);
        $message = sprintf("# Delta_DebugUtils::output()\n#   in %s [Line: %s] (retain: %s)\n#\n%s",
          $file,
          $line,
          $retain[$file][$line],
          Delta_StringUtils::indent(Delta_StringUtils::indent($message, 2), 1, '#'));
        $message = Delta_StringUtils::indent($message . "\n", 2);

        $output = new Delta_ConsoleOutput();
        $output->write($message, Delta_ANSIGraphic::FOREGROUND_CYAN);
      }

    } else {
      print_r($message);
    }
  }

  /**
   * アプリケーションのデバッグ出力モードが有効であるかどうかチェックします。
   * デバッグ出力の設定は application.yml の 'debug.output' 属性で指定可能です。
   *
   * @return bool デバッグモードが有効な場合は TRUE、無効な場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function isDebug()
  {
    return Delta_Config::getApplication()->getBoolean('debug.output');
  }

  /**
   * デバッグメッセージをファイルに出力します。
   * この関数はファイルアペンダによるロギングを簡略化したものです。
   *
   * @param mixed $message デバッグメッセージ。
   *   ログファイルのデータはローテートされないため、定期的に削除する必要があります。
   * @param string $path ログを保存するパス。APP_ROOT_DIR/logs からの相対パス、あるいは絶対パスが有効。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function write($message, $path = 'dlog.log')
  {
    $trace = debug_backtrace();
    $info = pathinfo($trace[0]['file']);

    if (($pos = strpos($info['filename'], '.')) !== FALSE) {
      $info['filename'] = substr($info['filename'], 0, $pos);
    }

    $parameters = array();
    $parameters['class'] = 'Delta_LoggerFileAppender';
    $parameters['file'] = $path;

    $logger = Delta_Logger::getLogger($info['filename'], FALSE);
    $logger->addAppender('dlogAppender', new Delta_ParameterHolder($parameters, TRUE));
    $logger->debug($message);
  }

  /**
   * {@link debug_backtrace()} 関数の結果を視覚的に見やすい形式で出力します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function trace()
  {
    $path = DELTA_ROOT_DIR . '/skeleton/templates/dtrace.php';

    $view = new Delta_View(new Delta_BaseRenderer());
    $view->setAttribute('trace', debug_backtrace());
    $view->setTemplatePath($path);
    $view->importHelpers();
    $view->execute();
  }
}
