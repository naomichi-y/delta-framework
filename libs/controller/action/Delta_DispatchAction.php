<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller.action
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * 1 つのフォームに複数の submit が存在する場合、実行するアクションへのリレーを行うディスパッチャです。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller.action
 */
abstract class Delta_DispatchAction extends Delta_Action
{
  /**
   * デフォルトのフォワード先を取得します。
   *
   * @return string デフォルトのフォワード先を返します。
   * @throws Delta_ForwardException フォワード先が不明な場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function defaultForward();

  /**
   * ディスパッチ先のアクションを取得、フォワード処理を行います。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function execute()
  {
    $parameters = $this->getRequest()->getParameters();
    $executeMethod = NULL;

    foreach ($parameters as $name => $value) {
      $imagePos = strrpos($name, '_x');

      if ($imagePos && strlen($name) - 2) {
        $executeMethod = substr($name, 0, -2);
        break;
      }

      if (strpos($name, 'dispatch') === 0) {
        $executeMethod = $name;
        break;
      }
    }

    if ($executeMethod !== NULL && method_exists($this, $executeMethod)) {
      $forward = $this->$executeMethod();

      if (is_array($forward)) {
        $this->getController()->forward($forward[0], $forward[1]);
      } else {
        $this->getController()->forward($forward);
      }

    } else {
      $forward = $this->defaultForward();

      if ($forward) {
        $this->getController()->forward($forward);
      }
    }

    return Delta_View::NONE;
  }
}
