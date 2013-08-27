<?php
/**
 * @package actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class AnalyzeSQLDetailAction extends Delta_Action
{
  public function execute()
  {
    $request = $this->getRequest();
    $view = $this->getView();

    $moduleName = $request->getQuery('module', NULL, TRUE);
    $type = $request->getQuery('type');
    $from = $request->getQuery('from');
    $to = $request->getQuery('to');
    $statementHash = $request->getQuery('hash');
    $sqlRequestId = $request->getQuery('id');

    $sqlRequestsDAO = Delta_DAOFactory::create('Delta_SQLRequestsDAO');

    if ($statementHash) {
      $statementInfo = $sqlRequestsDAO->getMostSlowStatementInfo($statementHash, $moduleName, $from, $to);
    } else {
      $statementInfo = $sqlRequestsDAO->findBySQLRequestId($sqlRequestId);
    }

    // 実行計画の表示
    if ($statementInfo) {
      $statement = trim($statementInfo['statement']);

      try {
        $sender = new Delta_HttpRequestSender('http://sqlformat.appspot.com/format/');
        $sender->setReadTimeout(3);
        $sender->setRequestMethod(Delta_HttpRequest::HTTP_POST);
        $sender->addParameter('data', $statement);
        $sender->addParameter('format', 'text');
        $sender->addParameter('reindent', TRUE);
        $sender->addParameter('n_indents', 2);
        $parser = $sender->send();

        if ($parser->getStatus() == 200) {
          $statementInfo['statement'] = $parser->getContents();
        }

      } catch (Exception $e) {}

      $isShowExplain = FALSE;
      $encoding = Delta_Config::getApplication()->get('charset.default');

      if (strcasecmp(mb_substr($statement, 0, 6, $encoding), 'SELECT') === 0) {
        $sql = 'EXPLAIN ' . $statement;
        $isShowExplain = TRUE;

      } else if (strcasecmp(mb_substr($statement, 0, 6, $encoding), 'UPDATE') === 0) {
        $pos = Delta_StringUtils::searchIndex($statement, 'SET', 0, '\'');
        $buffer = mb_substr($statement, 6, $pos - 6, $encoding);
        $sql = sprintf('EXPLAIN SELECT * FROM %s', $buffer);

        if (($pos = Delta_StringUtils::searchIndex($statement, 'WHERE', 0, '\'')) !== FALSE) {
          $sql .= ' ' . mb_substr($statement, $pos, mb_strlen($statement, $encoding), $encoding);
        }

        $isShowExplain = TRUE;

      } else if (strcasecmp(mb_substr($statement, 0, 6, $encoding), 'DELETE') === 0) {
        $pos = Delta_StringUtils::searchIndex($statement, 'FROM', 0, '\'');
        $buffer = mb_substr($statement, $pos + 4, $encoding);

        if (($pos = Delta_StringUtils::searchIndex($buffer, 'USING', 0, '\'')) !== FALSE) {
          $temp = mb_substr($buffer, 0, $pos, $encoding);

          if (($pos = Delta_StringUtils::searchIndex($statement, 'WHERE', 0, '\'')) !== FALSE) {
            $buffer = $temp . ' ' . mb_substr($statement, $pos, mb_strlen($statement, $encoding), $encoding);
          } else {
            $buffer = $temp;
          }
        }

        $sql = 'EXPLAIN SELECT * FROM ' . $buffer;
        $isShowExplain = TRUE;
      }

      // EXPLAIN 対象クエリの場合
      if ($isShowExplain) {
        try {
          $resultSet = $this->getDatabase()->getConnection()->rawQuery($sql);
          $records = $resultSet->readAll();

          $explainColumnNames = $records[0]->getNames();
          $view->setAttribute('explainColumnNames', $explainColumnNames);
          $view->setAttribute('explainRecords', $records, FALSE);

        } catch (PDOException $e) {}
      }

      $view->setAttribute('statementInfo', $statementInfo);
    }

    return Delta_View::SUCCESS;
  }
}
