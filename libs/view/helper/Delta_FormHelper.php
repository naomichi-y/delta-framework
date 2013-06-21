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
 * テンプレート上で HTML フォームを生成するためのヘルパメソッドを提供します。
 * このヘルパは、$form という変数名であらかじめテンプレートにインスタンスが割り当てられています。
 *
 * <code>
 * <?php echo $form->{method}; ?>
 * </code>
 *
 * global_helpers.yml の設定例:
 * <code>
 * form:
 *   # ヘルパクラス名。
 *   class: Delta_FormHelper
 *
 *   # エラーの発生しているフィールド単位でエラーメッセージを表示する場合は TRUE を指定。
 *   # FALSE を指定した場合はフィールド単位でのエラーが表示されなくなります。
 *   # 全てのエラーメッセージをリスト形式で表示する場合は {@link Delta_HTMLHelper::errors()} メソッドを使用して下さい。
 *   error: TRUE
 *
 *   # エラーが含まれている場合に出力するメッセージ。{@link Delta_FormHelper::containErrors()} メソッド使用時に参照されます。
 *   # この属性はフォーム内で何件のエラーが含まれているか表示する際に役立ちます。
 *   #   - \1: エラーの数。
 *   containErrors: 'Contains an error entry. (\1 errors)'
 *
 *   # フィールド単位で出力するエラーメッセージの HTML タグを設定します。
 *   # 'error' 属性が FALSE の場合はタグ自体出力されません。
 *   #   - \1: エラーメッセージ。
 *   errorFieldTag: '<div class="field_error_message">\1</div>'
 *
 *   # フィールドを囲む HTML タグ。
 *   # ヘルパが生成する入力フィールド (ボタンを除く) は 'fieldTag' で囲まれます。
 *   #   - \1: フィールドタグ。フィールドに関連付くラベルやエラーメッセージも含まれます。
 *   fieldTag: '<div class="form_field">\n\1</div>'
 *
 *   # checkbox、radio の各要素を囲む HTML タグ。
 *   #   - \1: フィールド要素のタグ。
 *   fieldElementTag: '<span class="field_element">\1</span>'
 *
 *   # 複数のフィールドが混在するグループのセパレータ。
 *   fieldSeparatorTag: '<span class="field_separator">\1</span>'
 *
 *   # 対象フィールドが必須入力であることを示すシンボル。{@link Delta_FormHelper::inputText()} メソッドを参照。
 *   requiredTag: '<span class="required">*</span>'
 * </code>
 * <i>その他に指定可能な属性は {@link Delta_Helper} クラスを参照。</i>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package view.helper
 */
class Delta_FormHelper extends Delta_Helper
{
  /**
   * @var Delta_ActionForm
   */
  private $_form;

  /**
   * @var Delta_ActionMessages
   */
  private $_messages;

  /**
   * @var Delta_HttpRequest
   */
  private $_request;

  /**
   * @var array
   */
  protected static $_defaultValues = array(
    'error' => TRUE,
    'errorFieldTag' => "<div class=\"field_error_message\">\\1</div>",
    'containErrors' => "Contains an error entry. (\\1 errors)",
    'fieldTag' => "<div class=\"form_field\">\n\\1</div>",
    'fieldElementTag' => "<span class=\"field_element\">\n\\1</span>",
    'fieldSeparatorTag' => "<span class=\"field_separator\">\\1</span>",
    'requiredTag' => '<span class="required">*</span>'
  );

