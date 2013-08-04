<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.config
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * このクラスは、実験的なステータスにあります。
 * これは、この関数の動作、関数名、ここで書かれていること全てが delta の将来のバージョンで予告な>く変更される可能性があることを意味します。
 * 注意を喚起するとともに自分のリスクでこのクラスを使用してください。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.config
 */
class Delta_ConfigCompiler extends Delta_Object
{
  /**
   * データをYAML 形式として構築します。
   *
   * @param mixed &$array 対象となるデータ。
   * @throws Delta_ParseException 属性値の解析に失敗した際に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function build(&$array)
  {
    $this->buildCallback($array);
  }

  /**
   * @param array &$array
   * @param string $target
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function buildCallback(&$array, $target = NULL)
  {
    if (is_array($array)) {
      foreach ($array as $name => &$value) {
        $this->buildCallback($value, $name);
      }

    } else {
      // 属性値に含まれる PHP タグを解析
      $pattern = '/^(<\?php.+)\?' . '>$/';

      if (preg_match($pattern, $array, $matches)) {
        try {
          $source = $matches[1] . ';';

          $parser = new Delta_PHPStringParser($source);
          $parser->execute();
          $array = $parser->fetch();

        } catch (Delta_ParseException $e) {
          $message = sprintf('Invalid \'%s\' attribute defined in the script. (%s)', $target, $e->getMessage());
          throw new Delta_ParseException($message);
        }

      } else if (gettype($array) === 'string') {
        // 属性値に改行を含める
        $from = array("\\r\\n", "\\r", "\\n", "\\t");
        $to = array("\r\n", "\r", "\n", "\t");

        $array = str_replace($from, $to, $array);
      }
    }
  }

  /**
   * @param array $policyConfig
   * @param array &$checkConfig
   * @param string $configPath
   * @param array $stack
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function validatePolicy(array $policyConfig,
    array &$checkConfig,
    $configPath,
    array &$stack = array())
  {
    foreach ($policyConfig as $policyPattern => $policyValues) {
      preg_match('/([^{]+)({([^}]+)})?/', $policyPattern, $matches);
      $policyName = $matches[1];
      $policies = array();

      // 属性オプションの解析
      if (isset($matches[3])) {
        $matches = Delta_StringUtils::splitExclude(rtrim($matches[3]), ' ', '\'', TRUE, 'UTF-8');

        foreach ($matches as $match) {
          $split = Delta_StringUtils::splitExclude($match, '=', '\'', TRUE, 'UTF-8');
          $split[1] = trim($split[1], "\"");

          $this->build($split[1]);
          $policies[$split[0]] = $split[1];
        }
      }

      $policyValueType = explode(',', Delta_ArrayUtils::find($policies, 'type', 'string'));
      $policyValueRequired = Delta_ArrayUtils::find($policies, 'required', FALSE);
      $policyValueDefault = Delta_ArrayUtils::find($policies, 'default');
      $policyOutput = Delta_ArrayUtils::find($policies, 'output', FALSE);

      if ($policyValueType[0] == 'boolean') {
        if (strcasecmp($policyValueDefault, 'TRUE') === 0) {
          $policyValueDefault = TRUE;
        } else {
          $policyValueDefault = FALSE;
        }

      } else {
        settype($policyValueDefault, $policyValueType[0]);
      }

      // 属性構成の解析
      if ($policyName !== '*') {
        if (array_key_exists($policyName, $checkConfig)) {
          $value = &$checkConfig[$policyName];

        } else if ($policyValueRequired) {
          if (sizeof($stack)) {
            $attribute = implode('.', $stack) . '.' . $policyName;
          } else {
            $attribute = $policyName;
          }

          $message = sprintf('Required attribute is undefined. [%s#%s]',
            $configPath,
            $attribute);
          throw new Delta_ConfigurationException($message);

        // デフォルト値のセット (キー指定あり)
        } else if ($policyOutput) {
          if ($policyValueType[0] == 'array') {
            $checkConfig[$policyName] = array();
          } else {
            $checkConfig[$policyName] = $policyValueDefault;
          }
          $value = &$checkConfig[$policyName];

        } else {
          continue;
        }

        $checkAttributes = array($policyName => &$value);

      } else {
        $checkAttributes = &$checkConfig;
      }

      foreach ($checkAttributes as $checkName => &$checkValue) {
        array_push($stack, $checkName);
        $checkValueType = gettype($checkValue);

        // デフォルト値のセット (キー指定なし)
        if ($policyName === '*' && $checkValue === NULL && !$policyValueRequired && $checkValueType !== 'array') {
          if ($policyOutput) {
            if ($policyValueType[0] === 'array') {
              $checkValue = array();
            } else {
              $checkValue = $policyValueDefault;
            }
          }
        }

        // 型の変換
        if ($checkValueType !== 'array' && $policyValueType[0] !== 'array') {
          settype($checkValue, $policyValueType[0]);
          $checkValueType = $policyValueType[0];
        }

        if (!in_array($checkValueType, $policyValueType)) {
          $message = sprintf('Behavior file invalid attribute types. [%s#%s]',
            $configPath,
            implode('.', $stack));
          throw new Delta_ConfigurationException($message);
        }

        if ($policyValueType[0] === 'array') {
          if (is_array($policyValues)) {
            $this->validatePolicy($policyValues, $checkValue, $configPath, $stack);
          }

        } else if ($checkValueType !== 'array' && Delta_StringUtils::nullOrEmpty($checkValue)) {
          $checkValue = $policyValueDefault;
        }

        array_pop($stack);
      }
    }

    array_pop($stack);
  }

  /**
   * @param string $configPath
   * @param array $data
   * @return array
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function compileApplication($configPath, $data)
  {
    $policyPath = DELTA_ROOT_DIR . '/skeleton/policy_config/application.yml';
    $policies = Delta_Config::getCustomFile($policyPath)->toArray();

    // パスに 'c:\nyamakita' といった文字列が含まれる場合、build() メソッドが '\n' を改行と見なしてしまう問題を回避
    if (isset($data['autoload']) && PHP_OS === 'WINNT') {
      foreach ($data['autoload'] as &$path) {
        $path = str_replace(DIRECTORY_SEPARATOR, '//', $path);
      }

      $this->build($data);

      foreach ($data['autoload'] as &$path) {
        $path = str_replace('//', DIRECTORY_SEPARATOR, $path);
      }

    } else {
      $this->build($data);
    }

    $this->validatePolicy($policies, $data, $configPath);

    if (isset($data['controller']['listener'])) {
      $array = explode(',', $data['controller']['listener']);
      $data['controller']['listener'] = Delta_ArrayUtils::trim($array);

    } else {
      $data['controller']['listener'] = array();
    }

    // 'charset.default' が 'auto' の時は mbstring.internal_encoding の指定を必須とする
    if ($data['charset']['default'] === 'auto') {
      $error = NULL;
      if (isset($data['php']['mbstring.internal_encoding'])) {
        $internalEncoding = $data['php']['mbstring.internal_encoding'];

        if (Delta_StringUtils::nullOrEmpty($internalEncoding)) {
          $error = '\'mbstring.internal_encoding\' is not specified.';

        } else if ($internalEncoding === 'pass' || $internalEncoding === 'auto') {
          $error = '\'mbstring.internal_encoding\' has specified an invalid value.';
        }

      } else {
        $error = '\'mbstring.internal_encoding\' is not specified.';
      }

      if ($error) {
        throw new Delta_ParseException($error);
      }
    }

    // モジュールリストを取得
    $modulePath = APP_ROOT_DIR . '/modules';

    if (is_dir($modulePath)) {
      $paths = scandir($modulePath);
      $modules = array();

      foreach ($paths as $path) {
        if ($path === '.' || $path === '..') {
          continue;
        }

        if (preg_match(Delta_CoreUtils::REGEXP_MODULE, $path)) {
          $modules[] = $path;
        }
      }

      // テーマモジュールの割り当て
      if (sizeof($data['theme']['modules']) == 0 || in_array('*', $data['theme']['modules'])) {
        $data['theme']['modules'] = $modules;
      }
    }

    return $data;
  }

  /**
   * @param string $configPath
   * @param array $data
   * @return array
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function compileBaseDicon($configPath, $data)
  {
    $policyPath = DELTA_ROOT_DIR . '/skeleton/policy_config/base_dicon.yml';
    $policies = Delta_Config::getCustomFile($policyPath)->toArray();

    $this->build($data);
    $this->validatePolicy($policies, $data, $configPath);

    $componentNames = &$data['componentNames'];
    $indexes = Delta_CoreUtils::getCoreClasses(DELTA_LIBS_DIR);

    foreach ($componentNames as $componentName => &$attributes) {
      if (isset($attributes['class'])) {
        $className = $attributes['class'];

        if (isset($indexes[$className])) {
          $attributes['path'] = $indexes[$className];
        } else {
          $attributes['path'] = Delta_ClassLoader::findPath($className, NULL, TRUE);
        }
      }
    }

    return $data;
  }

  /**
   * ルーティングテーブルに設定されている URI を元にした正規表現パターンを生成します。
   * このメソッドは、config/routes.yml のキャッシュを生成する際に {@link Delta_Config} クラスからコールされます。
   *
   * @param string $configPath 解析する設定ファイルのパス。
   * @param array $data routes.yml のリソース。
   * @return array 加工したルーティングテーブルを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function compileRoutes($configPath, $data)
  {
    $policyPath = DELTA_ROOT_DIR . '/skeleton/policy_config/routes.yml';
    $policies = Delta_Config::getCustomFile($policyPath)->toArray();

    $this->build($data);
    $this->validatePolicy($policies, $data, $configPath);

    foreach ($data as $name => &$keys) {
      $regexp = str_replace('/', '\/', $keys['uri']);
      $regexp = str_replace('.', '\.', $regexp);
      $regexp = str_replace(':action', '(?:[\w\.]+)', $regexp);
      $regexp = str_replace(':module', '(?:[\w\-]+)', $regexp);

      // Match pattern is ':foo', ':bar'...
      $regexp = preg_replace('/:\w+/', '(?:[\w-%]+)', $regexp);

      // Match pattern is '/*' (RFC2396)
      $reserved = ';\/\?:@&=+\$,';
      $unreserved = '\w-\.!~\*\'\(\)';
      $escaped = '%';

      // RFC に準拠しないブラウザ対策
      $extra = '\[\]';

      $replace = sprintf('/?(?:[\'%s%s%s%s\']+)?', $reserved, $unreserved, $escaped, $extra);
      $regexp = preg_replace('/\/\*/', $replace, $regexp);

