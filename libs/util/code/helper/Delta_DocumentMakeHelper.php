<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.code.helper
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
 * @package util.code.helper
 */

class Delta_DocumentMakeHelper extends Delta_Helper
{
  /**
   * @var string
   */
  private $_manualBaseURI = 'http://www.php.net/';

  /**
   * @var string
   */
  private $_fileId = NULL;

  /**
   * @var array
   */
  private $_indexes = array();

  /**
   * @var Delta_HTMLHelper
   */
  private $_html;

  /**
   * @var array
   */
  private $_internalFunctions;

  /**
   * @var array
   */
  private $_internalClasses;

  /**
   * @see Delta_Helper::__construct()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct(Delta_View $currentView, array $config = array())
  {
    $functions = get_defined_functions();

    $this->_internalFunctions = $functions['internal'];
    $this->_internalClasses = array_merge($this->getInternalClasses(), $this->getInternalInterfaces());

    parent::__construct($currentView, $config);
  }

  /**
   * @return array
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getInternalClasses()
  {
    $classes = get_declared_classes();
    $internalClasses = array();

    foreach ($classes as $className) {
      $class = new ReflectionClass($className);

      if ($class->isInternal()) {
        $internalClasses[] = $className;
      }
    }

    return $internalClasses;
  }

  /**
   * @return array
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getInternalInterfaces()
  {
    $interfaces = get_declared_interfaces();
    $internalInterfaces = array();

    foreach ($interfaces as $interfaceName) {
      $class = new ReflectionClass($interfaceName);

      if ($class->isInternal()) {
        $internalInterfaces[] = $interfaceName;
      }
    }

    return $internalInterfaces;
  }

  /**
   * @param string $fileId
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setFileID($fileId)
  {
    $this->_fileId = $fileId;
  }

  /**
   * @param array $indexes
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setIndexes($indexes)
  {
    $this->_indexes = $indexes;
    $this->_html = $this->_currentView->getHelperManager()->getHelper('html');
  }

  /**
   * @param string $string
   * @return bool
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function isExistClassName($string)
  {
    if (isset($this->_indexes['class'][$string]) || in_array($string, $this->_internalClasses)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * @param string $string
   * @return array
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function findConstantName($string)
  {
    $className = NULL;
    $constantName = NULL;

    if (preg_match('/^([_0-9A-Z]+)?$/', $string, $matches)) {
      $className = $this->_fileId;
      $constantName = $matches[1];

    } else if (preg_match('/^(\w+)::([_0-9A-Z]+)$/', $string, $matches)) {
      $className = $matches[1];
      $constantName = $matches[2];
    }

    if ($className !== NULL) {
      $search = sprintf('%s::%s', $className, $constantName);

      if (isset($this->_indexes['constant'][$search])) {
        return array($className, $constantName);
      }
    }

    return NULL;
  }

  /**
   * @param string $string
   * @return array
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function findMethodName($string)
  {
    $className = NULL;
    $methodName = NULL;

    if (preg_match('/^(\w+)(\(\))?$/', $string, $matches)) {
      $className = $this->_fileId;
      $methodName = $matches[1];

    } else if (preg_match('/^(\w+)::(\w+)(\(\))?$/', $string, $matches)) {
      $className = $matches[1];
      $methodName = $matches[2];
    }

    if ($className !== NULL) {
      $search = sprintf('%s::%s()', $className, $methodName);

      if (isset($this->_indexes['method'][$search])) {
        return array($className, $methodName);

      } else if (in_array($className, $this->_internalClasses)) {
        $class = new ReflectionClass($className);

        if ($class->hasMethod($methodName)) {
          return array($className, $methodName);
        }
      }
    }

    return NULL;
  }

  /**
   * @param string $string
   * @return array
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function findPropertyName($string)
  {
    $className = NULL;
    $propertyName = NULL;

    if (preg_match('/^\$(\w+)$/', $string, $matches)) {
      $className = $this->_fileId;
      $propertyName = $matches[1];

    } else if (preg_match('/^(\w+)::\$(\w+)$/', $string, $matches)) {
      $className = $matches[1];
      $propertyName = $matches[2];
    }

    if ($className !== NULL) {
      $search = sprintf('%s::$%s', $className, $propertyName);

      if (isset($this->_indexes['property'][$search])) {
        return array($className, $propertyName);
      }
    }

    return NULL;
  }

  /**
   * @param string $string
   * @return string
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function findFunctionName($string)
  {
    if (preg_match('/^(\w+)(\(\))?$/', $string, $matches)) {
      // ユーザ関数の検索
      $search = $matches[1] . '()';

      if (isset($this->_indexes[$search])) {
        return $matches[1];
      }

      // 内部関数の検索
      $search = $matches[1];

      if (in_array($search, $this->_internalFunctions)) {
        return $matches[1];
      }
    }

    return NULL;
  }

  /**
   * @param string $element
   * @param string $label
   * @return string
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function linkElement($element, $label = NULL)
  {
    if ($label === NULL) {
      $type = 'both';
    } else {
      $type = 'custom';
    }

    // 要素の解析順序は http://manual.phpdoc.org/HTMLSmartyConverter/HandS/phpDocumentor/tutorial_tags.see.pkg.html を参照

    // クラスへのリンク
    if ($this->isExistClassName($element)) {
      $value = $this->linkClass($element, $label);

    // ファイルへのリンク
    } else if (isset($this->_indexes['file'][$element])) {
      $value = $this->linkFile($element, $label);

    // 定数へのリンク
    } else if (isset($this->_indexes['define'][$element])) {
      $value = $this->linkDefine($element, $label);

    // クラス定数へのリンク
    } else if (($array = $this->findConstantName($element)) !== NULL) {
      $value = $this->linkConstant($array[0], $array[1], $type, $label);

    // メソッドへのリンク
    } else if (($array = $this->findMethodName($element)) !== NULL) {
      $value = $this->linkMethod($array[0], $array[1], $type, $label);

    // プロパティへのリンク
    } else if (($array = $this->findPropertyName($element)) !== NULL) {
      $value = $this->linkProperty($array[0], $array[1], $type, $label);

    // 関数へのリンク
    } else if (($functionName = $this->findFunctionName($element)) !== NULL) {
      $value = $this->linkFunction($functionName, $label);

    // URL へのリンク
    } else {
      if ($label === NULL) {
        $value = preg_replace(Delta_URLValidator::URL_QUERY_PATTERN, '<a href="\\1">\\1</a>', $element);

      } else {
        $value = preg_replace(Delta_URLValidator::URL_QUERY_PATTERN,
          sprintf('<a href="\\1">%s</a>', $label),
          $element);
      }
    }

    return $value;
  }

  /**
   * @param string $functionName
   * @return string
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function buildInternalFunctionLink($functionName)
  {
    return sprintf('%sfunction.%s.php', $this->_manualBaseURI, strtolower($functionName));
  }

  /**
   * @param string $className
   * @return string
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function buildInternalClassLink($className)
  {
    return sprintf('%sclass.%s.php', $this->_manualBaseURI, strtolower($className));
  }

  /**
   * @param string $className
   * @param string $methodName
   * @return string
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function buildInternalClassMethodLink($className, $methodName)
  {
    $methodName = ltrim(strtolower($methodName), '_');

    return sprintf('%s%s.%s.php', $this->_manualBaseURI, strtolower($className), $methodName);
  }

  /**
   * @param string $functionName
   * @param string $label
   * @return string
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function linkFunction($functionName, $label = NULL)
  {
    $search = $functionName . '()';
    $value = $functionName;

    // ユーザ関数の検索
    if (isset($this->_indexes[$search])) {
      $path = $this->_currentView->getAttribute('relativeAPIPath') . $this->_indexes[$search];

      if ($label === NULL) {
        $value = $value . '()';
      } else {
        $value = $label;
      }

      $value = $this->_html->link($value, $path);

    // 内部関数の検索
    } else if (in_array($value, $this->_internalFunctions)) {
      $path = $this->buildInternalFunctionLink($value);

      if ($label === NULL) {
        $value = $value . '()';
      } else {
        $value = $label;
      }

      $value = $this->_html->link($value, $path);
    }

    return $value;
  }

  /**
   * @param string $className
   * @param string $label
   * @return string
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function linkClass($className, $label = NULL)
  {
    $value = $className;

    if ($label !== NULL) {
      $value = $label;
    }

    // ユーザ定義クラスの検索
    if (isset($this->_indexes['class'][$className])) {
      $path = $this->_currentView->getAttribute('relativeAPIPath') . $this->_indexes['class'][$className];
      $value = $this->_html->link($value, $path);

    // 内部クラスの検索
    } else if (in_array($className, $this->_internalClasses)) {
      $path = $this->buildInternalClassLink($value);
      $value = $this->_html->link($value, $path);
    }

    return $value;
  }

  /**
   * @param string $fileName
   * @param string $label
   * @return string
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function linkFile($fileName, $label = NULL)
  {
    $value = $fileName;

    if (isset($this->_indexes['file'][$fileName])) {
      if ($label !== NULL) {
        $value = $label;
      }

      $path = $this->_currentView->getAttribute('relativeAPIPath') . $this->_indexes['file'][$fileName];
      $value = $this->_html->link($value, $path);
    }

    return $value;
  }

  /**
   * @param string $defineName
   * @param string $label
   * @return string
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function linkDefine($defineName, $label = NULL)
  {
    $value = $defineName;

    if (isset($this->_indexes['define'][$defineName])) {
      if ($label !== NULL) {
        $value = $label;
      }

      $path = $this->_currentView->getAttribute('relativeAPIPath') . $this->_indexes['define'][$defineName];
      $value = $this->_html->link($value, $path);
    }

    return $value;
  }

  /**
   * @param string $className
   * @param string $constantName
   * @param string $labelType
   * @param string $label
   * @return string
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function linkConstant($className, $constantName, $labelType = 'constant', $label = NULL)
  {
    $search = sprintf('%s::%s', $className, $constantName);

    switch ($labelType) {
      case 'both':
        $value = sprintf('%s::%s', $className, $constantName);
        break;

      case 'class':
        $value = $className;
        break;

      case 'constant':
        $value = $constantName;
        break;

      // custom
      default:
        $value = $label;
        break;
    }

    if (isset($this->_indexes['constant'][$search])) {
      if ($label !== NULL) {
        $value = $label;
      }

      $path = $this->_currentView->getAttribute('relativeAPIPath') . $this->_indexes['constant'][$search];
      $value = $this->_html->link($value, $path);
    }

    return $value;
  }

  /**
   * @param string $className
   * @param string $methodName
   * @param string $labelType
   * @param string $label
   * @return string
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function linkMethod($className, $methodName, $labelType = 'method', $label = NULL)
  {
    $search = sprintf('%s::%s()', $className, $methodName);

    switch ($labelType) {
      case 'both':
        $value = sprintf('%s::%s()', $className, $methodName);
        break;

      case 'class':
        $value = $className;
        break;

      case 'method':
        $value = sprintf('%s()', $methodName);
        break;

      // custom
      default:
        $value = $label;
        break;
    }

    if (isset($this->_indexes['method'][$search])) {
      $path = $this->_currentView->getAttribute('relativeAPIPath') . $this->_indexes['method'][$search];
      $value = $this->_html->link($value, $path);

    } else {
      $class = new ReflectionClass($className);

      if ($class->hasMethod($methodName)) {
        $path = $this->buildInternalClassMethodLink($className, $methodName);
        $value = $this->_html->link($value, $path);
      }
    }

    return $value;
  }

  /**
   * @param string $className
   * @param string $propertyName
   * @param string $labelType
   * @param string $label
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function linkProperty($className, $propertyName, $labelType = 'property', $label = NULL)
  {
    $search = sprintf('%s::$%s', $className, $propertyName);

    switch ($labelType) {
      case 'both':
        $value = sprintf('%s::$%s', $className, $propertyName);
        break;

      case 'class':
        $value = $className;
        break;

      case 'property':
        $value = '$' . $propertyName;
        break;

      // custom
      default:
        $value = $label;
        break;
    }

    if (isset($this->_indexes['property'][$search])) {
      $path = $this->_currentView->getAttribute('relativeAPIPath') . $this->_indexes['property'][$search];
      $value = $this->_html->link($value, $path);
    }

    return $value;
  }

  /**
   * @param array $matches
   * @return string
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function replaceLink(array $matches)
  {
    $type = $matches[1];
    $result = NULL;

    switch ($type) {
      case 'link':
        $value = $matches[1];
        $search = NULL;
        $label = NULL;

        if (($pos = strpos($matches[2], ' ')) !== FALSE) {
          $search = substr($matches[2], 0, $pos);
          $label = trim(substr($matches[2], $pos + 1));

        } else {
          $search = $label = $matches[2];
        }

        $result = $this->linkElement($search, $label);
        break;
    }

    return $result;
  }

  /**
   * @param string $string
   * @param bool $escape
   * @return string
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function decorate($string, $escape = TRUE)
  {
    if (strlen($string) == 0) {
      return $string;
    }

    if ($escape) {
      $string = $this->sanitize($string);
    }

    $lines = explode("\n", $string);

    $buffer = NULL;
    $inParagraph = FALSE;
    $isList = FALSE;
    $pos = FALSE;
    $preOffset = FALSE;

    foreach ($lines as $line) {
      $char = substr($line, 0, 2);

      if ($preOffset === FALSE && preg_match('/^\s*[-o\*] +(.*)/', $line, $matches, PREG_OFFSET_CAPTURE)) {
        // リストの開始位置を取得
        $pos = $matches[1][1];

        if ($isList) {
          $buffer .= "</li>\n";

        } else {
          if ($inParagraph) {
            $buffer .= "</div>\n";
            $inParagraph = FALSE;
          }

          $buffer .= "<ul>\n";
        }

        $buffer .= '<li>' . substr($line, $pos);
        $isList = TRUE;

      } else {
        if ($isList) {
          // リスト内で改行が含まれる場合
          if (strlen($line)) {
            preg_match('/ *(.*)/', $line, $matches, PREG_OFFSET_CAPTURE);

            // 改行以降の文字列がリスト開始位置より後であればリスト内文字列とする
            if ($matches[1][1] >= $pos) {
              $buffer .= "\n" . substr($line, $pos);

            } else {
              $buffer .= sprintf("</li>\n</ul>\n<div class=\"text\">%s\n", substr($line, $matches[1][1]));
              $inParagraph = TRUE;
              $isList = FALSE;
            }
          }

        } else if (strlen($line)) {
          if (!$inParagraph) {
            $buffer .= "<div class=\"text\">\n";
          }

          $preStart = FALSE;

          if (preg_match('/<(code|pre)>/', $line, $matches, PREG_OFFSET_CAPTURE)) {
            $pos = $matches[0][1];

            // <code|pre> タグの開始位置を取得
            preg_match('/( *)/', $line, $matches);
            $preOffset = strlen($matches[1]);

            // <code|pre> タグ以降の文字列エスケープ
            $line = substr($line, 0, $pos + 6) . Delta_StringUtils::escape(substr($line, $pos + 6));
            $preStart = TRUE;
          }

          if ($preOffset !== FALSE) {
            $regexp = sprintf('/ {%s}?(.*)/', $preOffset);
            if (preg_match('/<\/(code|pre)>/', $line, $matches, PREG_OFFSET_CAPTURE)) {
               $pos = $matches[0][1];

              // </code|/pre> タグ以前の文字列をエスケープ
              $before = substr($line, 0, $pos);

              if (preg_match($regexp, $before, $matches)) {
                $before = $matches[1];
              }

              $line = Delta_StringUtils::escape($before) . substr($line, $pos);
              $preOffset = FALSE;

            } else if (!$preStart) {
              // <code|pre> タグ内からインデント目的の余白を削除
              if (preg_match($regexp, $line, $matches)) {
                $line = Delta_StringUtils::escape($matches[1]);
              }
            }
          }

          $buffer .= $line . "\n";
          $inParagraph = TRUE;

        } else if ($preOffset !== FALSE) {
          $buffer .= "\n";

        } else if ($inParagraph) {
          $inParagraph = FALSE;
          $buffer .= "</div>\n";
        }
      }
    }

    if ($inParagraph) {
      $buffer .= "</div>\n";
    } else if ($isList) {
      $buffer .= "</li>\n</ul>\n";
    }

    $callback = array($this, 'replaceLink');
    $buffer = preg_replace_callback('/\{@([\w]+) *([^\}]+)\}/', $callback, $buffer);

    return $buffer;
  }

  /**
   * @param string $string
   * @return string
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function decorateText($string)
  {
    return $this->decorate($string);
  }

  /**
   * @param string $type
   * @param string $string
   * @return string
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function decorateTag($type, $string)
  {
    $string = $this->sanitize($string);

    switch ($type) {
      case 'author':
        $string = $this->_html->autoLink($string, array(), TRUE);
        break;

      case 'see':
        $string = $this->linkElement($string);
        break;

      case 'link':
        $array = array(NULL, 'link', $string);
        $string = $this->replaceLink($array);
        break;

      case 'snippet':
        $array = array(NULL, 'link', $string);
        $string = $this->replaceLink($array);
        break;

      default:
        break;
    }

    $string = $this->decorate($string, FALSE);

    return $string;
  }

  /**
   * @param string $code
   * @return string
   * @link http://manual.phpdoc.org/HTMLSmartyConverter/HandS/phpDocumentor/tutorial_phpDocumentor.howto.pkg.html#basics.desc DocBlock Description details
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function sanitize($code)
  {
    // 'strong' は拡張対応
    $allows = array('b', 'code', 'br', 'i', 'kbd', 'li', 'ol', 'p', 'pre', 'samp', 'ul', 'var', 'strong');

    // コード中で '&amp;' のように既にエスケープされている文字もダブルエンコードする ('&amp;amp;' 形式に変換)
    $string = Delta_StringUtils::escape($code, ENT_QUOTES, TRUE, $allows);

    $string = preg_replace('/<code>\s*/', '<div class="source"><code>', $string);
    $string = preg_replace('/<pre>(\s*)/', '<div class="source"><pre>\1', $string);
    $string = preg_replace('/<\/(code|pre)>/', '</\1></div>', $string);

    return $string;
  }
}
