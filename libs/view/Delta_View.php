<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package view
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

require DELTA_LIBS_DIR . '/view/renderer/Delta_Renderer.php';

/**
 * ビューの出力を制御します。
 *
 * このクラスは 'view' コンポーネントとして DI コンテナに登録されているため、{@link Delta_DIContainer::getComponent()}、あるいは {@link Delta_WebApplication::getView()} からインスタンスを取得することができます。
 *
 * base_dicon.yml の設定例:
 * <code>
 * componentNames:
 *   view:
 *     class: Delta_View
 *     setter:
 *       # {@link setAutoEscape()} メソッドをコールする (通常は指定不要)
 *       autoEscape: TRUE
 * </code>
 *
 * 新しいビューのインスタンスを生成し、テンプレートを描画することもできます。
 * <code>
 * $view = new Delta_View();
 * $view->setAttribute('greeting', 'Hello World!');
 * $view->importHelpers();
 *
 * // テンプレートファイルの出力
 * $view->setTemplatePath($path);
 * $view->execute();
 *
 * // 文字列から描画を行う
 * $view->setSource($source);
 * $view->execute();
 * </code>
 *
 * ビューはアクションの {@link Delta_Action::execute() 実行結果} にマッピングされます。
 *
 * アクションクラスにおけるビューのマッピング:
 * <code>
 * class GreetingAction extends Delta_Action
 * {
 *   public function execute()
 *   {
 *     // ビヘイビアに定義された 'success' ビューにマッピング
 *     return Delta_View::SUCCESS;
 *
 *     // 'error' ビューにマッピング
 *     return Delta_View::ERROR;
 *
 *     // 任意のマッピング名を指定することも可能
 *     return 'custom';
 *   }
 * }
 * </code>
 *
 * ビヘイビアの設定例:
 * <code>
 * view:
 *   # modules/{module_name}/templates/greeting.php テンプレートにマッピング
 *   success: greeting
 *
 *   # GreetingError アクションにフォワード
 *   error:
 *     forward: GreetingError
 *
 *   # GreetingResponse アクションにリダイレクト
 *   custom:
 *     redirect: GreetingResponse
 * </code>
 *
 * アクションの戻り値に {@link Delta_View::NONE} が指定された場合、テンプレートとのマッピングは行われません。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package view
 */

require DELTA_LIBS_DIR . '/view/helper/Delta_Helper.php';
require DELTA_LIBS_DIR . '/view/helper/Delta_HelperManager.php';

class Delta_View extends Delta_Object
{
  /**
   * ビューマッピング定数。(アクションが正常に処理された)
   */
  const SUCCESS = 'success';

  /**
   * ビューマッピング定数。(入力フォームへの遷移)
   */
  const INPUT = 'input';

  /**
   * ビューマッピング定数。(アクションでエラーが発生した)
   */
  const ERROR = 'error';

  /**
   * ビューマッピング定数。(アクションでセキュリティエラーが発生した)
   */
  const SAFETY_ERROR = 'safety_error';

  /**
   * ビューマッピング定数。(ビューを必要としない)
   */
  const NONE = 'none';

  /**
   * @var Delta_Renderer
   */
  private $_renderer;

  /**
   * @var Delta_HelperManager
   */
  private $_helperManager;

  /**
   * @var array
   */
  protected $_context = array();

  /**
   * @var bool
   */
  protected $_autoEscape = TRUE;

  /**
   * @var array
   */
  protected $_attributes = array();

  /**
   * @var array
   */
  protected $_helpers = array();

  /**
   * @var string
   */
  protected $_source;

  /**
   * @var string
   */
  protected $_templatePath;

