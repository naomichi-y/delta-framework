<?php
/**
 * This class was generated automatically by DAO Generator.
 * date: 09/14/2011 20:41:56
 *
 * @package libs.dao
 */
class Delta_PerformanceAnalyzerDAO extends Delta_DAO
{
  public function __construct()
  {
    $dataSourceId = Delta_PerformanceListener::getDataSourceId();
    $this->setDataSourceId($dataSourceId);
  }
}
