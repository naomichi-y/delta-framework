<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package view.helper
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * ヘルパのインスタンスを管理します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package view.helper
 */
class Delta_HelperManager extends Delta_Object
{
  /**
   * @var Delta_View
   */
  private $_view;

  /**
   * @var Delta_ParameterHolder
   */
  private $_config;

  /**
   * @var array
   */
  private $_instances = array();

  /**
   * @var string
   */
  private $_baseRouteName;

  /**
   * コンストラクタ。
   *
   * @param Delta_View $view ヘルパを適用するビューオブジェクト。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct(Delta_View $view)
  {
    $this->_view = $view;
    $this->_config = Delta_Config::getHelpers();
  }

  /**
   * ヘルパが生成するパスの基底ルートを設定します。
   *
   * @param string $baseRouteName ルート名。
   * @throws Delta_ConfigurationException 指定されたルートが見つからない場合に発生。
   * @since 2.0
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setBaseRouteName($baseRouteName)
  {
    $config = Delta_Config::getRoutes();

    if ($config->hasName($baseRouteName)) {
      $this->_baseRouteName = $baseRouteName;

    } else {
      $message = sprintf('Can\'t find route. [%s]', $baseRouteName);
      throw new Delta_ConfigurationException($message);
    }
  }

  /**
   * @since 2.0
   */
  public function getBaseRouteName()
  {
    return $this->_baseRouteName;
  }

  /**
   * ヘルパマネージャにヘルパを追加します。
   *
   * <code>
   * $manager->addHelper('custom', array('class' => 'CustomHelper'));
   * </code>
   *
   * @param string $helperId ヘルパ ID。
   * @param array $parameters ヘルパのパラメータ。
   *   指定可能なパラメータは {@link Delta_Helper} クラスを参照して下さい。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addHelper($helperId, array $parameters = array())
  {
    $this->_config->set($helperId, $parameters);
  }

  /**
   * ヘルパマネージャにヘルパが登録されているかチェックします。
   *
   * @param string $helperId チェック対象のヘルパ ID。
   * @return bool ヘルパが登録されている場合は TRUE、登録されていない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasHelper($helperId)
  {
    return $this->_config->hasName($helperId);
  }

  /**
   * ヘルパマネージャに登録されているヘルパを削除します。
   *
   * @param string $helperId 削除対象のヘルパ ID。
   * @return bool 削除が成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function removeHelper($helperId)
  {
    return $this->_config->remove($helperId);
  }

  /**
   * ヘルパのインスタンスを取得します。
   *
   * @param string $helperId 取得対象のヘルパ ID。
   * @return Delta_Helper ヘルパのインスタンスを返します。
   * @throws Delta_ConfigurationException 指定されたヘルパが味登録の場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getHelper($helperId)
  {
    $config = $this->_config->get($helperId);

    if ($config) {
      $className = $config->getString('class');

      if (empty($this->_instances[$className])) {
        Delta_ClassLoader::loadByPath($config->getString('path'), $className);

        $this->_instances[$className] = new $className($this->_view, $config->toArray());
        $this->_instances[$className]->initialize();
      }

      return $this->_instances[$className];

    } else {
      $message = sprintf('Helper is not registered in Delta_HelperManager. [%s]', $helperId);
      throw new Delta_ConfigurationException($message);
    }
  }

  /**
   * ヘルパマネージャに登録されている全てのヘルパ情報を取得します。
   *
   * @return Delta_ParameterHolder ヘルパマネージャに登録されている全てのヘルパ情報を取得します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getConfig()
  {
    return $this->_config;
  }

  /**
   * 登録されている全てのヘルパを破棄します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function clear()
  {
    $this->_config = array();
    $this->_instances = array();
  }
}
