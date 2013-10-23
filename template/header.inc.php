<?php
global $members_only_area;
$current_page = strtolower(basename($_SERVER['PHP_SELF']));
$lang = strtolower(substr(LangBase::getLang(), 0, 2));
$lang = (trim($lang) != '' ? $lang : 'en');
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=0" />

        <title>Finanças Pessoais</title>
        <meta name="description" content="">
        <meta name="keywords" content="">
        <meta name="author" content="Dario Santos">
        <link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />

        <link rel="stylesheet" href="css/ui-lightness/jquery-ui-1.8.21.custom.css">
        <link rel="stylesheet" href="css/html5_boilerplate.css">
        <link rel="stylesheet" href="css/template.css">

        <link rel="stylesheet" href="css/default.css" type="text/css" media="all" />
        <link rel="stylesheet" href="css/flexslider.css" type="text/css" media="all" />
        <link href='http://fonts.googleapis.com/css?family=Ubuntu:400,500,700' rel='stylesheet' type='text/css' />



        <!-- Grab Google CDN's jQuery, with a protocol relative URL; fall back to local if offline -->
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
        <script>window.jQuery || document.write('<script src="js/libs/jquery-1.7.2.min.js"><\/script>')</script>

        <!--[if lt IE 9]>
                <script src="js/libs/modernizr.custom.js"></script>
        <![endif]-->

        <script src="js/libs/jquery.flot.js"></script>
        <script src="js/libs/jquery.flot.pie.js"></script>

        <script src="js/libs/jquery-ui-1.8.21.custom.min.js"></script>
        <script src="js/libs/jquery-ui-timepicker-addon.js"></script>
        <script src="js/libs/jquery-ui-slideraccess-addon.js"></script>
        <script src="js/libs/jquery.flexslider-min.js" type="text/javascript"></script>
        <script src="js/libs/jquery.jeditable.js" type="text/javascript"></script>

        <script src="js/template.js"></script>
        <script src="js/plugins.js"></script>
        <script src="js/default.js"></script>
        <script src="js/budget.js"></script>
        <script src="js/insert_transaction.js"></script>

        <script type="text/javascript">

            var _gaq = _gaq || [];
            _gaq.push(['_setAccount', 'UA-XXXXXXXX-1']);
            _gaq.push(['_trackPageview']);

            (function() {
                var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
                ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
                var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
            })();

        </script>
    </head>
    <body>

        <!-- Prompt IE 6 users to install Chrome Frame. Remove this if you support IE 6.
             chromium.org/developers/how-tos/chrome-frame-getting-started -->
        <!--[if lt IE 7]><p class=chromeframe>Your browser is <em>ancient!</em> <a href="http://browsehappy.com/">Upgrade to a different browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">install Google Chrome Frame</a> to experience this site.</p><![endif]-->

        <div id="wrapper">

            <!-- top-nav -->
            <nav class="top-nav">
                <div class="shell">
                    <a href="#" class="nav-btn">HOMEPAGE<span></span></a>
                    <span class="top-nav-shadow"></span>
                    <ul>
                        <li <?php echo ($current_page == 'index.php' ? 'class="active"' : ''); ?>><span><a href="home">Home</a></span></li>
                        <li <?php echo ($current_page == 'data-transaction.php' ? 'class="active"' : ''); ?>><span><a href="data-transaction">Nova transacção</a></span></li>
                        <li <?php echo ($current_page == 'data-list.php' ? 'class="active"' : ''); ?>><span><a href="data-list">Listagem</a></span></li>
                        <li <?php echo ($current_page == 'data-statistics.php' ? 'class="active"' : ''); ?>><span><a href="data-statistics">Estatísticas</a></span></li>
                        <li <?php echo ($current_page == 'data-budget.php' ? 'class="active"' : ''); ?>><span><a href="data-budget">Orçamento</a></span></li>
                    </ul>
                </div>
            </nav>
            <!-- end of top-nav -->

