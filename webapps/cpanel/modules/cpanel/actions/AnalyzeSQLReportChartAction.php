<?php
/**
 * @package actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class AnalyzeSQLReportChartAction extends Delta_Action
{
  const MAX_X_POINTS = 10;

  public function execute()
  {
    $request = $this->getRequest();

    $moduleName = $request->getQuery('module', NULL, TRUE);
    $from = $request->getQuery('from');
    $to = $request->getQuery('to');

    $diff = Delta_DateUtils::getDiffDay($from, $to);

    if ($diff !== FALSE) {
      $midtermDays = $diff - 1;
      $division = self::MAX_X_POINTS - 2;

      if ($division == 1) {
        $division = 2;
      }

      $spanDays = round($midtermDays / $division);
      $interval = new DateInterval(sprintf('P%sD', $spanDays));

      $currentDate = new DateTime($from);
      $endDate = new DateTime($to);
      $targetDays[] = $currentDate->format('Y-m-d');
      $points = 1;

      while ($currentDate < $endDate && $points < self::MAX_X_POINTS - 1) {
        $currentDate->add($interval);
        $targetDays[] = $currentDate->format('Y-m-d');

        $points++;
      }

      if ($currentDate != $endDate) {
        $targetDays[] = $endDate->format('Y-m-d');
      }
    }

    $sqlRequestsDAO = Delta_DAOFactory::create('Delta_SQLRequestsDAO');
    $executeCounts = $sqlRequestsDAO->getExecuteCountsByDate($moduleName, $targetDays);
    $buffer = NULL;

    foreach ($executeCounts as $date => $values) {
      $buffer .= sprintf("%s,%s\n", $date, implode(',', $values));
    }

    echo trim($buffer, "\n");

    return Delta_View::NONE;
  }
}
