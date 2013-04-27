<?php
/**
 * @package modules.entry.actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */

class MemberRegisterDispatchAction extends Delta_DispatchAction
{
  public function defaultForward()
  {
    return $this->dispatchMemberRegisterForm();
  }

  public function dispatchMemberRegisterForm()
  {
    return 'MemberRegisterForm';
  }

  public function dispatchMemberRegister()
  {
    return 'MemberRegister';
  }
}
