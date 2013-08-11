<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package renderer
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * {@link Delta_View ビュー} を出力するための描画クラスです。
 *
 * <code>
 * $view = new Delta_View();
 * $view->setAttribtue('greeting', 'Hello World!');
 * $view->setTemplatePath($path);
 * $renderer = $view->getRenderer();
 * </code>
 *
 * 新しい描画クラスを作成する場合は、Delta_Renderer が提供する抽象メソッドを実装する必要があります。
 * <code>
 * // カスタム描画クラスの作成
 * class CustomRenderer extends Delta_Renderer
 * {
 *   public function getEngine()
 *   {}
 *
 *   public function render($data)
 *   {}
 *
 *   public function renderFile($path)
 *   {}
 * }
 *
 * // 描画クラスに CustomRenderer を使う
 * $view = new Delta_View(new CustomRenderer());
 * $view->setAttribute('greeting', 'Hello World!');
 * $view->execute();
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package view.renderer
 */

abstract class Delta_Renderer extends Delta_Object
{
  /**
   * @var string
   */
  private $_cacheDirectory;

  /**
   * @var array
   */
  protected $_context = array();

  /**
   * コンストラクタ。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct()
  {
    $cacheDirectory = NULL;

    // テンプレート設置パスの取得
    if (Delta_BootLoader::isBootTypeWeb()) {
      $extension = Delta_Config::getApplication()->getString('view.extension');
      $route = Delta_FrontController::getInstance()->getRequest()->getRoute();

      if ($route) {
        $moduleName = $route->getModuleName();

        // キャッシュディレクトリの取得
        $cacheDirectory = sprintf('%s%scache%stemplates%scache%s%s',
          APP_ROOT_DIR,
          DIRECTORY_SEPARATOR,
          DIRECTORY_SEPARATOR,
          DIRECTORY_SEPARATOR,
          DIRECTORY_SEPARATOR,
          $moduleName);

      } else {
        // キャッシュディレクトリの取得
        $cacheDirectory = sprintf('%s%scache%stemplates%scache%sconsole',
          APP_ROOT_DIR,
          DIRECTORY_SEPARATOR,
          DIRECTORY_SEPARATOR,
          DIRECTORY_SEPARATOR,
          DIRECTORY_SEPARATOR);
      }
    }

    $this->_cacheDirectory = $cacheDirectory;
  }

  /**
   * 描画オブジェクトに {@link Delta_View ビュー} のコンテキスト情報を設定します。
   *
   * @param array &$context {@link Delta_View ビュー} から渡されるコンテキスト情報。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setContext(array &$context)
  {
    $this->_context = $context;
  }

  /**
   * キャッシュディレクトリを設定します。
   * このメソッドは描画エンジンがキャッシュ機能をサポートしている場合のみ有効です。
   *
   * @param string $cacheDirectory キャッシュディレクトリパス。
   *   APP_ROOT_DIR からの相対パス、または絶対パスが指定可能。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setCacheDirectory($cacheDirectory)
  {
    $this->_cacheDirectory = Delta_FileUtils::buildAbsolutePath($cacheDirectory);
  }

  /**
   * キャッシュディレクトリを取得します。
   * このメソッドはキャッシュディレクトリが存在しない場合にディレクトリの生成を試みます。
   *
   * @return string キャッシュディレクトリのパスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getCacheDirectory()
  {
    // キャッシュディレクトリがない場合は作成
    if (!is_dir($this->_cacheDirectory)) {
      Delta_FileUtils::createDirectoryRecursive($this->_cacheDirectory, 0777);
    }

   return $this->_cacheDirectory;
  }

  /**
   * 描画エンジンオブジェクトを取得します。
   *
   * @return object 描画エンジンオブジェクトを取得します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function getEngine();

  /**
   * データを描画します。
   *
   * @param string $data 描画対象のデータ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function render($data);

  /**
   * ファイルの内容を描画します。
   * このメソッドは {@link Delta_View::execute()} メソッドからコールされます。
   *
   * @param string $path 描画対象のファイルパス。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function renderFile($path);
}
