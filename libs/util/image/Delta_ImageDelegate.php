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
 * {@link Delta_Image} を利用したイメージライブラリクラスが実装すべきメソッドを定義する抽象クラスです。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.image
 */
abstract class Delta_ImageDelegate extends Delta_Object
{
  /**
   * {@link Delta_Image} オブジェクト。
   * @var Delta_Image
   */
  protected $_image;

  /**
   * イメージの方向。(Exif: Orientation タグ)
   * @var int
   */
  protected $_orientation;

  /**
   * JPEG 圧縮率。
   * @var int
   */
  protected $_jpegQuality = 75;

  /**
   * PNG 圧縮率。
   * @var int
   */
  protected $_pngQuality = 2;

  /**
   * Delta_Image オブジェクトをセットします。
   *
   * @param Delta_Image $image イメージのインスタンス。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setImage(Delta_Image $image)
  {
    $this->_image = $image;
  }

  /**
   * イメージエンジンのタイプを取得します。
   *
   * @return string Delta_ImageFactory::IMAGE_ENGINE_* 定数を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function getImageEngine();

  /**
   * イメージファイルからイメージオブジェクト (またはリソース) を生成します。
   *
   * @param string $path 参照するイメージのパス。
   * @param int $type イメージの形式。Delta_Image::IMAGE_TYPE_* 定数を参照。
   * @return mixed ライブラリが提供するイメージオブジェクト、またはリソース型を返します。
   * @param bool $adjustOrientation TRUE を指定した場合、JPEG イメージの方向を Exif の Orientation タグに基づいて補正します。
   *   - {@link http://www.php.net/manual/book.exif.php Exif モジュール} がインストールされている場合のみ有効です。
   *     モジュールがインストールされていない場合は FALSE (生の画像を読み込む) と同じ動作になります。
   *   - GD エンジン利用時の補足: GD は回転補正は行うものの、イメージ反転 (水平反転・垂直反転) 補正を行いません。
   *   - ImageMagick エンジン利用時の補足: Imagick が ImageMagick 6.3 以降でコンパイルされている場合、Exif モジュールのインストールは不要となります。
   * @return mixed ライブラリが提供するイメージオブジェクト、またはリソース型を返します。
   *   オブジェクトの生成に失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function createOriginalImageFromFile($path, $type, $adjustOrientation = TRUE);

  /**
   * JPEG イメージの方向を補正します。
   * このメソッドは {@link createOriginalImageFromFile()} メソッドからコールされます。
   *
   * @param mixed &$image ライブラリが提供するイメージオブジェクト、またはリソース型。
   * @link http://sylvana.net/jpegcrop/exif_orientation.html Exif Orientation Tag
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function adjustOrientation(&$image);

  /**
   * バイナリデータからイメージオブジェクトを生成します。
   *
   * @param string $data 元となるバイナリデータ。
   * @return mixed ライブラリが提供するイメージオブジェクト、またはリソース型を返します。
   *   オブジェクトの生成に失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function createOriginalImageFromBinary($data);

  /**
   * オリジナルイメージのサイズを取得します。
   *
   * @param mixed &$image ライブラリが提供するイメージオブジェクト、またはリソース型。
   * @return array イメージサイズが格納された添字配列を返します。(0:横幅、1:縦幅)
   *   サイズ取得に失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function getOriginalImageBounds(&$image);

  /**
   * イメージをリサイズします。
   *
   * @param mixed &$image ライブラリが提供するイメージオブジェクト、またはリソース型。
   * @param int $fromWidth 元イメージの横幅サイズ。
   * @param int $fromHeight 元イメージの縦幅サイズ。
   * @param int $toWidth リサイズ後の横幅サイズ。
   * @param int $toHeight リサイズ後の縦幅サイズ。
   * @return bool リサイズに成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function resize(&$image, $fromWidth, $fromHeight, $toWidth, $toHeight);

  /**
   * @param mixed &$image ライブラリが提供するイメージオブジェクト、またはリソース型。
   * @param array $parameters トリミング情報を格納した配列。
   *   - newWidth: 新しいキャンバスの高さ。
   *   - newHeight: 新しいキャンバスの幅。
   *   - toXPos: キャンバスにトリミングイメージをコピーする際の開始座標。(X 軸)
   *   - toYPos: キャンバスにトリミングイメージをコピーする際の開始座標。(Y 軸)
   *   - fromXPos: トリミング開始座標。(X 軸)
   *   - fromYPos: トリミング開始座標。(Y 軸)
   *   - toWidth: キャンバスにコピーするトリミングイメージの幅。
   *   - toHeight:キャンバスにコピーするトリミングイメージの高さ。
   *   - fromWidth: トリミングするイメージの幅。
   *   - fromHeight: トリミングするイメージの高さ。
   *   - fillColor: 余白部分を塗りつぶす色。({@link Delta_ImageColor} オブジェクトのインスタンス)
   * @see Delta_Image::trim()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function trim(&$image, $parameters);

  /**
   * JPEG の品質を設定します。
   *
   * @param int $jpegQuality {@link Delta_Image::setJPEGQuality()} メソッドを参照。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setJPEGQuality($jpegQuality)
  {
    $this->_jpegQuality = $jpegQuality;
  }

  /**
   * PNG の品質を設定します。
   *
   * @param int $pngQuality {@link Delta_Image::setPNGQuality()} メソッドを参照。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setPNGQuality($pngQuality)
  {
    $this->_pngQuality = $pngQuality;
  }

  /**
   * イメージ形式を変換します。
   *
   * @param mixed &$image ライブラリが提供するイメージオブジェクト、またはリソース型。
   * @param int $fromType 元のイメージ形式。(Delta_Image::IMAGE_TYPE_* 定数)
   * @param int $toType 変換後のイメージ形式。(Delta_Image::IMAGE_TYPE_* 定数)
   * @param int $width 対象イメージの横幅サイズ。
   * @param int $height 対象イメージの縦幅サイズ。
   * @return bool 変換に成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function convertFormat(&$image, $fromType, $toType, $width, $height);

  /**
   * イメージオブジェクトを出力、または保存します。
   *
   * @param mixed &$image ライブラリが提供するイメージオブジェクト、またはリソース型。
   * @param int $type 対象となるイメージ形式。(IMAGE_TYPE_* 定数)
   * @param string $path イメージの出力パス。NULL 指定時は画面に描画される。
   * @return bool 出力または保存に成功したかどうかを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function createDestinationImage(&$image, $type, $path);

  /**
   * イメージデータを保持するオブジェクト (またはリソース) を取得します。
   *
   * @return mixed イメージデータを保持するオブジェクト (またはリソース) を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getRawImage()
  {
    return $this->_image->getDestinationImage();
  }

  /**
   * 使用中のメモリ領域を開放します。
   *
   * @param mixed &$image ライブラリが提供するイメージオブジェクト、またはリソース型。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function clear(&$image);
}
