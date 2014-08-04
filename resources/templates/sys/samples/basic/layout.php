<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="shortcut icon" href="/assets/ico/favicon.ico">

    <title>Starter Template for Bootstrap</title>

    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">

    <!-- Custom styles for this template -->
    <link href="/assets/css/starter-template.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>

  <body>

    <?php
      echo $this->render('samples/basic/nav.php', $_vars); // Example: how to inherit variables from parent
    ?>

	<div class="container">
		<div class="starter-template">
			<?php
			  if (isset($_exception) && strlen($_exception) > 0) {
				$vars = array('ex'=>$_exception);
				echo $this->render('sys/php-exception.php', $vars);
			  }
			?>
			<?php
			  if (isset($_captured) && strlen($_captured) > 0) {
				$vars = array('captured'=>$_captured);
				echo $this->render('sys/php-captured-output.php', $vars);
			  }
			?>

			<?php echo $_child; ?>
		</div>
	</div>

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    <script src="/bootstrap-3.1.1-dist/js/bootstrap.min.js"></script>
  </body>
</html>
