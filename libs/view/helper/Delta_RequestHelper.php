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
 * クライアントの情報やリクエストされたパラメータを取得するためのヘルパメソッドを提供します。
 * このヘルパは、$request という変数名であらかじめテンプレートにインスタンスが割り当てられています。
 *
 * <code>
 * <?php echo $request->{method}; ?>
 * </code>
 *
 * global_helpers.yml の設定例:
 * <code>
 * user:
 *   # ヘルパクラス名。
 *   class: Delta_RequestHelper
 * </code>
 * <i>その他に指定可能な属性は {@link Delta_Helper} クラスを参照。</i>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package view.helper
 */
class Delta_RequestHelper extends Delta_Helper
{
  /**
   * @var Delta_HttpRequest
   */
  private $_request;

  /**
   * @see Delta_Helper::__construct()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct(Delta_View $currentView, array $config = array())
  {
    parent::__construct($currentView, $config);

    $this->_request = $this->getRequest();
  }

  /**
   * {@link Delta_HttpRequest::hasParameter()} のエイリアスメソッドです。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasName($name)
  {
    return $this->_request->hasParameter($name);
  }

  /**
   * {@link Delta_HttpRequest::getParameter()} メソッドに {@link Delta_StringUtils::escape() HTML エスケープ} の機能を追加した拡張メソッドです。
   *
   * @param string $name {@link Delta_HttpRequest::getParameter()} メソッドを参照。
   * @param bool $escape 値を HTML エスケープした状態で返す場合は TRUE を指定。
   * @return string name に対応するパラメータ値を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function get($name, $escape = TRUE)
  {
    $value = NULL;

    if ($this->_request->hasParameter($name)) {
      $value = $this->_request->getParameter($name);

      if ($escape) {
        $value = Delta_StringUtils::escape($value);
      }
    }

    return $value;
  }

  /**
   * Delta_HttpRequest オブジェクトを取得します。
   *
   * @return Delta_HttpRequest HTTP リクエストオブジェクトを返します。
   * @since 1.1
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getContext()
  {
    return $this->_request;
  }
}
