<?php
/**
 * This class was generated automatically by DAO Generator.
 *
 * @package libs.dao
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class ManagersDAO extends Delta_DAO
{
  protected $_tableName = 'managers';
  protected $_primaryKeys = array('manager_id');

  public function find($loginId, $loginPassword)
  {
    $conn = $this->getConnection();
    $sql = 'SELECT manager_id, login_id, login_password, manager_name, register_date, last_update_date, delete_flag '
          .'FROM managers '
          .'WHERE login_id = :login_id '
          .'AND login_password = :login_password '
          .'AND delete_flag = 0';

    $stmt = $conn->createStatement($sql);
    $stmt->bindParam(':login_id', $loginId);
    $stmt->bindParam(':login_password', $loginPassword);
    $resultSet = $stmt->executeQuery();

    if ($record = $resultSet->readFirst()) {
      return $record->toEntity('Managers');
    }

    return FALSE;
  }
}
