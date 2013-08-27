<?php
/**
 * This class was generated automatically by DAO Generator.
 * date: 09/14/2011 20:41:56
 *
 * @package libs.dao
 */
class Delta_SQLRequestsDAO extends Delta_PerformanceAnalyzerDAO
{
  protected $_tableName = 'delta_sql_requests';
  protected $_primaryKeys = array('sql_request_id');

  const STATEMENT_TYPE_SELECT = 1;
  const STATEMENT_TYPE_INSERT = 2;
  const STATEMENT_TYPE_UPDATE = 3;
  const STATEMENT_TYPE_DELETE = 4;
  const STATEMENT_TYPE_OTHER = 127;

  private $_statementTypeNames = array(
    self::STATEMENT_TYPE_SELECT => 'SELECT',
    self::STATEMENT_TYPE_INSERT => 'INSERT',
    self::STATEMENT_TYPE_UPDATE => 'UPDATE',
    self::STATEMENT_TYPE_DELETE => 'DELETE',
    self::STATEMENT_TYPE_OTHER => 'OTHER',
  );

  public function findByActionRequestId($actionRequestId)
  {
    $conn = $this->getConnection();
    $query = 'SELECT sql_request_id, prepared_statement, statement, statement_type, TRUNCATE(process_time, 5) AS process_time, class_name, method_name, file_path, line '
          .'FROM delta_sql_requests '
          .'WHERE action_request_id = :action_request_id '
          .'ORDER BY sql_request_id ASC';

    $stmt = $conn->createStatement($query);
    $stmt->bindValue(':action_request_id', $actionRequestId);
    $resultSet = $stmt->executeQuery();

    $data = array();

    while ($record = $resultSet->read()) {
      $record['statement_type_name'] = $this->_statementTypeNames[$record['statement_type']];
      $data[] = $record;
    }

    return $data;
  }

  public function getExecuteCountsByDate($moduleName, $dates)
  {
    $conn = $this->getConnection();
    $summaryDateInQuery = NULL;

    foreach ($dates as $date) {
      $summaryDateInQuery .= $conn->quote($date) . ', ';
    }

    $summaryDateInQuery = trim($summaryDateInQuery, ', ');

    $query = 'SELECT mar.summary_date, msr.statement_type, COUNT(mar.action_request_id) AS execute_count '
          .'FROM delta_action_requests mar, delta_sql_requests msr '
          .'WHERE mar.action_request_id = msr.action_request_id '
          .'AND mar.hostname = :hostname '
          .'AND (mar.module_name = :module_name OR :module_name IS NULL) '
          .'AND mar.summary_date IN (' . $summaryDateInQuery . ') '
          .'GROUP BY summary_date, msr.statement_type '
          .'ORDER BY summary_date ASC, msr.statement_type ASC';

    $stmt = $conn->createStatement($query);
    $stmt->bindValue(':hostname', php_uname('n'));
    $stmt->bindValue(':module_name', $moduleName);
    $resultSet = $stmt->executeQuery();

    $data = array();
    $previousDate = NULL;

    while ($record = $resultSet->read()) {
      $currentDate = $record['summary_date'];

      if ($previousDate !== $currentDate) {
        $data[$currentDate]['select'] = 0;
        $data[$currentDate]['insert'] = 0;
        $data[$currentDate]['update'] = 0;
        $data[$currentDate]['delete'] = 0;
        $data[$currentDate]['other'] = 0;
      }

      switch ($record['statement_type']) {
        case self::STATEMENT_TYPE_SELECT;
          $statementType = 'select';
          break;

        case self::STATEMENT_TYPE_INSERT;
          $statementType = 'insert';
          break;

        case self::STATEMENT_TYPE_UPDATE;
          $statementType = 'update';
          break;

        case self::STATEMENT_TYPE_DELETE;
          $statementType = 'delete';
          break;

        default:
          $statementType = 'other';
          break;
      }

      $data[$currentDate][$statementType] = $record['execute_count'];
      $previousDate = $currentDate;
    }

    return $data;
  }

