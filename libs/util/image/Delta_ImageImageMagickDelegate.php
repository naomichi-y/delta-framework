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
 * ImageMagick を使用してイメージの作成や修正を行います。
 * 本クラスを利用するには、ImageMagick 及び Imagick のインストールが必要です。
 *
 * @link http://www.imagemagick.org/script/index.php ImageMagick
 * @link http://www.php.net/manual/imagick.installation.php Imagick Installation
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.image
 */
class Delta_ImageImageMagickDelegate extends Delta_ImageDelegate
{
  /**
   * @see Delta_ImageDelegate::getImageEngine()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getImageEngine()
  {
    return Delta_ImageFactory::IMAGE_ENGINE_IMAGE_MAGICK;
  }

  /**
   * @see Delta_ImageDelegate::createOriginalImageFromFile()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function createOriginalImageFromFile($path, $type, $adjustOrientation = TRUE)
  {
    $image = new Imagick($path);

    if ($type == Delta_Image::IMAGE_TYPE_JPEG) {
      if (method_exists($image, 'getImageOrientation')) {
        $this->_orientation = $image->getImageOrientation();

      } else if (extension_loaded('exif')) {
        $exif = read_exif_data($path);

        if (isset($exif['Orientation'])) {
          $this->_orientation = $exif['Orientation'];
        }
      }

      if ($adjustOrientation) {
        $this->adjustOrientation($image);
      }
    }

    return $image->getImage();
  }

  /**
   * @see Delta_ImageDelegate::adjustOrientation()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function adjustOrientation(&$image)
  {
    switch ($this->_orientation) {
      case 1:
        break;

      case 2:
        $image->flopImage();
        break;

      case 3:
        $image->rotateImage(new ImagickPixel(), 180);
        break;

      case 4:
        $image->flipImage();
        break;

      case 5:
        $image->rotateImage(new ImagickPixel(), 270);
        $image->flipImage();
        break;

      case 6:
        $image->rotateImage(new ImagickPixel(), 90);
        break;

      case 7:
        $image->rotateImage(new ImagickPixel(), 90);
        $image->flipImage();
        break;

      case 8:
        $image->rotateImage(new ImagickPixel(), 270);
        break;

      default:
        break;
    }
  }

  /**
   * @see Delta_ImageDelegate::createOriginalImageFromBinary()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function createOriginalImageFromBinary($data)
  {
    try {
      $image = new Imagick();
      $image->readImageBlob($data);

      if (method_exists($image, 'getImageOrientation')) {
        $this->_orientation = $image->getImageOrientation();
      }

      return $image->getImage();

    } catch (ImagickException $e) {
      return FALSE;
    }
  }

  /**
   * @see Delta_ImageDelegate::getOriginalImageBounds()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getOriginalImageBounds(&$image)
  {
    try {
      $attributes = $image->getImageGeometry();

      $array = array();
      $array[] = $attributes['width'];
      $array[] = $attributes['height'];

      return $array;

    } catch (ImagickException $e) {
      return FALSE;
    }
  }

  /**
   * @see Delta_ImageDelegate::resize()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function resize(&$image, $width, $height, $resizeWidth, $resizeHeight)
  {
    return $image->thumbnailImage($resizeWidth, $resizeHeight);
  }

  /**
   * @see Delta_ImageDelegate::trim()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function trim(&$image, $parameters)
  {
    try {
      $image->cropImage($parameters['newWidth'],
        $parameters['newHeight'],
        $parameters['fromXPos'],
        $parameters['fromYPos']);

      // setImagePage() を実行しないと GIF イメージをトリミングした際に画像サイズが元のままとなってしまう
      $image->setImagePage($parameters['newWidth'], $parameters['newHeight'], 0, 0);

      if ($parameters['toWidth'] < $parameters['newWidth'] ||
          $parameters['toHeight'] < $parameters['newHeight']) {

        $fillColor = new ImagickPixel();
        $fillColor->setColor($parameters['fillColor']->getHTMLColor());

        $type = $this->_image->getDestinationImageAttribute('type');
        $imageFormat = $this->getImageFormat($type);

        $newImage = new Imagick();
        $newImage->newimage($parameters['newWidth'], $parameters['newHeight'], $fillColor);
        $newImage->setImageFormat($imageFormat);

        $newImage->compositeImage($image,
          $image->getImageCompose(),
          $parameters['toXPos'],
          $parameters['toYPos']);
        $fillColor->clear();
        $fillColor->destroy();

        $image = $newImage;
      }

      return TRUE;

    } catch (ImagickException $e) {
      return FALSE;
    }
  }

  /**
   * @see Delta_ImageDelegate::convertToFormat()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function convertFormat(&$image, $fromType, $toType, $width, $height)
  {
    if ($fromType == $toType) {
      return TRUE;
    }

    if ($image->setImageFormat($this->getImageFormat($toType))) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * @see Delta_ImageDelegate::createDestinationImage()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function createDestinationImage(&$image, $type, $path)
  {
    $result = FALSE;

    try {
      $image->stripImage();

      if ($type == Delta_Image::IMAGE_TYPE_JPEG) {
        $image->setCompression(Imagick::COMPRESSION_JPEG);
        $image->setImageCompressionQuality($this->_jpegQuality);

      } else if ($type == Delta_Image::IMAGE_TYPE_PNG) {
        $image->setCompression(Imagick::COMPRESSION_ZIP);
        $image->setImageCompressionQuality($this->_pngQuality);
      }

      if ($path === NULL) {
        echo $image->getImageBlob();

        $result = TRUE;
      } else {
        $result = $image->writeImage($path);
      }

    } catch (ImagickException $e) {
      $result = FALSE;
    }

    return $result;
  }

  /**
   * @see Delta_ImageDelegate::clear()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function clear(&$image)
  {
    $image->clear();
    $image->destroy();
  }

  /**
   * Delta_Image::IMAGE_TYPE_* 定数を {@link http://php.net/manual/function.imagick-setimageformat.php Imagick::setImageFormat()} が解釈できるフォーマット文字列に変換します。
   *
   * @param int $type Delta_Image::IMAGE_TYPE_* 定数。
   * @return string {@link http://php.net/manual/function.imagick-setimageformat.php Imagick::setImageFormat()} が解釈できるフォーマット文字列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function getImageFormat($type)
  {
     $mimeType = image_type_to_mime_type($type);
     $imageFormat = substr($mimeType, strpos($mimeType, '/') + 1);

     return $imageFormat;
  }
}
