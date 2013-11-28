<?php
/**
 * @package modules.cpanel.forms
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class LoginForm extends Delta_Form
{
  public function build(Delta_DataFieldBuilder $builder)
  {
    $field = $builder->createDataField('login_password', 'パスワード');
    $field->addValidator('required');
    $builder->addField($field);
  }

  public function validate($checkToken = FALSE)
  {
    $result = parent::validate($checkToken);

    if ($result) {
      $loginPassword = $this->get('login_password');
      $validPassword = Delta_Config::getApplication()->get('cpanel.password');

      if (strcmp($loginPassword, $validPassword) != 0) {
        $this->setLogicError('ログイン認証に失敗しました。');
        $result = FALSE;
      }
    }

    return $result;
  }
}
