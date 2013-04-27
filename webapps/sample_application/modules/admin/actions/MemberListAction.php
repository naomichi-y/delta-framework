<?php
/**
 * @package modules.manager.actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */

class MemberListAction extends Delta_Action
{
  public function execute()
  {
    // 会員リストをデータベースから取得してページャに変換
    Delta_DAOFactory::create('Members')->findToPager()->assignView();

    return Delta_View::SUCCESS;
  }
}
