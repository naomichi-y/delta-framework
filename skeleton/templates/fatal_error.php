<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title><?php $isOutputDebug ? printf('%s: %s', $type, $message) : print($title); ?></title>
    <link rel="stylesheet" type="text/css" href="/assets/base/delta/css/base.css" />
    <link rel="stylesheet" type="text/css" href="/assets/base/delta/css/app_code_inspector.css" />
    <!--[if lt IE 9]>
    <script src="/assets/js/html5shiv.js"></script>
    <![endif]-->
  </head>
  <body>
    <header>
      <h1 id="fatal_error"><?php $isOutputDebug ? printf('%s: %s', $type, $message) : print($title); ?></h1>
    </header>
    <div id="contents" class="delta_context">
      <?php if ($isOutputDebug): ?>
      <dl>
        <dt>File</dt>
        <dd><span class="file_info"><?php echo $file ?> (Line: <?php echo $line ?>)</span></dd>
        <?php if (isset($code)): ?>
          <dt>Code</dt>
          <dd class="delta_code_inspector">
            <h2>Inspector code</h2>
            <p class="delta_stack_trace lang_php"><?php echo $code ?></p>
          </dd>
        <?php endif ?>
      </dl>
      <?php else: ?>
        <p>Please check the SAPI log.</p>
      <?php endif ?>
    </div>
  </body>
</html>
