<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package database.entity
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * データベースの 1 レコードを表現するエンティティオブジェクトです。
 * プロパティに (データベースの) NULL 値や式 (NOW() 等) を割り当てたい場合、{@link Delta_DatabaseExpression} クラスを利用して下さい。
 *
 * <code>
 * $entity = Delta_DAOFactory::create({dao_name})->createEntity();
 * $entity->greetingId = 1;
 * $entity->message = 'Hello World!';
 * $entity->name = new Delta_DatabaseExpression::null();
 * $entity->registerDate = new Delta_DatabaseExpression('NOW()');
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package database.entity
 */

abstract class Delta_DatabaseEntity extends Delta_Entity
{}
