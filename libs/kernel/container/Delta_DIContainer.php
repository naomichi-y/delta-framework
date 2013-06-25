<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package kernel.container
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * DI コンテナへのコンポーネントの登録、及び取得を管理します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package kernel.container
 */
class Delta_DIContainer
{
  /**
   * base_dicon.yml。
   * @var Delta_ParameterHolder
   */
  private $_config;

  /**
   * ロード済みのコンポーネントリスト。
   * @var string
   */
  private $_components = array();

  /**
   * コンストラクタ。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct()
  {
    $this->_config = Delta_Config::getDIContainer();
  }

  /**
   * base_dicon.yml に定義された引数を解析します。
   * '$' から始まる引数値はコンポーネントオブジェクトと見なされます。
   *
   * @param string $arguments DI に指定された引数文字列。
   * @return mixed 引数が DI コンポーネントを指す場合はオブジェクトに変換した値を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function parseMethodArgument($arguments)
  {
    if (substr($arguments, 0, 1) == '$') {
      $componentName = substr($arguments, 1);
      $arguments = $this->getComponent($componentName);
    }

    return $arguments;
  }

  /**
   * DI コンテナに attributes から構成されたコンポーネントを登録します。
   *
   * @param string $componentName コンポーネント名。
   * @param Delta_ParameterHolder $attributes コンポーネント属性。
   * @param bool $autoload コンポーネントの自動読み込みを試行する場合は TRUE を指定。
   * @return object コンポーネントオブジェクトを返します。
   * @throws RuntimeException コンポーネントの起動に失敗した際に発生。
   * @todo 1.14.0 で attributes を Delta_ParameterHolder に変更。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function componentRegister($componentName, array $attributes, $autoload = TRUE)
  {
    $component = NULL;

    if (isset($attributes['class'])) {
      $className = $attributes['class'];

      if (isset($attributes['path'])) {
        Delta_ClassLoader::loadByPath($attributes['path'], $className);

      } else if ($autoload) {
        Delta_ClassLoader::loadByName($className);
      }

      $reflection = new ReflectionClass($className);

      // コンポーネントの起動に必要なファイルを読み込む
      if (isset($attributes['includes'])) {
        foreach ($attributes['includes'] as $path) {
          // アクション側で同じファイルが読み込まれる可能性もあるので include_once() を使う
          require_once Delta_FileUtils::buildAbsolutePath($path);
        }
      }

      // コンストラクタインジェクション
      $constructor = Delta_ArrayUtils::find($attributes, 'constructor', array());
      $component = $this->executeConstructorInjection($reflection, $className, $constructor);

      // セッターインジェクション
      if (isset($attributes['setter'])) {
        $this->executeSetterInjection($reflection, $component, $attributes['setter']);
      }

      // メソッドインジェクション
      if (isset($attributes['method'])) {
        $this->executeMethodInjection($reflection, $component, $attributes['method']);
      }

      if (isset($attributes['instance'])) {
        if ($attributes['instance'] != 'prototype') {
          $this->_components[$componentName] = $component;
        }

      } else {
        $this->_components[$componentName] = $component;
      }

      return $component;

    } else {
      $message = sprintf('Class attribute of a component is undefined. [%s]', $componentName);
      throw new RuntimeException($message);
    }
  }

  /**
   * DI コンテナにコンポーネントを追加します。
   *
   * @param string $componentName コンポーネント名。
   * @param object $component コンポーネントオブジェクト。
   * @return bool コンポーネントの追加に成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addComponent($componentName, $component)
  {
    if (!isset($this->_components[$componentName])) {
      $this->_components[$componentName] = $component;

      return TRUE;
    }

    return FALSE;
  }

  /**
   * コンストラクタインジェクションを実行します。
   *
   * @param ReflectionClass $reflection コンポーネントのリフレクションクラス。
   * @param string $className コンポーネントクラス名。
   * @param array $arguments コンストラクタ引数。
   * @return object コンポーネントオブジェクトを返します。
   * @throws InvalidArgumentException メソッド引数が不正な場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function executeConstructorInjection(ReflectionClass $reflection, $className, array $arguments)
  {
    $constructor = $reflection->getConstructor();

    // コンストラクタが定義されている
    if ($constructor) {
      $this->checkPublicMethod($constructor);
    }

    // DI の設定でコンポーネント引数が渡されている
    $j = sizeof($arguments);

    if ($j) {
      for ($i = 0; $i < $j; $i++) {
        $arguments[$i] = $this->parseMethodArgument($arguments[$i]);
      }

      if ($j == 1) {
        $component = new $className($arguments[0]);

      } else if ($j == 2) {
        $component = new $className($arguments[0], $arguments[1]);

      } else if ($j == 3) {
        $component = new $className($arguments[0], $arguments[1], $arguments[2]);

      } else if ($j == 4) {
        $component = new $className($arguments[0], $arguments[1], $arguments[2], $arguments[3]);

      // 引数が 4 つ以上指定されている
      } else {
        $callMethod = 'new ' . $className;
        $component = $this->executeSpecialConstructorInjection($callMethod, $arguments, $j);
      }

    // クラスにコンストラクタが定義されていない
    } else if (!$constructor) {
      $component = new $className;

    // コンストラクタに引数が宣言されているが、DI の設定で引数が不足している
    } else if ($constructor->getNumberOfRequiredParameters() > $j) {
      $message = sprintf('Argument format of constructor is incorrect. [%s::__construct()]', $reflection->name);
      throw new InvalidArgumentException($message);

    // コンストラクタの引数がオプションで宣言されている
    } else {
      $component = new $className;
    }

    return $component;
  }

  /**
   * セッターインジェクションを実行します。
   *
   * @param ReflectionClass $reflection コンポーネントのリフレクションクラス。
   * @param object $component コンポーネントオブジェクト。
   * @param array $arguments セッダー引数。
   * @throws InvalidArgumentException メソッド引数が不正な場合に発生。
   * @throws RuntimeException メソッドが未定義の場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function executeSetterInjection(ReflectionClass $reflection, $component, array $arguments)
  {
    foreach ($arguments as $methodName => $setterValue) {
      $setterMethodName = 'set' . ucfirst($methodName);
      $setterValue = $this->parseMethodArgument($setterValue);

      // オブジェクトにセッダーメソッドが存在する
      if ($reflection->hasMethod($setterMethodName)) {
        $parameters = $reflection->getMethod($setterMethodName)->getParameters();

        if (1 != sizeof($parameters)) {
          $message = sprintf('Argument format of Setter-method is incorrect. [%s::%s()]', $reflection->name, $methodName);
          throw new InvalidArgumentException($message);
        }

        $this->checkPublicMethod($reflection->getMethod($setterMethodName));
        call_user_func(array($component, $setterMethodName), $setterValue);

      // マジックメソッドが定義されている
      } else if ($reflection->hasMethod('__set')) {
        $component->$methodName = $setterValue;

      // セッダーメソッドが未定義
      } else {
        $message = sprintf('Setter-method is undefined. [%s::%s()]', $reflection->name, $setterMethodName);
        throw new RuntimeException($message);
      }
    }
  }

  /**
   * メソッドインジェクションを実行します。
   *
   * @param ReflectionClass $reflection コンポーネントのリフレクションクラス。
   * @param object $component コンポーネントオブジェクト。
   * @param array $methods メソッドリスト。
   * @throws RuntimeException メソッドが未定義の場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function executeMethodInjection(ReflectionClass $reflection, $component, array $methods)
  {
    foreach ($methods as $methodName => $arguments) {
      // オブジェクトにメソッドが存在する
      if ($reflection->hasMethod($methodName)) {
        // DI 設定で引数が未指定
        if (Delta_StringUtils::nullOrEmpty($arguments)) {
          $component->$methodName();

        } else {
          $j = sizeof($arguments);

          for ($i = 0; $i < $j; $i++) {
            $arguments[$i] = $this->parseMethodArgument($arguments[$i]);
          }

          $this->checkPublicMethod($reflection->getMethod($methodName));
          $component = call_user_func_array(array($component, $methodName), $arguments);
        }

      // メソッドが未定義
      } else {
        $message = sprintf('Initialize method is undefined. [%s::%s()]', $reflection->name, $methodName);
        throw new RuntimeException($message);
      }
    }
  }

  /**
   * 引数の多いコンストラクタインジェクションを実行します。
   *
   * @param string $methodName コンポーネントクラス名。
   * @param array $arguments コンストラクタ引数。
   * @param int $size 引数のサイズ。
   * @throws RuntimeException コンストラクタの実行に失敗した場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function executeSpecialConstructorInjection($newClassName, array $arguments, $size)
  {
    $buffer = NULL;

    for ($i = 0; $i < $size; $i++) {
      if (is_object($arguments[$i])) {
        $buffer .= sprintf('$arguments[%s],', $i);
      } else {
        $buffer .= sprintf('\'%s\',', $arguments[$i]);
      }
    }

    try {
      $code = sprintf('$result = %s(%s);', $newClassName, rtrim($buffer, ','));
      eval($code);

    } catch (ErrorException $e) {
      $message = sprintf('Failed to new constructor. [%s]', $e->getMessage());
      throw new RuntimeException($message);
    }

    return $result;
  }

  /**
   * コンポーネントが持つメソッドにアクセス可能かチェックします。
   *
   * @param ReflectionClass $reflection コンポーネントのリフレクションクラス。
   * @param string $methodName チェック対象のメソッド名。
   * @throws RuntimeException メソッドのアクセス修飾子が private、または protected の場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function checkPublicMethod(ReflectionMethod $reflection)
  {
    if (!$reflection->isPublic()) {
      $message = sprintf('Method has not been public. [%s::%s()]', $reflection->class, $reflection->name);
      throw new RuntimeException($message);
    }
  }

  /**
   * DI コンテナから指定したコンポーネントを取得します。
   *
   * @param string $componentName 取得するコンポーネントの名称。
   * @return object コンポーネントオブジェクトを返します。
   * @throws RuntimeException コンポーネントが見つからない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getComponent($componentName)
  {
    $component = NULL;

    if (isset($this->_components[$componentName])) {
      $component = $this->_components[$componentName];

    } else {
      $search = 'componentNames.' . $componentName;
      $attributes = $this->_config->getArray($search);

      if ($attributes) {
        $component = $this->componentRegister($componentName, $attributes);

      } else {
        $className = ucfirst($componentName);
        $classPath = Delta_ClassLoader::findPath($className);

        $relativePath = str_replace('\\', '/', substr($classPath, strlen(APP_ROOT_DIR) + 1));

        if ($component === NULL) {
          $message = sprintf('Component is not found. [%s]', $componentName);
          throw new RuntimeException($message);
        }
      }
    }

    return $component;
  }

  /**
   * @see Delta_DIContainer::getComponent()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getComponentClassName($componentName)
  {
    return $this->_config->get('componentNames')->get($componentName)->getString('class');
  }

  /**
   * 指定したコンポーネントのインスタンスがコンテナに登録されているかチェックします。
   *
   * @param string $componentName チェック対象のコンポーネント名。
   * @return bool 指定したコンポーネントのインスタンスが登録済みの場合は TRUE を返します。
   * @see Delta_DIContainer::getComponent()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasComponent($componentName)
  {
    if (isset($this->_components[$componentName])) {
      return TRUE;
    }
  }
}
