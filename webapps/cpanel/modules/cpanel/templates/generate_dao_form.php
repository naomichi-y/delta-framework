<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="Content-Style-Type" content="text/css" />
    <meta http-equiv="Content-Script-Type" content="text/javascript" />
    <title>MCP : DAO ジェネレータ</title>
    <?php echo $html->includeCSS('/assets/base/delta/css/base.css') ?>
    <?php echo $html->includeJS('/assets/base/jquery-ui-1.8.16.custom/js/jquery-1.6.2.min.js') ?>
    <script type="text/javascript">
    <!--
    $(document).ready(function(){
      if (!$("#createType_dao").attr("checked")) {
        $("#row_base_dao_class_name").hide();
      }

      if (!$("#createType_entity").attr("checked")) {
        $("#row_base_entity_class_name").hide();
      }

      $("#namespace").change(function() {
        this.form.action = '/cpanel/generateDAOForm.do';
        this.form.submit();
      });

      $("#createType_dao").click(function() {
        $("#row_base_dao_class_name").fadeToggle();
      });

      $("#createType_entity").click(function() {
        $("#row_base_entity_class_name").fadeToggle();
      });
    });
    -->
    </script>
  </head>
  <body>
    <header>
      <?php $html->includeTemplate('includes/header'); ?>
      <h1>delta control panel : DAO ジェネレータ</h1>
    </header>
    <div id="contents">
      <p class="right"><?php echo $html->link('戻る', 'Home') ?></p>
      <?php if ($html->hasError()): ?>
        <?php echo $html->errors() ?>
      <?php else: ?>
        <?php echo $html->errors() ?>
        <?php echo $form->start('GenerateDAO') ?>
          <ul class="data">
            <li>
              <div class="data_label">参照データベース</div>
              <div class="data_content">
                <?php echo $form->select('namespace', $namespaceList) ?>
              </div>
            </li>
            <li>
              <div class="data_label">対象テーブル</div>
              <div class="data_content">
                <?php echo $form->select('tables', array('output' => $tables, 'values' => $tables), array('multiple' => 'multiple', 'size' => 10), array('error' => FALSE)) ?>
              </div>
            </li>
            <li>
              <div class="data_label">生成クラス</div>
              <div class="data_content">
                <?php echo $form->inputCheckboxes('createType', $createType, NULL, array('error' => FALSE)) ?>
              </div>
            </li>
            <li id="row_base_dao_class_name">
              <div class="data_label">DAO 基底クラス</div>
              <div class="data_content">
                <?php echo $form->inputText('baseDAOClassName') ?>
              </div>
            </li>
            <li id="row_base_entity_class_name">
              <div class="data_label">エンティティ基底クラス</div>
              <div class="data_content">
                <?php echo $form->inputText('baseEntityClassName') ?>
              </div>
            </li>
          </ul>
          <p><?php echo $form->inputSubmit('作成', array('class' => 'btn')) ?></p>
        <?php echo $form->close() ?>
      <?php endif ?>
    </div>
    <footer>
      <?php $html->includeTemplate('includes/footer') ?>
    </footer>
  </body>
</html>
