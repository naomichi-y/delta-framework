<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title><?php echo get_class($exception) ?></title>
    <link rel="stylesheet" type="text/css" href="/assets/base/delta/css/base.css" />
    <script type="text/javascript" src="/assets/base/require.js"></script>
    <script type="text/javascript" src="/assets/base/delta/js/code_inspector.js"></script>
  </head>
  <body>
    <header>
      <h1><?php printf('%s: %s', get_class($exception), $message) ?></h1>
    </header>
    <div id="contents" class="delta_context">
      <?php if ($exception instanceof Delta_Exception && $exception->hasTrigger()): ?>
        <dl class="delta_exception_trigger_code">
          <dt>Trigger code:</dt>
          <dd>
            <?php if ($exception->getTriggerCodeType() === 'php'): ?>
              <?php echo Delta_DebugUtils::syntaxHighlight($exception->getTriggerCode(), array('format' => array('target' => $exception->getTriggerLine()))) ?>
            <?php else: ?>
              <?php echo $exception->getTriggerCode() ?>
            <?php endif ?>
          </dd>
        </dl>
      <?php endif ?>

      <?php echo $trace ?>
    </div>
  </body>
</html>
