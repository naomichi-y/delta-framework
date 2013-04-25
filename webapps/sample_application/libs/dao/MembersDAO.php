<?php
/**
 * It is a class generated with delta DAO Maker automatically.
 *
 * @package libs.dao
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */

class MembersDAO extends Delta_DAO
{
  protected $_tableName = 'members';
  protected $_primaryKeys = array('member_id');

  public function isExistMailAddress($mailAddress)
  {
    $conn = $this->getConnection();
    $sql = 'SELECT member_id '
          .'FROM members '
          .'WHERE mail_address = :mail_address '
          .'AND delete_flag = 0';

    $stmt = $conn->createStatement($sql);
    $stmt->bindValue(':mail_address', $mailAddress);
    $resultSet = $stmt->executeQuery();

    if ($resultSet->getRowCount()) {
      return TRUE;
    }

    return FALSE;
  }

  public function findToPager()
  {
    $conn = $this->getConnection();
    $sql = 'SELECT member_id, mail_address, login_password, nickname, birth_date, blood, hobbies, message, register_date, last_update_date, delete_flag '
          .'FROM members '
          .'WHERE delete_flag = 0';
    $stmt = $conn->createStatement($sql);

    $pager = Delta_DatabasePager::getInstance();
    $pager->addSort('member_id', Delta_Pager::SORT_DESCENDING);
    $pager->fetch($stmt, 10);

    return $pager;
  }

  public function findByMailAddress($mailAddress)
  {
    $conn = $this->getConnection();
    $sql = 'SELECT member_id, mail_address, login_password, nickname, birth_date, blood, hobbies, message, register_date, last_update_date, delete_flag '
          .'FROM members '
          .'WHERE mail_address = :mail_address '
          .'AND delete_flag = 0';

    $stmt = $conn->createStatement($sql);
    $stmt->bindValue(':mail_address', $mailAddress);
    $resultSet = $stmt->executeQuery();

    if ($record = $resultSet->read()) {
      return $record->toEntity('Members');
    }

    return FALSE;
  }

  public function findByMemberId($memberId)
  {
    $conn = $this->getConnection();
    $sql = 'SELECT member_id, mail_address, login_password, nickname, birth_date, blood, hobbies, message, register_date, last_update_date, delete_flag '
          .'FROM members '
          .'WHERE member_id = :member_id '
          .'AND delete_flag = 0';

    $stmt = $conn->createStatement($sql);
    $stmt->bindValue(':member_id', $memberId);
    $resultSet = $stmt->executeQuery();

    if ($record = $resultSet->readFirst()) {
      return $record->toEntity('Members');
    }

    return FALSE;
  }
}
