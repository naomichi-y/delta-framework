<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller.filter
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * アプリケーション全体 (あるいは特定のモジュール) をメンテナンスモードに移行します。
 * メンテナンスモードが有効な場合、クライアントがリクエストした当該アクションは実行されず、メンテナンスページが出力されるようになります。
 * メンテナンスページのレイアウトをカスタマイズする場合は、アプリケーションディレクトリ下の templates/html/maintenance.php ファイルを編集して下さい。
 *
 * global_filters.yml の設定例:
 * <code>
 * {フィルタ ID}:
 *   # フィルタクラス名。
 *   class: Delta_MaintenanceFilter
 *
 *   # TRUE を指定した場合、定期メンテナンス設定状態に関わらず強制的にメンテナンスページを表示する。
 *   force: FALSE
 *
 *   # 定期メンテナンスのスケジュール属性。(オプション)
 *   periodic:
 *     # 定期メンテナンスの実行タイプを指定。
 *     #   - daily: 毎日実行。
 *     #   - weekly: target で指定した曜日に実行。0 を日曜とし、6 (土曜) まで指定可能。
 *     #   - day: target で指定した日をメンテナンス日とする。1 を 1 日とし、31 まで指定可能。-1 は月末日を表す特別な値。
 *     type: daily
 *
 *     # type で weekly、day のいずれかを指定した場合は必須。'1, 2, 3...' のようにカンマ区切りで複数の値を指定することも可能。
 *     target:
 *
 *     # メンテナンスの開始時間 (24 時間表記)。'00:30' ('0030' のような指定も可能) であれば 0 時 30 分が開始時刻となる。
 *     beginTime:
 *
 *     # メンテナンスの終了時間 (24 時間表記)。'0130' ('01:30' のような指定も可能) であれば 1 時 30 分が終了時刻となる。
 *     endTime:
 * </code>
 * <i>メンテナンスフィルタが有効な場合、後に続く全てのフィルタは実行されないことに注意して下さい。</i>
 * <i>その他に指定可能な属性は {@link Delta_Filter} クラスを参照して下さい。</i>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller.filter
 */
class Delta_MaintenanceFilter extends Delta_Filter
{
  /**
   * メンテナンスが有効な場合はメンテナンスページを出力し、無効な場合は後に続く処理を続行します。
   *
   * @see Delta_Filter::doFilter()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function doFilter(Delta_FilterChain $chain)
  {
    if ($this->isServiceAvailable()) {
      $chain->filterChain();

    } else {
      $path = sprintf('%s%shtml%smaintenance.php',
        $this->getAppPathManager()->getTemplatesPath(),
        DIRECTORY_SEPARATOR,
        DIRECTORY_SEPARATOR);

      $view = $this->getView();
      $view->setTemplatePath($path);
      $view->execute();
    }
  }

  /**
   * 現在アプリケーションが利用可能な (メンテナンスではない) 状態であるかチェックします。
   * メンテナンスの有無を柔軟に設定したい場合は当メソッドを拡張した継承クラスを作成して下さい。
   *
   * @return bool アプリケーションが利用可能な状態であれば TRUE、メンテナンス状態であれば FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isServiceAvailable()
  {
    if ($this->_holder->getBoolean('force')) {
      return FALSE;
    }

    $periodic = $this->_holder->get('periodic');

    if ($periodic) {
      if (!$periodic->hasName('beginTime')) {
        throw new Delta_ConfigurationException('\'periodic.beginTime\' attribute is not defined.');
      }

      if (!$periodic->hasName('endTime')) {
        throw new Delta_ConfigurationException('\'periodic.endTime\' attribute is not defined.');
      }

      $currentTime = date('Hi');
      $type = $periodic->getString('type', 'daily');
      $isMaintenanceDay = FALSE;

      if ($type === 'daily') {
        $isMaintenanceDay = TRUE;

      } else {
        if (!$periodic->hasName('target')) {
          throw new Delta_ConfigurationException('\'periodic.target\' attribute is not defined.');
        }

        $targets = array_map('convert_type', explode(',', $periodic->getString('target')));

        if ($type === 'weekly') {
          if (in_array(date('w'), $targets)) {
            $isMaintenanceDay = TRUE;
          }

        } else if ($type == 'day') {
          $day = date('j');

          if (in_array($day, $targets) || (in_array(-1, $targets) && $day == date('t'))) {
            $isMaintenanceDay = TRUE;
          }

        } else {
          $message = sprintf('\'type\' attribute value is illegal. [%s]', $type);
          throw new Delta_ConfigurationException($message);
        }
      }

      if ($isMaintenanceDay) {
        $beginTime = str_replace(':', '', $periodic->getString('beginTime'));
        $endTime = str_replace(':', '', $periodic->getString('endTime'));

        // メンテナンス時間の指定が 23:00～01:00 のような指定の場合
        if ($beginTime > $endTime) {
          // 01:00 は 24 足して 25:00 の扱いとする
          $currentTime = substr($currentTime, 0, 2) . substr($currentTime, 2);
          $endTime = (substr($endTime, 0, 2) + 24) . substr($endTime, 2);
        }

        if ($beginTime <= $currentTime && $currentTime <= $endTime) {
          return FALSE;
        }
      }
    }

    return TRUE;
  }
}
