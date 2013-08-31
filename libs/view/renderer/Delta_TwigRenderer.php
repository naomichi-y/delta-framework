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
 * Twig 描画クラスです。
 * Twig を有効にするには、base_dicon.yml を次のように設定する必要があります。
 *
 * base_dicon.yml の設定例:
 * <code>
 * componentNames:
 *   view:
 *     class: Delta_View
 *     constructor:
 *       - Delta_TwigRenderer
 *     setter:
 *       # エスケープ処理は Twig (Twig_Extension_Escaper) で行う
 *       autoEscape: FALSE
 *     includes:
 *       - vendors/Twig/lib/Twig/Autoloader.php
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
 *   <p>{{greeting}}</p>
 * </div>
 * </code>
 *
 * @link http://www.twig-project.org/ Twig
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package view.renderer
 */

class Delta_TwigRenderer extends Delta_Renderer
{
  /**
   * @see Delta_Renderer::__construct()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct()
  {
    parent::__construct();

    Twig_Autoloader::register();
  }

  /**
   * @see Delta_Renderer::getEngine()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getEngine()
  {
    static $engine = NULL;

    if ($engine === NULL) {
      $controller = Delta_FrontController::getInstance()->getResponse();

      $config = array();
      $config['cache'] = $this->getCacheDirectory();
      $conifg['charset'] = $response->getOutputEncoding();
      $config['auto_reload'] = TRUE;
      $config['autoescape'] = FALSE;

      $escaper = new Twig_Extension_Escaper(TRUE);

      $engine = new Twig_Environment(NULL, $config);
      $engine->addExtension($escaper);
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
    $engine->setLoader(new Twig_Loader_String());

    echo $engine->loadTemplate($data)->render($this->_context['attributes']);
  }

  /**
   * @see Delta_Renderer::renderFile()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function renderFile($path)
  {
    $engine = $this->getEngine();
    $engine->setLoader(new Twig_Loader_Filesystem(dirname($path)));

    echo $engine->render(basename($path), $this->_context['attributes']);
  }
}
