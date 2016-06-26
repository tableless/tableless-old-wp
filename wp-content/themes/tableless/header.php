<!DOCTYPE html>
<html lang="pt-br" <?php body_class();?>>
<head>
  <title><?php wp_title( '|', true, 'right' ); ?></title>
  <link rel="alternate" type="application/rss+xml" title="RSS 2.0" href="<?php bloginfo('rss2_url'); ?>">
  <link href='https://fonts.googleapis.com/css?family=PT+Serif+Caption|Lora:400,700' rel='stylesheet' type='text/css'>
  <link rel="stylesheet" href="<?php echo get_stylesheet_uri(); ?>">
  <link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
  <meta charset="utf-8">

  <script>
    (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
      (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
      m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
    })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

    ga('create', 'UA-335027-1', 'tableless.com.br');
    ga('send', 'pageview');
  </script>

  <?php // Banners DFP ?>
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
    googletag.defineSlot('/7969368/banner-comunidades', [300, 125], 'div-gpt-ad-1461064765000-0').addService(googletag.pubads());
    googletag.defineSlot('/7969368/BannerHorizontal', [[970, 90], [1024, 768]], 'div-gpt-ad-1461064765000-1').addService(googletag.pubads());
    googletag.defineSlot('/7969368/ForumTableless', [[970, 90], [728, 90]], 'div-gpt-ad-1461064765000-2').addService(googletag.pubads());
    googletag.defineSlot('/7969368/RetanguloMedio-MiddleSidebar', [[300, 250], [300, 300], [360, 360], [336, 280]], 'div-gpt-ad-1461064765000-3').addService(googletag.pubads());
    googletag.defineSlot('/7969368/RetanguloMedio-TopoSidebar', [[300, 250], [300, 300], [360, 360], [336, 280]], 'div-gpt-ad-1461064765000-4').addService(googletag.pubads());
    googletag.pubads().enableSingleRequest();
    googletag.pubads().collapseEmptyDivs();
    googletag.enableServices();
  });
</script>

  <!-- <?php wp_head();?> -->
</head>
<body>

<header class="tb-header">
  <div class="tb-container">
    <h1 class="tb-logo"><a href="<?php bloginfo('url');?>" title="<?php bloginfo('description');?>"><?php bloginfo('name');?></a></h1>
    <span class="tb-search-btn">Busca</span>
    <?php wp_nav_menu(array('menu' => 'Menu Principal', 'container' => 'nav', 'menu_class' => 'tb-menu', 'depth' => 2)); ?>

    <a href="#" class="tb-sandwich" aria-label="Clique para abrir o menu" icon-menu="&#9776;">Menu</a>
  </div>
</header>


<div class="tb-search-box">
  <div class="tb-container">
    <span class="tb-close-search"></span>
    <form role="search" method="get" id="searchform" class="searchform" action="<?php bloginfo('url');?>">
      <label class="screen-reader-text" for="s">Pesquisar por:</label>
      <input type="text" value="" name="s" id="s" placeholder="Buscar">
      <input type="submit" id="searchsubmit" value="Buscar">
    </form>

  </div>
</div>