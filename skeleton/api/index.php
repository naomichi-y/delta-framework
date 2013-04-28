<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="Content-Style-Type" content="text/css" />
    <meta http-equiv="Content-Script-Type" content="text/javascript" />
    <title><?php echo $title ?></title>
    <link rel="stylesheet" type="text/css" href="assets/css/base.css" />
    <link rel="stylesheet" type="text/css" href="assets/css/jquery.treeview.css" />
    <script src="assets/js/jquery.min.js" type="text/javascript"></script>
    <script src="assets/js/jquery.cookie.js" type="text/javascript"></script>
    <script src="assets/js/jquery.treeview.js" type="text/javascript"></script>
    <script src="assets/js/delta_api.js" type="text/javascript"></script>
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
