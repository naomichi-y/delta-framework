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
 * 16 進数カラーと RGB カラーを相互変換するユーティリティです。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.image
 */
class Delta_ImageColor extends Delta_Object
{
  /**
   * UNIX X11 カラー。
   * (grey 系に関しては grey、gray の両方に対応)
   * @var array
   */
  private static $_x11Colors = array(
    'black' => '000000', 'navy' => '000080', 'darkblue' => '00008B', 'mediumblue' => '0000CD',
    'blue' => '0000FF', 'darkgreen' => '006400', 'green' => '008000', 'teal' => '008080',
    'darkcyan' => '008B8B', 'deepskyblue' => '00BFFF', 'darkturquoise' => '00CED1', 'mediumspringgreen' => '00FA9A',
    'lime' => '00FF00', 'springgreen' => '00FF7F', 'aqua' => '00FFFF', 'cyan' => '00FFFF',
    'midnightblue' => '191970', 'dodgerblue' => '1E90FF', 'lightseagreen' => '20B2AA', 'forestgreen' => '228B22',
    'seagreen' => '2E8B57', 'darkslategray' => '2F4F4F', 'limegreen' => '32CD32', 'mediumseagreen' => '3CB371',
    'turquoise' => '40E0D0', 'royalblue' => '4169E1', 'steelblue' => '4682B4', 'darkslateblue' => '483D8B',
    'mediumturquoise' => '48D1CC', 'indigo' => '4B0082', 'darkolivegreen' => '556B2F', 'cadetblue' => '5F9EA0',
    'cornflowerblue' => '6495ED', 'slateblue' => '655ACD', 'mediumaquamarine' => '66CDAA', 'dimgray' => '696969',
    'olivedrab' => '6B8E23', 'slategray' => '708090', 'lightslategray' => '778899', 'mediumslateblue' => '7B68EE',
    'lawngreen' => '7CFC00', 'chartreuse' => '7FFF00', 'aquamarine' => '7FFFD4', 'maroon' => '800000',
    'purple' => '800080', 'olive' => '808000', 'gray' => '808080', 'skyblue' => '87CEEB',
    'lightskyblue' => '87CEFA', 'blueviolet' => '8A2BE2', 'darkred' => '80000', 'darkmagenta' => '8B008B',
    'saddlebrown' => '8B4513', 'darkseagreen' => '8FBC8F', 'lightgreen' => '90EE90', 'mediumpurple' => '9370DB',
    'darkviolet' => '9400D3', 'palegreen' => '98FB98', 'darkorchid' => '9932CC', 'yellowgreen' => '9ACD32',
    'sienna' => 'A0522D', 'brown' => 'A52A2A', 'darkgray' => 'A9A9A9', 'lightblue' => 'ADD8E6',
    'greenyellow' => 'ADFF2F', 'paleturquoise' => 'AFEEEE', 'lightsteelblue' => 'B0C4DE', 'powderblue' => 'B0E0E6',
    'firebrick' => 'B22222', 'darkgoldenrod' => 'B886CD', 'mediumorchid' => 'BA55D3', 'rosybrown' => 'BC8F8F',
    'darkkhaki' => 'BDB76B', 'silver' => 'C0C0C0', 'mediumvioletred' => 'C71585', 'indianred' => 'CD5C5C',
    'peru' => 'CD853F', 'chocolate' => 'D2691E', 'tan' => 'D2B48C', 'lightgray' => 'D3D3D3',
    'thistle' => 'D8BFD8', 'orchid' => 'DA70D6', 'goldenrod' => 'DAA520', 'palevioletred' => 'DB7093',
    'crimson' => 'DC143C', 'gainsboro' => 'DCDCDC', 'plum' => 'DDA0DD', 'burlywood' => 'DEB887',
    'lightcyan' => 'E0FFFF', 'lavender' => 'E6E6FA', 'darksalmon' => 'E9967A', 'violet' => 'EE82EE',
    'palegoldenrod' => 'EEE8AA', 'lightcoral' => 'F08080', 'khaki' => 'F0E68C', 'aliceblue' => 'F0F8FF',
    'honeydew' => 'F0FFF0', 'azure' => 'F0FFFF', 'sandybrown' => 'F4A460', 'wheat' => 'F5DEB3',
    'beige' => 'F5F5DC', 'whitesmoke' => 'F5F5F5', 'mintcream' => 'F5FFFA', 'ghostwhite' => 'F8F8FF',
    'salmon' => 'FA8072', 'antiquewhite' => 'FAEBD7', 'linen' => 'FAF0E6', 'lightgoldenrodyellow' => 'FAFAD2',
    'oldlace' => 'FDF5E6', 'red' => 'FF0000', 'fuchsia' => 'FF00FF', 'magenta' => 'FF00FF',
    'deeppink' => 'FF1493', 'orangered' => 'FF4500', 'tomato' => 'FF6347', 'hotpink' => 'FF69B4',
    'coral' => 'FF7F50', 'darkorange' => 'FF8C00', 'lightsalmon' => 'FFA07A', 'orange' => 'FFA500',
    'lightpink' => 'FFB6C1', 'pink' => 'FFC0CB', 'gold' => 'FFD700', 'peachpuff' => 'FFDAB9',
    'navajowhite' => 'FFDEAD', 'moccasin' => 'FFE4B5', 'bisque' => 'FFE4C4', 'mistyrose' => 'FFE4E1',
    'blanchedalmond' => 'FFEBCD', 'papayawhip' => 'FFEFD5', 'lavenderblush' => 'FFF0F5', 'seashell' => 'FFF5EE',
    'cornsilk' => 'FFF8DC', 'lemonchiffon' => 'FFFACD', 'floralwhite' => 'FFFAF0', 'snow' => 'FFFAFA',
    'yellow' => 'FFFF00', 'lightyellow' => 'FFFFE0', 'ivory' => 'FFFFF0', 'white' => 'FFFFFF',
    'darkgrey' => 'A9A9A9', 'darkslategrey' => '2F4F4F', 'dimgrey' => '696969', 'grey' => '808080',
    'lightgrey' => 'D3D3D3', 'lightslategrey' => '778899', 'slategrey' => '708090');

