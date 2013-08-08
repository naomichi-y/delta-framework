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
 * これは、この関数の動作、関数名、ここで書かれていること全てが delta の将来のバージョンで予告なく変更される可能性があることを意味します。
 * 注意を喚起するとともに自分のリスクでこのクラスを使用してください。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.code
 */

class Delta_APIGenerator extends Delta_Object
{
  /**
   * @var string
   */
  private $_parseDirectory;

  /**
   * @var string
   */
  private $_templateDirectory;

  /**
   * @var string
   */
  private $_outputparseDirectory;

  /**
   * @var Delta_View
   */
  private $_view;

  /**
   * @var array
   */
  private $_excludeDirectories = array();

  /**
   * @var string
   */
  private $_title = 'Class reference';

  /**
   * @var array
   */
  private $_indexes = array();

  /**
   * @var array
   */
  private $_summaries = array();

  /**
   * @var array
   */
  private $_pages = array();

  /**
   * @var string
   */
  private $_indexData = NULL;

  /**
   * @var array
   */
  private $_referenceDataList = array();

  /**
   * @var Delta_DocumentMakeHelper
   */
  private $_documentHelper;

  /**
   * @param string $parseDirectory
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($parseDirectory)
  {
    $parseDirectory = realpath(str_replace('/', DIRECTORY_SEPARATOR, $parseDirectory));

    $this->_parseDirectory = $parseDirectory;
    $this->_templateDirectory = DELTA_ROOT_DIR . '/skeleton/api';
    $this->_outputDirectory = APP_ROOT_DIR . '/data/api';

    $this->_view = new Delta_View(new Delta_BaseRenderer());
    $this->_view->importHelper('html');
  }

  /**
   * @param array $excludeDirectories
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setExcludeDirectories(array $excludeDirectories)
  {
    $array = array();

    // 相対パスで指定された除外ディレクトリを絶対パス形式に変換
    foreach ($excludeDirectories as $excludeDirectory) {
      if (Delta_FileUtils::isAbsolutePath($excludeDirectory)) {
        $array[] = $excludeDirectory;
      } else {
        $array[] = $this->_parseDirectory . DIRECTORY_SEPARATOR . $excludeDirectory;
      }
    }

    $this->_excludeDirectories = $array;
  }

  /**
   * @param string $title
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setTitle($title)
  {
    $this->_title = $title;
  }

  /**
   * @param string $templateDirectory
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setTemplateDirectory($templateDirectory)
  {
    $this->_templateDirectory = $templateDirectory;
  }

  /**
   * @param string $outputDirectory
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setOutputDirectory($outputDirectory)
  {
    $this->_outputDirectory = $outputDirectory;
  }

  /**
   * @return string
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getOutputDirectory()
  {
    return $this->_outputDirectory;
  }

  /**
   * @param bool $clean
   * @param bool $outputError
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function make($clean = TRUE, $outputError = FALSE)
  {
    // 出力ディレクトリを作り直す
    if ($clean) {
      Delta_FileUtils::deleteDirectory($this->_outputDirectory);
    }

    // スクリプトディレクトリの解析
    $this->parse($outputError);

    // クラスの親子関係を解析
    $this->parseDependencyClasses();
    $this->parseDependencyClassMembers();
  }

  /**
   * @param string $absolutePath
   * @return string
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function buildRelativePath($absolutePath)
  {
    $pos = strlen($this->_parseDirectory);
    $relativePath = str_replace('\\', '/', substr($absolutePath, $pos));

    return $relativePath;
  }

  /**
   * @param string $path
   * @param string $baseAnchor
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function addFileIndex($path, $baseAnchor)
  {
    $fileName = basename($path);

    if (($pos = strpos($fileName, '.')) !== FALSE) {
      $fileName = substr($fileName, 0, $pos);
    }

    $this->_indexes['file'][$fileName] = $baseAnchor;
  }

  /**
   * @param string $className
   * @param string $baseAnchor
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function addClassIndex($className, $baseAnchor)
  {
    $this->_indexes['class'][$className] = $baseAnchor;
  }

  /**
   * @param string $className
   * @param string $constantName
   * @param string $baseAnchor
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function addConstantIndex($className, $constants, $baseAnchor)
  {
    foreach ($constants as $constantName => $attributes) {
      $key = sprintf('%s::%s', $className, $constantName);
      $value = sprintf('%s#constant_%s', $baseAnchor, $constantName);

      $this->_indexes['constant'][$key] = $value;
    }
  }

  /**
   * @param string $className
   * @param array $methods
   * @param string $baseAnchor
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function addMethodIndex($className, array $methods, $baseAnchor)
  {
    foreach ($methods as $methodName => $attributes) {
      $key = sprintf('%s::%s()', $className, $methodName);
      $value = sprintf('%s#method_%s', $baseAnchor, $methodName);

      $this->_indexes['method'][$key] = $value;
    }
  }

  /**
   * @param string $className
   * @param array $properties
   * @param string $baseAnchor
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function addPropertyIndex($className, array $properties, $baseAnchor)
  {
    foreach ($properties as $propertyName => $attributes) {
      $key = sprintf('%s::$%s', $className, $propertyName);
      $value = sprintf('%s#property_%s', $baseAnchor, $propertyName);

      $this->_indexes['property'][$key] = $value;
    }
  }

  /**
   * @param string $defineName
   * @param array $defines
   * @param string $baseAnchor
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function addDefineIndex(array $defines, $baseAnchor)
  {
    foreach ($defines as $defineName => $attributes) {
      $key = $defineName;
      $value = sprintf('%s#define_%s', $baseAnchor, $defineName);

      $this->_indexes['define'][$key] = $value;
    }
  }

  /**
   * @param string $fileName
   * @param array $functions
   * @param string $baseAnchor
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function addFunctionIndex($fileName, array $functions, $baseAnchor)
  {
    foreach ($functions as $functionName => $attributes) {
      $key = $functionName . '()';
      $value = sprintf('%s#function_%s', $baseAnchor, $functionName);

      $this->_indexes[$key] = $value;
    }
  }

  /**
   * @return string
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function createAnchor($package, $fileName)
  {
    return sprintf('%s/%s.html', $package, Delta_StringUtils::convertSnakeCase($fileName));
  }

  /**
   * @param bool $outputError
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function parse($outputError)
  {
    // ディレクトリ内のスクリプトを解析
    $pattern = '/^.*.php$/';
    $options = array('excludes' => $this->_excludeDirectories);
    $files = Delta_FileUtils::search($this->_parseDirectory, $pattern, $options);

    foreach ($files as $path) {
      $tokenizer = new Delta_Tokenizer($path, $outputError);
      $tokenizer->parse($outputError);

      $result = $tokenizer->getResult();

      $baseAnchor = $this->createAnchor($result['file']['package'], $result['file']['name']);
      $this->addFileIndex($path, $baseAnchor);

      // 関数の定義が含まれる場合
      if (isset($result['functions'])) {
        $relativePath = $this->buildRelativePath($result['file']['absolutePath']);
        $result['file']['relativePath'] = $relativePath;

        $fileName = $result['file']['name'];

        $array = array();
        $array['anchor'] = $this->createAnchor($result['file']['package'], $fileName);

        if (isset($result['file']['document']['summary'])) {
          $array['summary'] = $result['file']['document']['summary'];
        }

        $this->_summaries[$result['file']['package']][$fileName] = $array;
        $this->_pages[$path] = $result;

        if (isset($result['defines'])) {
          $this->addDefineIndex($result['defines'], $array['anchor']);
        }

        if (isset($result['functions'])) {
          $this->addFunctionIndex($fileName, $result['functions'], $array['anchor']);
        }
      }

      // クラスの定義が含まれる場合
      if (isset($result['classes'])) {
        foreach ($result['classes'] as $classId => $attributes) {
          $relativePath = $this->buildRelativePath($result['file']['absolutePath']);
          $attributes['relativePath'] = $relativePath;
          $className = $attributes['name'];

          $array = array();
          $array['anchor'] = $this->createAnchor($attributes['package'], $attributes['name']);

          if (isset($attributes['document']['summary'])) {
            $array['summary'] = $attributes['document']['summary'];
          }

          $this->_summaries[$attributes['package']][$className] = $array;
          $this->_pages[$path]['classes'][$classId] = $attributes;

          if (isset($attributes['methods'])) {
            $this->addMethodIndex($className, $attributes['methods'], $array['anchor']);
          }

          if (isset($attributes['properties'])) {
            $this->addPropertyIndex($className, $attributes['properties'], $array['anchor']);
          }

          $this->addClassIndex($className, $array['anchor']);

          if (isset($attributes['constants'])) {
            $this->addConstantIndex($className, $attributes['constants'], $array['anchor']);
          }
        }
      }
    }

    ksort($this->_summaries);
  }

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function build()
  {
    // ヘルパインスタンスの生成
    $this->_documentHelper = new Delta_DocumentMakeHelper($this->_view);
    $this->_documentHelper->setIndexes($this->_indexes);

    // インデックスの作成
    $this->_indexData = $this->createIndexPage();

    // リファレンスの作成
    $this->_referenceDataList = $this->createReferencePage();
  }

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function parseDependencyClasses()
  {
    foreach ($this->_pages as $filePath => &$fileAttributes) {
      if (!isset($fileAttributes['classes'])) {
        continue;
      }

      $classes = &$fileAttributes['classes'];

      foreach ($classes as $classId => &$classAttributes) {
        // 親クラスの解析
        if (isset($classAttributes['inheritance'])) {
          $parentClassesInfo = $this->getParentClassesInfo($classAttributes['inheritance']);

          if (sizeof($parentClassesInfo)) {
            $inheritances = array();
            $interfaces = array();

            // 対象クラスがが依存している全てのインタフェース、親クラスを取得
            if (isset($classAttributes['interfaces'])) {
              $interfaces = $classAttributes['interfaces'];
            }

            foreach ($parentClassesInfo as $classInfo) {
              $inheritances[] = $classInfo['name'];

              if (isset($classInfo['interfaces'])) {
                foreach ($classInfo['interfaces'] as $interface) {
                  $interfaces[] = $interface;
                }
              }
            }

            $classAttributes['inheritanceTree'] = $inheritances;

            if (sizeof($interfaces)) {
              $interfaces = array_unique($interfaces);
              sort($interfaces);

              $classAttributes['interfaces'] = $interfaces;
            }

          } else {
            $classAttributes['inheritanceTree'] = array($classAttributes['inheritance']);
          }
        }

        $subclasses = $this->getSubclasses($classAttributes['name']);

        if (sizeof($subclasses)) {
          $classAttributes['subclasses'] = $subclasses;
        }
      }
    }
  }

  /**
   * @param string $searchClassName
   * @param array &$result
   * @return array
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function getParentClassesInfo($searchClassName, array &$result = array())
  {
    foreach ($this->_pages as $page) {
      if (!isset($page['classes'])) {
        continue;
      }

      $classes = $page['classes'];

      foreach ($classes as $classId => $attributes) {
        if ($attributes['name'] == $searchClassName) {
          $array = array();
          $array['name'] = $searchClassName;

          if (isset($attributes['interfaces'])) {
            $array['interfaces'] = $attributes['interfaces'];
          }

          $result[] = $array;

          if (isset($attributes['inheritance'])) {
            $this->getParentClassesInfo($attributes['inheritance'], $result);
            break;
          }
        }
      }
    }

    return $result;
  }

  /**
   * @param string $className
   * @return array
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function getSubclasses($className)
  {
    $subclasses = array();

    foreach ($this->_pages as $page) {
      if (!isset($page['classes'])) {
        continue;
      }

      foreach ($page['classes'] as $classId => $attributes) {
        if (isset($attributes['inheritance']) && $attributes['inheritance'] === $className) {
          $subclasses[] = $attributes['name'];
        }
      }
    }

    asort($subclasses);

    return $subclasses;
  }

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function parseDependencyClassMembers()
  {
    $types = array('methods', 'properties');

    foreach ($this->_pages as $filePath => &$fileAttributes) {
      if (!isset($fileAttributes['classes'])) {
        continue;
      }

      $classes = &$fileAttributes['classes'];

      foreach ($classes as $classId => &$classAttributes) {
        $classAttributes['hasOverrideProperty'] = FALSE;
        $classAttributes['hasOverrideMethod'] = FALSE;
        $classAttributes['hasInheritanceProperty'] = FALSE;
        $classAttributes['hasInheritanceMethod'] = FALSE;

        // 対象クラスに親クラスが存在する場合
        if (isset($classAttributes['inheritanceTree'])) {
          // 親から子クラスの順に解析
          $inheritanceTree = array_reverse($classAttributes['inheritanceTree']);

          foreach ($inheritanceTree as $parentClassName) {
            // 親クラスがパッケージ外の場合は解析しない
            $findClassAttributes = NULL;

            foreach ($this->_pages as $page) {
              if (!isset($page['classes'])) {
                continue;
              }

              foreach ($page['classes'] as $name => $values) {
                if ($values['name'] === $parentClassName) {
                  $findClassAttributes = $values;
                }
              }
            }

            if ($findClassAttributes === NULL) {
              continue;
            }

            foreach ($types as $type) {
              if (!isset($findClassAttributes[$type])) {
                continue;
              }

              foreach ($findClassAttributes[$type] as $name => $values) {
                // プライベートプロパティ (メソッド) は子に継承しない
                if ($values['access'] === 'private') {
                  continue;
                }

                $values['define'] = $parentClassName;

                // 親の持つプロパティ、メソッドをオーバーライド
                // クラスが孫→子→親の継承関係にある場合、親の持つメソッドは孫、子から見て 'isInheritance' が TRUE となる
                // 孫が直接親のメソッドを継承している場合、'isOverride' が TRUE となる
                if (isset($classAttributes[$type][$name])) {
                  if (!isset($classAttributes[$type][$name]['isInheritance'])) {
                    $classAttributes[$type][$name]['isOverride'] = TRUE;

                    if ($type === 'methods') {
                      $classAttributes['hasOverrideMethod'] = TRUE;
                    } else {
                      $classAttributes['hasOverrideProperty'] = TRUE;
                    }

                    if (!isset($findClassAttributes[$type][$name]['define']) || $findClassAttributes[$type][$name]['isOverride']) {
                      $classAttributes[$type][$name]['define'] = $parentClassName;
                    }
                  }

                // 親の持つプロパティ、メソッドを子に継承
                } else {
                  $values['isInheritance'] = TRUE;
                  $classAttributes[$type][$name] = $values;

                  if ($type === 'methods') {
                    $classAttributes['hasInheritanceMethod'] = TRUE;
                  } else {
                    $classAttributes['hasInheritanceProperty'] = TRUE;
                  }
                }
              }
            }
          }
        }

        // 親クラスに存在しないプロパティ、メソッドを識別する
        foreach ($types as $type) {
          if (!isset($classAttributes[$type])) {
            continue;
          }

          foreach ($classAttributes[$type] as $name => $value) {
            if (!isset($classAttributes[$type][$name]['define'])) {
              $classAttributes[$type][$name]['define'] = $classAttributes['name'];
            }

            if (!isset($classAttributes[$type][$name]['isOverride'])) {
              $classAttributes[$type][$name]['isOverride'] = FALSE;
            }

            if (!isset($classAttributes[$type][$name]['isInheritance'])) {
              $classAttributes[$type][$name]['isInheritance'] = FALSE;
            }

            if (!$classAttributes[$type][$name]['isOverride'] && !$classAttributes[$type][$name]['isInheritance']) {
              $classAttributes[$type][$name]['isOwner'] = TRUE;
            } else {
              $classAttributes[$type][$name]['isOwner'] = FALSE;
            }
          }
        }
      }
    }
  }

  /**
   * @return string
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function createIndexPage()
  {
    // メニュー部の生成
    $menuTemplate = $this->_templateDirectory . '/menu.php';

    $view = $this->_view;
    $view->setAttribute('menus', $this->_summaries, FALSE);
    $view->setAttribute('relativeIndexPath', '');
    $view->setAttribute('relativeAPIPath', 'reference/');
    $view->setTemplatePath($menuTemplate);
    $menuTag = $view->fetch();
    $view->clear();

    // コンテンツ部の生成
    $contentsTemplate = $this->_templateDirectory . '/index.php';

    $view->setAttribute('menus', $this->_summaries, FALSE);
    $view->setAttribute('relativeAPIPath', 'reference/');
    $view->setAttribute('title', $this->_title);
    $view->setAttribute('menuTag', $menuTag, FALSE);
    $view->setAttribute('document', $this->_documentHelper, FALSE);
    $view->setTemplatePath($contentsTemplate);
    $contents = $view->fetch();
    $view->clear();

    return $contents;
  }

  /**
   * @return string
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function createReferencePage()
  {
    // メニュー部の生成
    $menuTemplate = $this->_templateDirectory . '/menu.php';

    $view = $this->_view;
    $view->setAttribute('menus', $this->_summaries, FALSE);
    $view->setAttribute('relativeIndexPath', '../../');
    $view->setAttribute('relativeAPIPath', '../');
    $view->setTemplatePath($menuTemplate);

    $menuTag = $view->fetch();
    $view->clear();

    // コンテンツ部の生成
    $functionTemplate = $this->_templateDirectory . '/function.php';
    $classTemplate = $this->_templateDirectory . '/class.php';
    $data = array();

    $view->setAttribute('title', $this->_title);
    $view->setAttribute('menuTag', $menuTag, FALSE);
    $view->setAttribute('relativeAPIPath', '../');
    $view->setAttribute('document', $this->_documentHelper, FALSE);

    foreach ($this->_pages as $filePath => $fileAttributes) {
      if (isset($fileAttributes['defines'])) {
        ksort($fileAttributes['defines']);
      }

      if (isset($fileAttributes['functions'])) {
        ksort($fileAttributes['functions']);

        foreach ($fileAttributes['functions'] as $name => $functionAttributes) {
          $this->_documentHelper->setFileId($fileAttributes['file']['name']);
          $view->setAttribute('file', $fileAttributes, FALSE);
          $view->setTemplatePath($functionTemplate);
          $contents = $view->fetch();

          $path = $this->createReferencePath($fileAttributes['file']['package'], $fileAttributes['file']['name']);
          $data[$path] = $contents;
        }

        unset($fileAttributes);
      }

      if (isset($fileAttributes['classes'])) {
        foreach ($fileAttributes['classes'] as $name => $classAttributes) {
          if (isset($classAttributes['constants'])) {
            ksort($classAttributes['constants']);
          }

          if (isset($classAttributes['properties'])) {
            ksort($classAttributes['properties']);
          }

          if (isset($classAttributes['methods'])) {
            ksort($classAttributes['methods']);
          }

          $view->setAttribute('className', $classAttributes['name']);
          $view->setAttribute('class', $classAttributes, FALSE);

          $this->_documentHelper->setFileId($classAttributes['name']);
          $view->setTemplatePath($classTemplate);
          $contents = $view->fetch();

          $path = $this->createReferencePath($classAttributes['package'], $classAttributes['name']);
          $data[$path] = $contents;

          unset($classAttributes);
        }
      }
    }

    $view->clear();

    return $data;
  }

  /**
   * @param string $package
   * @param string $fileName
   * @return string
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function createReferencePath($package, $fileName)
  {
    $path = sprintf('%s/reference/%s/%s.html',
      $this->_outputDirectory,
      $package,
      Delta_StringUtils::convertSnakeCase($fileName));

    return $path;
  }

  /**
   * @param array $pages
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setPages(array $pages)
  {
    $this->_pages = $pages;
  }

  /**
   * @return array
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getPages()
  {
    return $this->_pages;
  }

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function write()
  {
    // メディアファイルのデプロイ
    $from = array();
    $from[] = DELTA_ROOT_DIR . '/docs/manual/assets/css/base.css';
    $from[] = DELTA_ROOT_DIR . '/docs/manual/assets/css/kube.css';
    $from[] = DELTA_ROOT_DIR . '/docs/manual/assets/images/logo.png';
    $from[] = $this->_templateDirectory . '/css';
    $from[] = $this->_templateDirectory . '/js';

    $to = array();
    $to[] = $this->_outputDirectory . '/assets/css/base.css';
    $to[] = $this->_outputDirectory . '/assets/css/kube.css';
    $to[] = $this->_outputDirectory . '/assets/images/logo.png';
    $to[] = $this->_outputDirectory . '/assets/css';
    $to[] = $this->_outputDirectory . '/assets/js';

    $j = sizeof($from);

    for ($i = 0; $i < $j; $i++) {
      Delta_FileUtils::copyRecursive($from[$i], $to[$i], array('recursive' => TRUE));
    }

    // インデックスページの出力
    $writePath = $this->_outputDirectory . '/index.html';
    Delta_FileUtils::writeFile($writePath, $this->_indexData);

    // API の出力
    foreach ($this->_referenceDataList as $path => $data) {
      Delta_FileUtils::writeFile($path, $data);
    }
  }
}
