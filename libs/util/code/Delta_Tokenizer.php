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

class Delta_Tokenizer extends Delta_Object
{
  const DOCBLOCK_FILE = 'file';
  const DOCBLOCK_FUNCTION = 'function';
  const DOCBLOCK_DEFINE = 'define';
  const DOCBLOCK_CLASS = 'class';
  const DOCBLOCK_CONSTANT = 'const';
  const DOCBLOCK_METHOD = 'method';
  const DOCBLOCK_PROPERTY = 'property';

  const REPORT_NOTICE = 'NOTICE';
  const REPORT_WARNING = 'WARNING';

  /**
   * @var string
   */
  private $_path;

  /**
   * @var bool
   */
  private $_outputError;

  /**
   * @var array
   */
  private $_tokens;

  /**
   * @var int
   */
  private $_last;

  /**
   * @var int
   */
  private $_current;

  /**
   * @var array
   */
  private $_result = array();

  /**
   * @var array
   */
  private $_docBlock = NULL;

  /**
   * @var bool
   */
  private $_inClass = FALSE;

  /**
   * @var bool
   */
  private $_isInterface = FALSE;

  /**
   * @var bool
   */
  private $_isFinal = FALSE;

  /**
   * @var bool
   */
  private $_isAbstractClass = FALSE;

  /**
   * @var bool
   */
  private $_isAbstractMethod = FALSE;

  /**
   * @var string
   */
  private $_classId = NULL;

  /**
   * @var int
   */
  private $_braceLevel = 0;

  /**
   * @var int
   */
  private $_functionBraceLevel = 0;

  /**
   * @var string
   */
  private $_access = 'public';

  /**
   * @var bool
   */
  private $_isStatic = FALSE;

  /**
   * @var bool
   */
  private $_inFunction = FALSE;

  /**
   * @var bool
   */
  private $_inInterfaceMethod = FALSE;

  /**
   * @var array
   */
  private $_errors = array();

