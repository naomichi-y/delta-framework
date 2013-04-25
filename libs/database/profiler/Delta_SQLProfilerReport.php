<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package database.profiler
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * {@link Delta_SQLProfiler} でプロファイルされる SQL の実行レポートオブジェクトです。
 *
 * @since 1.1
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package database.profiler
 */

class Delta_SQLProfilerReport extends Delta_Object
{
  /**
   * ステートメントタイプ定数。(SELECT クエリ)
   */
  const STATEMENT_TYPE_SELECT = 'select';

  /**
   * ステートメントタイプ定数。(INSERT クエリ)
   */
  const STATEMENT_TYPE_INSERT = 'insert';

  /**
   * ステートメントタイプ定数。(UPDATE クエリ)
   */
  const STATEMENT_TYPE_UPDATE = 'update';

  /**
   * ステートメントタイプ定数。(DELETE クエリ)
   */
  const STATEMENT_TYPE_DELETE = 'delete';

  /**
   * ステートメントタイプ定数。(その他のクエリ)
   */
  const STATEMENT_TYPE_OTHER = 'other';

  /**
   * データソース名。
   * @var string
   */
  public $dsn;

  /**
   * 実行モジュール名。
   * @var string
   */
  public $moduleName;

  /**
   * 実行アクション名。
   * @var string
   */
  public $actionName;

  /**
   * 実行コマンド名。
   * @var string
   */
  public $commandName;

  /**
   * ステートメントタイプ。
   * @var string
   */
  public $statementType;

  /**
   * 実行ステートメント。
   * @var string
   */
  public $statement;

  /**
   * 実行プリペアードステートメント。
   * @var string
   */
  public $preparedStatement;

  /**
   * ステートメントを一意に識別するハッシュ値。
   * @var string
   */
  public $statementHash;

  /**
   * ステートメントの実行時間。
   * @var float
   */
  public $time;

  /**
   * ステートメントが定義されたクラス名。
   * @var string
   */
  public $className;

  /**
   * ステートメントが定義されたファイルパス。
   * @var string
   */
  public $filePath;

  /**
   * ステートメントが定義された関数 (メソッド) 名。
   * @var string
   */
  public $methodName;

  /**
   * ステートメントが定義された行数。
   * @var int
   */
  public $line;
}
