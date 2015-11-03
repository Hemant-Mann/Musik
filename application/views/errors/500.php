<!DOCTYPE html>
<html lang="en" class="app">

<head>
    <meta charset="utf-8" />
    <title>Musik | Web App : Error 500</title>
    <link rel="icon" href="/favicon.ico" type="image/x-icon"/>
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon"/>
    <meta name="description" content="app, web app, responsive, admin dashboard, admin, flat, flat ui, ui kit, off screen nav" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <link rel="stylesheet" href="<?php echo CDN; ?>js/jPlayer/jplayer.flat.css" type="text/css" />
    <link rel="stylesheet" href="<?php echo CDN; ?>css/app.v1.css" type="text/css" />
    <meta name="author" content="Hemant Mann">
    <!--[if lt IE 9]> <script src="<?php echo CDN; ?>js/ie/html5shiv.js"></script> <script src="<?php echo CDN; ?>js/ie/respond.min.js"></script> <script src="<?php echo CDN; ?>js/ie/excanvas.js"></script> <![endif]-->
</head>
<body class="bg-light dk">
<?php if (DEBUG): ?>
            <pre><?php print_r($e); ?></pre>
        <?php endif; ?>
    <section id="content">
        <div class="row m-n">
            <div class="col-sm-4 col-sm-offset-4">
                <div class="text-center m-b-lg">
                    <h1 class="h text-white animated fadeInDownBig">500</h1>
                </div>
                <div class="text-center">
                    <h2 class="page-heading">Server Error</h2>
                </div>

                <div class="list-group auto m-b-sm m-b-lg">
                    <a href="/index" class="list-group-item"> <i class="fa fa-chevron-right icon-muted"></i> <i class="fa fa-fw fa-home icon-muted"></i> Goto homepage </a>
                    <a href="#" class="list-group-item"> <i class="fa fa-chevron-right icon-muted"></i> <i class="fa fa-fw fa-question icon-muted"></i> Send us a tip </a>
                </div>
            </div>
        </div>
    </section>
    <!-- footer -->
    <footer id="footer">
        <div class="text-center padder clearfix">
            <p> <small>Music | Web Application<br>&copy; <?php echo date('Y'); ?></small> </p>
        </div>
    </footer>
    <!-- / footer -->
    <!-- Bootstrap -->
    <!-- App -->
    <script src="<?php echo CDN; ?>js/app.v1.js"></script>
    <script src="<?php echo CDN; ?>js/app.plugin.js"></script>
    <script type="text/javascript" src="<?php echo CDN; ?>js/jPlayer/jquery.jplayer.min.js"></script>
    <script type="text/javascript" src="<?php echo CDN; ?>js/jPlayer/add-on/jplayer.playlist.min.js"></script>
    <script type="text/javascript" src="<?php echo CDN; ?>js/jPlayer/demo.js"></script>
</body>
</html>
