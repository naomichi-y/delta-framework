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
 * Mustache 描画クラスです。
 * Mustache を有効にするには、base_dicon.yml を次のように設定する必要があります。
 *
 * base_dicon.yml の設定例:
 * <code>
 * componentNames:
 *   view:
 *     class: Delta_View
 *     constructor:
 *       - Delta_MustacheRenderer
 *     setter:
 *       # エスケープは Mustache で行う
 *       autoEscape: FALSE
 *     includes:
 *       - vendors/mustache.php/src/Mustache/Autoloader.php
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
 *   <p>{{greeting}}</p>
 * </div>
 * </code>
 *
 * @link https://github.com/bobthecow/mustache.php mustache
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package view.renderer
 */

class Delta_MustacheRenderer extends Delta_Renderer
{
  /**
   * @see Delta_Renderer::__construct()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct()
  {
    parent::__construct();

    Mustache_Autoloader::register();
  }

  /**
   * @see Delta_Renderer::getEngine()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getEngine()
  {
    static $engine;

    if ($engine === NULL) {
      $container = Delta_DIContainerFactory::getContainer();

      $config = array();
      $config['charset'] = $container->getComponent('response')->getOutputEncoding();
      $config['cache'] = $this->getCacheDirectory();

      $engine = new Mustache_Engine($config);
    }

    return $engine;
  }

  /**
   * @see Delta_Renderer::display()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function render($data)
  {
    echo $this->getEngine()->render($data, $this->_context['attributes']);
  }

  /**
   * @see Delta_Renderer::renderFile()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function renderFile($path)
  {
    $extension = Delta_Config::getApplication()->getString('view.extension');

    $options = array('extension' => $extension);
    $loader = new Mustache_Loader_FilesystemLoader(dirname($path), $options);

    $engine = $this->getEngine();
    $engine->setLoader($loader);

    $info = pathinfo($path);

    $template = $this->getEngine()->loadTemplate($info['filename']);
    echo $template->render($this->_context['attributes']);
  }
}
