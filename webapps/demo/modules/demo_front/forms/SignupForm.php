<?php
/**
 * @package modules.demo_front.forms
 */
class SignupForm extends Delta_Form
{
  public function build(Delta_DataFieldBuilder $builder)
  {
    $field = $builder->createDataField('mail_address', 'メールアドレス');
    $field->addValidator('required');
    $field->addValidator('email');
    $builder->addField($field);

    $field = $builder->createDataField('login_password', 'パスワード');
    $field->addValidator('required');
    $field->addValidator('length', array('minLength' => 6, 'maxLength' => 8));
    $field->addValidator('mask', array('mask' => '/^(?=.*\d)(?=.*[a-z])\w{1,}$/', 'formatError' => '{$label}は英数字を組み合わせて下さい。'));
    $builder->addField($field);

    $field = $builder->createDataField('login_password_verify', 'パスワード(確認)');
    $field->addValidator('required');
    $field->addValidator('compare', array('compareField' => 'login_password', 'compareLabel' => 'パスワード'));
    $builder->addField($field);

    $field = $builder->createDataField('nickname', 'ニックネーム');
    $builder->addField($field);

    $field = $builder->createDataField('birth_year', '生年月日');
    $field->addValidator('date', array('yearField' => 'birth_year', 'monthField' => 'birth_month', 'dayField' => 'birth_day'));
    $builder->addField($field);

    $field = $builder->createDataField('birth_month', '月');
    $builder->addField($field);

    $field = $builder->createDataField('birth_day', '日');
    $builder->addField($field);

    $field = $builder->createDataField('blood', '血液型');
    $field->addValidator('radio');
    $builder->addField($field);

    $field = $builder->createDataField('hobbies', '趣味');
    $field->addValidator('check', array('requiredMin' => 1));
    $builder->addField($field);

    $field = $builder->createDataField('message', '自己紹介');
    $field->addValidator('required');
    $field->addValidator('length', array('minLneght' => 20, 'maxLength' => 30));
    $builder->addField($field);

    $field = $builder->createDataField('avatar', 'アバター');
    $field->addValidator('fileUpload', array('required' => FALSE, 'maxSize' => '2MB', 'mimeTypes' => array('image/jpeg', 'image/pjpeg', 'image/gif', 'image/png', 'image/x-png')));
    $builder->addField($field);
  }

  public function validate($checkToken = FALSE)
  {
    $result = parent::validate($checkToken);

    if ($result) {
      $membersDAO = Delta_DAOFactory::create('Members');

      if ($membersDAO->existsMailAddress($this->get('mail_address'))) {
        $this->addFieldError('mail_address', '指定されたメールアドレスは利用できません。');
        $result = FALSE;
      }
    }

    return $result;
  }
}
