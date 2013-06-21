<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta http-equiv="Content-Language" content="ja" />
    <meta charset="UTF-8" />
    <title>Skeleton template</title>
    <link rel="stylesheet" type="text/css" href="/assets/base/delta/css/base.css" />
    <!--[if lt IE 9]>
    <script src="/assets/js/html5shiv.js"></script>
    <![endif]-->
  </head>
  <body>
    <header>
      <h1>Hello <?php echo $request->getRoute()->getActionName() ?>!</h1>
    </header>
  </body>
</html>
