<?php
/**
 * @package modules.demo_front.controllers
 */
class SignupController extends Delta_ActionController
{
  public function formAction()
  {
    $form = $this->createForm('Signup');
    $form->saveToken();
    $form->set('birth_year', '1980');

    $this->getView()->setForm('form', $form);
  }

  public function confirmAction()
  {
    $view = $this->getView();

    $form = $this->createForm('Signup');
    $view->setForm('form', $form);

    if ($form->validate()) {
      $loginPasswordMask = str_repeat('*', strlen($form->get('login_password')));
      $view->setAttribute('loginPasswordMask', $loginPasswordMask);

      $tokenId = $this->getUser()->getAttribute(Delta_Form::TOKEN_FIELD_NAME);
      $previewPath = $this->getService('Member')->getAvatarPreviewPath($tokenId);

      $uploader = new Delta_ImageUploader('avatar');
      $hasUpload = FALSE;

      if ($uploader->isUpload()) {
        if (Delta_ImageFactory::isEnableImageEngine(Delta_ImageFactory::IMAGE_ENGINE_GD)) {
          $uploader->setImageEngine(Delta_ImageFactory::IMAGE_ENGINE_GD);

          $image = $uploader->getImage();
          $image->resizeByMaximum(200);
          $image->trim(100, 100, 50, 50, Delta_ImageColor::createFromHTMLColor('#ffffff'));
          $image->convertFormat(Delta_Image::IMAGE_TYPE_JPEG);
          $image->save($previewPath);

        } else {
          $uploader->deploy($previewPath);
        }

        $hasUpload = TRUE;
      }

      $view->setAttribute('hasUpload', $hasUpload);

    } else {
      $this->forward('form');
    }
  }

  public function previewAvatarAction()
  {
    $tokenId = $this->getUser()->getAttribute(Delta_Form::TOKEN_FIELD_NAME);
    $previewPath = $this->getService('Member')->getAvatarPreviewPath($tokenId);

    if (is_file($previewPath)) {
      $this->getResponse()->writeImage(Delta_FileUtils::readFile($previewPath));
    }

    return $this->getView()->setDisableOutput();
  }

  public function dispatchUnknownAction()
  {
    $this->forward('confirm');
  }

  public function registerAction()
  {
    $form = $this->createForm('Signup');

    if ($form->validate(TRUE)) {
      $member = $form->getEntity('Members');
      $member->login_password = Delta_StringUtils::buildHash($form->get('login_password'));
      $member->birth_date = $form->get('birth_year') . '-' . $form->get('birth_month') . '-' . $form->get('birth_day');
      $member->hobbies = array_sum($form->get('hobbies'));
      $member->register_date = new Delta_DatabaseExpression('NOW()');

      $membersDAO = Delta_DAOFactory::create('Members');
      $memberId = $membersDAO->insert($member);

      $tokenId = $form->get(Delta_Form::TOKEN_FIELD_NAME);

      $service = $this->getService('Member');
      $avatarPreviewPath = $service->getAvatarPreviewPath($tokenId);

      if (is_file($avatarPreviewPath)) {
        $avatarPath = $service->getAvatarPath($memberId);
        Delta_FileUtils::move($avatarPreviewPath, $avatarPath);
      }

      $this->getMessages()->add('会員登録が完了しました。');

    } else {
      $form->clear();
      $this->forward('form');
    }
  }
}
