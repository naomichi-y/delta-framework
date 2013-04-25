<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller.listener
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * {@link Delta_FrontControllerDelegate デリゲートコントローラ} のためのリスナーインタフェースを提供します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller.listener
 */

interface Delta_ControllerListener
{
  /**
   * イベントポイントを取得します。
   * イベントポイントの詳細については {@link Delta_FrontControllerDelegate} を参照して下さい。
   *
   * @return array イベントポイントを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getListenerPoints();
}
