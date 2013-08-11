<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller.action
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * コントローラとビジネスロジックを橋渡しするアダプタです。
 * コントローラはリクエストから適切な Delta_Action を選択して処理を開始します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller.action
 */

abstract class Delta_Action extends Delta_WebApplication
{
  /**
   * アクションパス。
   * @var string
   */
  private $_actionPath;

  /**
   * ビヘイビアパス。
   * @var string
   */
  private $_behaviorPath;

  /**
   * エントリパッケージ名。
   * @var string
   */
  private $_packageName;

  /**
   * バリデートを行うかどうか。
   * @var bool
   */
  private $_validate = FALSE;

  /**
   * アクション内でバリデートエラーが発生しているかどうか。
   * @var bool
   */
  private $_hasError = FALSE;

  /**
   * アクションに設定されているロールのリスト。
   * @var array
   */
  private $_roles;

  /**
   * コンストラクタ。
   *
   * @param string $actionPath アクションのファイルパス。
   * @param string $behaviorPath ビヘイビアのファイルパス。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($actionPath, $behaviorPath = NULL)
  {
    parent::__construct();

    $this->_actionPath = $actionPath;
    $this->_behaviorPath = $behaviorPath;
  }

  /**
   * アクションの初期化を行ないます。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function initialize()
  {}

  /**
   * フロントコントローラオブジェクトを取得します。
   *
   * @return Delta_FrontController フロントコントローラオブジェクトを返します。
   * @since 1.2
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getController()
  {
    return Delta_FrontController::getInstance();
  }

  /**
   * エントリパッケージを設定します。
   *
   * @param string $packageName エントリパッケージ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setPackageName($packageName)
  {
    $this->_packageName = $packageName;
  }

  /**
   * エントリパッケージを取得します。
   *
   * @return string エントリパッケージを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getPackageName()
  {
    return $this->_packageName;
  }

  /**
   * アクションクラスのファイルパスを取得します。
   *
   * @return string アクションクラスのファイルパスを取得します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getActionPath()
  {
    return $this->_actionPath;
  }

  /**
   * アクションに紐づくビヘイビアのファイルパスを取得します。
   * このメソッドはビヘイビアファイルが実際に存在するかどうかのチェックは行いません。
   *
   * @return string ビヘイビアのファイルパスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getBehaviorPath()
  {
    return $this->_behaviorPath;
  }

  /**
   * バリデータの設定を行います。
   * {@link initialize()} メソッドで {@link setValidate() setValidate(FALSE)} を設定した場合、{@link validate()}、及びビヘイビアのバリデータは実行されません。
   *
   * @param bool $validate バリデータを実行する場合は TRUE、実行しない場合は FALSE。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setValidate($validate)
  {
    $this->_validate = $validate;
  }

  /**
   * アクションにバリデータが設定されているかチェックします。
   *
   * @return bool バリデータが設定されているかどうかを TRUE/FALSE で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isValidate()
  {
    return $this->_validate;
  }

  /**
   * ビジネスロジックレベルのデータ検証を定義するためのメソッドです。
   * このメソッドは、ビヘイビアに設定されているバリデータでエラーが返されなかった場合のみ実行されます。
   *
   * @return bool ビジネスロジックレベルのデータチェックで問題がなければ TRUE、エラーが含まれる場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate()
  {
    return TRUE;
  }

  /**
   * アクションにバリデートエラーが発生しているかどうかを設定します。
   *
   * @param bool $hasError バリデートエラーが発生しているかどうかを TRUE/FALSE で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setValidateError($hasError)
  {
    $this->_hasError = $hasError;
  }

  /**
   * アクションでバリデートエラーが発生しているかどうかチェックします。
   *
   * @return bool バリデートエラーが発生しているかどうかを TRUE/FALSE で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasError()
  {
    return $this->_hasError;
  }

  /**
   * ビジネスロジック (メインの処理) を実行します。
   * execute() メソッドは戻り値として {@link Delta_View} 定数を返す必要があります。
   *
   * @return string フォワードするビューを返します。戻り値が不明な場合は {@link Delta_View::SUCCESS} が返ります。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function execute()
  {
    return Delta_View::SUCCESS;
  }

  /**
   * 実行中のアクション名を返します。
   *
   * @return string アクション名を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getActionName($withSuffix = FALSE)
  {
    $className = get_class($this);

    if ($withSuffix) {
      return $className;
    }

    return substr($className, 0, strrpos($className, 'Action'));
  }

  /**
   * バリデートエラーが発生した際に起動します。
   *
   * @return string フォワード先のビューを返します。
   *   メソッドがオーバーライドされていない場合は {@link Delta_View::ERROR} にフォワードされます。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validateErrorHandler()
  {
    return Delta_View::ERROR;
  }

  /**
   * セーフティエラーが発生した際に起動します。
   *
   * @return string フォワード先のビューを返します。
   *   メソッドがオーバーライドされていない場合は {@link Delta_View::SAFETY_ERROR} にフォワードされます。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function safetyErrorHandler()
  {
    return Delta_View::SAFETY_ERROR;
  }

  /**
   * アクションにロールセットを設定します。
   *
   * @param array $roles ロールセット。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setRoles(array $roles)
  {
    $this->_roles = $roles;
  }

  /**
   * アクションに設定されているロールの一覧を取得します。
   *
   * @return array ロールの一覧を返します。ロール設定が存在しない場合は NULL を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getRoles()
  {
    return $this->_roles;
  }

  /**
   * @param string $packageName
   * @param array $patterns
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function isValidPackage($packageName, array $patterns)
  {
    $allow = FALSE;
    $packageName = rtrim($packageName, '/');

    foreach ($patterns as $pattern) {
      $pattern = rtrim($pattern, '/');

      if (strcmp($pattern, $packageName) == 0) {
        $allow = TRUE;
        break;
      }

      if (substr($pattern, -2) == '/*') {
        $pattern = substr($pattern, 0, -2);

        if (strlen($pattern)) {
          if (strpos($packageName, $pattern) === 0) {
            $allow = TRUE;
            break;
          }

        } else {
          $allow = TRUE;
          break;
        }
      }
    }

    return $allow;
  }
}
