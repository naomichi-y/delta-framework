<nav>
  <div id="tree_header">
    <?php echo $html->link('Packages', $relativeIndexPath . 'index.html') ?> |
    <span id="tree_control">
      <?php echo $html->link('Collapse', '#') ?> |
      <?php echo $html->link('Expand', '#') ?>
    </span>
  </div>

  <ul class="treeview" id="tree">
    <?php foreach ($menus as $package => $names): ?>
      <li class="expandable">
        <div class="hitarea expandable-hitarea"></div>
        <span><?php echo $package ?></span>
        <ul style="display: none;">
          <?php foreach ($names as $name => $attributes): ?>
            <li><?php echo $html->link($name, $relativeAPIPath . $attributes['anchor'], array('title' => $name)) ?></li>
          <?php endforeach ?>
        </ul>
      </li>
    <?php endforeach ?>
  </ul>
</nav>
