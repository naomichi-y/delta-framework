<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.code
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * このクラスは、実験的なステータスにあります。
 * これは、この関数の動作、関数名、ここで書かれていること全てが delta の将来のバージョンで予告な>く変更される可能性があることを意味します。
 * 注意を喚起するとともに自分のリスクでこのクラスを使用してください。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.code
 */

class Delta_CodeInspector extends Delta_Object
{
  /**
   * 全てのファイルのスタックトレースを出力する。
   */
  const CODE_ALL_STACK = 1;

  /**
   * アプリケーションファイルのみスタックトレースを出力する。
   */
  const CODE_APP_STACK = 2;

  /**
   * 特定のクラスやメソッドに関するスタックトレースを出力する。
   */
  const CODE_NAME_FILTER = 3;

  /**
   * @var int
   */
  private static $_retainCount = 0;

  /**
   * @var string
   */
  private $_descriptionFormat = 'at \1\2\3';

  /**
   * @var string
   */
  private $_subDescriptionFormat = 'in \4 [Line: \5]';

  /**
   * @var int
   */
  private $_codeRange = 3;

  /**
   * @var int
   */
  private $_visibleMode = self::CODE_APP_STACK;

  /**
   * @var array
   */
  private $_functions = array();

  /**
   * @var array
   */
  private $_classes = array();

  /**
   * @var array
   */
  private $_methods = array();

  /**
   *
   *
   * @param string $descriptionFormat
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setDescriptionFormat($descriptionFormat)
  {
    $this->_descriptionFormat = $descriptionFormat;
  }

  /**
   *
   *
   * @param stirng $subDescriptionFormat
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setSubDescriptionFormat($subDescriptionFormat)
  {
    $this->_subDescriptionFormat = $subDescriptionFormat;
  }

  /**
   *
   *
   * @param int $codeRange
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setCodeRange($codeRange)
  {
    $this->_codeRange = $codeRange;
  }

  /**
   *
   *
   * @param int $visibleMode
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setVisibleMode($visibleMode)
  {
    $this->_visibleMode = $visibleMode;
  }

  /**
   *
   *
   * @param string $function
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addFunction($function)
  {
    $this->_functions[] = $function;
  }

  /**
   *
   *
   * @param string $class
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addClass($class)
  {
    $this->_classes[] = $class;
  }

  /**
   *
   *
   * @param string $class
   * @param string $method
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addMethod($class, $method)
  {
    $this->_methods[$class] = $method;
  }

  /**
   *
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function resetRetainCount()
  {
    self::$_retainCount = 0;
  }

  /**
   *
   *
   * @return string
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function buildFromBacktrace()
  {
    return $this->build(debug_backtrace(), 1);
  }

  /**
   *
   *
   * @param Exception $exception
   * @return string
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function buildFromException(Exception $exception)
  {
    $trace = $exception->getTrace();

    if (isset($trace[0]['class']) && $trace[0]['class'] === 'Delta_ErrorHandler') {
      Delta_ArrayUtils::removeShift($trace, 0);
      Delta_ArrayUtils::removeShift($trace, 0);
    }

    $options = array();
    $options['file'] = $exception->getFile();
    $options['line'] = $exception->getLine();

    return $this->build($trace, 2, $options);
  }

  /**
   *
   *
   * @param array $trace
   * @param int $mode
   * @param array $options
   * @return stirng
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function build(array $trace, $mode, array $options = NULL)
  {
    if (sizeof($this->_functions) || sizeof($this->_classes) || sizeof($this->_methods)) {
      $restrict = TRUE;
    } else {
      $restrict = FALSE;
    }

    $view = new Delta_View(new Delta_BaseRenderer());
    self::$_retainCount++;

    mt_srand();
    $traces = array();

    $j = sizeof($trace);

    if ($mode == 2) {
      $file = $options['file'];
      $line = $options['line'];
    }

    for ($i = 0; $i <= $j; $i++) {
      $traces[$i]['traceId'] = mt_rand();

      if ($mode === 1 && $i < $j) {
        $file = $trace[$i]['file'];
        $line = $trace[$i]['line'];
      }

      $isOutput = FALSE;

      if (is_file($file)) {
        if ($i < $j) {
          $function = Delta_ArrayUtils::find($trace[$i], 'function');
          $class = Delta_ArrayUtils::find($trace[$i], 'class');

        } else {
          $function = 'main';
          $class = NULL;
        }

        $file = strtr($file, '\\', '/');
        $isOutput = TRUE;

        if ($restrict) {
          if (!in_array($function, $this->_functions) &&
              !in_array($class, $this->_classes) &&
              empty($this->_methods[$class][$function])) {

            $isOutput = FALSE;
          }
        }

        if ($isOutput) {
          $options = array(
            'format' => array(
              'type' => 'active',
              'target' => $line,
              'start' => 12,
              'end' => 18
            )
          );

          $code = Delta_DebugUtils::syntaxHighlight(Delta_FileUtils::readFile($file), $options);
          $from = array('\1', '\2', '\3', '\4', '\5');
          $to = array($class, ($class) ? '::' : '', $function . '()', $file, $line);

          $traces[$i]['title'] = str_replace($from, $to, $this->_descriptionFormat);
          $traces[$i]['file'] = str_replace($from, $to, $this->_subDescriptionFormat);

          switch ($this->_visibleMode) {
            case self::CODE_ALL_STACK:
              $isExpand = TRUE;
              break;

            case self::CODE_APP_STACK:
              $info = pathinfo($file);

              if (strcmp(substr($info['filename'], 0, 6), 'Delta_') == 0 || $i == $j) {
                $isExpand = FALSE;
              } else {
                $isExpand = TRUE;
              }

              break;

            case self::CODE_NAME_FILTER:
              $isExpand = FALSE;

              break;
          }

          $traces[$i]['isExpand'] = $isExpand;
          $traces[$i]['code'] = $code;

        } // end if
      } // end if

      $traces[$i]['isOutput'] = $isOutput;

      if ($mode == 2 && $i < $j) {
        $file = Delta_ArrayUtils::find($trace[$i], 'file');
        $line = Delta_ArrayUtils::find($trace[$i], 'line');
      }

    } // end for

    $path = DELTA_ROOT_DIR . '/skeleton/templates/code_inspector.php';
    $view->setAttribute('traces', $traces, FALSE);
    $view->setTemplatePath($path);

    return $view->fetch();
  }
}

