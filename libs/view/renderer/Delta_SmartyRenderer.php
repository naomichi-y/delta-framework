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
 * Smarty 描画クラスです。
 * Smarty を有効にするには、base_dicon.yml を次のように設定する必要があります。
 *
 * base_dicon.yml の設定例:
 * <code>
 * componentNames:
 *   view:
 *     class: Delta_View
 *     constructor:
 *       - Delta_SmartyRenderer
 *     setter:
 *       # エスケープ処理は Smarty (escape_html) で行う
 *       autoEscape: FALSE
 *     includes:
 *       - vendors/Smarty/libs/Smarty.php
 * </code>
 *
 * ビューに変数を設定:
 * <code>
 * $view->setAttribute('greeting', 'Hello World!');
 * </code>
 *
 * ビューの実装例:
 * <code>
 * <div id="content">
 *   <p>{$greeting}</p>
 * </div>
 * </code>
 *
 * @link http://www.smarty.net/ Smarty
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package view.renderer
 */

class Delta_SmartyRenderer extends Delta_Renderer
{
  /**
   * @see Delta_Renderer::getEngine()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getEngine()
  {
    static $engine = NULL;

    if ($engine === NULL) {
      $engine = new Smarty();
      $engine->compile_dir = $this->getCacheDirectory();
      $engine->escape_html = TRUE;
    }

    return $engine;
  }

  /**
   * @see Delta_Renderer::render()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function render($data)
  {
    $engine = $this->getEngine();

    foreach ($this->_context['attributes'] as $name => $value) {
      $engine->assign($name, $value);
    }

    $engine->display('string:' . $data);
  }

  /**
   * @see Delta_Renderer::renderFile()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function renderFile($path)
  {
    $engine = $this->getEngine();

    foreach ($this->_context['attributes'] as $name => $value) {
      $engine->assign($name, $value);
    }

    $engine->display($path);
  }
}
