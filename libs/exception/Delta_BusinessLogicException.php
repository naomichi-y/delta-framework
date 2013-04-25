<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package exception
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * ビジネスロジックレベルのエラーが発生した際に通知される例外です。
 * アプリケーション独自の例外は、{@link Delta_DIBusinessLogicException} の派生クラスを生成して下さい。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package exception
 */
class Delta_BusinessLogicException extends Delta_ApplicationException
{}
