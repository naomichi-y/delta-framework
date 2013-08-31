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
 * サイト設定ファイルから値を参照するためのヘルパメソッドを提供します。
 * このヘルパは、$site という変数名であらかじめビューにインスタンスが割り当てられています。
 *
 * <code>
 * <?php echo $site->{method}; ?>
 * </code>
 *
 * global_helpers.yml の設定例:
 * <code>
 * site:
 *   # ヘルパクラス名。
 *   class: Delta_SiteHelper
 * </code>
 * <i>その他に指定可能な属性は {@link Delta_Helper} クラスを参照。</i>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package view.helper
 */
class Delta_SiteHelper extends Delta_Helper
{
  /**
   * @var array
   */
  private $_siteConfig;

  /**
   * @see Delta_Helper::__construct()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct(Delta_View $view, array $config = array())
  {
    parent::__construct($view, $config);

    $this->_siteConfig = Delta_Config::getSite();
  }

  /**
   * site.yml に定義された name の値を取得します。
   * このメソッドは {@link Delta_siteConfig::getSite()} から取得した値を {@link Delta_StringUtils::escape() HTML エスケープ} した形式で返します。
   *
   * @param string $name 検索対象の属性名。
   * @param mixed $alternative name 属性が見つからない場合に返す代替値。
   * @return mixed 指定した属性に対応する値を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function get($name, $alternative = NULL)
  {
    $value = $this->_siteConfig->get($name, $alternative);

    if ($value !== NULL) {
      $value = Delta_StringUtils::escape($value);
    }

    return $value;
  }
}
