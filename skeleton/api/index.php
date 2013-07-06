<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta charset="UTF-8" />
    <title><?php echo $title ?></title>
    <link rel="stylesheet" type="text/css" href="assets/css/base.css" />
    <link rel="stylesheet" type="text/css" href="assets/css/jquery.treeview.css" />
    <link rel="apple-touch-icon-precomposed" href="http://delta-framework.org/wp-content/themes/delta/images/apple_touch_icon.png" />
    <script src="assets/js/jquery.min.js" type="text/javascript"></script>
    <script src="assets/js/jquery.cookie.js" type="text/javascript"></script>
    <script src="assets/js/jquery.treeview.js" type="text/javascript"></script>
    <script src="assets/js/delta_api.js" type="text/javascript"></script>
    <!--[if lt IE 9]>
    <script src="assets/js/html5shiv.js"></script>
    <![endif]-->
  </head>
  <body>
    <header>
      <div class="row">
        <div class="half">
          <a href="index.html"><img src="assets/images/logo.png" alt="delta" /></a>
        </div>
        <div class="half">
          <!--#include virtual="/global_assets/content/navi.php" -->
        </div>
      </div>
      <h1 id="top"><?php echo $title ?></h1>
    </header>
    <div id="contents">
      <div class="row">
        <!-- quarter -->
        <div class="quarter">
          <?php echo $menuTag ?>
        </div>
        <!-- /quarter -->
        <!-- threequarter -->
        <div class="threequarter">
          <article>
            <h2>All packages</h2>
            <p>
            <?php foreach ($menus as $package => $names): ?>
              <?php echo $html->link($package, '#package_' . $package) ?>
            <?php endforeach ?>
            </p>
            <table summary="Package list">
              <colgroup>
                <col width="20%" />
                <col width="25%" />
                <col width="55%" />
              </colgroup>
              <tr>
                <th>Package</th>
                <th>Name</th>
                <th>Summary</th>
              </tr>
              <?php foreach ($menus as $package => $names): ?>
                <?php $name = key($names) ?>
                <tr>
                  <td rowspan="<?php echo sizeof($names) ?>" id="package_<?php echo $package ?>"><?php echo $package ?></td>
                  <td><?php echo $html->link($name, $relativeAPIPath . $names[$name]['anchor']) ?></td>
                  <td>
                    <?php if (isset($names[$name]['summary'])): ?>
                    <?php echo $document->decorateText($names[$name]['summary']) ?>
                    <?php endif ?>
                  </td>
                </tr>
                <?php next($names) ?>
                <?php while (list($name, $attributes) = each($names)): ?>
                <tr>
                  <td><?php echo $html->link($name, $relativeAPIPath . $attributes['anchor']) ?></td>
                  <td>
                    <?php if (isset($attributes['summary'])): ?>
                    <?php echo $document->decorateText($attributes['summary']) ?>
                    <?php endif ?>
                  </td>
                </tr>
                <?php endwhile ?>
              <?php endforeach ?>
            </table>
            <p class="right"><a href="#top">Top</a></p>
          </article>
        </div>
        <!-- /threequarter -->
      </div>
      <!-- /row -->
    </div>
    <!-- /contents -->
    <?php echo $html->includeTemplate('includes/footer') ?>
  </body>
</html>
