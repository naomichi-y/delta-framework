<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package database
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * クライテリアはレコードの抽出条件をスコープとして管理し、SQL を書くことなくデータを取得するメソッドを提供します。
 * <code>
 * class UsersDAO extends Delta_DAO
 * {
 *   // DAO クラスにクライテリアで利用する抽出条件 (スコープ) を宣言
 *   public function scopes(Delta_DatabaseCriteriaScopes $scopes) {
 *     // 抽出条件を配列形式で指定
 *     $scopes->add('condition1',
 *        array(
 *          // 値には SQL のコードを書くことが可能
 *          // キーに指定可能な値は {@link Delta_DatabaseCriteriaScopes::add()} メソッドを参照
 *          'where' => 'track_id = 200'
 *        )
 *     );
 *
 *     // 抽出条件をクロージャ形式で指定 (条件文に任意の値を指定可能)
 *     $scopes->add('condition2',
 *       function($registerDate) {
 *         return array(
 *          'where' => "register_date = $registerDate",
 *          'order' => 'user_id DESC'
 *         );
 *       )
 *     }
 *   }
 * }
 *
 * // クライテリアオブジェクトの取得
 * $criteria = Delta_DAOFactory::create('Users')->createCriteria();
 *
 * 'SELECT * FROM users'
 * $criteira->getQuery();
 * $records = $criteira->findAll();
 *
 * // レコード件数の表示
 * $records->count();
 *
 * // プライマリキー制約でレコードを抽出
 * $criteria->setPrimaryKeyValue(100);
 *
 * // 'SELECT * FROM users WHERE user_id = 100
 * $criteria->getQuery();
 * $criteria->find()->user_id;
 *
 * // 'condition1' でレコードを取得する
 * $criteria->add('condition1');
 *
 * // 'SELECT * FROM users WHERE track_id = 200'
 * $criteria->getQuery();
 *
 * // 'condition2' でレコードを取得する
 * $criteria = Delta_DAOFactory::create('Users')->createCriteria();
 *
 * // 条件は第 2 引数に配列形式で指定
 * $criteria->add('condition2', array(date('Y-m-d'));
 *
 * // 'SELECT * FROM users WHERE register_date = 'XXXX-XX-XX' ORDER BY user_id DESC
 * $criteria->getQuery();
 *
 * // 1 行目の user_id フィールドを取得する
 * $criteria->find()->user_id;
 *
 * // 複数のスコープを繋げて 1 つのクエリとすることも可能
 * $criteria->setPrimaryKeyValue(100)
 *   ->add('condition1')
 *   ->add('condition2', array(date('Y-m-d')));
 *
 * // "SELECT * FROM users WHERE user_id = 100 AND track_id = 200 AND register_date = 'XXXX-XX-XX' ORDER BY user_id DESC"
 * $criteria->getQuery();
 * </code>
 * <i>現在のところ、クライテリアはリレーションには対応していません。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package database.criteria
 */

class Delta_DatabaseCriteria extends Delta_Object
{
  /**
   * @var Delta_DatabaseConnection
   */
  private $_connection;

  /**
   * @var string
   */
  private $_tableName;

  /**
   * @var array
   */
  private $_primaryKeys;

  /**
   * @var bool
   */
  private $_primaryKeyConstraint = FALSE;

  /**
   * @var mixed
   */
  private $_primaryKeyValue;

  /**
   * @var array
   */
  private $_parimaryValues = array();

  /**
   * @var Delta_DatabaseCriteriaScopes
   */
  private $_scopes;

  /**
   * @var array
   */
  private $_conditions = array(
    'select' => '*',
    'where' => array(),
    'group' => NULL,
    'having' => NULL,
    'order' => NULL,
    'limit' => NULL,
    'offset' => NULL
  );

  /**
   * コンストラクタ。
   *
   * @param Delta_DatabaseConnection $connection コネクションオブジェクト。
   * @param string $tableName テーブル名。
   * @param Delta_DatabaseCriteriaScopes $scopes スコープオブジェクト。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct(Delta_DatabaseConnection $connection,
    $tableName,
    array $primaryKeys = array(),
    Delta_DatabaseCriteriaScopes $scopes = NULL)
  {
    $this->_connection = $connection;
    $this->_tableName = $tableName;
    $this->_primaryKeys = $primaryKeys;

    if ($scopes !== NULL) {
      $this->_scopes = $scopes->getScopes();
    }
  }

  /**
   * クライテリアにプライマリキーのレコード抽出条件制約を設定します。
   * <code>
   * $criteria = Delta_DAOFactory::create('Users')->createCriteria();
   *
   * // プライマリキーが持つ値
   * $criteria->setPrimaryKeyValue(100);
   *
   * // 'SELECT * FROM user_id WHERE user_id = 100'
   * $criteria->getQuery();
   * </code>
   *
   * @param mixed $primaryKeyValue {@link Delta_DAO::getPrimaryKyes() プライマリキー} が持つ値。
   *   プライマリキーが複数フィールドで構成される場合は配列形式で値を指定。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setPrimaryKeyValue($primaryKeyValue)
  {
    $this->_primaryKeyConstraint = TRUE;
    $this->_primaryKeyValue = $primaryKeyValue;

    return $this;
  }

  /**
   * 参照クエリを構築します。
   *
   * @param array conditions 抽出条件を含む配列。
   * @return string 構築した参照クエリを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function buildSelectQuery(array $conditions)
  {
    // 'SELECT' 句の生成
    $query = 'SELECT ' . $conditions['select'];

    // 'FROM' 句の生成
    $query .= ' FROM ' . $this->_tableName;

    // 'WHERE' 句の生成
    if ($this->_primaryKeyConstraint) {
      $wherePrimaryQuery = NULL;

      $valueSize = sizeof($this->_primaryKeyValue);
      $primaryKeySize = sizeof($this->_primaryKeys);
      $hasError = FALSE;

      if ($primaryKeySize == 0) {
        $message = sprintf('Primary key is undefined. [%s::$_primaryKeys]', get_class($this));
        throw new RuntimeException($message);
      }

      if ($valueSize > 1) {
        if ($primaryKeySize != $valueSize) {
          $hasError = TRUE;

        } else {
          for ($i = 0; $i < $primaryKeySize; $i++) {
            if ($i > 0) {
              $wherePrimaryQuery .= ' AND ';
            }

            $wherePrimaryQuery .= sprintf('%s = %s',
              $this->_primaryKeys[$i],
              $this->_connection->quote($this->_primaryKeyValue[$i]));
          }
        }

      } else {
        if ($primaryKeySize > 1) {
          $hasError = TRUE;

        } else {
          if (is_array($this->_primaryKeyValue)) {
            $primaryValue = $this->_primaryKeyValue[0];
          } else {
            $primaryValue = $this->_primaryKeyValue;
          }

          $wherePrimaryQuery = sprintf('%s = %s',
            $this->_primaryKeys[0],
            $this->_connection->quote($primaryValue));
        }
      }

      if ($hasError) {
        $message = 'Does not match the number of primary key and values.';
        throw new InvalidArgumentException($message);
      }

      $conditions['where'][] = array($wherePrimaryQuery, 'AND');
    }

    $j = sizeof($conditions['where']);

    if ($j > 0) {
      $query .= ' WHERE ';

      for ($i = 0; $i < $j; $i++) {
        if ($i > 0) {
          $query .= ' ' . $conditions['where'][$i][1] . ' ';
        }

        $query .= $conditions['where'][$i][0];
      }
    }

    // 'GROUP BY' 句の生成
    if ($conditions['group'] !== NULL) {
      $query .= ' GROUP BY ' . $conditions['group'];
    }

    // 'HAVING' 句の生成
    if ($conditions['having'] !== NULL) {
      $query .= ' HAVING ' . $conditions['having'];
    }

    // 'ORDER' 句の生成
    if ($conditions['order'] !== NULL) {
      $query .= ' ORDER BY ' . $conditions['order'];
    }

    // 'LIMIT' 句の生成
    if ($conditions['limit'] !== NULL) {
      $query .= ' LIMIT ' . $conditions['limit'];
    }

    // 'OFFSET' 句の生成
    if ($conditions['offset'] !== NULL) {
      $query .= ' OFFSET ' . $conditions['offset'];
    }

    return $query;
  }

  /**
   * クライテリアが生成したクエリを取得します。
   *
   * @return string クライテリアが生成したクエリを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getQuery()
  {
    return $this->buildSelectQuery($this->_conditions);
  }

  /**
   * 条件に一致するレコードが存在するかどうかチェックします。
   *
   * @return bool レコードが存在する場合は TRUE、存在しない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function exists()
  {
    if ($this->find()) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * 条件に一致するレコードの件数を取得します。
   *
   * @return int レコードの件数を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function count()
  {
    $conditions = $this->_conditions;
    $conditions['select'] = 'COUNT(*)';

    $query = $this->buildSelectQuery($conditions);
    $rs = $this->_connection->rawQuery($query);

    return $rs->read()->getByIndex(0);
  }

  /**
   * 条件に一致するレコードを取得します。
   *
   * @return Delta_RecordObject 条件に一致するレコードオブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function find()
  {
    $conditions = $this->_conditions;
    $conditions['limit'] = 1;
    $conditions['offset'] = 0;

    $query = $this->buildSelectQuery($conditions);
    $rs = $this->_connection->rawQuery($query);

    return $rs->read();
  }

  /**
   * プライマリキー制約を元に先頭行のレコードを取得します。
   *
   * $criteria = Delta_DAOFactory::create('Users')->createCriteria();
   *
   * // 'SELECT * FROM users ORDER BY user_id ASC LIMIT 1 OFFSET 0'
   * $criteria->findFirst()->getQuery();
   *
   * @return Delta_RecordObject 先頭行のレコードを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function findFirst()
  {
    return $this->getLimitRecords('ASC');
  }

  /**
   * プライマリキー制約を元に最終行のレコードを取得します。
   *
   * $criteria = Delta_DAOFactory::create('Users')->createCriteria();
   *
   * // 'SELECT * FROM users ORDER BY user_id DESC LIMIT 1 OFFSET 0'
   * $criteria->findLast()->getQuery();
   *
   * @return Delta_RecordObject 最終行のレコードを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function findLast()
  {
    return $this->getLimitRecords('DESC');
  }

  /**
   * @param string $type
   * @return Delta_RecordObject
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function getLimitRecords($type)
  {
    $conditions = $this->_conditions;
    $conditions['limit'] = 1;
    $conditions['offset'] = 0;

    $orderQuery = NULL;

    foreach ($this->_primaryKeys as $primaryKey) {
      $orderQuery .= sprintf('%s %s, ', $primaryKey, $type);
    }

    $conditions['order'] = rtrim($orderQuery, ', ');

    $query = $this->buildSelectQuery($conditions);
    $rs = $this->_connection->rawQuery($query);

    return $rs->read();
  }

  /**
   * 条件に一致する全てのレコードを取得します。
   *
   * @return array 条件に一致する全てのレコードを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function findAll()
  {
    $query = $this->buildSelectQuery($this->_conditions);
    $rs = $this->_connection->rawQuery($query);
    $records = array();

    while ($record = $rs->read()) {
      $records[] = $record;
    }

    return $records;
  }

  /**
   * クライテリアにスコープを追加します。
   *
   * @param string $scopeName スコープ名。
   * @param array $variables スコープに割り当てる変数のリスト。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function add($scopeName, array $variables = array())
  {
    if (!isset($this->_scopes[$scopeName])) {
      $message = sprintf('Can\'t find scope ID. [%s]', $scopeName);
      throw new Delta_ConfigurationException($message);
    }

    $scope = $this->_scopes[$scopeName];

    if (is_object($scope)) {
      $variables = $this->_connection->getCommand()->quoteValues($variables);
      $scope = call_user_func_array($scope, $variables);
    }

    // 'select' の取得
    if (isset($scope['select']) && strlen($scope['select'])) {
      $this->_conditions['select'] = $scope['select'];
    }

    // 'where' の取得
    if (isset($scope['where']) && strlen($scope['where'])) {
      $this->_conditions['where'][] = array($scope['where'], 'AND');
    }

    // 'group' の取得
    if (isset($scope['group']) && strlen($scope['group'])) {
      $this->_conditions['group'] = $scope['group'];
    }

    // 'having' の取得
    if (isset($scope['having']) && strlen($scope['having'])) {
      $this->_conditions['having'] = $scope['having'];
    }

    // 'order' の取得
    if (isset($scope['order']) && strlen($scope['order'])) {
      $this->_conditions['order'] = $scope['order'];
    }

    // 'limit' の取得
    if (isset($scope['limit']) && strlen($scope['limit'])) {
      $this->_conditions['limit'] = $scope['limit'];
    }

    // 'offset' の取得
    if (isset($scope['offset']) && strlen($scope['offset'])) {
      $this->_conditions['offset'] = $scope['offset'];
    }

    return $this;
  }
}
