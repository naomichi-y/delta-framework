<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta charset="UTF-8" />
    <title><?php echo $title ?> - <?php echo $className ?></title>
    <link rel="stylesheet" type="text/css" href="../../assets/css/base.css" />
    <link rel="stylesheet" type="text/css" href="../../assets/css/jquery.treeview.css" />
    <link rel="apple-touch-icon-precomposed" href="http://delta-framework.org/wp-content/themes/delta/images/apple_touch_icon.png" />
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
            <h2>
              <?php if ($class['isInterface']): ?>
                Interface
              <?php elseif ($class['isAbstract']): ?>
                Abstract class
              <?php elseif ($class['isFinal']): ?>
                Final class
              <?php else: ?>
                Class
              <?php endif ?>
              <?php echo $className ?>
            </h2>
            <p class="right">
              <a href="#description">Description</a> |
              <a href="#constants">Constants</a> |
              <?php if (!$class['isInterface']): ?>
                <a href="#properties">Properties</a> |
              <?php endif ?>
              <a href="#methods">Methods</a>
            </p>

            <h3 id="description">Description</h3>
            <?php if (isset($class['document']['description'])): ?>
              <?php echo $document->decorateText($class['document']['description']) ?>
            <?php else: ?>
              <p>このクラスは現在のところ詳細な情報はありません。</p>
            <?php endif ?>
            <table summary="Description">
              <colgroup>
                <col width="20%" />
                <col width="80%" />
              </colgroup>
              <?php if (isset($class['document']['tags'])): ?>
                <?php foreach ($class['document']['tags'] as $type => $tagAttribute): ?>
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
                <th class="left">Inheritance</th>
                <td>
                  <?php echo $className ?>
                  <?php if (isset($class['inheritance'])): ?>
                    <?php echo Delta_StringUtils::joinArray(' &raquo; ', $class['inheritanceTree'], FALSE, array($document, 'linkClass')) ?>
                  <?php endif ?>
                </td>
              </tr>
              <?php if (isset($class['interfaces'])): ?>
              <tr>
                <th class="left">Interfaces</th>
                <td>
                  <?php echo Delta_StringUtils::joinArray(', ', $class['interfaces'], TRUE, array($document, 'linkClass')) ?>
                </td>
              </tr>
              <?php endif ?>
              <?php if (isset($class['subclasses'])): ?>
              <tr>
                <th class="left">Subclasses</th>
                <td>
                  <?php echo Delta_StringUtils::joinArray(', ', $class['subclasses'], TRUE, array($document, 'linkClass')) ?>
                </td>
              </tr>
              <?php endif ?>
              <tr>
                <th class="left">Source file</th>
                <td><?php echo $class['relativePath'] ?></td>
              </tr>
            </table>
            <p class="right"><a href="#top">To top</a></p>

            <h3 id="constants">Constants</h3>
            <?php if (isset($class['constants'])): ?>
              <table summary="Constants">
                <colgroup>
                  <col width="20%" />
                  <col width="80%" />
                </colgroup>
                <tr>
                  <th>Constnat</th>
                  <th>Summary</th>
                </tr>
                <?php foreach ($class['constants'] as $name => $constant): ?>
                  <tr>
                    <td class="left"><?php echo $html->link($name, '#constant_' . $name) ?></td>
                    <td class="left">
                      <?php if (isset($constant['document']['summary'])): ?>
                        <?php echo $document->decorateText($constant['document']['summary']) ?>
                      <?php endif ?>
                    </td>
                  </tr>
                <?php endforeach ?>
              </table>
            <?php else: ?>
              <p>定義されている定数はありません。</p>
            <?php endif ?>
            <p class="right"><a href="#">To top</a></p>

            <?php if (!$class['isInterface']): ?>
              <h3 id="properties">Properties</h3>
              <?php if ($class['hasPublicProperty'] || $class['hasProtectedProperty'] || $class['hasInheritanceProperty'] || $class['hasOverrideProperty']): ?>
                <?php if (isset($class['properties'])): ?>
                  <p id="toggleProperties"><?php echo $html->link('Hide inherited properties', '#') ?></p>
                  <table summary="Properties">
                    <colgroup>
                      <col width="20%" />
                      <col width="20%" />
                      <col width="40%" />
                      <col width="20%" />
                    </colgroup>
                    <tr>
                      <th>Property</th>
                      <th>Type</th>
                      <th>Summary</th>
                      <th>Defined by</th>
                    </tr>
                    <?php foreach ($class['properties'] as $name => $property): ?>
                      <?php if ($property['access'] != 'private'): ?>
                        <?php if ($property['isInheritance']): ?>
                          <tr class="inheritanceProperties">
                        <?php else: ?>
                          <tr>
                        <?php endif ?>
                          <td>
                            <?php if ($property['isInheritance']): ?>
                              <?php echo $document->linkProperty($property['define'], $name, 'property') ?>
                            <?php else: ?>
                              <?php echo $html->link($property['variable'], '#property_' . $name) ?>
                            <?php endif ?>
                          </td>
                          <td>
                            <?php if (isset($property['document']['tags']['var'])): ?>
                              <?php echo $document->linkClass($property['document']['tags']['var']) ?>
                            <?php endif ?>
                          </td>
                          <td>
                            <?php if (isset($property['document']['summary'])): ?>
                              <?php echo $document->decorateText($property['document']['summary']) ?>
                            <?php endif ?>
                          </td>
                          <td><?php echo $document->linkClass($property['define']) ?></td>
                        </tr>
                      <?php endif ?>
                    <?php endforeach ?>
                  </table>
                <?php else: ?>
                  <p>定義されているプロパティはありません。</p>
                <?php endif ?>
              <?php else: ?>
                <p>公開されているプロパティはありません。</p>
              <?php endif ?>
              <p class="right"><a href="#top">To top</a></p>
            <?php endif ?>

            <h3 id="methods">Methods</h3>
            <?php if ($class['hasPublicMethod'] || $class['hasProtectedMethod'] || $class['hasInheritanceMethod'] || $class['hasOverrideMethod']): ?>
              <?php if (isset($class['methods'])): ?>
                <p id="toggleMethods"><?php echo $html->link('Hide inherited methods', '#') ?></p>
                <table summary="Methods">
                  <colgroup>
                    <col width="20%" />
                    <col width="60%" />
                    <col width="30%" />
                   </colgroup>
                  <tr>
                    <th>Method</th>
                    <th>Summary</th>
                    <th>Defined by</th>
                  </tr>
                  <?php foreach ($class['methods'] as $name => $method): ?>
                    <?php if ($method['access'] != 'private'): ?>
                      <?php if ($method['isInheritance']): ?>
                        <tr class="inheritanceMethods">
                      <?php else: ?>
                        <tr>
                      <?php endif ?>
                        <td>
                          <?php if ($method['isInheritance']): ?>
                            <?php echo $document->linkMethod($method['define'], $name, 'method') ?>
                          <?php else: ?>
                            <?php echo $html->link($name . '()', '#method_' . $name) ?>
                          <?php endif ?>
                        </td>
                        <td>
                          <?php if (isset($method['document']['summary'])): ?>
                            <?php echo $document->decorateText($method['document']['summary']) ?>
                          <?php endif ?>
                        </td>
                        <td><?php echo $document->linkClass($method['define']) ?></td>
                      </tr>
                    <?php endif ?>
                  <?php endforeach ?>
                </table>
              <?php else: ?>
                <p>定義されているメソッドはありません。</p>
              <?php endif ?>
            <?php else: ?>
              <p>公開されているメソッドはありません。</p>
            <?php endif ?>
            <p class="right"><a href="#methods">To methods</a></p>

            <?php if (isset($class['constants'])): ?>
            <h3>Constant details</h3>
              <dl>
              <?php foreach ($class['constants'] as $name => $constant): ?>
                <dt id="constant_<?php echo $name ?>"><?php echo $name ?></dt>
                <dd>
                  <div class="source"><code><?php echo Delta_StringUtils::escape($constant['statement']) ?></code></div>
                  <?php if (isset($constant['document']['description'])): ?>
                    <?php echo $document->decorateText($constant['document']['description']) ?>
                  <?php else: ?>
                    <p>この定数は現在のところ詳細な情報はありません。</p>
                  <?php endif ?>
                  <p class="right"><a href="#constants">To constants</a></p>
                </dd>
              <?php endforeach ?>
              </dl>
            <?php endif ?>

            <?php if ($class['hasPublicProperty'] || $class['hasProtectedProperty']): ?>
            <h3>Property details</h3>
              <dl>
              <?php foreach ($class['properties'] as $name => $property): ?>
                <?php if ($property['access'] !== 'private' && ($property['isOwner'] || $property['isOverride'])): ?>
                  <dt id="property_<?php echo $name ?>"><?php echo $property['variable'] ?></dt>
                  <dd>
                    <div class="source"><code><?php echo Delta_StringUtils::escape($property['statement']) ?></code></div>
                    <?php if (isset($property['document']['description'])): ?>
                      <?php echo $document->decorateText($property['document']['description']) ?>
                    <?php else: ?>
                      <p>このプロパティは現在のところ詳細な情報はありません。</p>
                    <?php endif ?>
                    <?php if ($property['isOverride']): ?>
                      <ul class="note">
                        <li>
                          Overrides:
                          <p><?php echo $document->linkProperty($property['define'], $name, 'both') ?></p>
                        </li>
                      </ul>
                    <?php endif ?>
                    <p class="right"><a href="#properties">To properties</a></p>
                  </dd>
                <?php endif ?>
              <?php endforeach ?>
              </dl>
            <?php endif ?>

            <?php if ($class['hasPublicMethod'] || $class['hasProtectedMethod']): ?>
            <h3>Method details</h3>
              <dl>
                <?php foreach ($class['methods'] as $name => $method): ?>
                  <?php if ($method['access'] != 'private' && (($method['isOwner']) || $method['isOverride'])): ?>
                    <dt id="method_<?php echo Delta_StringUtils::escape($name) ?>"><?php echo Delta_StringUtils::escape($name) ?>()</dt>
                    <dd>
                      <div class="source"><code><?php echo Delta_StringUtils::escape($method['statement']) ?></code></div>
                      <?php if (isset($method['document']['description'])): ?>
                        <?php echo $document->decorateText($method['document']['description']) ?>
                      <?php else: ?>
                        <p>このメソッドは現在のところ詳細な情報はありません。引数のリストのみが記述されています。</p>
                      <?php endif ?>
                      <?php if ($method['hasParameter'] || $method['hasReturn']): ?>
                        <table summary="Method detail">
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
                        <?php foreach ($method['document']['tags'] as $type => $typeAttributes): ?>
                          <?php if ($type === 'param'): ?>
                            <?php foreach ($typeAttributes as $parameter => $tagAttributes): ?>
                            <tr>
                              <td><?php echo $parameter ?></td>
                              <td><?php echo $document->linkClass($tagAttributes['type']) ?></td>
                              <td>
                                <?php if (isset($tagAttributes['description'])): ?>
                                  <?php echo $document->decorateText($tagAttributes['description']) ?>
                                <?php endif ?>
                              </td>
                            </tr>
                            <?php endforeach ?>
                          <?php endif ?>
                        <?php endforeach ?>
                        <?php foreach ($method['document']['tags'] as $type => $typeAttributes): ?>
                          <?php if ($type === 'return' && $typeAttributes['type'] !== 'void'): ?>
                            <tr>
                              <td>{return}</td>
                              <td><?php echo $document->linkClass($typeAttributes['type']) ?></td>
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
                      <?php if ($method['isOverride'] || $method['document']['hasExtraTag']): ?>
                        <ul class="note">
                          <?php if ($method['isOverride']): ?>
                            <li>
                              Overrides:
                              <div class="text"><?php echo $document->linkMethod($method['define'], $name, 'both') ?></div>
                            </li>
                          <?php endif ?>
                          <?php foreach ($method['document']['tags'] as $type => $typeAttribute): ?>
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
                      <p class="right"><a href="#methods">To methods</a></p>
                    </dd>
                  <?php endif ?>
                <?php endforeach ?>
              </dl>
            <?php endif ?>
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
