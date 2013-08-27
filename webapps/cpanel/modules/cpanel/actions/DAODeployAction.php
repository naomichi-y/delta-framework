<?php
/**
 * @package actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class DAODeployAction extends Delta_Action
{
  public function execute()
  {
    $form = $this->getForm();
    $view = $this->getView();

    // エンティティの移動
    $fromDirectory = APP_ROOT_DIR . '/tmp/entity';
    $toDirectory = APP_ROOT_DIR . '/libs/entity';

    $entities = $form->get('entities');
    $renameFiles = $this->moveFiles($fromDirectory, $toDirectory, $entities, TRUE);
    $view->setAttribute('entities', $renameFiles);

    // DAO の移動
    $renameFiles = array();

    $fromDirectory = APP_ROOT_DIR . '/tmp/dao';
    $toDirectory = APP_ROOT_DIR . '/libs/dao';

    $dataAccessObjects = $form->get('dataAccessObjects');
    $renameFiles = $this->moveFiles($fromDirectory, $toDirectory, $dataAccessObjects, FALSE);
    $view->setAttribute('dataAccessObjects', $renameFiles);

    return Delta_View::SUCCESS;
  }

  private function moveFiles($fromDirecotry, $toDirectory, $fileNames, $force = FALSE)
  {
    $renameFiles = array();

    if (is_array($fileNames)) {
      if (!is_dir($toDirectory)) {
        Delta_FileUtils::createDirectory($toDirectory);
      }

      foreach ($fileNames as $fileName) {
        $fromPath = $fromDirecotry . '/' . $fileName;
        $toPath = $toDirectory . '/' . $fileName;

        if (!is_file($fromPath)) {
          continue;
        }

        if ($force || !$force && !is_file($toPath)) {
          rename($fromPath, $toPath);
          $renameFiles[] = $fileName;
        }
      }
    }

    return $renameFiles;
  }
}
