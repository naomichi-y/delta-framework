<?php
/**
 * @package modules.cpanel.forms
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class DaoForm extends Delta_Form
{
  public function build(Delta_DataFieldBuilder $builder)
  {
    $field = $builder->createDataField('namespace', '参照データベース');
    $field->addValidator('select', array('requiredMin' => 1));
    $builder->addField($field);

    $field = $builder->createDataField('tables', '対象テーブル');
    $field->addValidator('select', array('requiredMin' => 1));
    $builder->addField($field);

    $field = $builder->createDataField('create_type', '生成クラス');
    $field->addValidator('check', array('requiredMin' => 1));
    $builder->addField($field);

    $field = $builder->createDataField('base_dao_name', 'DAO 基底クラス');
    $field->addValidator('required');
    $builder->addField($field);

    $field = $builder->createDataField('base_entity_name', 'エンティティ基底クラス');
    $field->addValidator('required');
    $builder->addField($field);
  }
}
