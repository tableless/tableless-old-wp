<!DOCTYPE html>
<html lang="pt-br" <?php body_class();?>>
<head>
  <title><?php wp_title( '|', true, 'right' ); ?></title>
  <link rel="alternate" type="application/rss+xml" title="RSS 2.0" href="<?php bloginfo('rss2_url'); ?>">
  <link href='https://fonts.googleapis.com/css?family=PT+Serif+Caption' rel='stylesheet' type='text/css'>
  <link rel="stylesheet" href="<?php echo get_stylesheet_uri(); ?>">
  <link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
  <meta charset="utf-8">
  <meta name="description" content="<?php if( $post->post_excerpt ) { ?><?php echo get_the_excerpt(); ?><?php } ?>">
<!--
  <script>
    (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
      (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
      m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
    })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

    ga('create', 'UA-335027-1', 'tableless.com.br');
    ga('send', 'pageview');
  </script>
  <script type='text/javascript'>
    var googletag = googletag || {};
    googletag.cmd = googletag.cmd || [];
    (function() {
      var gads = document.createElement('script');
      gads.async = true;
      gads.type = 'text/javascript';
      var useSSL = 'https:' == document.location.protocol;
      gads.src = (useSSL ? 'https:' : 'http:') +
        '//www.googletagservices.com/tag/js/gpt.js';
      var node = document.getElementsByTagName('script')[0];
      node.parentNode.insertBefore(gads, node);
    })();
  </script>

  <script type='text/javascript'>
    googletag.cmd.push(function() {
      googletag.defineSlot('/7969368/banner-comunidades', [300, 125], 'div-gpt-ad-1437354150041-0').addService(googletag.pubads());
      googletag.defineSlot('/7969368/RetanguloMedio-MiddleSidebar', [[300, 250], [300, 300]], 'div-gpt-ad-1437354150041-1').addService(googletag.pubads());
      googletag.defineSlot('/7969368/RetanguloMedio-TopoSidebar', [[300, 250], [300, 300]], 'div-gpt-ad-1437354150041-2').addService(googletag.pubads());
      googletag.pubads().enableSingleRequest();
      googletag.pubads().collapseEmptyDivs();
      googletag.enableServices();
    });
  </script> -->

  <?php wp_head();?>
</head>
<body>

<header class="tb-header">
  <div class="container">
    <h1 class="tb-logo"><a href="<?php bloginfo('url');?>" title="<?php bloginfo('description');?>"><?php bloginfo('name');?></a></h1>
    <span class="tb-search-btn">Busca</span>
    <?php wp_nav_menu(array('menu' => 'Menu Principal', 'container' => 'nav', 'menu_class' => 'tb-menu', 'depth' => 2)); ?>

    <a href="#" class="tb-sandwich" aria-label="Clique para abrir o menu" icon-menu="&#9776;">Menu</a>
  </div>
</header>
