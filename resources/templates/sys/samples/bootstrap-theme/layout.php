<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="shortcut icon" href="/assets/ico/favicon.png">

    <title><?php echo isset($title) ? $title : 'MattPHP'; ?></title>

    <!-- Bootstrap core CSS -->
    <link href="/bootstrap/css/bootstrap.css" rel="stylesheet">
    <!-- Bootstrap theme -->
    <link href="/bootstrap/css/bootstrap-theme.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="/assets/css/theme.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>

  <body>

    <?php
      echo $this->render('samples/nav.php', $_vars); // Example: how to inherit variables from parent
    ?>

    <?php
      if (isset($_captured) && strlen($_captured) > 0) {
          echo '<div class="container"><pre>'.$_captured.'</pre></div>';
      }
    ?>

    <?php
      echo $_child; // Example: How to render child "view" content. Requires $_extends in child.
    ?>

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://code.jquery.com/jquery-1.11.0.min.js"></script>
    <script src="/bootstrap/js/bootstrap.min.js"></script>
    <script src="/assets/js/holder.js"></script>

  </body>
</html>
