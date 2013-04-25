<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.image
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * イメージファイルのアップロード機能を提供します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.image
 */
class Delta_ImageUploader extends Delta_FileUploader
{
  /**
   * @var string
   */
  private $_imageEngine = Delta_ImageFactory::IMAGE_ENGINE_GD;

  /**
   * イメージエンジンを設定します。
   * このメソッドは、{@link getImage()} メソッドでイメージオブジェクトを取得する前に呼び出す必要があります。
   * 特に指定がない場合は GD エンジンが使用されます。
   *
   * @param int $imageEngine Delta_Factory::IMAGE_ENGINE_* 定数を指定。
   * @throw Delta_UnsupportedException PHP がイメージエンジンをサポートしていない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setImageEngine($imageEngine)
  {
    if (!extension_loaded($imageEngine)) {
      $message = sprintf('Library it not found. [%s]', $imageEngine);
      throw new Delta_UnsupportedException($message);
    }

    $this->_imageEngine = $imageEngine;
  }

  /**
   * アップロードされたファイルのイメージオブジェクトを取得します。
   *
   * @param bool $adjustOrientation {@link Delta_ImageDelegate::createOriginalImageFromFile()} メソッドを参照。
   * @return Delta_Image Delta_Image のインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getImage($adjustOrientation = TRUE)
  {
    $image = Delta_ImageFactory::create($this->_imageEngine);
    $image->load($this->getTemporaryFilePath(), $adjustOrientation);

    return $image;
  }
}
