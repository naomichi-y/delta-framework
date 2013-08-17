<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package kernel.observer.listener
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * このクラスは、実験的なステータスにあります。
 * これは、この関数の動作、関数名、ここで書かれていること全てが delta の将来のバージョンで予告な>く変更される可能性があることを意味します。
 * 注意を喚起するとともに自分のリスクでこのクラスを使用してください。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package kernel.observer.listener
 */
class Delta_PerformanceListener extends Delta_WebApplicationEventListener
{
  /**
   * @var int
   */
  private $_startTime;

  /**
   * @var Delta_SQLProfiler
   */
  private $_profiler;

  /**
   * @see Delta_ApplicationEventListener::getListenEvents()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getListenEvents()
  {
    return array('preProcess', 'postProcess');
  }

  /**
   * @see Delta_ApplicationEventListener::getBootMode()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getBootMode()
  {
    return Delta_BootLoader::BOOT_MODE_WEB;
  }

  /**
   * @see Delta_KernelEventObserver::preProcess()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function preProcess()
  {
    $this->_profiler = Delta_DatabaseManager::getInstance()->getProfiler();
    $this->_profiler->start();
    $this->_startTime = microtime(TRUE);

    Delta_ClassLoader::addSearchPath(DELTA_ROOT_DIR . '/webapps/cpanel/libs');
  }

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   * @since 1.1
   */
  public static function getDataSourceId()
  {
    $listenersConfig = Delta_Config::get(Delta_Config::TYPE_DEFAULT_APPLICATION)->get('observer.listeners');
    $dataSourceId = FALSE;

    if ($listenersConfig) {
      foreach ($listenersConfig as $listenerId => $attributes) {
        if ($listenerId === 'performanceListener') {
          $dataSourceId = $attributes->get('dataSource', Delta_DatabaseManager::DEFAULT_DATASOURCE_ID);
          break;
        }
      }
    }

    return $dataSourceId;
  }

  /**
   * @see Delta_KernelEventObserver::postProcess()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function postProcess()
  {
    if ($this->_profiler) {
      $endTime = microtime(TRUE);
      $reporter = Delta_SQLProfiler::getInstance();

      try {
        $processTime = Delta_NumberUtils::roundDown($endTime - $this->_startTime, 3);
        $controller = Delta_FrontController::getInstance();

        $request = $controller->getRequest();
        $session = $request->getSession();
        $route = $request->getRoute();

        if ($session->isActive()) {
          $sessionId = $session->getId();
        } else {
          $sessionId = NULL;
        }

        // ActionRequest の生成
        $report = array(
          'hostname' => php_uname('n'),
          'sessionId' => $sessionId,
          'requestPath' => $request->getURI(FALSE),
          'moduleName' => $route->getModuleName(),
          'actionName' => $route->getActionName(),
          'selectCount' => $reporter->getSelectCount(),
          'insertCount' => $reporter->getInsertCount(),
          'updateCount' => $reporter->getUpdateCount(),
          'deleteCount' => $reporter->getDeleteCount(),
          'otherCount' => $reporter->getOtherCount(),
          'processTime' => $processTime,
          'summaryDate' => new Delta_DatabaseExpression('CURDATE()'),
          'registerDate' => new Delta_DatabaseExpression('NOW()')
        );
        $actionRequest = new Delta_ActionRequests($report);

        $actionRequestsDAO = Delta_DAOFactory::create('Delta_ActionRequestsDAO');
        $actionRequestId = $actionRequestsDAO->insert($actionRequest);

        // SQLRequest の生成
        $reports = $reporter->getReports();
        $sqlRequestsDAO = Delta_DAOFactory::create('Delta_SQLRequestsDAO');

        $deltaRootDirectory = str_replace('/', DIRECTORY_SEPARATOR, DELTA_ROOT_DIR);

        foreach ($reports as $report) {
          if (strpos($report->fileName, $deltaRootDirectory) !== FALSE) {
            continue;
          }

          $sqlRequest = new Delta_SQLRequests();
          $sqlRequest->actionRequestId = $actionRequestId;

          switch ($report->statementType) {
            case 'select':
              $statementType = Delta_SQLRequestsDAO::STATEMENT_TYPE_SELECT;
              break;

            case 'insert':
              $statementType = Delta_SQLRequestsDAO::STATEMENT_TYPE_INSERT;
              break;

            case 'update':
              $statementType = Delta_SQLRequestsDAO::STATEMENT_TYPE_UPDATE;
              break;

            case 'delete':
              $statementType = Delta_SQLRequestsDAO::STATEMENT_TYPE_DELETE;
              break;

            default:
              $statementType = Delta_SQLRequestsDAO::STATEMENT_TYPE_OTHER;
              break;
          }

          $sqlRequest->statementType = $statementType;
          $sqlRequest->statementHash = $report->statementHash;

          if (isset($report->preparedStatement)) {
            $sqlRequest->preparedStatement = $report->preparedStatement;
          } else {
            $sqlRequest->preparedStatement = Delta_DatabaseExpression::null();
          }

          $sqlRequest->statement = $report->statement;
          $sqlRequest->processTime = $report->time;
          $sqlRequest->filePath = $report->fileName;
          $sqlRequest->className = $report->className;
          $sqlRequest->methodName = $report->methodName;
          $sqlRequest->line = $report->line;

          $sqlRequestsDAO->insert($sqlRequest);
        }

      } catch (PDOException $e) {
        Delta_ErrorHandler::invokeFatalError(E_ERROR,
          $e->getMessage(),
          __FILE__,
          __LINE__);
      }
    }
  }
}
