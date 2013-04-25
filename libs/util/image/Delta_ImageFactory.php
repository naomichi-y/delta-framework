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
 * イメージを扱うためのユーティリティライブラリを提供します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.image
 */
class Delta_ImageFactory extends Delta_Object
{
  /**
   * GD ライブラリ定数。
   */
  const IMAGE_ENGINE_GD = 'gd';

  /**
   * ImageMagick ライブラリ定数。
   */
  const IMAGE_ENGINE_IMAGE_MAGICK = 'imagick';

  /**
   * 指定したイメージエンジンがシステム内で有効な状態にあるかどうかチェックします。
   *
   * @param string $type IMAGE_ENGINE_* 定数を指定。
   * @return bool イメージエンジンが有効な場合は TRUE、無効な場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function isEnableImageEngine($type)
  {
    return extension_loaded($type);
  }

  /**
   * Delta_ImageFactory がサポートしているイメージエンジンのリストを取得します。
   *
   * @return array サポートしているイメージエンジンの定数 (IMAGE_ENGINE_*) を配列形式で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getSupportedImageEngine()
  {
    $array = array();

    if (self::isEnableImageEngine(self::IMAGE_ENGINE_GD)) {
      $array[] = self::IMAGE_ENGINE_GD;
    }

    if (self::isEnableImageEngine(self::IMAGE_ENGINE_IMAGE_MAGICK)) {
      $array[] = self::IMAGE_ENGINE_IMAGE_MAGICK;
    }

    return $array;
  }

  /**
   * イメージを扱うためのユーティリティクラスを取得します。
   *
   * @param string $type 使用するイメージエンジン。IMAGE_ENGINE_* 定数を指定。
   * @return Delta_Image Delta_Image オブジェクトのインスタンスを返します。
   * @throws Delta_UnsupportedException ライブラリが使用可能な状態でない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function create($type = self::IMAGE_ENGINE_GD)
  {
    if (!self::isEnableImageEngine($type)) {
      $message = sprintf('Extension is not loaded. [%s]', $type);
      throw new Delta_UnsupportedException($message);
    }

    $delegate = NULL;

    switch ($type) {
      case self::IMAGE_ENGINE_GD:
        $delegate = new Delta_ImageGDDelegate();
        break;

      case self::IMAGE_ENGINE_IMAGE_MAGICK:
        $delegate = new Delta_ImageImageMagickDelegate();
        break;

      default:
        $message = sprintf('Library is not available. [%s]', $type);
        throw new Delta_UnsupportedException($message);
    }

    return self::createFromDelegate($delegate);
  }

  /**
   * イメージを扱うためのユーティリティクラスを取得します。
   *
   * @param Delta_ImageDelegate $delegate Delta_ImageDelegate を実装したイメージクラスのインスタンス。
   * @return Delta_Image Delta_Image オブジェクトのインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function createFromDelegate(Delta_ImageDelegate $delegate)
  {
    $instance = new Delta_Image();
    $instance->setDelegate($delegate);

    return $instance;
  }
}
