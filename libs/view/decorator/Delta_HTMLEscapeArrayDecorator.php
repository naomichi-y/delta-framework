<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package view.decorator
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * 配列に含まれる全てのデータ (スカラー、配列、オブジェクト) を HTML エスケープします。
 *
 * <code>
 * $array = array('&');
 * $decorator = new Delta_HTMLEscapeArrayDecorator($array);
 *
 * // &amp;
 * echo $decorator[0];
 *
 * $array = array('>' => '&');
 * $decorator = new Delta_HTMLEscapeArrayDecorator($array);
 *
 * foreach ($decorator as $key => $value) {
 *   // &gt;:&amp\n;
 *   echo $key . ':' . $value . "\n";
 * }
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package view.decorator
 */

class Delta_HTMLEscapeArrayDecorator extends Delta_HTMLEscapeDecorator implements ArrayAccess, Iterator, Countable
{
  /**
   * @var array
   */
  private $_current;

  /**
   * コンストラクタ。
   *
   * @param array 対象とする配列データ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct(array $array)
  {
    $this->_data = $array;
  }

  /**
   * @see ArrayAccess::offsetSet()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function offsetSet($offset, $value)
  {
    if ($offset === NULL) {
      $this->_data[] = $value;
    } else {
      $this->_data[$offset] = $value;
    }
  }

  /**
   * @see ArrayAccess::offsetExists()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function offsetExists($offset)
  {
    return isset($this->_data[$offset]);
  }

  /**
   * @see ArrayAccess::offsetUnset()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function offsetUnset($offset)
  {
    unset($this->_data[$offset]);
  }

  /**
   * @see ArrayAccess::offsetGet()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function offsetGet($offset)
  {
    return Delta_StringUtils::escape($this->_data[$offset]);
  }

  /**
   * @see Iterator::rewind()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function rewind() {
    reset($this->_data);
  }

  /**
   * @see Iterator::current()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function current() {
    return Delta_StringUtils::escape($this->_current);
  }

  /**
   * @see Iterator::key()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function key() {
    return Delta_StringUtils::escape(key($this->_data));
  }

  /**
   * @see Iterator::next()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function next() {
    next($this->_data);
  }

  /**
   * @see Iterator::valid()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  function valid() {
    $current = current($this->_data);

    if ($current !== FALSE) {
      $this->_current = $current;

      return TRUE;
    }

    return FALSE;
  }

  /**
   * @see Countable::count()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function count()
  {
    return sizeof($this->_data);
  }
}