  /**
   * @param string $path
   * @param bool $outputError
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($path, $outputError = FALSE)
  {
    $this->_path = $path;
    $this->_outputError = $outputError;

    $this->_tokens = token_get_all(Delta_FileUtils::readFile($path));
    $this->_current = 0;
    $this->_last = sizeof($this->_tokens);

    // ファイルパスの取得
    $this->_result['file']['absolutePath'] = $path;

    // ファイル名の取得
    $fileInfo = pathinfo($path);

    if (($pos = strpos($fileInfo['filename'], '.')) !== FALSE) {
      $fileName = substr($fileInfo['filename'], 0, $pos);
    } else {
      $fileName = $fileInfo['filename'];
    }

    $this->_result['file']['name'] = $fileName;
  }

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function parse()
  {
    $result = &$this->_result;

    for (; $this->_current < $this->_last; $this->_current++) {
      $token = $this->_tokens[$this->_current];

      if (is_array($token)) {
        // ファイルコメントの解析
        if ($this->_docBlock !== NULL && !isset($result['file']['document'])) {
          // T_DOC_COMMENT が連続する場合、1 つ目のブロックをファイルコメントと見なす
          if (isset($this->_tokens[$this->_current + 1]) && $this->_tokens[$this->_current + 1][0] == T_DOC_COMMENT) {
            $result['file']['document'] = $this->buildDocBlock(self::DOCBLOCK_FILE);
          }
        }

        switch ($token[0]) {
          // コメントの解析
          case T_DOC_COMMENT:
            $this->_docBlock = $token;
            break;

          // define 定数の解析
          case T_STRING:
            if ($token[1] === 'define') {
              $this->parseDefine();
            }

            break;

          // final 修飾子が含まれている
          case T_FINAL:
            $this->_isFinal = TRUE;
            break;

          // 抽象クラスが含まれている
          case T_ABSTRACT:
            if ($this->_inClass) {
              $this->_isAbstractMethod = TRUE;
            } else {
              $this->_isAbstractClass = TRUE;
            }
            break;

          // クラス・インタフェースの解析
          case T_INTERFACE:
            $this->_isInterface = TRUE;

          case T_CLASS:
            $this->parseClass();
            break;

          // 親クラスの解析
          case T_EXTENDS:
            $this->parseInheritance();
            break;

          // インタフェースの解析
          case T_IMPLEMENTS:
            $this->parseInterfaces();
            break;

          // アクセス修飾子が含まれている
          case T_PRIVATE:
          case T_PUBLIC:
          case T_PROTECTED:
            $this->_access = $token[1];
            break;

          // static 修飾子が含まれている
          case T_STATIC:
            $this->_isStatic = TRUE;
            break;

          // const 定数の解析
          case T_CONST:
            $this->parseConstant();
            break;

          // プロパティの解析
          case T_VARIABLE:
            if ($this->_inClass && !$this->_inFunction) {
              $this->parseProperty();
            }

            break;

          // 関数・メソッドの解析
          case T_FUNCTION:
            // 無名関数は解析対象外
            if (!$this->_inFunction) {
              $this->_functionBraceLevel = $this->_braceLevel;

              if ($this->_isInterface) {
                $this->_inInterfaceMethod = TRUE;
                $this->parseMethod();

              } else {
                $this->_inFunction = TRUE;

                if ($this->_inClass) {
                  $this->parseMethod();

                } else {
                  $this->parseFunction();
                }
              }
            }

            break;

          // 複雑な構文
          case T_CURLY_OPEN:
            $this->_braceLevel++;
            break;
        }

      } else {
        switch ($token) {
          case ';':
            if ($this->_isAbstractMethod) {
              $this->_isAbstractMethod = FALSE;
              $this->_inFunction = FALSE;

            } else if ($this->_inInterfaceMethod) {
              $this->_inInterfaceMethod = FALSE;
            }

            break;

          case '{':
            $this->_braceLevel++;
            break;

          case '}':
            $this->_braceLevel--;

            // メソッドの終わり
            if ($this->_inFunction && $this->_functionBraceLevel == $this->_braceLevel) {
              $this->_inFunction = FALSE;

            } else if ($this->_braceLevel == 0) {
              // クラスの終わり
              $this->_inClass = FALSE;
              $this->_isAbstractClass = FALSE;
              $this->_isInterface = FALSE;
            }

            break;
        }
      }
    }

    // ファイルコメントが宣言されていない場合に警告
    if (!isset($result['file']['document'])) {
      $message = 'Undefined file comment.';
      $this->addError(2, self::REPORT_NOTICE, $message);
    }

    // パッケージの取得
    if (isset($result['file']['document']['tags']['package'])) {
      $package = $result['file']['document']['tags']['package'];
    } else {
      $package = 'default';
    }

    $result['file']['package'] = $package;
  }

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function parseClass()
  {
    $array = array();
    $array['hasPublicProperty'] = FALSE;
    $array['hasProtectedProperty'] = FALSE;
    $array['hasPrivateProperty'] = FALSE;
    $array['hasPublicMethod'] = FALSE;
    $array['hasProtectedMethod'] = FALSE;
    $array['hasPrivateMethod'] = FALSE;

    // インタフェースの判定
    if ($this->_tokens[$this->_current][0] == T_INTERFACE) {
      $array['isInterface'] = TRUE;
    } else {
      $array['isInterface'] = FALSE;
    }

    $this->_inClass = TRUE;

    // クラス名 (インタフェース名) の取得
    $this->_current += 2;
    $array['name'] = $this->_tokens[$this->_current][1];

    // クラスコメントの解析
    $array['document'] = $this->buildDocBlock(self::DOCBLOCK_CLASS);

    // パッケージ名の取得
    if (isset($array['document']['tags']['package'])) {
      $array['package'] = $array['document']['tags']['package'];

    } else {
      $array['package'] = 'default';
    }

    // クラス ID の確定
    $this->_classId = $array['package'] . '.' . $array['name'];

    // final 修飾子を追加
    if ($this->_isFinal) {
      $array['isFinal'] = TRUE;
      $this->_isFinal = FALSE;

    } else {
      $array['isFinal'] = FALSE;
    }

    // abstract 修飾子の追加
    if ($this->_isAbstractClass) {
      $array['isAbstract'] = TRUE;
      $this->_isAbstract = FALSE;

    } else {
      $array['isAbstract'] = FALSE;
    }

    $this->_result['classes'][$this->_classId] = $array;
  }

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function parseInheritance()
  {
    $this->_current += 2;
    $className = $this->_tokens[$this->_current][1];
    $this->_result['classes'][$this->_classId]['inheritance'] = $className;
  }

  /**
   * @param string $buffer
   * @return string
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function splitDocumentTags($buffer)
  {
    $buffer = substr($buffer, 1);
    $lines = preg_split("/\n@/", $buffer);
    $attributes = array();

    foreach ($lines as $line) {
      if (preg_match('/^([\w]+)\s?(.+)?/s', $line, $matches)) {
        $tag = $matches[1];

        // タグに説明が含まれる場合
        if (isset($matches[2])) {
          $value = $matches[2];

        // タグに説明が含まれない場合は警告とする (e.g. '@deprecated')
        } else {
          $value = '';
          $message = 'Undefined tag comment.';
          $this->addError($this->_current, self::REPORT_NOTICE, $message);
        }

        switch ($tag) {
          // @param タグの解析
          case 'param':
            if (preg_match('/([\w]+) *(&?\$\w+) *(.*)/s', $value, $matches)) {
              $array = array();
              $array['type'] = $matches[1];

              $description = trim($matches[3]);

              if (strlen($description) == 0) {
                $message = '@' . $line;
                $this->addError($this->_docBlock[2], self::REPORT_WARNING, $message);

              } else {
                $array['description'] = $description;
              }

              $attributes['param'][$matches[2]] = $array;

            } else {
              $message = '@' . $line;
              $this->addError($this->_docBlock[2], self::REPORT_WARNING, trim($message));
            }

            break;

          // @return タグの解析
          case 'return':
            if (preg_match('/([\w]+) *(.*)/s', $value, $matches)) {
              if (strlen($matches[2]) == 0) {
                $message = '@' . $line;
                $this->addError($this->_docBlock[2], self::REPORT_WARNING, $message);

              } else {
                $array = array();
                $array['type'] = $matches[1];
                $array['description'] = $matches[2];

                $attributes['return'] = $array;
              }

            } else {
              $message = '@' . $line;
              $this->addError($this->_docBlock[2], self::REPORT_WARNING, $message);
            }

            break;

          // その他のタグの解析
          default:
            if (preg_match('/(.*)/s', $value, $matches)) {
              $description = trim($matches[1]);

              switch ($tag) {
                case 'package':
                case 'var':
                case 'access':
                  $attributes[$tag] = $description;
                  break;

                default:
                  $attributes[$tag][] = $description;
                  break;
              }

            } else {
              $message = '@' . $line;
              $this->addError($this->_docBlock[2], self::REPORT_WARNING, $message);
            }

            break;
        }

      } else {
        $message = $line;
        $this->addError($this->_docBlock[2], self::REPORT_WARNING, $message);
      }
    }

    return $attributes;
  }

  /**
   * @param bool $docBlockType
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function buildDocBlock($docBlockType)
  {
    $attributes = array();
    $attributes['hasExtraTag'] = FALSE;

    if (is_array($this->_docBlock)) {
      // コメント行のマークアップを削除
      $document = $this->_docBlock[1];
      $document = preg_replace("/^ *\/\**[\r|\n|\r\n]/", '', $document);
      $document = preg_replace("/^ *\** {0,1}/m", '', $document);
      $document = preg_replace("/^ *\**\/ *$/m", '', $document);

      $description = NULL;

      if (preg_match("/^@[\w]+|\n@[\w]+/", $document, $matches, PREG_OFFSET_CAPTURE)) {
        $description = trim(substr($document, 0, $matches[0][1]));

        if ($matches[0][1] == 0) {
          $buffer = substr($document, $matches[0][1]);
        } else {
          // 改行分を +1
          $buffer = substr($document, $matches[0][1] + 1);
        }

        $tags = $this->splitDocumentTags($buffer);

        if (Delta_ArrayUtils::isExistKeyWithExpect($tags, array('param', 'return'))) {
          $attributes['hasExtraTag'] = TRUE;
        }

        $attributes['tags'] = $tags;

      } else {
        $description = trim($document);
      }

      if (strlen($description)) {
        $attributes['description'] = $description;
      }

    } else {
      $this->_docBlock[2] = $this->_tokens[$this->_current][2];
    }

    // 要約の作成
    if (isset($attributes['description']) && strlen($attributes['description'])) {
      $lines = explode("\n", $attributes['description']);
      $attributes['summary'] = $lines[0];

    } else {
      $line = $this->_tokens[$this->_current][2];
      $message = sprintf('Undefined %s comment.', $docBlockType);
      $this->addError($line, self::REPORT_NOTICE, $message);
    }

    // メソッドコメントで '@return' タグが無い場合は void 型の戻り値を設定
    if ($docBlockType == self::DOCBLOCK_FUNCTION || $docBlockType == self::DOCBLOCK_METHOD) {
      if (!isset($attributes['tags']['return'])) {
        $array = array();
        $array['type'] = 'void';
        $array['description'] = NULL;

        $attributes['tags']['return'] = $array;
      }
    }

    $this->_docBlock = NULL;

    return $attributes;
  }

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function parseInterfaces()
  {
    $this->_current += 2;
    $interfaces = array();

    for (; $this->_current < $this->_last; $this->_current++) {
      $token = $this->_tokens[$this->_current];

      if (is_array($token)) {
        if ($token[0] == T_STRING) {
          $interfaces[] = $token[1];
        }

      } else {
        if ($token === '{') {
          $this->_braceLevel++;
          break;
        }
      }
    }

    $this->_result['classes'][$this->_classId]['interfaces'] = $interfaces;
  }

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function parseConstant()
  {
    $array = array();
    $array['document'] = $this->buildDocBlock(self::DOCBLOCK_CONSTANT);

    $const = $this->buildConstant();
    $name = key($const);

    $array['statement'] = $this->buildAssignmentString($const);

    $this->_result['classes'][$this->_classId]['constants'][$name] = $array;
  }

  /**
   * @return array
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function buildConstant($isClass = TRUE)
  {
    $array = array();
    $i = 0;

    for ($this->_current++; $this->_current < $this->_last; $this->_current++) {
      $token = $this->_tokens[$this->_current];

      if (is_array($token)) {
        switch ($token[0]) {
          // 名前または値が文字列で構成される場合
          case T_CONSTANT_ENCAPSED_STRING:
            $string = $this->_tokens[$this->_current][1];

            if ($i == 0) {
              $array[$i] = str_replace(array('\'', '"'), '', $string);
            } else {
              $array[$i] = $string;
            }

            break;

          case T_LINE:
          case T_FILE:
          case T_DIR:
          case T_FUNC_C:
          case T_CLASS_C:
          case T_METHOD_C:
          case T_NS_C:
          case T_LNUMBER:
          case T_DNUMBER:
          case T_STRING:
          case T_DOUBLE_COLON:
            if (isset($array[$i])) {
              $array[$i] .= $token[1];
            } else {
              $array[$i] = $token[1];
            }

            break;

          case T_WHITESPACE:
            if (isset($array[$i]) && isset($array[1])) {
              $array[$i] .= $token[1];
            }

            break;
        }

      } else {
        if ($token === ';') {
          break;

        } else if ($token === '=' || $token === ',') {
          $i++;

        // 演算子の追加
        } else if ($i == 1) {
          if (isset($array[$i])) {
            $array[$i] .= $token;
          } else {
            $array[$i] = $token;
          }
        }
      }
    }

    return array($array[0] => $array[1]);
  }

  /**
   * @param array $assignments
   * @return string
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function buildAssignmentString(array $assignments)
  {
    $buffer = NULL;

    foreach ($assignments as $name => $value) {
      if (Delta_StringUtils::nullOrEmpty($value)) {
        $buffer .= $name . ', ';
      } else {
        $buffer .= $name .= ' = ' . $value . ', ';
      }
    }

    return trim($buffer, ', ');
  }

  /**
   * @return array
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function buildModifier()
  {
    $token = $this->_tokens[$this->_current];
    $modifier = NULL;
    $array = array();

    // 抽象メソッドの解析
    if ($this->_isAbstractMethod) {
      $array['isAbstract'] = TRUE;
      $modifier = 'abstract ';
      $this->_isAbstract = FALSE;

    } else {
      $array['isAbstract'] = FALSE;
    }

    // アクセス修飾子の解析
    $array['access'] = $this->_access;
    $this->_access = 'public';

    $modifier .= $array['access'] . ' ';

    // static 修飾子の解析
    if ($this->_isStatic) {
      $array['isStatic'] = TRUE;
      $modifier .= 'static ';
      $this->_isStatic = FALSE;

    } else {
      $array['isStatic'] = FALSE;
    }

    $array['modifier'] = trim($modifier);

    return $array;
  }

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function parseMethod()
  {
    $array = $this->buildModifier();
    $array['document'] = $this->buildDocBlock(self::DOCBLOCK_METHOD);

    // メソッド引数の解析
    switch ($array['access']) {
      case 'public':
        $this->_result['classes'][$this->_classId]['hasPublicMethod'] = TRUE;
        break;

      case 'protected':
        $this->_result['classes'][$this->_classId]['hasProtectedMethod'] = TRUE;
        break;

      case 'private':
        $this->_result['classes'][$this->_classId]['hasPrivateMethod'] = TRUE;
        break;
    }

    // 参照渡しメソッド
    $this->_current += 2;

    if (is_string($this->_tokens[$this->_current])) {
      $this->_current++;
      $methodName = '&' . $this->_tokens[$this->_current][1];

    // 値渡しメソッド
    } else {
      $methodName = $this->_tokens[$this->_current][1];
    }

    $this->_current += 2;
    $assignments = $this->getAssignments();

    $array['statement'] = sprintf('%s %s %s(%s)',
      $array['modifier'],
      $array['document']['tags']['return']['type'],
      $methodName,
      $this->buildAssignmentString($assignments));

    if ($array['document']['tags']['return']['type'] !== 'void') {
      $array['hasReturn'] = TRUE;
    } else {
      $array['hasReturn'] = FALSE;
    }

    if (isset($array['document']['tags']['param'])) {
      $array['hasParameter'] = TRUE;
    } else {
      $array['hasParameter'] = FALSE;
    }

    $this->_result['classes'][$this->_classId]['methods'][$methodName] = $array;
  }

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function parseProperty()
  {
    $array = $this->buildModifier();
    $array['document'] = $this->buildDocBlock(self::DOCBLOCK_PROPERTY);

    switch ($array['access']) {
      case 'public':
        $this->_result['classes'][$this->_classId]['hasPublicProperty'] = TRUE;
        break;

      case 'protected':
        $this->_result['classes'][$this->_classId]['hasProtectedProperty'] = TRUE;
        break;

      case 'private':
        $this->_result['classes'][$this->_classId]['hasPrivateProperty'] = TRUE;
        break;
    }

    $assignments = $this->getAssignments();

    foreach ($assignments as $name => $value) {
      if (isset($array['document']['tags']['var'])) {
        $type = $array['document']['tags']['var'];

      } else {
        $type = 'mixed';
        $array['document']['tags']['var'] = $type;
      }

      $array['variable'] = $name;
      $array['statement'] = sprintf('%s %s %s',
                                    $array['modifier'],
                                    $type,
                                    $this->buildAssignmentString(array($name => $value)));

      $name = substr($name, 1);
      $this->_result['classes'][$this->_classId]['properties'][$name] = $array;
    }
  }

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function parseFunction()
  {
    $array = array();
    $array['document'] = $this->buildDocBlock(self::DOCBLOCK_FUNCTION);

    $this->_current += 2;

    // アクセス修飾子の解析
    if (isset($array['document']['tags']['access'])) {
      $array['access'] = $array['document']['tags']['access'];
    } else {
      $array['access'] = 'public';
    }

    // 参照渡し関数
    if (is_string($this->_tokens[$this->_current])) {
      $this->_current++;
      $name = '&' . $this->_tokens[$this->_current][1];

    // 値渡し関数
    } else {
      $name = $this->_tokens[$this->_current][1];
    }

    $this->_current += 2;
    $assignments = $this->getAssignments();

    $array['statement'] = sprintf('%s %s(%s)',
                                 $array['document']['tags']['return']['type'],
                                 $name,
                                 $this->buildAssignmentString($assignments));

    if ($array['document']['tags']['return']['type'] !== 'void') {
      $array['hasReturn'] = TRUE;
    } else {
      $array['hasReturn'] = FALSE;
    }

    if (isset($array['document']['tags']['param'])) {
      $array['hasParameter'] = TRUE;
    } else {
      $array['hasParameter'] = FALSE;
    }

    $this->_result['functions'][$name] = $array;
  }

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function parseDefine()
  {
    $array = array();
    $array['document'] = $this->buildDocBlock(self::DOCBLOCK_DEFINE);

    $define = $this->buildConstant(FALSE);
    $name = key($define);

    $array['statement'] = $this->buildAssignmentString($define);

    $this->_result['defines'][$name] = $array;
  }

  /**
   * @param mixed $value
   * @return array
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function getAssignments($value = array())
  {
    $statement = NULL;
    $isReference = FALSE;
    $inVariable = FALSE;
    $inTypeHinting = FALSE;

    for (; $this->_current < $this->_last; $this->_current++) {
      $token = $this->_tokens[$this->_current];

      if (is_array($token)) {
        switch ($token[0]) {
          case T_VARIABLE:
            // 参照渡し
            if ($isReference) {
              if ($inTypeHinting) {
                $statement .= '&' . $token[1];
              } else {
                $statement = '&' . $token[1];
              }

              $isReference = FALSE;

            // 値渡し
            } else {
              if ($inTypeHinting) {
                $statement .= $token[1];

              } else {
                $statement = $token[1];
              }
            }

            $value[$statement] = NULL;
            $inTypeHinting = FALSE;
            $inVariable = TRUE;
            break;

          case T_ARRAY:
            if ((!$this->_inFunction && !$this->_inInterfaceMethod) || ($inVariable || $value === NULL)) {
              $this->_current += 2;

              if ($statement === NULL) {
                $value .= 'array(' . $this->getAssignments(NULL) . ')';
              } else {
                $value[$statement] = 'array(' . $this->getAssignments(NULL) . ')';
              }

              break;
            }

          case T_STRING:
            // タイプヒンティングの解析
            if (is_array($value) && !$inVariable) {
              $statement .= $token[1] . ' ';
              $inTypeHinting = TRUE;

              break;
            }

          case T_LINE:
          case T_FILE:
          case T_DIR:
          case T_FUNC_C:
          case T_CLASS_C:
          case T_METHOD_C:
          case T_NS_C:
          case T_LNUMBER:
          case T_DNUMBER:
          case T_CONSTANT_ENCAPSED_STRING:
          case T_PAAMAYIM_NEKUDOTAYIM:
          case T_DOUBLE_COLON:
            if ($statement === NULL) {
              $value .= $token[1];
            } else {
              $value[$statement] .= $token[1];
            }

            break;

          // T_DOUBLE_ARROW、T_WHITESPACE
          default:
            if ($statement === NULL) {
              // 配列の要素がスカラー型で構成される場合
              if (!is_array($value)) {
                $value .= $token[1];
              }

            } else if (isset($value[$statement])) {
              $value[$statement] .= $token[1];
            }

            break;
        }

      } else {
        switch ($token) {
          case '&':
            $isReference = TRUE;
            break;

          case ';':
          case ')':
            break 2;

          case ',':
            $inVariable = FALSE;

            if ($statement == NULL) {
              $value .= $token;
            } else {
              $statement = NULL;
            }

            break;

          case '=':
            break;

          // 演算子の追加
          default:
            if ($statement === NULL) {
              if (!is_array($value)) {
                $value .= $token;
              }

            } else {
              $value[$statement] .= $token;
            }

            break;
        }
      }
    }

    if (is_array($value)) {
      return Delta_ArrayUtils::trim($value);
    }

    return $value;
  }

  /**
   * @return array
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getResult()
  {
    return $this->_result;
  }

  /**
   * @param int $line
   * @param string $type
   * @param string $message
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function addError($line, $type, $message)
  {
    $array = array();
    $array['line'] = $line;
    $array['type'] = $type;
    $array['message'] = $message;

    if ($this->_outputError) {
      printf("  %s: %s\n    %s [Line: %s]\n", $type, $message, $this->_path, $line);
    }

    $this->_errors[] = $array;
  }

  /**
   * @return array
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getErrors()
  {
    return $this->_errors;
  }
}
