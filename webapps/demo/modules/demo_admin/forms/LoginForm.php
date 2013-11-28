<?php
/**
 * @package modules.demo_front.forms
 */
class LoginForm extends Delta_Form
{
  public function build(Delta_DataFieldBuilder $builder)
  {
    $field = $builder->createDataField('login_id', 'ID');
    $field->addValidator('required');
    $builder->addField($field);

    $field = $builder->createDataField('login_password', 'パスワード');
    $field->addValidator('required');
    $builder->addField($field);
  }

  public function validate($checkToken = FALSE)
  {
    $result = parent::validate($checkToken);

    if ($result) {
      $loginId = $this->get('login_id');
      $loginPassword = $this->get('login_password');
      $loginPassword = sha1('salt' . $loginPassword);

      $managerssDAO = Delta_DAOFactory::create('Managers');
      $manager = $managerssDAO->find($loginId, $loginPassword);

      if (!$manager) {
        $this->setLogicError('ログイン認証に失敗しました。');
        $result = FALSE;
      }
    }

    return $result;
  }
}
