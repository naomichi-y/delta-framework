<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title><?php echo $title ?> - <?php echo $file['file']['name'] ?></title>
    <link rel="stylesheet" type="text/css" href="../../assets/css/base.css" />
    <link rel="stylesheet" type="text/css" href="../../assets/css/jquery.treeview.css" />
    <script src="../../assets/js/jquery.min.js" type="text/javascript"></script>
    <script src="../../assets/js/jquery.cookie.js" type="text/javascript"></script>
    <script src="../../assets/js/jquery.treeview.js" type="text/javascript"></script>
    <script src="../../assets/js/delta_api.js" type="text/javascript"></script>
    <!--[if lt IE 9]>
    <script src="../../assets/js/html5shiv.js"></script>
    <![endif]-->
  </head>
  <body>
    <header>
      <div class="row">
        <div class="half">
          <a href="../../index.html"><img src="../../assets/images/logo.png" alt="delta" /></a>
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
            <h2><?php echo $file['file']['name'] ?></h2>
            <p class="right">
              <a href="#description">Description</a> |
              <a href="#defines">Defines</a> |
              <a href="#functions">Functions</a>
            </p>

            <h3 id="description">Description</h3>
            <?php if (isset($file['file']['document']['description'])): ?>
              <?php echo $document->decorateText($file['file']['document']['description']) ?>
            <?php else: ?>
              <p>この関数は現在のところ詳細な情報はありません。</p>
            <?php endif ?>
              <table summary="Description">
                <colgroup>
                  <col width="20%" />
                  <col width="80%" />
                </colgroup>
                <?php if (isset($file['file']['document']['tags'])): ?>
                  <?php foreach ($file['file']['document']['tags'] as $type => $tagAttribute): ?>
                    <?php if (is_array($tagAttribute)): ?>
                      <?php foreach ($tagAttribute as $description): ?>
                      <tr>
                        <th class="left"><?php echo ucfirst($type) ?></th>
                        <td><?php echo $document->decorateTag($type, $description) ?></td>
                      </tr>
                      <?php endforeach ?>
                    <?php else: ?>
                      <tr>
                        <th class="left"><?php echo ucfirst($type) ?></th>
                        <td><?php echo $document->decorateTag($type, $tagAttribute) ?></td>
                      </tr>
                    <?php endif ?>
                  <?php endforeach ?>
                <?php endif ?>
                <tr>
                  <th class="left">Source file</th>
                  <td><?php echo $file['file']['relativePath'] ?></td>
                </tr>
              </table>
            <p class="right"><a href="#top">To top</a></p>

            <h3 id="defines">Defines</h3>
            <?php if (isset($file['defines'])): ?>
              <table summary="Defines">
                <colgroup>
                  <col width="20%" />
                  <col width="80%" />
                </colgroup>
                <tr>
                  <th>Define</th>
                  <th>Summary</th>
                </tr>
                <?php foreach ($file['defines'] as $name => $define): ?>
                  <tr>
                    <td class="left"><?php echo $html->link($name, '#define_' . $name) ?></td>
                    <td class="left">
                      <?php if (isset($define['document']['summary'])): ?>
                        <?php echo $document->decorateText($define['document']['summary']) ?>
                      <?php endif ?>
                    </td>
                  </tr>
                <?php endforeach ?>
              </table>
            <?php else: ?>
              <p>定義されている定数はありません。</p>
            <?php endif ?>
            <p class="right"><a href="#top">To top</a></p>

            <h3 id="functions">Functions</h3>
            <?php if (isset($file['functions'])): ?>
              <table summary="Functions">
                <colgroup>
                  <col width="20%" />
                  <col width="80%" />
                </colgroup>
                <tr>
                  <th>Function</th>
                  <th>Summary</th>
                </tr>
                <?php foreach ($file['functions'] as $name => $function): ?>
                  <?php if ($function['access'] !== 'private'): ?>
                    <tr>
                      <td><?php echo $html->link($name . '()', '#function_' . $name) ?></td>
                      <td>
                        <?php if (isset($function['document']['summary'])): ?>
                          <?php echo $document->decorateText($function['document']['summary']) ?>
                        <?php endif ?>
                      </td>
                    </tr>
                  <?php endif ?>
                <?php endforeach ?>
              </table>
            <?php else: ?>
              <p>定義されている関数はありません。</p>
            <?php endif ?>
            <p class="right"><a href="#top">To top</a></p>

            <?php if (isset($file['defines'])): ?>
            <h3>Define details</h3>
              <dl>
              <?php foreach ($file['defines'] as $name => $define): ?>
                <dt id="define_<?php echo $name ?>"><?php echo $name ?></dt>
                <dd>
                  <div class="source"><code><?php echo Delta_StringUtils::escape($define['statement']) ?></code></div>
                  <?php if (isset($define['document']['description'])): ?>
                    <?php echo $document->decorateText($define['document']['description']) ?>
                  <?php else: ?>
                    <p>この定数は現在のところ詳細な情報はありません。</p>
                  <?php endif ?>
                  <p class="right"><a href="#defines">To defines</a></p>
                </dd>
              <?php endforeach ?>
              </dl>
            <?php endif ?>

            <h3>Function details</h3>
            <dl>
              <?php foreach ($file['functions'] as $name => $function): ?>
                <dt id="function_<?php echo Delta_StringUtils::escape($name) ?>"><?php echo Delta_StringUtils::escape($name) ?>()</dt>
                <dd>
                  <div class="source"><code><?php echo Delta_StringUtils::escape($function['statement']) ?></code></div>
                  <?php if (isset($function['document']['description'])): ?>
                    <?php echo $document->decorateText($function['document']['description']) ?>
                  <?php else: ?>
                    <p>この関数は現在のところ詳細な情報はありません。引数のリストのみが記述されています。</p>
                  <?php endif ?>
                  <?php if ($function['hasParameter'] || $function['hasReturn']): ?>
                    <table summary="Function details">
                    <colgroup>
                      <col width="15%" />
                      <col width="15%" />
                      <col width="70%" />
                    </colgroup>
                    <tr>
                      <th>Property</th>
                      <th>Type</th>
                      <th>Description</th>
                    </tr>
                    <?php foreach ($function['document']['tags'] as $type => $typeAttributes): ?>
                      <?php if ($type === 'param'): ?>
                        <?php foreach ($typeAttributes as $parameter => $tagAttributes): ?>
                        <tr>
                          <td><?php echo $parameter ?></td>
                          <td><?php echo $tagAttributes['type'] ?></td>
                          <td>
                            <?php if (isset($tagAttributes['description'])): ?>
                              <?php echo $document->decorateText($tagAttributes['description']) ?>
                            <?php endif ?>
                          </td>
                        </tr>
                        <?php endforeach ?>
                      <?php endif ?>
                    <?php endforeach ?>
                    <?php foreach ($function['document']['tags'] as $type => $typeAttributes): ?>
                      <?php if ($type === 'return' && $typeAttributes['type'] !== 'void'): ?>
                        <tr>
                          <td>{return}</td>
                          <td><?php echo $typeAttributes['type'] ?></td>
                          <td>
                            <?php if (isset($typeAttributes['description'])): ?>
                              <?php echo $document->decorateText($typeAttributes['description']) ?>
                            <?php endif ?>
                          </td>
                        </tr>
                      <?php endif ?>
                    <?php endforeach ?>
                    </table>
                  <?php endif ?>
                  <?php if ($function['document']['hasExtraTag']): ?>
                    <ul class="note">
                    <?php foreach ($function['document']['tags'] as $type => $typeAttribute): ?>
                      <?php if ($type !== 'param' && $type !== 'return'): ?>
                        <?php if (is_array($typeAttribute)): ?>
                          <?php foreach ($typeAttribute as $description): ?>
                            <li><?php echo ucfirst($type) . ': ' . $document->decorateTag($type, $description) ?></li>
                          <?php endforeach ?>
                        <?php else: ?>
                          <li><?php echo ucfirst($type) . ': ' . $document->decorateTag($type, $typeAttribute) ?></li>
                        <?php endif ?>
                      <?php endif ?>
                    <?php endforeach ?>
                    </ul>
                  <?php endif ?>
                  <p class="right"><a href="#functions">To functions</a></p>
                </dd>
              <?php endforeach ?>
            </dl>
          </div>
        </article>
        <!-- /threequarter -->
      </div>
      <!-- /row -->
    </div>
    <!-- /contents -->
    <?php echo $html->includeTemplate('includes/footer') ?>
  </body>
</html>