  /**
   * RGB カラーモデルにおける赤色。
   * @var int
   */
  private $_red;

  /**
   * RGB カラーモデルにおける緑色。
   * @var int
   */
  private $_green;

  /**
   * RGB カラーモデルにおける青色。
   * @var int
   */
  private $_blue;

  /**
   * コンストラクタ。
   *
   * @param int $red {@link createFromRGB()} メソッドを参照。
   * @param int $green {@link createFromRGB()} メソッドを参照。
   * @param int $blue {@link createFromRGB()} メソッドを参照。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function __construct($red, $green, $blue)
  {
    $this->_red = $red;
    $this->_green = $green;
    $this->_blue = $blue;
  }

  /**
   * RGB カラーを元に Delta_ImageColor オブジェクトのインスタンスを生成します。
   *
   * @param int $red RGB カラーモデルにおける赤色。(0～255)
   * @param int $green RGB カラーモデルにおける緑色。(0～255)
   * @param int $blue RGB カラーモデルにおける青色。(0～255)
   * @return Delta_ImageColor Delta_ImageColor のインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function createFromRGB($red, $green, $blue)
  {
    return new Delta_ImageColor($red, $green, $blue);
  }

  /**
   * HTML カラーを元に Delta_ImageColor オブジェクトのインスタンスを生成します。
   *
   * @param string $color HTML カラー。指定可能な書式は次の通り。
   *   - #ff0000
   *   - #f00
   *   - red (X11 カラー)
   *   指定された値が識別できない場合は黒色 (R:0、G:0、B:0) として扱われます。
   * @return Delta_ImageColor Delta_ImageColor のインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function createFromHTMLColor($color)
  {
    $color = strtolower($color);

    if (substr($color, 0, 1) === '#') {
      $color = str_replace('#', '', $color);

    } else if (isset(self::$_x11Colors[$color])) {
      $color = self::$_x11Colors[$color];

    } else {
      $color = '000000';
    }

    $length = strlen($color);
    $rgb = array();

    if ($length == 6) {
      for ($i = 0; $i < 3; $i++) {
        $rgb[$i] = hexdec(substr($color, $i * 2, 2));
      }

    } else if ($length == 3) {
      for ($i = 0; $i < 3; $i++) {
        $rgb[$i] = hexdec($color[$i] . $color[$i]);
      }
    }

    return new Delta_ImageColor($rgb[0], $rgb[1], $rgb[2]);
  }

  /**
   * RGB カラーを HTML カラー (16 進数) 表記に変換します。
   *
   * @param int $red {@link createFromRGB()} メソッドを参照。
   * @param int $green {@link createFromRGB()} メソッドを参照。
   * @param int $blue {@link createFromRGB()} メソッドを参照。
   * @return string 変換後の HTML カラーを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function RGBtoHTMLColor($red, $green, $blue)
  {
    return sprintf('#%02x%02x%02x', $red, $green, $blue);
  }

  /**
   * HTML カラーを取得します。
   *
   * @return string HTML カラーを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getHTMLColor()
  {
    return self::RGBtoHTMLColor($this->_red, $this->_green, $this->_blue);
  }

  /**
   * 0～255 の範囲にある赤色の成分を取得します。
   *
   * @return int 赤色の成分を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getRed()
  {
    return $this->_red;
  }

  /**
   * 0～255 の範囲にある緑色の成分を取得します。
   *
   * @return int 緑色の成分を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getGreen()
  {
    return $this->_green;
  }

  /**
   * 0～255 の範囲にある青色の成分を取得します。
   *
   * @return int 青色の成分を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getBlue()
  {
    return $this->_blue;
  }
}
