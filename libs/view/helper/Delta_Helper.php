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
 *   # ビューに割り当てるヘルパインスタンスの変数名。
 *   # 未指定の場合はヘルパ ID が変数名として使用される。
 *   assign:
 *
 *   # ビュー変数の自動割り当てを行うかどうかの指定。
 *   # TRUE を指定した場合は出力ビュー決定時に自動的にインスタンスが割り当てられる。
 *   bind: TRUE
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package view.helper
 */
abstract class Delta_Helper extends Delta_Object
{
  /**
   * ヘルパが持つデフォルト属性。
   * @var array
   */
  protected static $_defaultValues = array();

  /**
   * ビューオブジェクト。
   * @var Delta_View。
   */
  protected $_view;

  /**
   * ヘルパ属性。
   * @var Delta_ParameterHolder
   */
  protected $_config;

  /**
   * {@link Delta_RouteResolver} オブジェクト。
   * @var Delta_RouteResolver
   */
  protected $_router;

  /**
   * コンストラクタ。
   *
   * @param Delta_View $view ヘルパを適用するビューオブジェクト。
   * @param array $config ヘルパ属性。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct(Delta_View $view, array $config = array())
  {
    $this->_view = $view;
    $this->_config = new Delta_ParameterHolder(Delta_ArrayUtils::merge(static::$_defaultValues, $config));
    $this->_router = Delta_FrontController::getInstance()->getRouter();
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
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function initialize()
  {}

  /**
   * {@link Delta_HelperManager::setBaseRouteName()} メソッドで設定したルートを基にリクエストパスを生成します。
   *
   * @param mixed $path {@link Delta_RouteResolver::buildRequestPath()} メソッドを参照。
   * @param array $queryData {@link Delta_RouteResolver::buildRequestPath()} メソッドを参照。
   * @param bool $absolute {@link Delta_RouteResolver::buildRequestPath()} メソッドを参照。
   * @param bool $secure {@link Delta_RouteResolver::buildRequestPath()} メソッドを参照。
   * @return string {@link Delta_RouteResolver::buildRequestPath()} メソッドを参照。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function buildRequestPath($path, $queryData, $absolute = FALSE, $secure = NULL)
  {
    $baseRouteName = $this->_view->getHelperManager()->getBaseRouteName();

    if ($baseRouteName !== NULL) {
      if (is_array($path)) {
        if (!isset($path['route'])) {
          $path['route'] = $baseRouteName;
        }

      } else {
        $path = array('route' => $baseRouteName, 'action' => $path);
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