  public function getDailySummary($moduleName, $beginSummaryDate, $endSummaryDate)
  {
    $conn = $this->getConnection();
    $query = 'SELECT mar.summary_date, msr.statement_type, COUNT(mar.action_request_id) AS execute_count, TRUNCATE(SUM(msr.process_time), 2) AS total_process_time '
          .'FROM delta_action_requests mar, delta_sql_requests msr '
          .'WHERE mar.action_request_id = msr.action_request_id '
          .'AND mar.hostname = :hostname '
          .'AND mar.summary_date between :begin_summary_date AND :end_summary_date '
          .'AND (mar.module_name = :module_name OR :module_name IS NULL) '
          .'GROUP BY summary_date, msr.statement_type '
          .'ORDER BY summary_date DESC';

    $stmt = $conn->createStatement($query);
    $stmt->bindValue(':hostname', php_uname('n'));
    $stmt->bindValue(':module_name', $moduleName);
    $stmt->bindValue(':begin_summary_date', $beginSummaryDate);
    $stmt->bindValue(':end_summary_date', $endSummaryDate);
    $resultSet = $stmt->executeQuery();

    $data = array();
    $previousDate = NULL;

    while ($record = $resultSet->read()) {
      $currentDate = $record['summary_date'];

      if ($previousDate !== $currentDate) {
        $array = array();
        $array['execute_count'] = 0;
        $array['total_process_time'] = 0;

        $data[$currentDate]['select'] = $array;
        $data[$currentDate]['insert'] = $array;
        $data[$currentDate]['update'] = $array;
        $data[$currentDate]['delete'] = $array;
        $data[$currentDate]['other'] = $array;
      }

      switch ($record['statement_type']) {
        case self::STATEMENT_TYPE_SELECT;
          $statementType = 'select';
          break;

        case self::STATEMENT_TYPE_INSERT;
          $statementType = 'insert';
          break;

        case self::STATEMENT_TYPE_UPDATE;
          $statementType = 'update';
          break;

        case self::STATEMENT_TYPE_DELETE;
          $statementType = 'delete';
          break;

        default:
          $statementType = 'other';
          break;
      }

      $array = array();
      $array['execute_count'] = $record['execute_count'];
      $array['total_process_time'] = $record['total_process_time'];

      $data[$currentDate][$statementType] = $array;
      $previousDate = $currentDate;
    }

    return $data;
  }

  public function findSlowStatementByAction($moduleName, $actionName, $beginSummaryDate, $endSummaryDate)
  {
    $conn = $this->getConnection();
    $query = 'SELECT msr.sql_request_id, msr.statement, TRUNCATE(msr.process_time, 5) AS process_time, mar.register_date '
          .'FROM delta_sql_requests msr, delta_action_requests mar '
          .'WHERE msr.action_request_id = mar.action_request_id '
          .'AND module_name = :module_name '
          .'AND action_name = :action_name '
          .'AND mar.hostname = :hostname '
          .'AND mar.summary_date BETWEEN :begin_summary_date AND :end_summary_date '
          .'GROUP BY msr.statement_hash '
          .'ORDER BY msr.process_time DESC '
          .'LIMIT 10';

    $stmt = $conn->createStatement($query);
    $stmt->bindValue(':hostname', php_uname('n'));
    $stmt->bindValue(':module_name', $moduleName);
    $stmt->bindValue(':action_name', $actionName);
    $stmt->bindValue(':begin_summary_date', $beginSummaryDate);
    $stmt->bindValue(':end_summary_date', $endSummaryDate);
    $resultSet = $stmt->executeQuery();

    $data = $resultSet->readAll();

    return $data;
  }

  public function findSlowStatement($moduleName, $beginSummaryDate, $endSummaryDate, $orderType)
  {
    if ($orderType === 'averageTime') {
      $orderColumn = 'average_process_time';
    } else {
      $orderColumn = 'request_count';
    }

    $conn = $this->getConnection();
    $query = sprintf('SELECT statement_hash, statement, request_count, TRUNCATE(total_process_time / request_count, 5) AS average_process_time, last_access_date '
          .'FROM ('
          .'SELECT msr.statement_hash, msr.statement, COUNT(msr.statement_hash) AS request_count, SUM(msr.process_time) AS total_process_time, MAX(register_date) AS last_access_date '
          .'FROM delta_sql_requests msr, delta_action_requests mar '
          .'WHERE msr.action_request_id = mar.action_request_id '
          .'AND msr.prepared_statement IS NULL '
          .'AND mar.hostname = :hostname '
          .'AND (mar.module_name = :module_name OR :module_name IS NULL) '
          .'AND mar.summary_date between :begin_summary_date AND :end_summary_date '
          .'GROUP BY msr.statement_hash '
          .') AS alias '
          .'ORDER BY %s DESC '
          .'LIMIT 20', $orderColumn);

    $stmt = $conn->createStatement($query);
    $stmt->bindValue(':hostname', php_uname('n'));
    $stmt->bindValue(':module_name', $moduleName);
    $stmt->bindValue(':begin_summary_date', $beginSummaryDate);
    $stmt->bindValue(':end_summary_date', $endSummaryDate);
    $resultSet = $stmt->executeQuery();

    $data = $resultSet->readAll();

    return $data;
  }

