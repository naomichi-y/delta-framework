<?php
/**
 * @package libs.service
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class MemberService extends Delta_Service
{
  public function getIconPreviewPath($tokenId)
  {
    return sprintf('%s/tmp/icons/%s', APP_ROOT_DIR, $tokenId);
  }

  public function getIconPath($memberId)
  {
    $subdirectory = substr(str_pad($memberId, 2, '0', STR_PAD_LEFT), -2);
    $file = str_pad($memberId, 8, '0', STR_PAD_LEFT);

    $path = sprintf('%s/webroot/assets/images/icons/%s/%s.jpg',
                    APP_ROOT_DIR,
                    $subdirectory,
                    $file);

    return $path;
  }
}