      $keys['regexp'] = sprintf('/^%s(?:\?.+)?$/', $regexp);

      if (empty($keys['packages'])) {
        continue;
      }
    }

    return $data;
  }

  /**
   * @param string $configPath
   * @param array $data
   * @return array
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function compileGlobalFilters($configPath, $data)
  {
    $policyPath = DELTA_ROOT_DIR . '/skeleton/policy_config/global_filters.yml';
    $policies = Delta_Config::getCustomFile($policyPath)->toArray();

    $this->build($data);
    $this->validatePolicy($policies, $data, $configPath);

    return $data;
  }

  /**
   * @param string $configPath
   * @param array $data
   * @return array
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function compileModuleFilters($configPath, $data)
  {
    return $this->compileGlobalFilters($configPath, $data);
  }

  /**
   * @param string $configPath
   * @param array $data
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   * @return array
   */
  public function compileGlobalBehavior($configPath, $data)
  {
    $policyPath = DELTA_ROOT_DIR . '/skeleton/policy_config/global_behavior.yml';
    $policies = Delta_Config::getCustomFile($policyPath)->toArray();

    $this->build($data);
    $this->validatePolicy($policies, $data, $configPath);

    return $data;
  }

  /**
   * @param string $configPath
   * @param array $data
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   * @return array
   */
  public function compileModuleBehavior($configPath, $data)
  {
    return $this->compileGlobalBehavior($configPath, $data);
  }

  /**
   * @param string $configPath
   * @param array $data
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   * @return array
   */
  public function compileActionBehavior($configPath, $data)
  {
    return $this->compileGlobalBehavior($configPath, $data);
  }

  /**
   * @param string $configPath
   * @param array $data
   * @return array
   * @throws Delta_ConfigurationException
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function compileGlobalHelpers($configPath, $data)
  {
    $policyPath = DELTA_ROOT_DIR . '/skeleton/policy_config/global_helpers.yml';
    $policies = Delta_Config::getCustomFile($policyPath)->toArray();

    $this->build($data);
    $this->validatePolicy($policies, $data, $configPath);

    foreach ($data as $helperId => &$attributes) {
      if (empty($attributes['class'])) {
        $message = sprintf('"class" attribute is undefined. [%s.yml#%s.class]',
          $configPath,
          $helperId);
        throw new Delta_ConfigurationException($message);
      }

      $attributes['path'] = Delta_ClassLoader::findPath($attributes['class'], NULL, TRUE);
    }

    return $data;
  }

  /**
   * @param string $configPath
   * @param string $data
   * @return array
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function compileModuleHelpers($configPath, $data)
  {
    // global_helpers.yml が持つ属性を helpers.yml にマージ
    $baseConfig = Delta_Config::getCustomFile('global_helpers')->toArray();
    $data = Delta_ArrayUtils::mergeRecursive($baseConfig, $data);

    return $this->compileGlobalHelpers($configPath, $data);
  }
}