  /**
   * @see Delta_Helper::__construct()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct(Delta_View $currentView, array $config = array())
  {
    $this->_form = Delta_DIContainerFactory::getContainer()->getComponent('form');
    $this->_messages = $this->getMessages();
    $this->_request = $this->getRequest();

    parent::__construct($currentView, $config);
  }

  /**
   * フォームの開始タグを生成します。
   * attributes に 'method' 属性の指定がない場合、生成されるフォームのメソッドは GET 形式となります。
   * start() メソッドで開始したフォームは {@link close()} メソッドで閉じるようにして下さい。
   *
   * @param string $path フォームの送信先。
   *   指定可能なパスの書式は {@link Delta_RouteResolver::buildRequestPath()} メソッドを参照。
   * @param mixed $attributes タグに追加する属性。{@link Delta_HTMLHelper::link()} メソッドを参照。
   * @param mixed $extra タグの出力オプション。
   *   - absolute: TRUE を指定した場合、path を絶対パスに変換する。
   *   - secure: URI スキームの指定。詳しくは {@link Delta_RouteResolver::buildRequestPath()} を参照。既定値は NULL。
   *       (secure オプション指定時は absolute 属性は TRUE と見なされる)
   *   - query: 追加のクエリパラメータを連想配列形式で指定。
   * @return string 生成したタグを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function start($path = NULL, $attributes = array(), $extra = array())
  {
    $extra = parent::constructParameters($extra);
    $secure = Delta_ArrayUtils::find($extra, 'secure');

    if ($secure === NULL) {
      $absolute = Delta_ArrayUtils::find($extra, 'absolute', FALSE);
    } else {
      $absolute =  TRUE;
    }

    $queryData = Delta_ArrayUtils::find($extra, 'query', array());

    $defaults = array();
    $defaults['action'] = $this->buildRequestPath($path, $queryData, $absolute, $secure);
    $defaults['method'] = 'post';

    $attributes = self::constructParameters($attributes, $defaults);

    $buffer = self::buildTagAttribute($attributes, FALSE);
    $buffer = sprintf("<form%s>\n", $buffer);

    return $buffer;
  }

  /**
   * ファイルアップロード用のフォーム開始タグを生成します。
   * このメソッドは基本的に {@link start()} と同じです。
   * 唯一の違いは、フォームのエンコーディング形式 (ファイルの送信を許可するかどうか) にあります。
   *
   * @param string $path {@link start()} メソッドを参照。
   * @param mixed $attributes {@link start()} メソッドを参照。
   * @param mixed $extra {@link start()} メソッドを参照。
   * @return string {@link start()} メソッドを参照。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function startMultipart($path = NULL, $attributes = array(), $extra = array())
  {
    $attributes = parent::constructParameters($attributes);
    $attributes['enctype'] = 'multipart/form-data';

    return $this->start($path, $attributes, $extra);
  }

  /**
   * フォームの終了タグを生成します。
   * {@link Delta_AuthorityUser::saveToken() トランザクショントークン} が発行されている場合は、自動的にトークン用の hidden タグを生成します。
   *
   * @return string 生成したタグを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function close()
  {
    $buffer = NULL;

    if ($this->_form->hasName('tokenId')) {
      $attributes = array('value' => $this->_form->get('tokenId'));
      $buffer = $this->inputHidden('tokenId', $attributes);
    }

    $buffer .= "</form>\n";

    return $buffer;
  }

  /**
   * ラベルタグを生成します。
   *
   * @param string $fieldId ラベルに紐付けるフィールドの ID。
   * @param string $label ラベル名。
   * @param mixed $attributes タグに追加する属性。{@link Delta_HTMLHelper::link()} メソッドを参照。
   * @return string 生成したタグを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function label($fieldId, $label, $attributes = array())
  {
    $attributes = parent::constructParameters($attributes);
    $attributes['for'] = $fieldId;

    $attributes = self::buildTagAttribute($attributes, FALSE);

    $buffer = sprintf('<label%s>%s</label>',
      $attributes,
      Delta_StringUtils::escape($label));

    return $buffer;
  }

  /**
   * {@link Delta_ActionForm::get()} メソッドに {@link Delta_StringUtils::escape() HTML エスケープ} 機能を追加した拡張メソッドです。
   *
   * @param bool $escape 値を HTML エスケープした状態で返す場合は TRUE を指定。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function get($name, $escape = TRUE)
  {
    $value = $this->_form->get($name);

    if ($escape) {
      $value = Delta_StringUtils::escape($value);
    }

    return $value;
  }

  /**
   * {@link Delta_ActionForm::hasName()} のエイリアスメソッドです。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasName($name)
  {
    return $this->_form->hasName($name);
  }

  /**
   * {@link Delta_ActionForm::getFields()} メソッドに {@link Delta_StringUtils::escape() HTML エスケープ} 機能を追加した拡張メソッドです。
   *
   * @param bool $escape 値を HTML エスケープした状態で返す場合は TRUE を指定。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getFields($escape = TRUE)
  {
    $values = $this->_form->getFields();

    if ($escape) {
      $values = Delta_StringUtils::escape($values);
    }

    return $values;
  }

  /**
   * {@link Delta_ActionMessages::hasFieldError()} メソッドを複数のフィールドチェックに対応させた拡張メソッドです。
   *
   * @param mixed $fields チェック対象のフィールド名。
   *   配列形式で複数のフィールドを指定した場合は、1 つ以上のフィールドにエラーが含まれているかどうかをチェックします。
   * @return bool 対象フィールドにエラーが含まれる場合は TRUE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasFieldError($fields)
  {
    if (!is_array($fields)) {
      $fields = array($fields);
    }

    foreach ($fields as $fieldName) {
      if ($this->_messages->hasFieldError($fieldName)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@link Delta_ActionMessages::getFieldError()} メソッドに {@link Delta_StringUtils::escape() HTML エスケープ} 機能を追加した拡張メソッドです。
   *
   * @param bool $escape 値を HTML エスケープした状態で返す場合は TRUE を指定。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getFieldError($name, $escape = TRUE)
  {
    $message = $this->_messages->getFieldError($name);

    if ($escape) {
      $message = Delta_StringUtils::escape($message);
    }

    return $message;
  }

  /**
   * フォームにエラーが含まれる場合、エラーメッセージを包括したメッセージタグを生成します。
   *
   * @param mixed $attributes タグに追加する属性。{@link Delta_HTMLHelper::link()} メソッドを参照。
   * @return string 出力されるメッセージはヘルパ属性の 'containErrors' に定義したタグが使用されます。
   *   また、フォームにエラーが含まれない場合は NULL を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function containFieldErrors($attributes = array('class' => 'error'))
  {
    $errorSize = $this->_messages->getErrorSize(Delta_ActionMessages::ERROR_TYPE_FIELD);

    if ($errorSize) {
      $attributes = self::buildTagAttribute($attributes, TRUE);

      $buffer = str_replace('\1', $errorSize, $this->_config->getString('containErrors'));
      $buffer = sprintf("<div%s>%s</div>\n", $attributes, Delta_StringUtils::escape($buffer));

      return $buffer;
    }

    return NULL;
  }

  /**
   * 対象フィールドに含まれたエラーメッセージを表示するためのタグを取得します。
   *
   * @param mixed $fieldName フィールド名、またはフィールド名の配列。
   *   '.' (ピリオド) 区切りのキー名が指定された場合は連想配列として認識されます。
   * @return string 出力されるメッセージはヘルパ属性の 'errorFieldTag' に定義したタグが使用されます。
   *   fieldName が配列で構成される場合はメッセージの内容をリスト化します。
   *   また、フィールドにエラーが含まれない場合は NULL を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getErrorTag($fieldName)
  {
    if (is_array($fieldName)) {
      $fields = (array) $fieldName;
      $buffer = NULL;

      foreach ($fields as $name) {
        if ($this->hasFieldError($name)) {
          $buffer .= '<li>' . $this->getFieldError($name) . "</li>\n";
        }
      }

      if ($buffer) {
        $buffer = "\n<ul>\n" . $buffer . "</ul>\n";
        $buffer = str_replace('\1', $buffer, $this->_config->getString('errorFieldTag'));

        return $buffer;
      }

    } else {
      if ($this->hasFieldError($fieldName)) {
        $buffer = str_replace('\1', $this->getFieldError($fieldName), $this->_config->getString('errorFieldTag'));

        return $buffer;
      }
    }

    return NULL;
  }

  /**
   * @param string &$buffer
   * @param string $fieldName
   * @param array $extra
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function decorateControlAppendError(&$buffer, $fieldName, $extra)
  {
    $isAppendError = FALSE;

    // フィールド単位でエラーを出力するかどうかの判定
    if ($this->_config->getBoolean('error', TRUE)) {
      if (Delta_ArrayUtils::find($extra, 'error', TRUE)) {
        $isAppendError = TRUE;
      }

    } else if (Delta_ArrayUtils::find($extra, 'error', FALSE)) {
      $isAppendError = TRUE;
    }

    if ($isAppendError && $this->hasFieldError($fieldName)) {
      $buffer .= $this->getErrorTag($fieldName) . "\n";
    }
  }

  /**
   * @param string &$buffer
   * @param string $type
   * @param string $fieldName
   * @param array $attributes
   * @param array $extra
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function decorateControlAppendTitle(&$buffer, $type, $fieldName, $attributes, $extra)
  {
    // 'set' は複数のコントロールを含む特殊な形式
    if ($type === 'radio' || $type === 'checkbox' || $type === 'set') {
      if (isset($extra['label']) && $extra['label']) {
        $label = Delta_StringUtils::escape($extra['label']);

        if (isset($extra['required'])) {
          $label .= ' ' . $this->_config->getString('requiredTag');
        }

        $buffer = sprintf("\n<legend>%s</legend>\n%s",
          $label,
          $buffer);

        // フィールドに紐付くエラータグの生成
        if ($fieldName !== NULL) {
          $this->decorateControlAppendError($buffer, $fieldName, $extra);
        }

        $buffer = sprintf("<fieldset>%s</fieldset>\n", $buffer);

      } else {
        if ($fieldName !== NULL) {
          $this->decorateControlAppendError($buffer, $fieldName, $extra);
        }
      }

    } else {
      if (isset($extra['label']) && $extra['label']) {
        if (isset($attributes['id'])) {
          $id = $attributes['id'];
        } else {
          $id = $fieldName;
        }

        $label = Delta_StringUtils::escape($extra['label']);

        if (isset($extra['required'])) {
          $label .= ' ' . $this->_config->getString('requiredTag');
        }

        $buffer = sprintf("<label for=\"%s\">%s</label>\n%s",
          $id,
          $label,
          $buffer);
      }

      if ($fieldName !== NULL) {
        $this->decorateControlAppendError($buffer, $fieldName, $extra);
      }
    }
  }

  /**
   * @param array &$buffer
   * @param array $extra
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function decorateControlAppendSet(&$buffer, $extra)
  {
    if (isset($extra['fieldTag'])) {
      $fieldTag = $extra['fieldTag'];
    } else {
      $fieldTag = $this->_config->getString('fieldTag');
    }

    $buffer = str_replace('\1', $buffer, $fieldTag) . "\n";
  }

  /**
   * フィールドタグを生成します。
   * フィールド単位でのエラーメッセージ出力が有効な場合は、フィールドの状況に応じてエラータグも同時に出力します。
   *
   * @param string &$buffer フィールドタグ文字列。
   * @param string $type フィールドタイプ。取り得る値は次の通り。
   *   - text: テキストフィールド
   *   - password: パスワードフィールド
   *   - radio: ラジオフィールド
   *   - checkbox: チェックボックスフィールド
   *   - select: セレクトフィールド
   *   - submit: 送信ボタン
   *   - reset: リセットボタン
   *   - button: 汎用ボタン
   *   - file: ファイルアップロードボタン
   *   - hidden: 隠しフィールド
   *   - image: 画像ボタン
   *   - textarea: テキストエリアフィールド
   *   - set: 複数のフィールドが混在する特殊な形式
   * @param string $fieldName フィールド名'.' (ピリオド) 区切りのキー名が指定された場合は連想配列として認識されます。
   * @param mixed $attributes タグに追加する属性。{@link Delta_HTMLHelper::link()} メソッドを参照。
   * @param mixed $extra {@link Delta_FormHelper::inputText()} メソッドを参照。
   * @return string フィールドタグを出力します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function decorateControl(&$buffer, $type, $fieldName, $attributes, $extra = array())
  {
    $extra = parent::constructParameters($extra);
    $decorate = Delta_ArrayUtils::find($extra, 'decorate', TRUE);

    if (!$decorate) {
      return;
    }

    // 'label' 属性が FALSE の場合はラベルを出力しない
    $this->decorateControlAppendTitle($buffer, $type, $fieldName, $attributes, $extra);

    // フィールド、ラベル、エラーメッセージを括るタグを生成
    switch ($type) {
      case 'text':
      case 'password':
      case 'radio':
      case 'checkbox':
      case 'select':
      case 'file':
      case 'textarea':
      case 'hidden';
        $this->decorateControlAppendSet($buffer, $extra);
        break;

      default:
        break;
    }
  }

  /**
   * text フィールドを生成します。
   *
   * @param string $fieldName フィールド名。'.' (ピリオド) を含む名前は連想配列名として扱われる。
   * @param mixed $attributes タグに追加する属性。{@link Delta_HTMLHelper::link()} メソッドを参照。
   * @param mixed $extra タグの出力オプション。
   *   - label: フィールドに表示するラベル。テキスト系フィールドは label タグ、ラジオやチェックボックスといった選択ボックスは fieldset、legend タグを生成する。
   *   - required: TRUE を指定した場合、'label' に対象フィールドが必須入力であることを示すタグ ('requiredTag' 属性を参照) を追加する。
   *   - error: ヘルパ属性 'error' と同じ。フィールド単位でエラーメッセージの出力制御を行う。
   *       ヘルパ属性の 'error' が TRUE かつ、extra で array('error' => FALSE) が指定された場合、対象フィールドのエラーメッセージは出力されない。
   *       ヘルパ属性の 'error' が FALSE かつ extra で array('error' => TRUE) が指定された場合、対象フィールドのエラーメッセージが出力される。
   *   - decorate: ラベルの装飾やエラーメッセージのタグを生成するかどうか。既定値は TRUE。
   *   - fieldTag: フィールドを囲む HTML タグ。ヘルパ属性 'fieldTag' を個別のフィールドごとに制御する。
   * @return string 生成したタグを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function inputText($fieldName, $attributes = array(), $extra = array())
  {
    $defaults = array();
    $defaults['name'] = $fieldName;
    $defaults['value'] = $this->_form->get($fieldName, '');

    $attributes = self::constructParameters($attributes, $defaults);
    $buffer = $this->buildInputField('text', $attributes);

    $this->decorateControl($buffer, 'text', $fieldName, $attributes, $extra);

    return $buffer;
  }

  /**
   * @param array $attributes
   * @return array
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function appendInputFullStyle($attributes)
  {
    if ($this->_request->getUserAgent()->isMobile()) {
      if ($this->_request->getUserAgent()->isDoCoMo()) {
        $appendStyle = '-wap-input-format: &quot;*&lt;ja:h&gt;&quot;';
        $attributes = $this->appendStyle($attributes, $appendStyle);

      } else if ($this->_request->getUserAgent()->isAU()){
        $appendStyle = '-wap-input-format: *M;';
        $attributes = $this->appendStyle($attributes, $appendStyle);

      // SoftBank は入力文字制限がかかってしまうので、旧属性を使用する
      // (XHTML では mode ではなく istyle 属性を使用)
      } else if ($this->_request->getUserAgent()->isSoftBank()) {
        $attributes['istyle'] = '1';
      }

    } else if ($this->_request->getUserAgent()->isDefault()) {
      $appendStyle = 'ime-mode: active';
      $attributes = $this->appendStyle($attributes, $appendStyle);
    }

    return $attributes;
  }

  /**
   * 全角文字入力用の text フィールドタグを生成します。
   * 入力文字の指定はクライアントの環境やデバイスに依存します。
   *
   * メソッドが対応する入力補助スタイル:
   *   o WAP 2.0 CSS (-wap-input-format)
   *   o IE 独自拡張の CSS (ime-style)
   *   o その他携帯端末の HTML 属性 (istyle)
   *
   * 入力補助をサポートするメソッド:
   *   o {@link inputTextFull()}
   *   o {@link inputTextHalf()}
   *   o {@link inputTextAlphabet()}
   *   o {@link inputTextNumeric()}
   * <i>SoftBank は WAP 2.0 CSS ではなく、'istyle' 属性を用いて入力補助を行います。
   * これは、WAP 2.0 を実装した一部の機種が入力文字を固定 (強制) してしまう問題があるためです。</i>
   *
   * @see Delta_FormHelper::inputText()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function inputTextFull($fieldName, $attributes = array(), $extra = array())
  {
    $attributes = parent::constructParameters($attributes);
    $attributes = $this->appendInputFullStyle($attributes);

    return $this->inputText($fieldName, $attributes, $extra);
  }

  /**
   * @param array $attributes
   * @return array
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function appendInputHalfStyle($attributes)
  {
    if ($this->_request->getUserAgent()->isDoCoMo()) {
      $appendStyle = '-wap-input-format: &quot;*&lt;ja:hk&gt;&quot;';
      $attributes = $this->appendStyle($attributes, $appendStyle);

    } else if ($this->_request->getUserAgent()->isSoftBank()) {
      $attributes['istyle'] = '2';
    }

    return $attributes;
  }

  /**
   * 半角カナ入力用の text フィールドタグを生成します。
   *
   * @see Delta_FormHelper::inputText()
   * @see Delta_FormHelper::inputTextFull()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function inputTextHalf($fieldName, $attributes = array(), $extra = array())
  {
    $attributes = parent::constructParameters($attributes);
    $attributes = $this->appendInputHalfStyle($attributes);

    return $this->inputText($fieldName, $attributes, $extra);
  }

  /**
   * @param array $attributes
   * @return array
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function appendInputAlphabetStyle($attributes)
  {
    if ($this->_request->getUserAgent()->isMobile()) {
      if ($this->_request->getUserAgent()->isDoCoMo()) {
        $appendStyle = '-wap-input-format: &quot;*&lt;ja:en&gt;&quot;';
        $attributes = $this->appendStyle($attributes, $appendStyle);

      } else if ($this->_request->getUserAgent()->isAU()) {
        $appendStyle = '-wap-input-format: *m;';
        $attributes = $this->appendStyle($attributes, $appendStyle);

      } else {
        $attributes['istyle'] = '3';
      }

    } else if ($this->_request->getUserAgent()->isDefault()) {
      $appendStyle = 'ime-mode: inactive';
      $attributes = $this->appendStyle($attributes, $appendStyle);
    }

    return $attributes;
  }

  /**
   * 英字文字入力用の text フィールドを生成します。
   *
   * @see Delta_FormHelper::inputText()
   * @see Delta_FormHelper::inputTextFull()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function inputTextAlphabet($fieldName, $attributes = array(), $extra = array())
  {
    $attributes = parent::constructParameters($attributes);
    $attributes = $this->appendInputAlphabetStyle($attributes);

    return $this->inputText($fieldName, $attributes, $extra);
  }

  /**
   * @param array $attributes
   * @return array
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function appendInputNumericStyle($attributes)
  {
    if ($this->_request->getUserAgent()->isMobile()) {
      if ($this->_request->getUserAgent()->isDoCoMo()) {
        $appendStyle = '-wap-input-format:&quot;*&lt;ja:n&gt;&quot;';
        $attributes = $this->appendStyle($attributes, $appendStyle);

      } else if ($this->_request->getUserAgent()->isAU()) {
        $appendStyle = '-wap-input-format:*N;';
        $attributes = $this->appendStyle($attributes, $appendStyle);

      } else {
        $attributes['istyle'] = '4';
      }

    } else if ($this->_request->getUserAgent()->isDefault()) {
      $appendStyle = 'ime-mode: inactive';
      $attributes = $this->appendStyle($attributes, $appendStyle);
    }

    return $attributes;
  }

  /**
   * 数字入力用の text フィールドを生成します。
   *
   * @see Delta_FormHelper::inputText()
   * @see Delta_FormHelper::inputTextFull()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function inputTextNumeric($fieldName, $attributes = array(), $extra = array())
  {
    $attributes = parent::constructParameters($attributes);
    $attributes = $this->appendInputNumericStyle($attributes);

    return $this->inputText($fieldName, $attributes, $extra);
  }

  /**
   * @param array $attributes
   * @param string $appendStyle
   * @return array
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function appendStyle($attributes, $appendStyle)
  {
    if (isset($attributes['style'])) {
      $appendStyle = trim($attributes['style'], ';') . '; ' . $appendStyle;
    }

    $attributes['style'] = $appendStyle;

    return $attributes;
  }

  /**
   * password フィールドを生成します。
   * ユーザエージェントが携帯の場合、デフォルトの入力モードは数値形式になります。
   *
   * @param string $fieldName フィールド名。'.' (ピリオド) を含む名前は連想配列名として扱われる。
   * @param mixed $attributes タグに追加する属性。{@link Delta_HTMLHelper::link()} メソッドを参照。
   * @param mixed $extra {@link inputText()} メソッドを参照。
   * @return string 生成したタグを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function inputPassword($fieldName, $attributes = array(), $extra = array())
  {
    $defaults = array();
    $defaults['name'] = $fieldName;
    $defaults['value'] = $this->_form->get($fieldName, '');

    $attributes = self::constructParameters($attributes, $defaults);

    if ($this->_request->getUserAgent()->isMobile()) {
      $attributes = $this->appendInputAlphabetStyle($attributes);
    }

    $buffer = $this->buildInputField('password', $attributes);
    $this->decorateControl($buffer, 'password', $fieldName, $attributes, $extra);

    return $buffer;
  }

  /**
   * 1 つの要素を持つ radio フィールドを生成します。
   * メソッドの使い方は {@link inputRadios()} とほぼ同じですが、タグに含まれる ID 属性は要素値を含めない点が異なります。
   * <code>
   * // 出力されるタグ:
   * <div class="form_field">
   *   <span class="field_element">
   *     <input type="radio" value="yes" name="agreement" id="agreement" />
   *     <label for="agreement">Agreement</label>
   *   </span>
   * </div>
   * $form->inputRadio('agreement', array('yes' => 'Agreement')) ?>
   * </code>
   *
   * @see inputRadios()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function inputRadio($fieldName,
    $option,
    $attributes = array(),
    $extra = array())
  {
    $extra['single'] = TRUE;

    $buffer = $this->buildMultipleFields('radio', $fieldName, $option, $attributes, $extra);
    $this->decorateControl($buffer, 'radio', $fieldName, $attributes, $extra);

    return $buffer;
  }

  /**
   * radio フィールドを生成します。
   * 個々のタグに付加される ID 属性の名前は、"フィールド名 + '_' + 要素値" の形式となります。
   *
   * @param string $fieldName フィールド名。'.' (ピリオド) を含む名前は連想配列名として扱われる。
   * @param array $options データを構成する配列。
   *   <code>
   *   array('1' => 'Male', '2' => 'Female')
   *   </code>
   *   要素名と値の配列を個別に指定することも可能。
   *   <code>
   *   $array = array();
   *   $array['output'] = array('Male', 'Female');
   *   $array['values'] = array('1', '2');
   *   </code>
   * @param mixed $attributes タグに追加する属性。{@link Delta_HTMLHelper::link()} メソッドを参照。
   * @param mixed $extra タグの出力オプション。
   *     - selected: あらかじめ選択状態とする要素値。文字列または配列形式で複数指定可能。(ラジオボタンは 1 つのみ選択可能)
   *     - fieldElementTag: フィールド要素を囲む HTML タグ。ヘルパ属性 'fieldElementTag' を個別のフィールドごとに制御する。
   *     - separator: 要素間を区切る文字列。セパレータ文字はエスケープされる。
   *     - tagSeparator: 要素間を区切る文字列。セパレータ文字列はエスケープされない。
   *   その他の指定可能なオプションは {@link inputText()} メソッドを参照。
   * @return string 生成したタグを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function inputRadios($fieldName,
    $options,
    $attributes = array(),
    $extra = array())
  {
    $buffer = $this->buildMultipleFields('radio', $fieldName, $options, $attributes, $extra);
    $this->decorateControl($buffer, 'radio', $fieldName, $attributes, $extra);

    return $buffer;
  }

  /**
   * 1 つの要素を持つ checkbox フィールドを生成します。
   * メソッドの使い方は {@link inputCheckboxes()} とほぼ同じですが、タグに含まれる ID 属性は要素値を含めない点が異なります。
   * <code>
   * // 出力されるタグ:
   * <div class="form_field">
   *   <span class="field_element">
   *     <input type="checkbox" value="yes" name="agreement" id="agreement" />
   *     <label for="agreement">Agreement</label>
   *   </span>
   *   // チェック状態とは別に、フィールド自体が送信されたかどうかを判別するための隠しフィールドが自動生成される
   *   <input type="hidden" name="_agreement" value="on" id="_agreement" />
   * </div>
   * $form->inputCheckbox('agreement', array('yes' => 'Agreement')) ?>
   * </code>
   *
   * @see inputCheckboxes()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function inputCheckbox($fieldName,
    $option,
    $attributes = array(),
    $extra = array())
  {
    $extra['single'] = TRUE;

    $buffer = $this->buildMultipleFields('checkbox', $fieldName, $option, $attributes, $extra);
    $buffer .= $this->buildCheckboxSendField($fieldName);

    $this->decorateControl($buffer, 'checkbox', $fieldName, $attributes, $extra);

    return $buffer;
  }

  /**
   * checkbox フィールドを生成します。
   * 個々のタグに付加される ID 属性の名前は、"フィールド名 + '_' + 要素値" の形式となります。
   *
   * @param string $fieldName フィールド名。'.' (ピリオド) を含む名前は連想配列名として扱われる。
   * @param array $options {@link inputRadios()} メソッドを参照。
   * @param mixed $attributes タグに追加する属性。{@link Delta_HTMLHelper::link()} メソッドを参照。
   * @param mixed $extra {@link inputText()}、及び {@link inputRadios()} メソッドを参照。
   * @return string 生成したタグを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function inputCheckboxes($fieldName,
    $options,
    $attributes = array(),
    $extra = array())
  {
    $buffer = $this->buildMultipleFields('checkbox', $fieldName, $options, $attributes, $extra);
    $buffer .= $this->buildCheckboxSendField($fieldName);

    $this->decorateControl($buffer, 'checkbox', $fieldName, $attributes, $extra);

    return $buffer;
  }

  /**
   * @param string $fieldName
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function buildCheckboxSendField($fieldName)
  {
    $hiddenFieldName = '_' . $fieldName;

    $attributes = array();
    $attributes['value'] = 'on';
    $attributes['id'] = $hiddenFieldName;
    $tag = $this->inputHidden($hiddenFieldName, $attributes);

    return $tag;
  }

  /**
   * select フィールドを生成します。
   *
   * @param string $fieldName フィールド名。'.' (ピリオド) を含む名前は連想配列名として扱われる。
   * @param array $options {@link inputRadios()} メソッドを参照。
   *   optgroup タグを生成したい場合は連想配列形式で値を指定する。
   *   <code>
   *   $array = array(
   *     'OS' => array(
   *       1 => 'Windows',
   *       2 => 'Mac',
   *       3 => 'Linux'
   *     ),
   *     'Database' => array(
   *       1 => 'MySQL',
   *       2 => 'PostgreSQL',
   *       3 => 'SQLite'
   *     ),
   *     'Programming' => 'PHP'
   *   );
   *   </code>
   * @param mixed $attributes タグに追加する属性。{@link Delta_HTMLHelper::link()} メソッドを参照。
   * @param mixed $extra {@link inputText()}、及び {@link inputRadios()} メソッドを参照。
   * @return string 生成したタグを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function select($fieldName,
    $options,
    $attributes = array(),
    $extra = array())
  {
    $defaults = array();
    $defaults['name'] = $fieldName;
    $attributes = self::constructParameters($attributes, $defaults);

    // 複数選択を許可するかどうか
    $multiple = FALSE;

    if (array_key_exists('multiple', $attributes)) {
      $attributes['name'] = $fieldName . '[]';
      $multiple = TRUE;
    }

    $buffer = sprintf("<select%s>\n%s\n</select>\n",
      self::buildTagAttribute($attributes, FALSE),
      $this->buildMultipleFields('select', $fieldName, $options, $attributes, $extra));

    // hidden タグの追加
    if ($multiple) {
      $hiddenFieldName = '_' . $fieldName;
      $hiddenAttributes = array('id' => $hiddenFieldName);
      $buffer .= $this->inputHidden($hiddenFieldName, $hiddenAttributes);
    }

    $this->decorateControl($buffer, 'select', $fieldName, $attributes, $extra);

    return $buffer;
  }

  /**
   * 数値を選択する select フィールドを生成します。
   *
   * @param string $fieldName フィールド名。'.' (ピリオド) を含む名前は連想配列名として扱われる。
   * @param array $options 数値の範囲。
   *   - from: 数値の範囲開始値。
   *   - to: 数値の範囲終了値。
   *   - interval: 数値の間隔。既定値は 1。
   * @param mixed $attributes タグに追加する属性。{@link Delta_HTMLHelper::link()} メソッドを参照。
   * @param mixed $extra {@link inputText()}、及び {@link inputRadios()} メソッドを参照。
   * @return string 生成したタグを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function selectNumber($fieldName, $options, $attributes = array(), $extra = array())
  {
    $array = array();
    $options = self::constructParameters($options);

    $from = Delta_ArrayUtils::find($options, 'from', 0);
    $to = Delta_ArrayUtils::find($options, 'to', 0);
    $interval = Delta_ArrayUtils::find($options, 'interval', 1);

    for ($i = $from; $i <= $to;) {
      $array[$i] = $i;
      $i += $interval;
    }

    return $this->select($fieldName, $array, $attributes, $extra);
  }

  /**
   * 日付リストを生成します。
   * 生成されるフィールドは、年、月、日から構成される 3 つのセレクトボックスです。
   *
   * @param mixed $conditions 日付リストの出力オプション。
   *   - time: 基準となる時刻。{@link strtotime} 関数が解釈可能な英文形式の日付、または UNIX タイムスタンプを指定可能。
   *   - fieldPrefix: 各フィールド名に追加する接頭辞。既定値は 'date'。
   *        例えば年フィールドは 'date.year' (date['year']) となる。
   *   - fieldAssoc: 各フィールド名を連想配列の形式とするかどうか。既定値は TRUE。(連想配列形式とする)
   *       FALSE 指定時はフィールド名の 'fieldPrefix' に続けて 'Year'、'Month'、'Day' が追加される。
   *   - yearAsText: TRUE を指定した場合、年をフィールドではなくテキストとして表示する。
   *   - yearEmpty: 年リストの初めの項目に指定した文字列を追加する。(文字列の値は '' となる)
   *   - yearFormat: 年の表示フォーマット。既定値は 'Y'。指定可能なフォーマットは {@link date()} 関数を参照。
   *   - yearValueFormat: 年の値フォーマット。既定値は 'Y'。指定可能なフォーマットは {@link date()} 関数を参照。
   *   - startYear: 年リストの開始年、または現在の年からの相対年数を指定可能。
   *       例えば '-3' であれば現在の年 - 3 年前が開始年となる。(+、- を指定する際は文字列型での指定が必要)
   *   - endYear: 年リストの終了年、または現在の年からの相対年数を指定可能。
   *   - displayYears: 年リストの表示指定。FALSE を指定した場合は項目を出力しない。
   *   - reverseYears: 年リストを降順表示する。
   *   - monthEmpty: 月リストの初めの項目に指定した文字列を追加する。(文字列の値は '' となる)
   *   - monthFormat: 月の表示フォーマット。既定値は 'm'。指定可能なフォーマットは {@link date()} 関数を参照。
   *   - monthValueFormat: 月の値フォーマット。既定値は 'm'。指定可能なフォーマットは {@link date()} 関数を参照。
   *   - displayMonths: 月リストの表示指定。FALSE を指定した場合は項目を出力しない。
   *   - dayEmpty: 日リストの初めの項目に指定した文字列を追加する。(文字列の値は '' となる)
   *   - dayFormat: 日の表示フォーマット。既定値は 'd'。指定可能なフォーマットは {@link date()} 関数を参照。
   *   - dayValueFormat: 日の値フォーマット。既定値は 'd'。指定可能なフォーマットは {@link date()} 関数を参照。
   *   - displayDays: 日リストの表示指定。FALSE を指定した場合は項目を出力しない。
   *   - separator: フィールド間を区切るセパレータのリスト。セパレータ要素はエスケープされる。
   *       リストの要素数は最大 3。array('年', '月', '日') のような指定も可能。ヘルパ属性 'fieldSeparatorTag' の項も参照。
   *   - tagSeparator: フィールド間を区切るセパレータのリスト。セパレータ要素はエスケープされない。
   * @param mixed $attributes 各フィールドに共通して追加する属性。属性。{@link Delta_HTMLHelper::link()} メソッドを参照。
   * @param mixed $extra {@link inputText()} メソッドを参照。
   * @return string 生成したタグを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function selectDate($conditions = array(),
    $attributes = array(),
    $extra = array())
  {
    $conditions = parent::constructParameters($conditions);
    $attributes = parent::constructParameters($attributes);
    $extra = parent::constructParameters($extra);

    $time = Delta_ArrayUtils::find($conditions, 'time', time());
    $fieldPrefix = Delta_ArrayUtils::find($conditions, 'fieldPrefix', 'date');
    $fieldAssoc = Delta_ArrayUtils::find($conditions, 'fieldAssoc', TRUE);

    $yearAsText = Delta_ArrayUtils::find($conditions, 'yearAsText', FALSE);
    $yearEmpty = Delta_ArrayUtils::find($conditions, 'yearEmpty');
    $yearFormat = Delta_ArrayUtils::find($conditions, 'yearFormat', 'Y');
    $yearValueFormat = Delta_ArrayUtils::find($conditions, 'yearValueFormat', 'Y');

    // Delta_ArrayUtils::find() は戻り値が第 3 引数の型に変換されるため文字列型のデフォルト値を設定
    // ('+3' という指定が数値型で認識されると 3 年～と誤認されるため)
    $startYear = Delta_ArrayUtils::find($conditions, 'startYear', '1900'); // 文字列型、または数値型
    $endYear = Delta_ArrayUtils::find($conditions, 'endYear', date('Y')); // 文字列型、または数値型

    $displayYears = Delta_ArrayUtils::find($conditions, 'displayYears', TRUE);
    $reverseYears = Delta_ArrayUtils::find($conditions, 'reverseYears', TRUE);

    $monthEmpty = Delta_ArrayUtils::find($conditions, 'monthEmpty');
    $monthFormat = Delta_ArrayUtils::find($conditions, 'monthFormat', 'm');
    $monthValueFormat = Delta_ArrayUtils::find($conditions, 'monthValueFormat', 'm');
    $displayMonths = Delta_ArrayUtils::find($conditions, 'displayMonths', TRUE);

    $dayEmpty = Delta_ArrayUtils::find($conditions, 'dayEmpty');
    $dayFormat = Delta_ArrayUtils::find($conditions, 'dayFormat', 'd');
    $dayValueFormat = Delta_ArrayUtils::find($conditions, 'dayValueFormat', 'd');
    $displayDays = Delta_ArrayUtils::find($conditions, 'displayDays', TRUE);
    $separator = Delta_ArrayUtils::find($conditions, 'separator', array('/', '/'));
    $tagSeparator = Delta_ArrayUtils::find($conditions, 'tagSeparator');

    // セパレータの取得
    $j = sizeof($separator);
    $separators = array_fill(0, 3, NULL);

    for ($i = 0; $i < $j; $i++) {
      $separators[$i] = str_replace('\1', Delta_StringUtils::escape($separator[$i]), $this->_config->getString('fieldSeparatorTag'));
    }

    // タグセパレータの取得
    $j = sizeof($tagSeparator);

    for ($i = 0; $i < $j; $i++) {
      $separators[$i] = str_replace('\1', $tagSeparator[$i], $this->_config->getString('fieldSeparatorTag'));
    }

    $buffer = NULL;

    if (!is_numeric($time)) {
      $time = strtotime($time);
    }

    // 個々のフィールドは <div> タグで括らない
    $unitExtra = $extra;
    $unitExtra['decorate'] = FALSE;

    // 年リストの生成
    if ($displayYears) {
      if ($yearAsText) {
        $buffer = date('Y', $time) . "\n";

      } else {
        $options = array();

        if ($yearEmpty !== NULL) {
          $options[''] = $yearEmpty;
        }

        $c = substr($startYear, 0, 1);

        if ($c == '-') {
          $startYear = date('Y', $time) - substr($startYear, 1);
        } else if ($c == '+') {
          $startYear = date('Y', $time) + substr($startYear, 1);
        }

        $c = substr($endYear, 0, 1);

        if ($c == '-') {
          $endYear = date('Y', $time) - substr($endYear, 1);
        } else if ($c == '+') {
          $endYear = date('Y', $time) + substr($endYear, 1);
        }

        if ($startYear > $endYear) {
          $endYear = $startYear;
        }

        if ($reverseYears) {
          $i = $endYear;
        } else {
          $i = $startYear;
        }

        while (TRUE) {
          $work = mktime(0, 0, 0, 1, 1, $i);

          $value = Delta_DateUtils::date($yearValueFormat, $work);
          $output = Delta_DateUtils::date($yearFormat, $work);

          $options[$value] = $output;

          if ($reverseYears) {
            if ($i <= $startYear) {
              break;
            }

            $i--;

          } else {
            if ($i >= $endYear) {
              break;
            }

            $i++;
          }
        }

        if ($fieldAssoc) {
          $fieldNameYear = $fieldPrefix . '.year';
        } else {
          $fieldNameYear = $fieldPrefix . 'Year';
        }

        // 年リストの属性
        $unitAttributes = $attributes;

        if (isset($unitAttributes['id'])) {
          $unitAttributes['id'] = $unitAttributes['id'] . '_year';
        }

        if ($this->_form->hasName($fieldNameYear)) {
          $selected = $this->_form->get($fieldNameYear);
        } else {
          $selected = date('Y', $time);
        }

        $unitExtra['selected'] = $selected;
        $buffer = $this->select($fieldNameYear, $options, $unitAttributes, $unitExtra) . $separators[0] . "\n";
      }
    }

    // 月リストの生成
    if ($displayMonths) {
      $options = array();

      if ($monthEmpty !== NULL) {
        $options[''] = $monthEmpty;
      }

      $year = date('Y');

      for ($i = 1; $i <= 12; $i++) {
        $work = mktime(0, 0, 0, $i, 1);

        $value = Delta_DateUtils::date($monthValueFormat, $work);
        $output = Delta_DateUtils::date($monthFormat, $work);

        $options[$value] = $output;
      }

      if ($fieldAssoc) {
        $fieldNameMonth = $fieldPrefix . '.month';
      } else {
        $fieldNameMonth = $fieldPrefix . 'Month';
      }

      // 月リストの属性
      $unitAttributes = $attributes;

      if (isset($unitAttributes['id'])) {
        $unitAttributes['id'] = $unitAttributes['id'] . '_month';
      }

      if ($this->_form->hasName($fieldNameMonth)) {
        $selected = $this->_form->get($fieldNameMonth);
      } else {
        $selected = Delta_DateUtils::date($monthValueFormat, $time);
      }

      $unitExtra['selected'] = $selected;
      $buffer .= $this->select($fieldNameMonth, $options, $unitAttributes, $unitExtra) . $separators[1] . "\n";
    }

    // 日リストの生成
    if ($displayDays) {
      $options = array();

      if ($dayEmpty !== NULL) {
        $options[''] = $dayEmpty;
      }

      for ($i = 1; $i <= 31; $i++) {
        $work = mktime(0, 0, 0, 0, $i);

        $value = Delta_DateUtils::date($dayValueFormat, $work);
        $output = Delta_DateUtils::date($dayFormat, $work);

        $options[$value] = $output;
      }

      if ($fieldAssoc) {
        $fieldNameDay = $fieldPrefix . '.day';
      } else {
        $fieldNameDay = $fieldPrefix . 'Day';
      }

      // 日リストの属性
      $unitAttributes = $attributes;

      if (isset($unitAttributes['id'])) {
        $unitAttributes['id'] = $unitAttributes['id'] . '_day';
      }

      if ($this->_form->hasName($fieldNameDay)) {
        $selected = $this->_form->get($fieldNameDay);
      } else {
        $selected = Delta_DateUtils::date($dayValueFormat, $time);
      }

      $unitExtra['selected'] = $selected;
      $buffer .= $this->select($fieldNameDay, $options, $unitAttributes, $unitExtra) . $separators[2];
    }

    if ($fieldNameYear) {
      $this->decorateControlAppendError($buffer, $fieldNameYear, $extra);
    }

    if ($fieldNameMonth) {
      $this->decorateControlAppendError($buffer, $fieldNameMonth, $extra);
    }

    if ($fieldNameDay) {
      $this->decorateControlAppendError($buffer, $fieldNameDay, $extra);
    }

    $this->decorateControlAppendTitle($buffer, 'set', NULL, $attributes, $extra);
    $this->decorateControlAppendSet($buffer, $extra);

    return $buffer;
  }

  /**
   * submit フィールドを生成します。
   *
   * @param string $value ボタンに表示する文字列。
   * @param mixed $attributes タグに追加する属性。{@link Delta_HTMLHelper::link()} メソッドを参照。
   * @param mixed $extra {@link inputText()} メソッドを参照。
   * @return string 生成したタグを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function inputSubmit($value = NULL, $attributes = array(), $extra = array())
  {
    $defaults = array();

    if ($value !== NULL) {
      $defaults['value'] = $value;
    }

    $attributes = self::constructParameters($attributes, $defaults);

    $buffer = $this->buildInputField('submit', $attributes);
    $fieldName = Delta_ArrayUtils::find($attributes, 'name');

    $this->decorateControl($buffer, 'submit', $fieldName, $attributes, $extra);

    return $buffer;
  }

  /**
   * reset フィールドを生成します。
   *
   * @param string $value ボタンに表示する文字列。
   * @param mixed $attributes タグに追加する属性。{@link Delta_HTMLHelper::link()} メソッドを参照。
   * @param mixed $extra {@link inputText()} メソッドを参照。
   * @return string 生成したタグを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function inputReset($value = NULL, $attributes = array(), $extra = array())
  {
    $defaults = array();

    if ($value !== NULL) {
      $defaults['value'] = $value;
    }

    $attributes = self::constructParameters($attributes, $defaults);

    $buffer = $this->buildInputField('reset', $attributes);
    $fieldName = Delta_ArrayUtils::find($attributes, 'name');

    $this->decorateControl($buffer, 'reset', $fieldName, $attributes, $extra);

    return $buffer;
  }

  /**
   * button フィールドを生成します。
   *
   * @param string $value ボタンに表示する文字列。
   * @param mixed $attributes タグに追加する属性。{@link Delta_HTMLHelper::link()} メソッドを参照。
   * @param mixed $extra {@link inputText()} メソッドを参照。
   * @return string 生成したタグを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function inputButton($value, $attributes = array(), $extra = array())
  {
    $defaults = array();
    $defaults['value'] = $value;

    $attributes = self::constructParameters($attributes, $defaults);

    $buffer = $this->buildInputField('button', $attributes);
    $fieldName = Delta_ArrayUtils::find($attributes, 'name');

    $this->decorateControl($buffer, 'button', $fieldName, $attributes, $extra);

    return $buffer;
  }

  /**
   * file フィールドを生成します。
   *
   * @param string $fieldName フィールド名。'.' (ピリオド) を含む名前は連想配列名として扱われる。
   * @param mixed $attributes タグに追加する属性。{@link Delta_HTMLHelper::link()} メソッドを参照。
   * @param mixed $extra {@link inputText()} メソッドを参照。
   * @return string 生成したタグを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function inputFile($fieldName, $attributes = array(), $extra = array())
  {
    $defaults = array();
    $defaults['name'] = $fieldName;
    $defaults['value'] = '';

    $attributes = self::constructParameters($attributes, $defaults);
    $buffer = $this->buildInputField('file', $attributes);

    $this->decorateControl($buffer, 'file', $fieldName, $attributes, $extra);

    return $buffer;
  }

  /**
   * hidden フィールドを生成します。
   *
   * @param string $fieldName フィールド名。'.' (ピリオド) を含む名前は連想配列名として扱われる。
   * @param mixed $attributes タグに追加する属性。{@link Delta_HTMLHelper::link()} メソッドを参照。
   * @return string 生成したタグを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function inputHidden($fieldName, $attributes = array())
  {
    $defaults = array();
    $defaults['name'] = $fieldName;
    $defaults['value'] = $this->_form->get($fieldName);

    $attributes = self::constructParameters($attributes, $defaults);
    $buffer = $this->buildInputField('hidden', $attributes);

    $this->decorateControl($buffer, 'hidden', $fieldName, $attributes, array('decorate' => FALSE));

    return $buffer;
  }

  /**
   * クライアントから送信されたフィールド名を元に同じ名前の hidden フィールドを生成します。
   * フィールドには送信された値が含まれます。
   * 対象フィールドが配列で構成される場合 (複数選択可能なチェックボックス等) は、送信されたパラメータの数だけフィールドを生成します。
   * 例えば '?foo[]=100&foo[]=200&foo[]=300' というリクエストが発生した場合、inputHiddens('foo') は 3 つの hidden フィールドを生成することになります。
   *
   * @param string $fieldName フィールド名。'.' (ピリオド) を含む名前は連想配列名として扱われる。
   * @param mixed $attributes タグに追加する属性。{@link Delta_HTMLHelper::link()} メソッドを参照。
   * @return string 生成したタグを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function inputHiddens($fieldName, $attributes = array())
  {
    $values = $this->_form->get($fieldName);
    $buffer = NULL;

    if (is_array($values)) {
      foreach ($values as $name => $value) {
        $assocName = $fieldName . '.' . $name;
        $buffer .= $this->inputHidden($assocName, $attributes);
      }

    } else {
      $buffer = $this->inputHidden($fieldName, $attributes);
    }

    return $buffer;
  }

  /**
   * POST (または GET) で送信されたデータを元に hidden フィールドを生成します。
   *
   * @return string 生成したタグを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function requestDataToInputHiddens()
  {
    $parameters = $this->_request->getParameters();
    unset($parameters['tokenId']);

    $buffer = $this->parameterToInputHiddens($parameters);

    return $buffer;
  }

  /**
   * 連想配列のデータを元に hidden フィールドを生成します。
   *
   * @param array $parameters 名前と値から構成される連想配列のリスト。
   * @return string 生成したタグを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function parameterToInputHiddens($parameters)
  {
    $buffer = NULL;

    if (is_array($parameters)) {
      foreach ($parameters as $name => $value) {
        $buffer .= $this->buildInputHiddens($name, $value);
      }
    }

    return $buffer;
  }

  /**
   * @param string $fieldName
   * @param string $fieldValue
   * @return buffer
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function buildInputHiddens($fieldName, $fieldValue)
  {
    $buffer = NULL;

    if (is_array($fieldValue)) {
      foreach ($fieldValue as $assocName => $assocValue) {
        $assocName = sprintf('%s.%s', $fieldName, $assocName);
        $buffer .= $this->inputHidden($assocName, array('value' => $assocValue));
      }

    } else {
      $buffer .= $this->inputHidden($fieldName, array('value' => $fieldValue));
    }

    return $buffer;
  }

  /**
   * image フィールドを生成します。
   *
   * @param string $source {@link Delta_HTMLHelper::buildAssetPath()} メソッドを参照。
   * @param mixed $attributes タグに追加する属性。{@link Delta_HTMLHelper::link()} メソッドを参照。
   * @param mixed $extra タグの出力オプション。
   *     - absolute: イメージのパスを絶対パスに変換する場合は TRUE を指定。既定値は FALSE。
   *   その他の指定可能なオプションは {@link inputText()} メソッドを参照。
   * @return string 生成したタグを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function inputImage($source, $attributes = array(), $extra = array())
  {
    $extra = self::constructParameters($extra);
    $absolute = Delta_ArrayUtils::find($extra, 'absolute', FALSE);

    $html = $this->_currentView->getHelperManager()->getHelper('html');
    $imagePath = $html->buildAssetPath($source, 'image', array('absolute' => $absolute));

    $defaults = array();
    $defaults['src'] = $imagePath;
    $defaults['value'] = '';

    $attributes = self::constructParameters($attributes, $defaults);
    $buffer = $this->buildInputField('image', $attributes, $defaults);
    $fieldName = Delta_ArrayUtils::find($attributes, 'name');

    $this->decorateControl($buffer, 'image', $fieldName, $attributes, $extra);

    return $buffer;
  }

  /**
   * textarea フィールドを生成します。
   *
   * @param string $fieldName フィールド名。'.' (ピリオド) を含む名前は連想配列名として扱われる。
   * @param mixed $attributes タグに追加する属性。{@link Delta_HTMLHelper::link()} メソッドを参照。
   *   'value' を指定することでテキストエリアの初期値を指定することが可能。
   *   array('value' => 'default') は <textarea ...>default</textarea> といったタグを生成する。
   * @param mixed $extra {@link inputText()} メソッドを参照。
   * @return string 生成したタグを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function textarea($fieldName, $attributes = array(), $extra = array())
  {
    $defaults = array();
    $defaults['name'] = $fieldName;

    $value = NULL;
    $attributes = self::constructParameters($attributes, $defaults);

    if ($this->_form->hasName($fieldName)) {
      $value = Delta_StringUtils::escape($this->_form->get($fieldName));

    } else if (isset($attributes['value'])) {
      $value = Delta_StringUtils::escape($attributes['value']);
      unset($attributes['value']);
    }

    $buffer = sprintf("<textarea%s>%s</textarea>\n",
      self::buildTagAttribute($attributes, FALSE),
      $value);

    $this->decorateControl($buffer, 'textarea', $fieldName, $attributes, $extra);

    return $buffer;
  }

  /**
   * ラジオ、あるいはチェックボックスのリストを生成します。
   *
   * @param string $type 'radio'、'checkbox'、'select' のいずれかを指定。
   * @param string $fieldName フィールド名。
   * @param mixed $options リストを構成する連想配列。
   * @param mixed $attributes タグに追加する属性。{@link Delta_HTMLHelper::link()} メソッドを参照。
   * @param mixed $extra タグの出力オプション。
   *   - selected
   *   - fieldElementTag
   *   - separator
   *   - tagSeparator
   *   - single
   * @return string 生成したタグを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function buildMultipleFields($type, $fieldName, $options, $attributes, $extra)
  {
    $attributes = parent::constructParameters($attributes);
    $options = parent::constructParameters($options);
    $extra = parent::constructParameters($extra);

    // $options 配列が output (値)、values (キー) の連想配列で構成される場合は 1 つの配列に統合する
    if (isset($options['output']) && isset($options['values'])) {
      $output = self::constructParameters($options['output']);
      $values = self::constructParameters($options['values']);

      $outputSize = sizeof($output);
      $valueSize = sizeof($values);

      if ($outputSize && $valueSize) {
        // キーと値の個数がマッチしている場合
        if ($outputSize == $valueSize && $valueSize > 0) {
          $options = array_combine($values, $output);
        } else {
          $options = array();
        }
      }
    }

    // チェックボックスはフィールド名を強制送信する
    $hiddenOutput = FALSE;

    if ($type === 'checkbox') {
      $hiddenOutput = TRUE;
      $hiddenFieldName = '_' . $fieldName;
    }

    // フィールド要素のセパレータ
    $separator = Delta_ArrayUtils::find($extra, 'separator');

    if ($separator !== NULL) {
      $separator = "\n" . Delta_StringUtils::escape($separator) . "\n";
    } else {
      $separator = "\n";
    }

    $tagSeparator = Delta_ArrayUtils::find($extra, 'tagSeparator');

    if ($tagSeparator !== NULL) {
      $separator = $tagSeparator . "\n";
    }

    // フィールド要素を囲うタグ
    if (isset($extra['fieldElementTag'])) {
      $fieldElementTag = $extra['fieldElementTag'];
    } else {
      $fieldElementTag = $this->_config->get('fieldElementTag');
    }

    // 生成する要素数
    if (isset($extra['single'])) {
      $single = TRUE;
    } else {
      $single = FALSE;
    }

    // 生成するコントロールが既に送信済みの場合は選択値を取得
    $selected = NULL;

    // チェックボックス (フィールド名 foo) で何も選択せずに送信すると、foo パラメータは送信されず、リストが送信されたことを示す _foo パラメータが追加される
    if ($this->_request->hasParameter($fieldName) || ($hiddenOutput && $this->_request->hasParameter($hiddenFieldName))) {
      $selected = $this->_request->getParameter($fieldName);

    } else if ($this->_form->hasName($fieldName)) {
      $selected = $this->_form->get($fieldName);

    // リクエスト (またはフォームにセット) されたデフォルト値がない、またはリストが未送信の場合に限りヘルパに渡されたデフォルト値を設定する
    } else if ($selected === NULL) {
      $selected = Delta_ArrayUtils::find($extra, 'selected');
    }

    if (!is_array($selected)) {
      $selected = array($selected);
    }

    $iterator = function($options)
      use ($type, $fieldName, $selected, $attributes, $separator, $fieldElementTag, $single) {
      $buffer = NULL;

      $j = sizeof($options);
      $i = 0;

      foreach ($options as $optionValue => $optionLabel) {
        $optionValue = (string) $optionValue;
        $optionLabel = Delta_StringUtils::escape($optionLabel);

        $defaults = array();
        $defaults['value'] = $optionValue;

        // select ボックスの項目生成
        if ($type == 'select') {
          if (in_array($optionValue, $selected)) {
            $defaults['selected'] = 'selected';
          }

          $optionValue = Delta_StringUtils::escape($optionValue);
          $buffer .= sprintf("<option%s>%s</option>\n",
             self::buildTagAttribute($defaults, FALSE),
             $optionLabel);

        // checkbox、radio の項目生成
        } else {
          $defaults['name'] = $fieldName;

          if (in_array($optionValue, $selected)) {
            $defaults['checked'] = 'checked';
          }

          $itemAttributes = self::constructParameters($attributes, $defaults);

          if ($type == 'checkbox') {
            if ($single) {
              $itemAttributes['name'] = $itemAttributes['name'];
            } else {
              $itemAttributes['name'] = $itemAttributes['name'] . '[]';
            }
          }

          // チェックボックス要素の ID は 'フィールド名 + 要素値' の文字列を使う
          if ($single) {
            $itemAttributes['id'] = $itemAttributes['id'];
          } else {
            $itemAttributes['id'] = $itemAttributes['id'] . '_' . $optionValue;
          }

          if (Delta_StringUtils::nullOrEmpty($optionLabel)) {
            $elementTag = sprintf("<input type=\"%s\"%s>\n",
              $type,
              self::buildTagAttribute($itemAttributes));

          } else {
            // <label><input type="..." /></label> の形式は古いブラウザ (Internet Explorer 6 など) が対応していないので使用しない
            $elementTag = sprintf("<input type=\"%s\"%s>\n<label for=\"%s\">%s</label>\n",
              $type,
              self::buildTagAttribute($itemAttributes),
              $itemAttributes['id'],
              $optionLabel);
          }

          $buffer .= str_replace('\1', $elementTag, $fieldElementTag) . "\n";

          // 生成する要素数が制限されてる場合はループを抜ける
          if ($single) {
            break;
          }
        }

        $i++;

        if ($i != $j) {
          $buffer .= $separator;
        }
      }

      return $buffer;
    };

    // リスト要素の生成
    if ($type === 'select') {
      $buffer = NULL;

      foreach($options as $elementName => $elementValue) {
        if (is_array($elementValue)) {
          $buffer .= sprintf("<optgroup label=\"%s\">\n%s</optgroup>\n",
            $elementName,
            $iterator($elementValue));

        } else {
          $buffer .= $iterator(array($elementName => $elementValue));
        }
      }

    } else {
      $buffer = $iterator($options);
    }

    if ($type === 'checkbox' || $type === 'radio') {
      $buffer .= "\n";
    }

    return $buffer;
  }

  /**
   * input フィールドを生成します。
   *
   * @param string $type 生成する input タイプを指定。
   * @param mixed $attributes タグに追加する属性。{@link Delta_HTMLHelper::link()} メソッドを参照。
   * @return string 生成したタグを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function buildInputField($type, $attributes)
  {
    $buffer = sprintf("<input type=\"%s\"%s>\n",
      $type,
      self::buildTagAttribute($attributes));

    return $buffer;
  }

  /**
   * '.' を含むフィールド名を PHP が解析可能な連想配列形式に変換します。
   *
   * @param string $fieldName 変換対象のフィールド名。
   * @return string 連想配列形式のフィールド名を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private static function convertFieldNameToAssoc($fieldName)
  {
    // フィールド名に '.' が含まれる場合は '[]' 形式に変換
    if (strpos($fieldName, '.') !== FALSE) {
      $array = explode('.', $fieldName);
      $buffer = $array[0];
      next($array);

      while (list($index, $key) = each($array)) {
        $buffer .= '[' . $key . ']';
      }

      return $buffer;
    }

    return $fieldName;
  }

  /**
   * @param array $attributes
   * @param array $defaults
   * @return array
   * @see Delta_Helper::constructParameters()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  protected static function constructParameters($attributes, $defaults = array())
  {
    $attributes = parent::constructParameters($attributes, $defaults);

    // ID 属性が未定義の場合は追加
    if (isset($attributes['name'])) {
      if (!isset($attributes['id'])) {
        $method = array(get_called_class(), 'buildId');
        $attributes['id'] = forward_static_call($method, $attributes['name']);
      }

      $attributes['name'] = self::convertFieldNameToAssoc($attributes['name']);
    }

    return $attributes;
  }

  /**
   * フィールドに付加する ID を生成します。
   * このメソッドはタグ出力メソッド (例えば {@link inputText()} の attributes 引数で ID が指定されなかった場合に内部的にコールされます。
   *
   * @param string $fieldName フィールド名。
   * @return string フィールドの ID 名を返します。
   *   デフォルトの動作はフィールド名をそのまま ID 文字列として返します。
   *   ID 形式を変更したい場合はメソッドをオーバーライドして下さい。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  protected static function buildId($fieldName)
  {
    return $fieldName;
  }
}
