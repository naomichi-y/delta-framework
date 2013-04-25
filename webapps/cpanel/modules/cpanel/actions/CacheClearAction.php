<?php
/**
 * @package actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class CacheClearAction extends Delta_Action
{
  public function execute()
  {
    $clearDirectory = $this->getRequest()->getAttribute('clearDirectory');
    Delta_FileUtils::deleteDirectory($clearDirectory, FALSE);

    $this->getMessages()->add('キャッシュを削除しました。');

    return Delta_View::SUCCESS;
  }
}
