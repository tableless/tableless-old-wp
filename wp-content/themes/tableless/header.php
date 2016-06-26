<!DOCTYPE html>
<html lang="pt-br" <?php body_class();?>>
<head>
  <title><?php wp_title( '|', true, 'right' ); ?></title>
  <link rel="alternate" type="application/rss+xml" title="RSS 2.0" href="<?php bloginfo('rss2_url'); ?>">
  <!-- <link href='https://fonts.googleapis.com/css?family=PT+Serif+Caption|Lora:400,700' rel='stylesheet' type='text/css'> -->
  <style type="text/css">
    /* cyrillic */
@font-face {
  font-family: 'Lora';
  font-style: normal;
  font-weight: 400;
  src: local('Lora'), local('Lora-Regular'), url(https://fonts.gstatic.com/s/lora/v9/XXbc_aQtUtjJrkp7pYGEKhTbgVql8nDJpwnrE27mub0.woff2) format('woff2');
  unicode-range: U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
}
/* latin-ext */
@font-face {
  font-family: 'Lora';
  font-style: normal;
  font-weight: 400;
  src: local('Lora'), local('Lora-Regular'), url(https://fonts.gstatic.com/s/lora/v9/tHQOv8O1rd82EIrTHlzvmhTbgVql8nDJpwnrE27mub0.woff2) format('woff2');
  unicode-range: U+0100-024F, U+1E00-1EFF, U+20A0-20AB, U+20AD-20CF, U+2C60-2C7F, U+A720-A7FF;
}
/* latin */
@font-face {
  font-family: 'Lora';
  font-style: normal;
  font-weight: 400;
  src: local('Lora'), local('Lora-Regular'), url(https://fonts.gstatic.com/s/lora/v9/rAXKWvABQNHjPUk26ixVvvesZW2xOQ-xsNqO47m55DA.woff2) format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2212, U+2215, U+E0FF, U+EFFD, U+F000;
}
/* cyrillic */
@font-face {
  font-family: 'Lora';
  font-style: normal;
  font-weight: 700;
  src: local('Lora Bold'), local('Lora-Bold'), url(https://fonts.gstatic.com/s/lora/v9/yNp9UcngimMxgyQxKMt1QVKPGs1ZzpMvnHX-7fPOuAc.woff2) format('woff2');
  unicode-range: U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
}
/* latin-ext */
@font-face {
  font-family: 'Lora';
  font-style: normal;
  font-weight: 700;
  src: local('Lora Bold'), local('Lora-Bold'), url(https://fonts.gstatic.com/s/lora/v9/sNDli5YcfijR40K0xz3mZVKPGs1ZzpMvnHX-7fPOuAc.woff2) format('woff2');
  unicode-range: U+0100-024F, U+1E00-1EFF, U+20A0-20AB, U+20AD-20CF, U+2C60-2C7F, U+A720-A7FF;
}
/* latin */
@font-face {
  font-family: 'Lora';
  font-style: normal;
  font-weight: 700;
  src: local('Lora Bold'), local('Lora-Bold'), url(https://fonts.gstatic.com/s/lora/v9/mlTYdpdDwCepOR2s5kS2CwLUuEpTyoUstqEm5AMlJo4.woff2) format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2212, U+2215, U+E0FF, U+EFFD, U+F000;
}
/* cyrillic-ext */
@font-face {
  font-family: 'PT Serif Caption';
  font-style: normal;
  font-weight: 400;
  src: local('PT Serif Caption'), local('PTSerif-Caption'), url(https://fonts.gstatic.com/s/ptserifcaption/v8/7xkFOeTxxO1GMC1suOUYWWz3ba-QVZDaogEc-deKBHE.woff2) format('woff2');
  unicode-range: U+0460-052F, U+20B4, U+2DE0-2DFF, U+A640-A69F;
}
/* cyrillic */
@font-face {
  font-family: 'PT Serif Caption';
  font-style: normal;
  font-weight: 400;
  src: local('PT Serif Caption'), local('PTSerif-Caption'), url(https://fonts.gstatic.com/s/ptserifcaption/v8/7xkFOeTxxO1GMC1suOUYWbDqTijJ-NI5JRiB9pidTSE.woff2) format('woff2');
  unicode-range: U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
}
/* latin-ext */
@font-face {
  font-family: 'PT Serif Caption';
  font-style: normal;
  font-weight: 400;
  src: local('PT Serif Caption'), local('PTSerif-Caption'), url(https://fonts.gstatic.com/s/ptserifcaption/v8/7xkFOeTxxO1GMC1suOUYWZd9qcI-wbUfOR7BQjW_jGs.woff2) format('woff2');
  unicode-range: U+0100-024F, U+1E00-1EFF, U+20A0-20AB, U+20AD-20CF, U+2C60-2C7F, U+A720-A7FF;
}
/* latin */
@font-face {
  font-family: 'PT Serif Caption';
  font-style: normal;
  font-weight: 400;
  src: local('PT Serif Caption'), local('PTSerif-Caption'), url(https://fonts.gstatic.com/s/ptserifcaption/v8/7xkFOeTxxO1GMC1suOUYWe6-aqqlJr6SOYV8xoy8QRI.woff2) format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2212, U+2215, U+E0FF, U+EFFD, U+F000;
}

  </style>
  <link rel="stylesheet" href="<?php echo get_stylesheet_uri(); ?>">
  <link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
  <meta charset="utf-8">


  <?php // Banners DFP ?>

  <?php wp_head();?>
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