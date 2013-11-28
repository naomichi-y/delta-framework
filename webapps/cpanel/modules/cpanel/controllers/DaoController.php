<?php
/**
 * @package controllers
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class DaoController extends Delta_ActionController
{
  public function formAction()
  {
    $form = $this->createForm('Dao');
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

      $view->setAttribute('create_type', $array);

      // 基底クラスの指定
      $form->set('base_dao_name', 'Delta_DAO', FALSE);
      $form->set('base_entity_name', 'Delta_DatabaseEntity', FALSE);

    } catch (Exception $e) {
      $this->getMessages()->addError($e->getMessage());
    }

    $this->getView()->setForm('form', $form);
  }

  public function generateAction()
  {
    $form = $this->createForm('Dao');

    if ($form->validate()) {
      $view = $this->getView();

      $namespace = $form->get('namespace');
      $tables = $form->get('tables');
      $createType = $form->get('create_type');
      $baseDaoName = $form->get('base_dao_name');
      $baseEntityName = $form->get('base_entity_name');

      $createEntity = FALSE;
      $createDAO = FALSE;

      if (in_array('entity', $createType)) {
        $createEntity = TRUE;
      }

      if (in_array('dao', $createType)) {
        $createDAO = TRUE;
      }

      $command = $this->getDatabase()->getConnection($namespace)->getCommand();

      $basePath = DELTA_ROOT_DIR . '/skeleton/database_classes';
      $requirePath = $basePath . '/entity_class.php.tpl';
      $entityViewPath = Delta_FileUtils::readFile($requirePath);

      $requirePath = $basePath . '/dao_class.php.tpl';
      $daoViewPath = Delta_FileUtils::readFile($requirePath);

      $entities = array();
      $dataAccessObjects = array();

      $tmpEntityPath = APP_ROOT_DIR . '/tmp/entity';

      if (!is_dir($tmpEntityPath)) {
        Delta_FileUtils::createDirectory($tmpEntityPath);
      }

      $tmpPath = APP_ROOT_DIR . '/tmp/dao';

      if (!is_dir($tmpPath)) {
        Delta_FileUtils::createDirectory($tmpPath);
      }

      foreach ($tables as $tableName) {
        $pascalTableName = Delta_StringUtils::convertPascalCase($tableName);

        // エンティティクラスの生成
        if ($createEntity) {
          $fileName = $pascalTableName . 'Entity.php';

          $array = array();
          $array['absolute'] = $tmpEntityPath . '/' . $fileName;
          $array['relative'] = 'tmp/' . $fileName;
          $array['file'] = $fileName;

          $entities[] = $array;

          $fields = $command->getFields($tableName);
          $j = count($fields);

          $entityName = Delta_StringUtils::convertCamelCase($pascalTableName);
          $propertiesBuffer = '  protected $_entityName = \'' . $entityName . "\';\n";

          for ($i = 0; $i < $j; $i++) {
            $propertyName = strtolower($fields[$i]);
            $propertiesBuffer .= '  public $' . $propertyName . ";\n";
          }

          $from = array(
            '{%BASE_ENTITY_CLASS_NAME%}',
            '{%CLASS_NAME%}',
            '{%PROPERTIES%}'
          );
          $to = array(
            $baseEntityName,
            $pascalTableName . 'Entity',
            $propertiesBuffer
          );

          $classBuffer = str_replace($from, $to, $entityViewPath);

          Delta_FileUtils::writeFile($array['absolute'], $classBuffer);
        }

        // DAO クラスの生成
        if ($createDAO) {
          $fileName = $pascalTableName . 'DAO.php';

          $array = array();
          $array['absolute'] = $tmpPath . '/' . $fileName;
          $array['relative'] = 'tmp/' . $fileName;
          $array['file'] = $pascalTableName . 'DAO.php';

          $dataAccessObjects[] = $array;
          $primaryKeys = $command->getPrimaryKeys($tableName);
          $primaryKeysString = implode(', ', Delta_ArrayUtils::appendEachString($primaryKeys, '\''));

          $from = array(
            '{%BASE_DAO_CLASS_NAME%}',
            '{%DATA_SOURCE_ID%}',
            '{%CLASS_NAME%}',
            '{%TABLE_NAME%}',
            '{%PRIMARY_KEYS%}'
          );
          $to = array(
            $baseDaoName,
            $namespace,
            $pascalTableName . 'DAO',
            $tableName,
            $primaryKeysString
          );

          $classBuffer = str_replace($from, $to, $daoViewPath);
          Delta_FileUtils::writeFile($array['absolute'], $classBuffer);
        }

        $view->setForm('form', $form);
      }

      $view->setAttribute('entities', $entities);
      $view->setAttribute('dataAccessObjects', $dataAccessObjects);

    } else {
      $this->forward('form');
    }
  }

  public function deployAction()
  {
    $form = $this->createForm();
    $view = $this->getView();

    // エンティティファイルの移動
    $fromDirectory = APP_ROOT_DIR . '/tmp/entity';
    $toDirectory = APP_ROOT_DIR . '/libs/entity';

    $entities = $form->get('entities');
    $renameFiles = $this->moveFiles($fromDirectory, $toDirectory, $entities, TRUE);
    $view->setAttribute('entities', $renameFiles);

    // DAO ファイルの移動
    $renameFiles = array();

    $fromDirectory = APP_ROOT_DIR . '/tmp/dao';
    $toDirectory = APP_ROOT_DIR . '/libs/dao';

    $dataAccessObjects = $form->get('dataAccessObjects');
    $renameFiles = $this->moveFiles($fromDirectory, $toDirectory, $dataAccessObjects, FALSE);
    $view->setAttribute('dataAccessObjects', $renameFiles);
  }

  private function moveFiles($fromDirecotry, $toDirectory, $fileNames, $force = FALSE)
  {
    $renameFiles = array();

    if (is_array($fileNames)) {
      if (!is_dir($toDirectory)) {
        Delta_FileUtils::createDirectory($toDirectory);
      }

      foreach ($fileNames as $fileName) {
        $fromPath = $fromDirecotry . '/' . $fileName;
        $toPath = $toDirectory . '/' . $fileName;

        if (!is_file($fromPath)) {
          continue;
        }

        if ($force || !$force && !is_file($toPath)) {
          rename($fromPath, $toPath);
          $renameFiles[] = $fileName;
        }
      }
    }

    return $renameFiles;
  }
}
