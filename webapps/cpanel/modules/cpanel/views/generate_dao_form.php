<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta charset="UTF-8" />
    <title>DCP : DAO ジェネレータ</title>
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
    <!--[if lt IE 9]>
    <script src="/assets/js/html5shiv.js"></script>
    <![endif]-->
  </head>
  <body>
    <header>
      <?php $html->includeView('/includes/header'); ?>
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
              <div class="data-label">参照データベース</div>
              <div class="data-content">
                <?php echo $form->select('namespace', $namespaceList) ?>
              </div>
            </li>
            <li>
              <div class="data-label">対象テーブル</div>
              <div class="data-content">
                <?php echo $form->select('tables', array('output' => $tables, 'values' => $tables), array('multiple' => 'multiple', 'size' => 10), array('error' => FALSE)) ?>
              </div>
            </li>
            <li>
              <div class="data-label">生成クラス</div>
              <div class="data-content">
                <?php echo $form->inputCheckboxes('createType', $createType, NULL, array('error' => FALSE)) ?>
              </div>
            </li>
            <li id="row_base_dao_class_name">
              <div class="data-label">DAO 基底クラス</div>
              <div class="data-content">
                <?php echo $form->inputText('baseDAOClassName') ?>
              </div>
            </li>
            <li id="row_base_entity_class_name">
              <div class="data-label">エンティティ基底クラス</div>
              <div class="data-content">
                <?php echo $form->inputText('baseEntityClassName') ?>
              </div>
            </li>
          </ul>
          <p class="center"><?php echo $form->inputSubmit('作成', array('class' => 'btn')) ?></p>
        <?php echo $form->close() ?>
      <?php endif ?>
    </div>
    <footer>
      <?php $html->includeView('/includes/footer') ?>
    </footer>
  </body>
</html>
