<?php
/**
 * @package actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class GenerateDAOAction extends Delta_Action
{
  public function execute()
  {
    $form = $this->getForm();
    $view = $this->getView();

    $namespace = $form->get('namespace');
    $tables = $form->get('tables');
    $createType = $form->get('createType');
    $baseDAOClassName = $form->get('baseDAOClassName');
    $baseEntityClassName = $form->get('baseEntityClassName');

    $createEntity = false;
    $createDAO = false;

    if (in_array('entity', $createType)) {
      $createEntity = true;
    }

    if (in_array('dao', $createType)) {
      $createDAO = true;
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
          $baseEntityClassName,
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
          $baseDAOClassName,
          $namespace,
          $pascalTableName . 'DAO',
          $tableName,
          $primaryKeysString
        );

        $classBuffer = str_replace($from, $to, $daoViewPath);
        Delta_FileUtils::writeFile($array['absolute'], $classBuffer);
      }
    }

    $view->setAttribute('entities', $entities);
    $view->setAttribute('dataAccessObjects', $dataAccessObjects);

    return Delta_View::SUCCESS;
  }
}
