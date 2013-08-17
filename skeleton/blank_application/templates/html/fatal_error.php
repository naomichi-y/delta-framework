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
    <div id="contents" class="delta-context">
      <dl>
        <dt>File</dt>
        <dd><span class="delta-file-info"><?php echo $file ?> (Line: <?php echo $line ?>)</span></dd>
        <?php if (isset($code)): ?>
          <dt>Code</dt>
          <dd class="delta-code-inspector">
            <h2>Inspector code</h2>
            <p class="delta-stack-trace lang-php"><?php echo $code ?></p>
          </dd>
        <?php endif ?>
      </dl>
    </div>
  </body>
</html>
