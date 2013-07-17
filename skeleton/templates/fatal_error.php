<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta charset="UTF-8" />
    <title><?php printf('%s: %s', $type, $message); ?></title>
    <link rel="stylesheet" type="text/css" href="/assets/base/delta/css/base.css" />
    <link rel="stylesheet" type="text/css" href="/assets/base/delta/css/app_code_inspector.css" />
    <!--[if lt IE 9]>
    <script src="/assets/js/html5shiv.js"></script>
    <![endif]-->
  </head>
  <body>
    <header>
      <h1 id="fatal_error"><?php printf('%s: %s', $type, $message) ?></h1>
    </header>
    <div id="contents" class="delta_context">
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
    </div>
  </body>
</html>