  /**
   * コンストラクタ。
   *
   * @param mixed $renderer 指定した描画エンジンでビューを出力することができる。指定可能な形式は次の通り。
   *   o {@link Delta_Renderer} を実装した描画エンジンオブジェクト。
   *   o 描画エンジンクラス名の指定: 例えば 'Delta_BaseRenderer' と言った文字列を指定することができる。
   *   o 未指定の場合: Delta_BaseRenderer のインスタンスを生成。
   * @throws InvalidArgumentException renderer に指定されたオブジェクトが {@link Delta_Renderer} を実装していない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($renderer = NULL)
  {
    if ($renderer === NULL) {
      $this->_renderer = new Delta_BaseRenderer();

    } else if (is_string($renderer)) {
      $this->_renderer = new $renderer;

    } else if (is_a($renderer, 'Delta_Renderer', TRUE)) {
      $this->_renderer = $renderer;

    } else {
      $message = sprintf('Specified object does not implement the Delta_Renderer. [%s]', get_class($renderer));
      throw new InvalidArgumentException($message);
    }

    $this->_context['helpers'] = &$this->_helpers;
    $this->_context['attributes'] = &$this->_attributes;

    $this->_renderer->setContext($this->_context);
    $this->_helperManager = new Delta_HelperManager($this);
  }

  /**
   * ビューに設定される変数の HTML エスケープモードを制御します。
   *
   * @param bool $autoEscape FALSE を指定した場合、{@link setAttribtue()}、{@link setAttributes()} メソッドにおけるエスケープ制御を全て無効にする。(ただし既にビューに登録されている変数については無効)
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setAutoEscape($autoEscape)
  {
    $this->_autoEscape = $autoEscape;
  }

  /**
   * 描画オブジェクトを取得します。
   *
   * @return Delta_Renderer 描画オブジェクトのインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getRenderer()
  {
    return $this->_renderer;
  }

  /**
   * ヘルパマネージャのインスタンスを取得します。
   *
   * @return Delta_HelperManager ヘルパマネージャのインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getHelperManager()
  {
    return $this->_helperManager;
  }

  /**
   * ビューに変数を設定します。
   *
   * @param string $name ビューに割り当てる変数名。
   * @param mixed $value 変数が持つ値。
   * @param bool $escape ビューに割り当てる変数を HTML エスケープする場合に TRUE を指定。
   *   エスケープ対象となる型はスカラー型、配列型、オブジェクト型。
   *   o 配列変数は {@link Delta_HTMLEscapeArrayDecorator} オブジェクトに変換された上でビューに渡される。
   *   o オブジェクト変数は {@link Delta_HTMLEscapeObjectDecorator} オブジェクトに変換された上でビューに渡される。
   *   o {@link setAutoEscape()} が FALSE に指定された場合、escape の指定は無効となる。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setAttribute($name, $value, $escape = TRUE)
  {
    if ($escape && $this->_autoEscape) {
      $value = Delta_StringUtils::escape($value);
    }

    $this->_attributes[$name] = $value;
  }

  /**
   * ビューに複数の変数を設定します。
   *
   * @param array $attributes 変数名と変数値から構成される連想配列。
   * @param bool $escape {@link setAttribute()} メソッドを参照。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setAttributes(array $attributes = array(), $escape = TRUE)
  {
    foreach ($attributes as $name => $value) {
      $this->setAttribute($name, $value, $escape);
    }
  }

  /**
   * ビューに設定されている変数の値を取得します。
   *
   * @param string $name 取得対象の変数名。
   * @return 変数が持つ値を返します。値が見つからない場合は NULL を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getAttribute($name)
  {
    if (isset($this->_attributes[$name])) {
      return $this->_attributes[$name];
    }

    return NULL;
  }

  /**
   * ビューに設定されている全ての変数を取得します。
   *
   * @return array 変数名と変数値から構成される連想配列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getAttributes()
  {
    return $this->_attributes;
  }

  /**
   * 変数がビューに設定されているかどうかチェックします。
   *
   * @param string $name チェック対象の変数名。
   * @return bool 変数がビューに設定されている場合は TRUE、設定されていない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasAttribute($name)
  {
    return array_key_exists($name, $this->_attributes);
  }

  /**
   * ビューに設定されている変数を削除します。
   *
   * @param string $name 削除対象の変数名。
   * @return bool 変数の削除に成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function removeAttribute($name)
  {
    $result = array_key_exists($name, $this->_attributes);

    if ($result) {
      unset($this->_attributes[$name]);
    }

    return $result;
  }

  /**
   * ビューに設定されている全ての変数を削除します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function clear()
  {
    $this->_attributes = array();
  }

  /**
   * ビューの出力結果を取得します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function fetch()
  {
    ob_start();
    $this->execute();

    return ob_get_clean();
  }

  /**
   * ビューに指定したファイルを出力します。
   * 出力結果を文字列として取得したい場合は {@link fetch()} メソッドを使用して下さい。
   *
   * @throws Delta_ParseException {@link setTemplatePath()} で指定されたファイルが見つからない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function execute()
  {
    if ($this->_templatePath) {
      if (is_file($this->_templatePath)) {
        $this->_renderer->renderFile($this->_templatePath);

      } else {
        $message = sprintf('Template path is not found. [%s]', $this->_templatePath);
        throw new Delta_ParseException($message);
      }

    } else {
      $this->_renderer->render($this->_source);
    }
  }

  /**
   * 出力対象のソースコードを設定します。
   *
   * @param string $source 出力対象のソースコード。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setSource($source)
  {
    $this->_source = $source;
  }

  /**
   * 出力対象のソースコードを取得します。
   *
   * @return string 出力対象のソースコードを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getSource()
  {
    return $this->_source;
  }

  /**
   * 出力対象のテンプレートパスを設定します。
   *
   * @param string $templatePath 出力対象のファイルパス。
   *   {@link Delta_AppPathManager::getModuleTemplatesPath() 現在有効なテンプレートディレクトリ} から相対パスでファイルを指定。
   *   例えば admin モジュールで path に 'greeting' を指定した場合、出力対象のファイルパスは {APP_ROOT_DIR}/modules/admin/templates/greeting.php' となる。(コンソール環境から実行した場合は APP_ROOT_DIR からの相対パスとなる)
   *   指定可能なパス形式については {@link Delta_AppPathManager::buildAbsolutePath()} を参照。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setTemplatePath($templatePath)
  {
    $basePath = NULL;

    if (Delta_BootLoader::isBootTypeWeb()) {
      $extension = Delta_Config::getApplication()->getString('view.extension');
      $route = Delta_FrontController::getInstance()->getRequest()->getRoute();

      if ($route) {
        $basePath = $this->getAppPathManager()->getModuleTemplatesPath($route->getModuleName());
      }

    } else {
      $basePath = APP_ROOT_DIR;
      $extension = NULL;
    }

    $this->_templatePath = Delta_AppPathManager::buildAbsolutePath($basePath, $templatePath, $extension);
  }

  /**
   * 出力対象のテンプレートパスを取得します。
   *
   * @return string 出力対象のテンプレートパスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getTemplatePath()
  {
    return $this->_templatePath;
  }

  /**
   * ビューに指定したヘルパを読み込みます。
   *
   * @param string $helperId ビューに読み込むヘルパ ID。
   * @return bool ヘルパの読み込みが成功した場合は TRUE、失敗した ('bind' 属性が FALSE の) 場合は FALSE を返します。
   * @throws Delta_ConfigurationException 指定されたヘルパがヘルパ設定ファイルに未定義の場合に発生。
   * @since 1.2
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function importHelper($helperId)
  {
    $result = FALSE;
    $manager = $this->getHelperManager();
    $helperConfig = $manager->getConfig()->get($helperId);

    if ($helperConfig) {
      if ($helperConfig->getBoolean('bind')) {
        $assignName = $helperConfig->getString('assign', $helperId);
        $helper = $manager->getHelper($helperId);

        $this->_helpers[$assignName] = $helper;
        $result = TRUE;
      }

    } else {
      $message = sprintf('Helper is undefined. [%s]', $helperId);
      throw new Delta_ConfigurationException($message);
    }

    return $result;
  }

  /**
   * ヘルパ設定ファイルに定義されている全てのヘルパをビューで有効化します。
   * このメソッドは {@link execute()} メソッドにより、ビューが出力される直前にコールされます。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function importHelpers()
  {
    $manager = $this->getHelperManager();
    $helpersConfig = $manager->getConfig();

    foreach ($helpersConfig as $helperId => $helperConfig) {
      // インスタンスの生成が許可されているクラスのみオブジェクトを生成
      if ($helperConfig->getBoolean('bind')) {
        $assignName = $helperConfig->getString('assign', $helperId);
        $helper = $manager->getHelper($helperId);

        $this->_helpers[$assignName] = $helper;
      }
    }
  }

  /**
   * ビューに割り当てられた全てのヘルパを取得します。
   *
   * @return array ヘルパに割り当てられた全てのヘルパを返します。
   * @since 1.2
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getHelpers()
  {
    return $this->_helpers;
  }
}
