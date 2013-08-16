<?php
/**
 * @package modules.entry.actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class MemberRegisterAction extends Delta_Action
{
  public function validate()
  {
    // 正当なトークンでリクエストされているかチェック
    $tokenState = $this->getUser()->getTokenState(TRUE);
    $result = FALSE;

    if ($tokenState == Delta_AuthorityUser::TOKEN_VALID) {
      $result = TRUE;

    } else if ($tokenState  == Delta_AuthorityUser::TOKEN_INVALID) {
      $this->getMessages()->addError('登録は完了済みです。');
      $this->getForm()->clear();

    } else {
      $this->getMessages()->addError('不正な画面遷移です。');
      $this->getForm()->clear();
    }

    return $result;
  }

  public function execute()
  {
    // フィールドデータをエンティティに変換
    $membersDAO = Delta_DAOFactory::create('Members');
    $member = $membersDAO->formToEntity();

    $form = $this->getForm();

    $birthDate = implode('/', $form->get('birth'));
    $passwordHash = Delta_StringUtils::buildHash($form->get('loginPassword'));
    $hobbies = array_sum($form->get('hobbies'));

    $member->loginPassword = $passwordHash;
    $member->birthDate = $birthDate;
    $member->hobbies = $hobbies;
    $member->registerDate = new Delta_DatabaseExpression('NOW()');

    $membersDAO->insert($member);
    $entity = $membersDAO->findByMailAddress($member->mailAddress);

    $tokenId = $form->get('tokenId');

    $service = $this->getService('Member');
    $writePath = $service->getIconPath($entity->memberId);
    $previewPath = $service->getIconPreviewPath($tokenId);

    if (is_file($previewPath)) {
      Delta_FileUtils::move($previewPath, $writePath);
    }

    $this->getMessages()->add('会員登録が完了しました。');

    return Delta_View::SUCCESS;
  }
}
