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
 * PHPTAL 描画クラスです。
 * PHPTAL を有効にするには、base_dicon.yml を次のように設定する必要があります。
 *
 * base_dicon.yml の設定例:
 * <code>
 * componentNames:
 *   view:
 *     class: Delta_View
 *     constructor:
 *       - Delta_PHPTALRenderer
 *     setter:
 *       # エスケープ処理は PHPTAL で行う
 *       autoEscape: FALSE
 *     includes:
 *       - vendors/PHPTAL/PHPTAL.php
 * </code>
 *
 * ビューに変数を設定:
 * <code>
 * $view->setAttribute('greeting', 'Hello World!');
 * </code>
 *
 * テンプレートの実装例:
 * <code>
 * <div id="content">
 *   <p tal:content="var"></p>
 * </div>
 *
 * @link http://phptal.org/ PHPTAL
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package view.renderer
 */

class Delta_PHPTALRenderer extends Delta_Renderer
{
  /**
   * @see Delta_Renderer::getEngine()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getEngine()
  {
    static $engine = NULL;

    if ($engine === NULL) {
      $container = Delta_DIContainerFactory::getContainer();

      $engine = new PHPTAL();
      $engine->setEncoding($container->getComponent('response')->getOutputEncoding());
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
    $engine->setSource($data);

    foreach ($this->_context['attributes'] as $name => $value) {
      $engine->set($name, $value);
    }

    $engine->echoExecute();
  }

  /**
   * @see Delta_Renderer::renderFile()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function renderFile($path)
  {
    $engine = $this->getEngine();
    $engine->setTemplate($path);

    foreach ($this->_context['attributes'] as $name => $value) {
      $engine->set($name, $value);
    }

    $engine->echoExecute();
  }
}
