<?php
/**
 * @package modules.entry.actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */

class MemberRegisterConfirmAction extends Delta_Action
{
  /**
   * バリデータで検証できないビジネスロジックの検証を行います。
   */
  public function validate()
  {
    // トークンの状態が正常かチェック
    if ($this->getUser()->getTokenState() != Delta_AuthorityUser::TOKEN_VALID) {
      $this->getMessages()->addError('不正な画面遷移です。');

      return FALSE;
    }

    // バリデータでチェックできない項目を検証
    $mailAddress = $this->getForm()->get('mailAddress');

    $membersDAO = Delta_DAOFactory::create('Members');

    if ($membersDAO->existsMailAddress($mailAddress)) {
      $this->getMessages()->addFieldError('mailAddress', '指定されたメールアドレスは使用できません。');

      return FALSE;
    }

    return TRUE;
  }

  public function execute()
  {
    $form = $this->getForm();
    $view = $this->getView();

    // パスワードをマスクして表示
    $loginPassword = $form->get('loginPassword');
    $loginPasswordMask = str_repeat('*', strlen($loginPassword));

    $view->setAttribute('loginPasswordMask', $loginPasswordMask);

    // アップロードイメージのサンプルを出力
    $tokenId = $form->get('tokenId');
    $previewPath = $this->getService('Member')->getIconPreviewPath($tokenId);
    $imageEngine = NULL;

    $uploader = new Delta_ImageUploader('icon');
    $hasUpload = FALSE;

    if ($uploader->isUpload()) {
      // 実行環境に GD、あるいは Imagick モジュールが組み込まれている場合は、イメージのリサンプリングを行う
      if (Delta_ImageFactory::isEnableImageEngine(Delta_ImageFactory::IMAGE_ENGINE_GD)) {
        $imageEngine = Delta_ImageFactory::IMAGE_ENGINE_GD;
      } else {
        $imageEngine = Delta_ImageFactory::IMAGE_ENGINE_IMAGE_MAGICK;
      }

      if ($imageEngine !== NULL) {
        $uploader->setImageEngine($imageEngine);

        // イメージのリサイズ
        $fillColor = Delta_ImageColor::createFromHTMLColor('#ffffff');

        $image = $uploader->getImage();
        $image->resizeByMaximum(200);
        $image->trim(100, 100, 50, 50, $fillColor);
        $image->convertFormat(Delta_Image::IMAGE_TYPE_JPEG);
        $image->save($previewPath);

      } else {
        $uploader->deploy($previewPath);
      }

      $hasUpload = TRUE;
    }

    $view->setAttribute('hasUpload', $hasUpload);

    return Delta_View::SUCCESS;
  }
}
