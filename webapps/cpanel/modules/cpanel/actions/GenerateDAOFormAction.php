<?php
/**
 * @package actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class GenerateDAOFormAction extends Delta_Action
{
  public function execute()
  {
    $form = $this->getForm();
    $view = $this->getView();

    $config = Delta_Config::getApplication();
    $namespaceList = array();

    if (!$config->hasName('database')) {
      throw new Delta_ParseException('データベース接続情報が未定義です。(config/application.yml)');
    }

    foreach ($config->get('database') as $name => $values) {
      $namespaceList[$name] = $name;
    }

    if ($form->hasName('namespace')) {
      $dataSource = $form->get('namespace');
    } else {
      $dataSource = key($namespaceList);
    }

    $view->setAttribute('namespaceList', $namespaceList);
    $conn = NULL;

    try {
      $command = $this->getDatabase()->getConnection($dataSource)->getCommand();
      $view->setAttribute('tables', $command->getTables());

      $array = array();
      $selected = array();

      $array['dao'] = 'DAO';
      $array['entity'] = 'エンティティ';

      foreach ($array as $name => $value) {
        array_push($selected, $name);
      }

      $view->setAttribute('createType', $array);

      // 基底クラスの指定
      $form->set('baseDAOClassName', 'Delta_DAO', FALSE);
      $form->set('baseEntityClassName', 'Delta_DatabaseEntity', FALSE);

    } catch (Exception $e) {
      $this->getMessages()->addError($e->getMessage());
    }

    return Delta_View::SUCCESS;
  }
}
