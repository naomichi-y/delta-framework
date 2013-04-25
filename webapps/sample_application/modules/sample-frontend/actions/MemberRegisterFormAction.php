<?php
/**
 * @package modules.entry.actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */

class MemberRegisterFormAction extends Delta_Action
{
  public function execute()
  {
    // トランザクショントークンを発行
    $this->getUser()->saveToken();

    // 誕生年の初期値
    if (!$this->getMessages()->hasFieldError()) {
      $this->getForm()->set('birth.year', 1980);
    }

    return Delta_View::SUCCESS;
  }
}
