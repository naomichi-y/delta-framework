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
 * イメージのリサイズやフォーマット変換といった機能を提供するユーティリティクラスです。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.image
 */
class Delta_Image extends Delta_Object
{
  /**
   * GIF 定数。
   */
  const IMAGE_TYPE_GIF = IMAGETYPE_GIF;

  /**
   * JPEG 定数。
   */
  const IMAGE_TYPE_JPEG = IMAGETYPE_JPEG;

  /**
   * PNG 定数。
   */
  const IMAGE_TYPE_PNG = IMAGETYPE_PNG;

  /**
   * {@link Delta_ImageDelegate} オブジェクト。
   * @var Delta_ImageDelegate
   */
  private $_delegate;

  /**
   * オリジナルイメージオブジェクト。(またはリソース)
   * @var mixed
   */
  private $_originalImage;

  /**
   * オリジナルイメージ属性。
   * @var array
   */
  private $_originalImageAttributes = array();

  /**
   * 出力イメージオブジェクト。(またはリソース)
   */
  private $_destinationImage;

  /**
   * 出力イメージ属性。
   * @var array
   */
  private $_destinationImageAttributes = array();

  /**
   * JPEG 最大データサイズ
   * @var int
   */
  private $_jpegMaximumDataSize;

  /**
   * 利用するイメージライブラリを設定します。
   *
   * @param Delta_ImageDelegate $delegate Delta_ImageDelegate を実装したイメージクラスのインスタンス。
   * @return Delta_Image Delta_Image オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setDelegate(Delta_ImageDelegate $delegate)
  {
    $this->_delegate = $delegate;
    $this->_delegate->setImage($this);

    return $this;
  }

  /**
   * デリゲートオブジェクトを取得します。
   *
   * @return Delta_ImageDelegate Delta_ImageDelegate を実装したイメージオブジェクトのインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getDelegate()
  {
    return $this->_delegate;
  }

  /**
   * イメージファイルを読み込みます。
   *
   * @param string $path 参照するイメージのパス。APP_ROOT_DIR からの相対パス、あるいは絶対パスが有効。
   * @param bool $adjustOrientation {@link Delta_ImageDelegate::createOriginalImageFromFile()} メソッドを参照。
   * @return Delta_Image Delta_Image のインスタンスを返します。
   * @throws Delta_IOException ファイルの読み込みに失敗した際に発生。
   * @throws Delta_ParseException ファイルを識別できない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function load($path, $adjustOrientation = TRUE)
  {
    $path = Delta_FileUtils::buildAbsolutePath($path);

    if (!is_file($path)) {
      $message = sprintf('File is not exist. [%s]', $path);
      throw new Delta_IOException($message);

    } else if (!is_file($path)) {
      $message = sprintf('File doesn\'t read. [%s]', $path);
      throw new Delta_IOException($message);
    }

    $array = array();
    $array['path'] = $path;
    $array['type'] = self::parseImageTypeFromFile($path);
    $array['mime'] = image_type_to_mime_type($array['type']);
    $array['mimeSubType'] = substr($array['mime'], strpos($array['mime'], '/') + 1);

    $image = $this->_delegate->createOriginalImageFromFile($array['path'], $array['type'], $adjustOrientation);

    if ($image === FALSE) {
      $message = sprintf('Failed to parse images. [%s]', $path);
      throw new Delta_ParseException($message);
    }

    $size = $this->_delegate->getOriginalImageBounds($image);

    if ($size === FALSE) {
      $message = sprintf('Failed to get the image size. [%s]', $path);
      throw new Delta_ParseException($message);
    }

    $array['width'] = $size[0];
    $array['height'] = $size[1];

    $this->_originalImageAttributes = $array;
    $this->_originalImage = $image;

    $this->_destinationImageAttributes = $array;
    $this->_destinationImage = $image;
  }

  /**
   * イメージエンジンによってオブジェクト (またはリソース) に変換されたオリジナルイメージを取得します。
   *
   * @return mixed 入力イメージのオブジェクト (またはリソース) を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function &getOriginalImage()
  {
    return $this->_originalImage;
  }

  /**
   * オリジナルイメージの属性を取得します。
   *
   * 取得可能な属性:
   *   - path: オリジナルイメージのパス。
   *   - type: イメージの形式。(IMAGE_TYPE_* 定数)
   *   - mime: イメージの MIME タイプ。
   *   - mimeSubType: イメージの MIME サブタイプ。
   *   - width: イメージの横幅。
   *   - height: イメージの縦幅。
   *
   * @param string $name 取得対象の属性名。
   * @return string イメージの属性値を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getOriginalImageAttribute($name)
  {
    return $this->getAttribute($this->_originalImageAttributes, $name);
  }

  /**
   * イメージエンジンによってオブジェクト (またはリソース) に変換された出力イメージを取得します。
   *
   * @return mixed 出力イメージのオブジェクト (またはリソース) を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function &getDestinationImage()
  {
    return $this->_destinationImage;
  }

  /**
   * 出力イメージの属性を取得します。
   *
   * @param string $name 取得対象の属性名。
   *   取得可能な属性は {@link getOriginalImageAttribute()} メソッドを参照。
   * @return string イメージの属性値を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getDestinationImageAttribute($name)
  {
    return $this->getAttribute($this->_destinationImageAttributes, $name);
  }

  /**
   * 出力イメージの属性を設定します。
   *
   * @param string $name 属性名。
   * @param mixed $value 属性値。
   * @return Delta_Image Delta_Image オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setDestinationImageAttribute($name, $value)
  {
    $this->_destinationImageAttributes[$name] = $value;

    return $this;
  }

  /**
   * イメージが保持する属性を取得します。
   *
   * @param array $array 属性が格納された連想配列。
   * @param string $name 属性名。
   * @return string 属性値を返します。
   * @throws InvalidArgumentException イメージが読み込まれていない、もしくは指定された属性名が不正な場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function getAttribute(array $array, $name)
  {
    if (isset($array[$name])) {
      return $array[$name];
    }

    if (sizeof($array)) {
      $message = sprintf('Specified attribute is wrong. [%s]', $name);

    } else {
      $message = sprintf('Image is not loaded.');
    }

    throw new InvalidArgumentException($message);
  }

  /**
   * イメージ形式を解析します。
   * このメソッドはファイルヘッダから形式を判別するため、{@link getimagesize()} 関数よりも正確な結果を返します。
   *
   * @param string $path 参照するイメージのパス。
   * @return int IMAGE_TYPE_* 定数を返します。
   * @throws Delta_ParseException 解析不能な形式が指定された場合に発生。(解析可能な形式は IMAGE_TYPE_* 定数を参照)
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function parseImageTypeFromFile($path)
  {
    $fp = fopen($path, 'rb');
    $data = fread($fp, 8);
    fclose($fp);

    return self::parseImageTypeFromBinary($data);
  }

  /**
   * @param string $data
   * @return int
   * @throws Delta_UnsupportedException
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function parseImageTypeFromBinary($data)
  {
    $type = NULL;

    if (strncmp("\x89PNG\x0d\x0a\x1a\x0a", $data, 8) === 0) {
      $type = self::IMAGE_TYPE_PNG;

    } else if (strncmp('GIF87a', $data, 6) == 0 || strncmp('GIF89a', $data, 6) == 0) {
      $type = self::IMAGE_TYPE_GIF;

    } else if (strncmp("\xff\xd8", $data, 2) == 0) {
      $type = self::IMAGE_TYPE_JPEG;

    } else if (strlen($data) == 0) {
      throw new Delta_DataNotFoundException('Image data not found.');

    } else {
      $message = sprintf('Image format is not supported.');
      throw new Delta_UnsupportedException($message);
    }

    return $type;
  }

  /**
   * イメージサイズを取得します。
   *
   * @return string イメージのサイズを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getSize()
  {
    return strlen($this->getData(FALSE));
  }

  /**
   * JPEG の書き出しサイズを指定サイズ以下に設定します。
   * データの圧縮は {@link save()}、または {@link render()} メソッドで実行されますが、指定サイズ以下に制限できなかった場合は {@link Delta_ImageException} をスローします。
   *
   * @param int $jpegMaximumDataSize 制限するデータサイズ。単位はバイト。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setJPEGMaximumDataSize($jpegMaximumDataSize)
  {
    $this->_jpegMaximumDataSize = $jpegMaximumDataSize;
  }

  /**
   * 横幅、縦幅を指定してイメージのリサイズを行います。
   *
   * @param int $width リサイズ後の横幅。
   * @param int $height リサイズ後の縦幅。
   * @return Delta_Image Delta_Image オブジェクトを返します。
   * @throws RuntimeException リサイズに失敗した際に発生。
   * @throws InvalidArgumentException 高さの指定が不正な場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function resizeByPixel($width, $height)
  {
    $image = &$this->getDestinationImage();
    $fromWidth = $this->getDestinationImageAttribute('width');
    $fromHeight = $this->getDestinationImageAttribute('height');

    if ($width <= 0) {
      $message = '$width value is illegal. [resizeByPixcel($width, $height)]';
      throw new InvalidArgumentException($message);

    } else if ($height <= 0) {
      $message = '$height value is illegal. [resizeByPixcel($width, $height)]';
      throw new InvalidArgumentException($message);
    }

    if (!$this->_delegate->resize($image, $fromWidth, $fromHeight, $width, $height)) {
      $path = $this->getOriginalImageAttribute('path');

      $message = sprintf('Resampling of the image failed. [%s]', $path);
      throw new RuntimeException($message);
    }

    $this->_destinationImageAttributes['width'] = $width;
    $this->_destinationImageAttributes['height'] = $height;

    return $this;
  }

  /**
   * 指定した最大サイズを基準にイメージのリサイズを行います。
   * 例えば横 200、縦 100 のイメージを最大値 50 でリサイズした場合、横 50、縦 25 ピクセルのイメージが生成されます。
   *
   * @param int $maximum リサイズ後の最大サイズ。
   * @param bool $adjust 元のイメージが maximum より小さい場合、強制的に maximum のサイズに合わせるかどうかを設定します。
   * @return Delta_Image Delta_Image オブジェクトを返します。
   * @throws InvalidArgumentException 高さの指定が不正な場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function resizeByMaximum($maximum, $adjust = FALSE)
  {
    $array = array();

    $width = $this->getDestinationImageAttribute('width');
    $height = $this->getDestinationImageAttribute('height');

    if ($adjust || !$adjust && ($maximum < $width || $maximum < $height)) {
      if ($height < $width) {
        $array[0] = $maximum;
        $array[1] = (int) (($maximum / $width) * $height);

      } else {
        $array[0] = (int) (($maximum / $height) * $width);
        $array[1] = $maximum;
      }

      $this->resizeByPixel($array[0], $array[1]);
    }

    return $this;
  }

  /**
   * 指定した最大横サイズ値を元にイメージのリサイズを行います。
   * 例えば横 200、縦 100 のイメージを最大値 50 でリサイズした場合、横 50、縦 25 ピクセルのイメージが生成されます。
   *
   * @param int $maximum リサイズ後の最大サイズ。
   * @return Delta_Image Delta_Image オブジェクトを返します。
   * @throws InvalidArgumentException 高さの指定が不正な場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function resizeByMaximumWidth($width)
  {
    $rate = $width / $this->getDestinationImageAttribute('width');

    if ($rate == 0) {
      $message = '$width value is illegal. [resizeByMaximumWidth($width)]';
      throw new InvalidArgumentException($message);
    }

    $array = array();
    $array[0] = $width;

    // int でキャストすると情報落ちが発生するので ceil() で切り上げ整数化を行う
    $array[1] = ceil($this->getDestinationImageAttribute('height') * $rate);

    $this->resizeByPixel($array[0], $array[1]);

    return $this;
  }

  /**
   * 指定した最大縦サイズ値を元にイメージのリサイズを行います。例えば横 200、縦 100 のイメージを最大値 50 でリサイズした場合、横 100、縦 50 ピクセルのイメージが生成されます。
   *
   * @param int $maximum リサイズ後の最大サイズ。
   * @return Delta_Image Delta_Image オブジェクトを返します。
   * @throws InvalidArgumentException 高さの指定が不正な場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function resizeByMaximumHeight($height)
  {
    $rate = $height / $this->getDestinationImageAttribute('height');

    if ($rate == 0) {
      $message = '$height value is illegal. [resizeByMaximumHeight($height)]';
      throw new InvalidArgumentException($message);
    }

    $array = array();
    $array[0] = (int) ceil(($this->getDestinationImageAttribute('width') * $rate));
    $array[1] = $height;

    $this->resizeByPixel($array[0], $array[1]);

    return $this;
  }

  /**
   * 指定したパーセント値を元にイメージのリサイズを行います。
   * 例えば横 200、縦 100 のイメージを10%:10% でリサイズした場合、横 20、縦 10 ピクセルのイメージが生成されます。
   *
   * @param int $width 横幅のリサイズ値。単位はパーセント。
   * @param int $height 縦幅のリサイズ値。単位はパーセント。
   * @return Delta_Image Delta_Image オブジェクトを返します。
   * @throws InvalidArgumentException 高さの指定が不正な場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function resizeByPercent($width, $height)
  {
    $array = array();
    $array[0] = (int) ($this->getDestinationImageAttribute('width') * $width / 100);
    $array[1] = (int) ($this->getDestinationImageAttribute('height') * $height / 100);

    $this->resizeByPixel($array[0], $array[1]);

    return $this;
  }

  /**
   * イメージ形式を変換します。
   *
   * @param int $toType 変換後のイメージ形式。IMAGE_TYPE_* 定数を指定。
   * @return Delta_Image Delta_Image オブジェクトを返します。
   * @throws Delta_ImageException イメージの変換に失敗した際に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function convertFormat($toType)
  {
    $image = &$this->getDestinationImage();
    $fromType = $this->getDestinationImageAttribute('type');

    $this->_destinationImageAttributes['type'] = $toType;
    $this->_destinationImageAttributes['mime'] = image_type_to_mime_type($toType);

    $result = $this->_delegate->convertFormat($image,
      $fromType,
      $toType,
      $this->getDestinationImageAttribute('width'),
      $this->getDestinationImageAttribute('height'));

    if (!$result) {
      $from = image_type_to_extension($fromType, FALSE);
      $to = image_type_to_extension($toType, FALSE);

      $message = sprintf('Image conversion failed. [%s to %s]', $from, $to);
      throw new Delta_ImageException($message);
    }

    return $this;
  }

  /**
   * イメージをトリミングします。
   *
   * @param int $newWidth トリミングする横のサイズ。
   *   元のイメージより大きい値が指定された場合、隙間の部分は fillColor に指定した色で塗りつぶされます。(height も同様)
   * @param int $newHeight トリミングする縦のサイズ。
   * @param int $horizontal 水平方向のトリミング開始位置。パーセント単位で指定可能。
   * @param int $vertical 垂直方向のトリミング開始位置。パーセント単位で指定可能。
   * @param Delta_ImageColor $fillColor 余白を塗りつぶす色。
   * @return Delta_Image Delta_Image オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function trim($newWidth, $newHeight, $horizontal = 50, $vertical = 50, $fillColor = NULL)
  {
    $width = $this->getDestinationImageAttribute('width');
    $height = $this->getDestinationImageAttribute('height');

    if ($fillColor === NULL) {
      $fillColor = Delta_ImageColor::createFromRGB(0, 0, 0);
    }

    if ($width >= $newWidth) {
      $toXPos = 0;
      $fromXPos = ($width - $newWidth) * ($horizontal / 100);
    } else {
      $toXPos = ($newWidth - $width) * ($horizontal / 100);
      $fromXPos = 0;
    }

    if ($height >= $newHeight) {
      $toYPos = 0;
      $fromYPos = ($height - $newHeight) * ($vertical / 100);
    } else {
      $toYPos = ($newHeight - $height) * ($vertical / 100);
      $fromYPos = 0;
    }

    $parameters = array();
    $parameters['newWidth'] = $newWidth;
    $parameters['newHeight'] = $newHeight;
    $parameters['toXPos'] = $toXPos;
    $parameters['toYPos'] = $toYPos;
    $parameters['fromXPos'] = $fromXPos;
    $parameters['fromYPos'] = $fromYPos;
    $parameters['toWidth'] = $width;
    $parameters['toHeight'] = $height;
    $parameters['fromWidth'] = $width;
    $parameters['fromHeight'] = $height;
    $parameters['fillColor'] = $fillColor;

    $this->_delegate->trim($this->getDestinationImage(), $parameters);

    $this->_destinationImageAttributes['width'] = $newWidth;
    $this->_destinationImageAttributes['height'] = $newHeight;

    return $this;
  }

  /**
   * 指定した最小サイズを基準にイメージのトリミングを行います。
   * 例えば横 400px、縦 800px のイメージを最小サイズ 200px でトリミングした場合、長さが短い方の辺を基準とし、イメージ座標 (200, 300) から 200*200 サイズのトリミングを行います。(horizontal、vertical が共に 50 の場合)
   *
   * @param int $minimum 最小サイズ値。対象イメージが最小サイズ以下の場合、隙間の部分は fillColor に指定した色で塗りつぶされます。
   *   サイズが未指定 (NULL) の場合は、縦横の比率の小さい方をベースにトリミングします。
   * @param int $horizontal {@link trim()} メソッドを参照。
   * @param int $vertical {@link trim()} メソッドを参照。
   * @param Delta_ImageColor $fillColor {@link trim()} メソッドを参照。
   * @return Delta_Image Delta_Image オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function trimByMinimum($minimum = NULL, $horizontal = 50, $vertical = 50, $fillColor = NULL)
  {
    $width = $this->getDestinationImageAttribute('width');
    $height = $this->getDestinationImageAttribute('height');

    if ($minimum === NULL) {
      if ($width < $height) {
        $minimum = $width;
      } else {
        $minimum = $height;
      }
    }

    $newWidth = $minimum;
    $newHeight = $minimum;

    $toXPos = 0;
    $toYPos = 0;

    if ($fillColor === NULL) {
      $fillColor = Delta_ImageColor::createFromRGB(0, 0, 0);
    }

    // パターン 1: 対象イメージが $minimum より小さい (縦横に余白を追加)
    if ($width < $minimum && $height < $minimum) {
      $fromXPos = $fromYPos = 0;
      $fromWidth = $toWidth = $width;
      $fromHeight = $toHeight = $height;

      $toXPos = ($minimum - $width) * ($horizontal / 100);
      $toYPos = ($minimum - $height) * ($vertical / 100);

    } else if ($width >= $height) {
      $fromXPos = ($width - $minimum) * ($horizontal / 100);

      // パターン2: 縦横共に $minimum サイズを満たしている
      if ($height >= $minimum) {
        $fromYPos = ($height - $minimum) * ($vertical / 100);
        $fromWidth = $fromHeight = $minimum;

        $toWidth = $toHeight = $minimum;

      // パターン3: 縦に余白を追加
      } else {
        $fromYPos = 0;
        $fromWidth = $toWidth = $minimum;
        $fromHeight = $toHeight = $height;

        $toYPos = ($minimum - $height) * ($vertical / 100);
      }

    } else {
      $fromYPos = ($height - $minimum) * ($vertical / 100);

      // パターン4: 縦横共に $minimum サイズを満たしている
      if ($width >= $minimum) {
        $fromXPos = ($width - $minimum) * ($horizontal / 100);
        $fromWidth = $fromHeight = $minimum;

        $toWidth = $toHeight = $minimum;

      // パターン5: 横に余白を追加
      } else {
        $fromXPos = 0;
        $fromWidth = $toWidth = $width;
        $fromHeight = $toHeight = $minimum;

        $toXPos = ($minimum - $width) * ($horizontal / 100);
      }
    }

    $parameters = array();
    $parameters['newWidth'] = $minimum;
    $parameters['newHeight'] = $minimum;
    $parameters['toXPos'] = $toXPos;
    $parameters['toYPos'] = $toYPos;
    $parameters['fromXPos'] = $fromXPos;
    $parameters['fromYPos'] = $fromYPos;
    $parameters['toWidth'] = $toWidth;
    $parameters['toHeight'] = $toHeight;
    $parameters['fromWidth'] = $fromWidth;
    $parameters['fromHeight'] = $fromHeight;
    $parameters['fillColor'] = $fillColor;

    $this->_delegate->trim($this->getDestinationImage(), $parameters);

    $this->_destinationImageAttributes['width'] = $newWidth;
    $this->_destinationImageAttributes['height'] = $newHeight;

    return $this;
  }

  /**
   * イメージ形式変換時における JPEG の品質を設定します。
   *
   * @param int $jpegQuality 0〜100 までの範囲で圧縮率を指定。デフォルトの圧縮率は 80。
   * @return Delta_Image Delta_Image オブジェクトを返します。
   * @throws RangeException 範囲外の圧縮率が指定された場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setJPEGQuality($jpegQuality)
  {
    if ($jpegQuality < 0 || $jpegQuality > 100) {
      $message = sprintf('The compression ratio should be in the range of 0-100.');
      throw new RangeException($message);
    }

    $this->_delegate->setJPEGQuality($jpegQuality);

    return $this;
  }

  /**
   * イメージ形式変換時における PNG の品質を設定します。
   *
   * @param int $pngQuality 0 (圧縮しない)〜9 までの範囲で圧縮率を指定。デフォルトの圧縮率は 2。
   * @return Delta_Image Delta_Image オブジェクトを返します。
   * @throws RangeException 範囲外の圧縮率が指定された場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setPNGQuality($pngQuality)
  {
    if ($pngQuality < 0 || $pngQuality > 9) {
      $message = sprintf('The compression ratio should be in the range of 0-9.');
      throw new RangeException($message);
    }

    $this->_delegate->setPNGQuality($pngQuality);

    return $this;
  }

  /**
   * イメージデータをフラッシュします。
   *
   * @param string $path イメージの出力パス。NULL 指定時は画面に描画される。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function flush($path = NULL)
  {
    $image = &$this->getDestinationImage();
    $type = $this->getDestinationImageAttribute('type');
    $jpegMaximumDataSize = $this->_jpegMaximumDataSize;

    if ($path === NULL) {
      $target = 'output';
    } else {
      $target = 'save';
    }

    if ($jpegMaximumDataSize === NULL) {
      if (!$this->_delegate->createDestinationImage($image, $type, $path)) {
        $message = sprintf('Failed to %s image. [%s]', $target, $path);
        throw new Delta_ImageException($message);
      }

    // setJPEGMaximumDataSize() が指定された場合はデータサイズを一定以下まで圧縮
    } else if ($type == Delta_Image::IMAGE_TYPE_JPEG) {
      $currentQuality = 75;
      $degradation = 5;
      $tempPath = NULL;

      do {
        if ($tempPath !== NULL) {
          unlink($tempPath);
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'checksize_');
        $this->_delegate->setJPEGQuality($currentQuality);

        if (!$this->_delegate->createDestinationImage($image, $type, $tempPath)) {
          $message = sprintf('Failed to %s image. [%s]', $target, $tempPath);
          throw new Delta_ImageException($message);
        }

        $currentSize = filesize($tempPath);
        $currentQuality = $currentQuality - $degradation;

      } while ($jpegMaximumDataSize < $currentSize && $currentQuality >= 0);

      // 指定サイズ以下に圧縮できない場合は例外をスロー
      if ($jpegMaximumDataSize < $currentSize) {
        $message = sprintf('Can\'t be compressed to less than %s byte. [setJPEGMaximumDataSize()]', $jpegMaximumDataSize);
        throw new Delta_ImageException($message);
      }

      if ($path === NULL) {
        echo file_get_contents($tempPath);
        unlink($tempPath);

      } else {
        rename($tempPath, $path);
      }
    }
  }

  /**
   * イメージを保存します。
   *
   * @param string $path イメージの保存先パス。APP_ROOT_DIR からの相対パス、あるいは絶対パスが有効。
   * @return Delta_Image Delta_Image オブジェクトを返します。
   * @throws Delta_ImageExeption イメージの保存に失敗した際に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function save($path)
  {
    if (!Delta_FileUtils::isAbsolutePath($path)) {
      $path = APP_ROOT_DIR . DIRECTORY_SEPARATOR . $path;
    }

    $directory = dirname($path);

    if (!file_exists($directory)) {
      Delta_FileUtils::createDirectoryRecursive($directory);
    }

    $this->_destinationImageAttributes['path'] = $path;
    $this->flush($path);

    return $this;
  }

  /**
   * イメージを出力します。
   *
   * @param bool $header イメージヘッダの出力を制御します。
   *   デフォルトではイメージ形式に応じた Content-Type ヘッダを出力します。
   * @return Delta_Image Delta_Image オブジェクトを返します。
   * @throws RuntimeException イメージの出力に失敗した際に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function render($header = TRUE)
  {
    $image = &$this->getDestinationImage();

    $this->flush();

    if ($header) {
      $type = $this->getDestinationImageAttribute('type');

      $response = Delta_FrontController::getInstance()->getResponse();
      $response->setContentType(image_type_to_mime_type($type));
    }

    return $this;
  }

  /**
   * イメージのバイナリデータを取得します。
   *
   * @param bool $encode TRUE を指定した場合、バイナリデータを Base64 でエンコードした結果を返します。
   * @return string イメージのバイナリデータを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getData($encode = TRUE)
  {
    ob_start();
    $this->render(FALSE);
    $data = ob_get_contents();
    ob_end_clean();

    if ($encode) {
      return base64_encode($data);
    }

    return $data;
  }

  /**
   * イメージを表示するための HTML タグを生成します。
   * <i>生成されたデータ長が長すぎる場合、ブラウザによってはイメージが出力されない可能性があります。</i>
   *
   * @param mixed $attributes タグに追加する属性。{@link Delta_HTMLHelper::link()} メソッドを参照。
   * @return string イメージを表示するための HTML タグを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function createHTMLTag(array $attributes = array())
  {
    $buffer = Delta_Helper::buildTagAttribute($attributes, FALSE);

    $string = sprintf('<img src="data:%s;base64,%s"%s />',
      image_type_to_mime_type($this->getDestinationImageAttribute('type')),
      $this->getData(),
      $buffer);

    return $string;
  }

  /**
   * 拡張子を含めたイメージの名前を取得します。
   *
   * @param string $newName 拡張子を含めないイメージの新しい名前。
   * @return string イメージの名前を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getName($newName = NULL)
  {
    $name = basename($this->getDestinationImageAttribute('path'));

    if ($newName === NULL) {
      return $name;
    }

    $newName .= image_type_to_extension($this->getDestinationImageAttribute('type'));

    return $newName;
  }

  /**
   * 使用中のメモリ領域を開放します。
   *
   * @return Delta_Image Delta_Image オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function clear()
  {
    $this->_delegate->clear($this->getOriginalImage());
    $this->_delegate->clear($this->getDestinationImage());

    return $this;
  }

  /**
   * 指定したイメージファイルを出力します。
   * このメソッドは Expires や ETag ヘッダを出力することでブラウザへのキャッシングを行い、2 回目以降の読み込みを高速化します。
   *
   * @param string $path 出力するイメージのパス。
   *   APP_ROOT_DIR からの相対パス、あるいは絶対パスが有効。
   * @return Delta_Image Delta_Image オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function read($path)
  {
    $path = Delta_FileUtils::buildAbsolutePath($path);

    $lastModify = filemtime($path);
    $etag = md5($lastModify . $path);
    $time = gmdate('r', $lastModify);
    $file = getimagesize($path);

    $controller = Delta_FrontController::getInstance();
    $response = $controller->getRequest();
    $request = $controller->getResponse();

    $response->setContentType($file['mime']);
    $response->setHeader('Content-Length', filesize($path));
    $response->setHeader('Last-Modified', $time);
    $response->setHeader('Cache-Control', 'must-revalidate');
    $response->setHeader('Expires', $time);
    $response->setHeader('Etag', $etag);

    $test1 = $test2 = FALSE;

    // 前回のリクエスト以降に更新が行われていないかチェック
    if ($request->getEnvironment('HTTP_IF_MODIFIED_SINCE') == $time) {
      $test1 = TRUE;
    }

    // 前回返した ETag とリクエストされた ETag が同じ場合は更新がないと見なす
    if ($request->getEnvironment('HTTP_IF_NONE_MATCH') == $etag) {
      $test2 = TRUE;
    }

    if ($test1 || $test2) {
      header('HTTP/1.1 304 Not Modified');
      exit();
    }

    readfile($path);

    return $this;
  }

  /**
   * イメージのコピーを生成します。
   *
   * @return Delta_Image イメージのコピーを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function copy()
  {
    return clone $this;
  }

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function __clone()
  {
    $image = $this->getDestinationImage();

    if (is_object($image)) {
      $this->_destinationImage = clone $image;
    }
  }
}
