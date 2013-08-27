<?php
/**
 * @package modules.manager.actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */

class LoginAction extends Delta_Action
{
  public function execute()
  {
    $form = $this->getForm();

    $loginId = $form->get('loginId');
    $loginPassword = sha1('salt' . $form->get('loginPassword'));

    $managersDAO = Delta_DAOFactory::create('Managers');
    $manager = $managersDAO->find($loginId, $loginPassword);

    if ($manager) {
      $user = $this->getUser();

      $user->addRole('manager');
      $user->setAttribute('manager', $manager);

      return Delta_View::SUCCESS;
    }

    $this->getMessages()->addError('ログイン認証に失敗しました。');

    return Delta_View::ERROR;
  }
}
