<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package view.helper
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * 全てのヘルパの基底となるクラスです。
 * ヘルパのインスタンスは {@link Delta_HelperManager::getHelper()} から取得して下さい。
 *
 * global_helpers.yml の設定例:
 * <code>
 * {ヘルパ ID}:
 *   # 実装クラス名。
 *   class:
 *
 *   # テンプレートに割り当てるヘルパインスタンスの変数名。
 *   # 未指定の場合はヘルパ ID が変数名として使用される。
 *   assign:
 *
 *   # テンプレート変数の自動割り当てを行うかどうかの指定。
 *   # TRUE を指定した場合は出力テンプレート決定時に自動的にインスタンスが割り当てられる。
 *   bind: TRUE
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package view.helper
 */
abstract class Delta_Helper extends Delta_DIController
{
  /**
   * ヘルパで使用する基底のルータ名。
   * @var string
   */
  protected static $_baseRouter;

  /**
   * ヘルパが持つデフォルト属性。
   * @var array
   */
  protected static $_defaultValues = array();

  /**
   * ビューオブジェクト。
   * @var Delta_View。
   */
  protected $_currentView;

  /**
   * ヘルパ属性。
   * @var Delta_ParameterHolder
   */
  protected $_config;

  /**
   * {@link Delta_Router} オブジェクト。
   * @var Delta_Router
   */
  protected $_router;

  /**
   * コンストラクタ。
   *
   * @param Delta_View $currentView ヘルパを適用するビューオブジェクト。
   * @param array $config ヘルパ属性。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct(Delta_View $currentView, array $config = array())
  {
    $this->_currentView = $currentView;
    $this->_config = new Delta_ParameterHolder(Delta_ArrayUtils::mergeRecursive(static::$_defaultValues, $config));
    $this->_router = Delta_Router::getInstance();
  }

  /**
   * ヘルパの出力を制御するデフォルトパラメータのリストを取得します。
   *
   * @return array ヘルパの出力を制御するデフォルトパラメータのリストを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getDefaultValues()
  {
    return self::$_defaultValues;
  }

  /**
   * ヘルパを初期化します。
   * このメソッドは {@link Delta_View::loadHelpers()} がコールされた直後に実行されます。
   *
   * @see Delta_View::execute()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function initialize()
  {}

  /**
   * パスの生成に用いる基底のルータを設定します。
   *
   * @param string $baseRouter ルータ名。
   * @throws Delta_ConfigurationException 指定されたルータが見つからない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function setBasePathRouter($baseRouter)
  {
    $config = Delta_Config::getRoutes();

    if ($config->hasName($baseRouter)) {
      self::$_baseRouter = $baseRouter;

    } else {
      $message = sprintf('Can\'t find router. [%s]', $baseRouter);
      throw new Delta_ConfigurationException($message);
    }
  }

  /**
   * {@link setBasePathRouter()} メソッドで設定したルータを用いてリクエストパスを生成します。
   *
   * @param mixed $path {@link Delta_Router::buildRequestPath()} メソッドを参照。
   * @param array $queryData {@link Delta_Router::buildRequestPath()} メソッドを参照。
   * @param bool $absolute {@link Delta_Router::buildRequestPath()} メソッドを参照。
   * @param bool $secure {@link Delta_Router::buildRequestPath()} メソッドを参照。
   * @return string {@link Delta_Router::buildRequestPath()} メソッドを参照。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function buildRequestPath($path, $queryData, $absolute = FALSE, $secure = NULL)
  {
    if (self::$_baseRouter !== NULL) {
      if (is_array($path)) {
        if (!isset($path['router'])) {
          $path['router'] = self::$_baseRouter;
        }

      } else if (ctype_upper(substr($path, 0, 1))) {
        $path = array('action' => $path, 'router' => self::$_baseRouter);
      }
    }

    return $this->_router->buildRequestPath($path, $queryData, $absolute, $secure);
  }

  /**
   * ヘルパメソッドに渡された引数をヘルパが理解可能な配列形式に変換します。
   * <code>
   * $parameters = array('foo' => '100', 'bar' => '200');
   * $defaults = array('bar' => '100', 'baz' => '300');
   *
   * // array('foo' => '100', 'bar' => '200', 'baz' => '300')
   * Delta_Helper::constructParameters($parameters, $defaults);
   * </code>
   *
   * @param mixed $parameters 配列、または {@link Delta_HTMLEscapeDecorator} オブジェクトのインスタンス。
   * @param array $defaults データが持つデフォルト値。
   * @return array 配列形式のデータを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  protected static function constructParameters($parameters, $defaults = array())
  {
    $array = array();

    if (is_array($parameters)) {
      $array = $parameters;

    } else if ($parameters instanceof Delta_HTMLEscapeArrayDecorator) {
      $array = $parameters->getRaw();

    } else if ($parameters instanceof Delta_HTMLEscapeObjectDecorator) {
      $raw = $parameters->getRaw();

      if ($raw instanceof Delta_ParameterHolder) {
        $array = $raw->toArray();
      }
    }

    if (sizeof($defaults)) {
      $array = array_merge($defaults, $array);
    }

    return $array;
  }

  /**
   * HTML タグに追加する属性文字列を構築します。
   *
   * @param mixed $attributes タグに追加する属性。{@link Delta_HTMLHelper::link()} メソッドを参照。
   * @param bool $closingTag 閉じタグを付ける場合は TRUE、付けない場合は FALSE を指定。
   * @return string 生成した属性文字列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function buildTagAttribute($attributes, $closingTag = TRUE)
  {
    $attributes = self::constructParameters($attributes);
    $buffer = NULL;

    foreach ($attributes as $name => $value) {
      if (Delta_StringUtils::nullOrEmpty($name)) {
        $buffer .= $value . ' ';
      } else {
        $buffer .= sprintf('%s="%s" ', $name, $value);
      }
    }

    $buffer = trim($buffer);

    if (strlen($buffer)) {
      $buffer = ' ' . $buffer;
    }

    if ($closingTag) {
      $buffer .= ' /';
    }

    $buffer = Delta_StringUtils::escape($buffer, ENT_NOQUOTES);

    return $buffer;
  }
}
