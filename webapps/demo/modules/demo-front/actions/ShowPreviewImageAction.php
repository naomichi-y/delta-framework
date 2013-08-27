<?php
/**
 * @package modules.entry.actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */

class ShowPreviewImageAction extends Delta_Action
{
  public function validateErrorHandler()
  {
    return Delta_View::NONE;
  }

  public function execute()
  {
    $tokenId = $this->getUser()->getAttribute('tokenId');
    $previewPath = $this->getService('Member')->getIconPreviewPath($tokenId);

    if (is_file($previewPath)) {
      $data = Delta_FileUtils::readFile($previewPath);
      $this->getResponse()->writeBinary($data, 'image/jpeg');
    }

    return Delta_View::NONE;
  }
}
