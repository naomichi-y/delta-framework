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
 * アプリケーションレベルのエラーが発生した際に通知される例外です。
 * ビジネスロジック上で発生する例外は、派生クラス {@link Delta_BusinessLogicException} を使用して下さい。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package exception
 */
class Delta_ApplicationException extends Delta_Exception
{}
