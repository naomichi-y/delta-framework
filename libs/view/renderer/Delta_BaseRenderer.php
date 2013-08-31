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
 * delta が提供する標準の描画クラスです。
 * このクラスは生の PHP コードでビューを作成することができます。
 *
 * ビューに変数を設定:
 * <code>
 * $view->setAttribute('greeting', 'Hello World!');
 * </code>
 *
 * ビューの実装例:
 * <code>
 * <div id="content">
 *   <?php echo $form->start() ?>
 *     <p><?php echo $greeting ?></p>
 *   <?php echo $form->close() ?>
 * </div>
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package view.renderer
 */

class Delta_BaseRenderer extends Delta_Renderer
{
  /**
   * @see Delta_Renderer::getEngine()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getEngine()
  {
    throw new RuntimeException('Rendering engine is not defined.');
  }

  /**
   * @see Delta_Renderer::render()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function render($data)
  {
    $parser = new Delta_PHPStringParser($data, $this->_context['attributes']);
    $parser->execute();
    $parser->output();
  }

  /**
   * @see Delta_Renderer::renderFile()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function renderFile($path)
  {
    // $this->_context['attributes'] には 'path' 変数が含まれる可能性もあるため、名前を変えておく
    $_path = $path;

    extract($this->_context['helpers']);
    extract($this->_context['attributes']);

    require $_path;
  }
}