  public function findSlowPreparedStatement($moduleName, $beginSummaryDate, $endSummaryDate, $orderType)
  {
    if ($orderType === 'averageTime') {
      $orderColumn = 'average_process_time';
    } else {
      $orderColumn = 'request_count';
    }

   $conn = $this->getConnection();
    $query = sprintf('SELECT statement_hash, statement, request_count, TRUNCATE(total_process_time / request_count, 5) AS average_process_time, last_access_date '
          .'FROM ('
          .'SELECT msr.statement_hash, msr.prepared_statement AS statement, COUNT(msr.statement_hash) AS request_count, SUM(msr.process_time) AS total_process_time, MAX(register_date) AS last_access_date '
          .'FROM delta_sql_requests msr, delta_action_requests mar '
          .'WHERE msr.action_request_id = mar.action_request_id '
          .'AND msr.prepared_statement IS NOT NULL '
          .'AND mar.hostname = :hostname '
          .'AND (mar.module_name = :module_name OR :module_name IS NULL) '
          .'AND mar.summary_date between :begin_summary_date AND :end_summary_date '
          .'GROUP BY msr.statement_hash '
          .') AS alias '
          .'ORDER BY %s DESC '
          .'LIMIT 20', $orderColumn);

    $stmt = $conn->createStatement($query);
    $stmt->bindValue(':hostname', php_uname('n'));
    $stmt->bindValue(':module_name', $moduleName);
    $stmt->bindValue(':begin_summary_date', $beginSummaryDate);
    $stmt->bindValue(':end_summary_date', $endSummaryDate);
    $resultSet = $stmt->executeQuery();

    $data = $resultSet->readAll();

    return $data;
  }

  public function getMostSlowStatementInfo($statementHash, $moduleName, $beginSummaryDate, $endSummaryDate)
  {
    $conn = $this->getConnection();
    $query = 'SELECT msr.statement, TRUNCATE(msr.process_time, 5) AS most_slow_process_time, msr.file_path, msr.class_name, msr.method_name, msr.line '
          .'FROM delta_sql_requests msr, delta_action_requests mar '
          .'WHERE msr.action_request_id = mar.action_request_id '
          .'AND mar.hostname = :hostname '
          .'AND (mar.module_name = :module_name OR :module_name IS NULL) '
          .'AND mar.summary_date between :begin_summary_date AND :end_summary_date '
          .'AND msr.statement_hash = :statement_hash '
          .'ORDER BY msr.process_time DESC '
          .'LIMIT 1';

    $stmt = $conn->createStatement($query);
    $stmt->bindValue(':hostname', php_uname('n'));
    $stmt->bindValue(':module_name', $moduleName);
    $stmt->bindValue(':begin_summary_date', $beginSummaryDate);
    $stmt->bindValue(':end_summary_date', $endSummaryDate);
    $stmt->bindValue(':statement_hash', $statementHash);
    $resultSet = $stmt->executeQuery();

    return  $resultSet->readFirst();
  }

  public function findBySQLRequestId($queryRequestId)
  {
    $conn = $this->getConnection();
    $query = 'SELECT msr.statement, TRUNCATE(msr.process_time, 5) AS most_slow_process_time, msr.file_path, msr.class_name, msr.method_name, msr.line '
          .'FROM delta_sql_requests msr, delta_action_requests mar '
          .'WHERE msr.action_request_id = mar.action_request_id '
          .'AND mar.hostname = :hostname '
          .'AND msr.sql_request_id = :sql_request_id';

    $stmt = $conn->createStatement($query);
    $stmt->bindValue(':hostname', php_uname('n'));
    $stmt->bindValue(':sql_request_id', $queryRequestId);
    $resultSet = $stmt->executeQuery();

    return $resultSet->readFirst();
  }

  public function deleteByStatementHash($statementHash)
  {
    $conn = $this->getConnection();
    $query = 'DELETE msr FROM delta_action_requests mar, delta_sql_requests msr '
          .'WHERE mar.action_request_id = msr.action_request_id '
          .'AND msr.statement_hash = :statement_hash '
          .'AND mar.hostname = :hostname';

    $stmt = $conn->createStatement($query);
    $stmt->bindValue(':hostname', php_uname('n'));
    $stmt->bindValue(':statement_hash', $statementHash);
    $affectedCount = $stmt->execute();

    if ($affectedCount) {
      return TRUE;
    }

    return FALSE;
  }
}
