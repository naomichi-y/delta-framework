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
      if (!$("#create_type_dao").attr("checked")) {
        $("#row_base_dao_class_name").hide();
      }

      if (!$("#create_type_entity").attr("checked")) {
        $("#row_base_entity_class_name").hide();
      }

      $("#namespace").change(function() {
        this.form.action = '/cpanel/generateDAOForm.do';
        this.form.submit();
      });

      $("#create_type_dao").click(function() {
        $("#row_base_dao_class_name").fadeToggle();
      });

      $("#create_type_entity").click(function() {
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
      <p class="right"><?php echo $html->link('戻る', array('route' => 'cpanelRootRoute')) ?></p>
      <?php echo $form->start('generate') ?>
        <?php echo $form->errorFields() ?>
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
              <?php echo $form->select('tables', array('output' => $tables, 'values' => $tables), array('multiple' => 'multiple', 'size' => 10)) ?>
            </div>
          </li>
          <li>
            <div class="data-label">生成クラス</div>
            <div class="data-content">
              <?php echo $form->inputCheckboxes('create_type', $create_type) ?>
            </div>
          </li>
          <li id="row_base_dao_class_name">
            <div class="data-label">DAO 基底クラス</div>
            <div class="data-content">
              <?php echo $form->inputText('base_dao_name') ?>
            </div>
          </li>
          <li id="row_base_entity_class_name">
            <div class="data-label">エンティティ基底クラス</div>
            <div class="data-content">
              <?php echo $form->inputText('base_entity_name') ?>
            </div>
          </li>
        </ul>
        <p class="center"><?php echo $form->inputSubmit('作成', array('class' => 'btn')) ?></p>
      <?php echo $form->close() ?>
    </div>
    <footer>
      <?php $html->includeView('/includes/footer') ?>
    </footer>
  </body>
</html>
