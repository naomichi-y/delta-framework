<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package net.mail
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * メッセージパートにおける添付データを管理します。
 *
 * このクラスは実験的なステータスにあります。
 * これは、このクラスの動作、メソッド名、ここで書かれていること全てが delta の将来のバージョンで予告なく変更される可能性があることを意味します。
 * 注意を喚起するとともに自分のリスクでこのクラスを使用して下さい。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package net.mail
 */
class Delta_MailAttachment extends Delta_MailPart
{
  /**
   * メッセージ本文とは離された添付データ。
   * RFC 1806, RFC 2183
   */
  const DISPOSITION_TYPE_INLINE = 1;

  /**
   * メッセージ本文の一部として表す。
   * RFC 1806, RFC 2183
   */
  const DISPOSITION_TYPE_ATTACHMENT = 2;

  /**
   * Content-Disposition タイプ。(DISPOSITION_TYPE_*)
   * @var int
   */
  private $_dispositionType = self::DISPOSITION_TYPE_ATTACHMENT;

  /**
   * Content-Disposition に設定されているパラメータリスト。
   * @var array
   */
  private $_dispositionTypeParameters = array();

  /**
   * MIME タイプ。
   * @var string
   */
  private $_mimeType;

  /**
   * 添付ファイル名。
   * @var string
   */
  private $_fileName;

  /**
   * 添付ファイルサイズ。
   * @var int
   */
  private $_fileSize;

  /**
   * 添付データ。
   * @var string
   */
  private $_data;

  /**
   * コンストラクタ。
   *
   * @param string $lineFeed パートの改行コード。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($lineFeed = "\r\n")
  {
    parent::__construct($lineFeed);
  }

  /**
   * 添付データの配置タイプ (Content-Disposition 内の配置タイプ) を設定します。
   *
   * @param int $dispositionType 添付データの配置タイプ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   * @todo $parameters、$buildHeader 追加
   */
  public function setDispositionType($dispositionType, array $parameters = NULL, $buildHeader = FALSE)
  {
    $buffer = $dispositionType;
    $lineFeed = $this->_lineFeed;

    if ($parameters) {
      foreach ($parameters as $name => $value) {
        $name = strtolower($name);
        $this->_dispositionTypeParameters[$name] = $value;

        if ($buildHeader) {
          $buffer .= '; ' . $name . '="' . $value . '"';
        }
      }
    }

    if ($buildHeader) {
      $this->addHeader('Content-Disposition', $buffer);
    }

    $this->_dispositionType = $dispositionType;
  }

  /**
   * 添付データの配置タイプ (Content-Disposition 内の配置タイプ) を取得します。
   *
   * @return int 添付データの配置タイプを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getDispositionType()
  {
    return $this->_dispositionType;
  }

  /**
   * @todo
   */
  public function getDispositionTypeParameters()
  {
    return $this->_dispositionTypeParameters;
  }

  /**
   * 添付データの MIME タイプを設定します。
   *
   * @param string $mimeType MIME タイプ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setMimeType($mimeType)
  {
    $this->_mimeType = $mimeType;
  }

  /**
   * 添付データの MIME タイプを取得します。
   *
   * @return string MIME タイプを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getMimeType()
  {
    return $this->_mimeType;
  }

  /**
   * 添付ファイル名を設定します。
   *
   * @param string $fileName 添付ファイル名。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setFileName($fileName)
  {
    $this->_fileName = $fileName;
  }

  /**
   * 添付ファイル名を取得します。
   *
   * @return string 添付ファイル名を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getFileName()
  {
    return $this->_fileName;
  }

  /**
   * ファイルサイズを設定します。
   *
   * @param int $size ファイルサイズ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setFileSize($fileSize)
  {
    $this->_fileSize = $fileSize;
  }

  /**
   * ファイルサイズを取得します。
   *
   * @return int ファイルサイズを取得します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getFileSize()
  {
    return $this->_fileSize;
  }

  /**
   * 添付データを設定します。
   *
   * @param string $attachment 添付データ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setAttachment($attachment)
  {
    $this->_data = $attachment;
  }

  /**
   * 添付データを取得します。
   *
   * @return string 添付データを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getAttachment()
  {
    return $this->_data;
  }

  /**
   * @todo
   */
  public function buildPart($width)
  {
    $contents = parent::buildPart($width);
    $attachment = $this->getAttachment();

    if ($attachment !== NULL) {
      $contents .= $this->_lineFeed . wordwrap($attachment, $width, $this->_lineFeed, TRUE) . $this->_lineFeed;
    }

    return $contents;
  }
}
