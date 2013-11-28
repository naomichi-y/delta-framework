<?php
/**
 * @package modules.demo_admin.controllers
 */
class MemberController extends Delta_ActionController
{
  public function listAction()
  {
    Delta_DAOFactory::create('Members')->findToPager()->assignView();
  }

  public function profileAction()
  {
    $memberId = $this->getRequest()->getQuery('memberId');
    $member = Delta_DAOFactory::create('Members')->findByMemberId($memberId);

    // デモアプリケーション上、会員が存在しない場合の処理は行っていない
    // 通常は例外を発生させるか、テンプレート側でエラーを表示する
    if ($member) {
      $view = $this->getView();
      $view->setAttribute('mailAddress', $member->mail_address);
      $view->setAttribute('nickname', $member->nickname);
      $view->setAttribute('birthDate', $member->birth_date);
      $view->setAttribute('blood', $member->blood);
      $view->setAttribute('hobbies', $member->hobbies);
      $view->setAttribute('message', $member->message);

    } else {
      throw new Delta_DataNotFoundException('データがありません。');
    }
  }
}
