<?php
/**
 * This class was generated automatically by DAO Generator.
 * date: 09/14/2011 20:41:56
 *
 * @package libs.dao
 */
class Delta_ActionRequestsDAO extends Delta_PerformanceAnalyzerDAO
{
  protected $_tableName = 'delta_action_requests';
  protected $_primaryKeys = array('action_request_id');

  public function getBeginSummary()
  {
    $conn = $this->getConnection();
    $query = 'SELECT summary_date '
          .'FROM delta_action_requests '
          .'ORDER BY action_request_id ASC '
          .'LIMIT 1';

    $resultSet = $conn->rawQuery($query);
    $summaryDate = $resultSet->readField(0);

    return $summaryDate;
  }

  public function getEndSummary()
  {
    $conn = $this->getConnection();
    $query = 'SELECT summary_date '
          .'FROM delta_action_requests '
          .'ORDER BY action_request_id DESC '
          .'LIMIT 1';

    $resultSet = $conn->rawQuery($query);
    $summaryDate = $resultSet->readField(0);

    return $summaryDate;
  }

  public function findSlowActions($moduleName = NULL, $beginSummaryDate, $endSummaryDate, $orderType)
  {
    if ($orderType === 'averageTime') {
      $orderColumn = 'average_process_time';
    } else if ($orderType === 'manyCount') {
      $orderColumn = 'request_count';
    } else {
      $orderColumn = 'max_process_time';
    }

    $conn = $this->getConnection();
    $query = sprintf('SELECT module_name, action_name, request_count, TRUNCATE(total_process_time / request_count, 2) AS average_process_time, max_process_time, last_access_date '
          .'FROM '
          .'(SELECT module_name, action_name, COUNT(action_name) AS request_count, SUM(process_time) AS total_process_time, MAX(process_time) AS max_process_time, MAX(register_date) AS last_access_date '
          .'FROM delta_action_requests '
          .'WHERE hostname = :hostname '
          .'AND (module_name = :module_name OR :module_name IS NULL) '
          .'AND summary_date BETWEEN :begin_summary_date and :end_summary_date '
          .'GROUP BY module_name, action_name) AS alias '
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

  public function findSlowRequests($moduleName, $actionName, $beginSummaryDate, $endSummaryDate)
  {
    $conn = $this->getConnection();
    $query = 'SELECT action_request_id, request_path, request_count, total_select_count / request_count AS average_select_count, total_insert_count / request_count AS average_insert_count, total_update_count / request_count AS average_update_count, total_delete_count / request_count AS average_delete_count, total_other_count / request_count AS average_other_count, TRUNCATE(total_process_time / request_count, 2) AS average_process_time, last_access_date '
          .'FROM ('
          .'SELECT MAX(action_request_id) AS action_request_id, request_path, SUM(select_count) AS total_select_count, SUM(insert_count) AS total_insert_count, SUM(update_count) AS total_update_count, SUM(delete_count) AS total_delete_count, SUM(other_count) AS total_other_count, SUM(process_time) AS total_process_time, COUNT(request_path) AS request_count, MAX(register_date) AS last_access_date '
          .'FROM delta_action_requests '
          .'WHERE hostname = :hostname '
          .'AND module_name = :module_name '
          .'AND action_name = :action_name '
          .'AND summary_date BETWEEN :begin_summary_date and :end_summary_date '
          .'GROUP BY request_path '
          .'ORDER BY action_request_id DESC '
          .') AS alias '
          .'LIMIT 20';

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

  public function deleteByModuleAndAction($moduleName, $actionName)
  {
    $conn = $this->getConnection();
    $query = 'DELETE mar, msr FROM delta_action_requests mar, delta_sql_requests msr '
          .'WHERE mar.action_request_id = msr.action_request_id '
          .'AND mar.module_name = :module_name '
          .'AND mar.action_name = :action_name '
          .'AND mar.hostname = :hostname';

    $stmt = $conn->createStatement($query);
    $stmt->bindValue(':hostname', php_uname('n'));
    $stmt->bindValue(':module_name', $moduleName);
    $stmt->bindValue(':action_name', $actionName);
    $affectedCount = $stmt->execute();

    if ($affectedCount) {
      return TRUE;
    }

    return FALSE;
  }
}
