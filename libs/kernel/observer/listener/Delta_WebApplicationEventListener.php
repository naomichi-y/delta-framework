<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package kernel.observer.listener
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * Web アプリケーションのためのイベントリスナです。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package kernel.observer.listener
 */

class Delta_WebApplicationEventListener extends Delta_ApplicationEventListener
{
  /**
   * array('{@link preOutput() preOutput}') を取得します。
   *
   * @see Delta_ApplicationEventListener::getListenerEvents()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getListenEvents()
  {
    return array('preOutput');
  }

  /**
   * @see Delta_ApplicationEventListener::getBootMode()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getBootMode()
  {
    return Delta_BootLoader::BOOT_MODE_WEB;
  }

  /**
   * URI からルートが確定したタイミング (アクションのインスタンスが生成される前) で起動します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function postRouteConnect()
  {}

  /**
   * コンテンツが出力される直前に起動します。
   * 内部エンコーディングと {@link Delta_HttpResponse::setOutputEncoding()} で指定されたエンコーディングが異なる場合、エンコードの変換が行われます。
   *
   * @param string &$contents 出力するコンテンツ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function preOutput(&$contents)
  {
    $response = Delta_DIContainerFactory::getContainer()->getComponent('response');

    if ($response->hasBinary()) {
      $contents = $response->getWriteBuffer();

    } else {
      $internalEncoding = Delta_Config::getApplication()->get('charset.default');
      $outputEncoding = $response->getOutputEncoding();

      if ($internalEncoding !== $outputEncoding) {
        $contents = mb_convert_encoding($contents, $outputEncoding, $internalEncoding);
      }
    }
  }
}
