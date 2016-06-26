<footer class="tb-footer">
  <div class="tb-container">

  <div class="tb-footer-post">
    <h5>Leia também:</h5>
    <?php $posts = get_posts('orderby=rand&numberposts=1'); foreach($posts as $post) { ?>
        <a href="<?php the_permalink();?>">
          <!-- <h1 class="tb-big-title"><?php the_title(); ?></h1> -->
          <p class="tb-lead-text"><?php echo get_the_excerpt(); ?></p>
        </a>
        <!--a href="<?php bloginfo('url'); ?>/?author=<?php the_author_meta('ID'); ?>" rel="author" class="tb-author">Por <?php the_author(); ?></a-->
  <?php } ?>
  </div>

    <nav>
      <h6>Sobre</h6>
      <ul>
        <li><a href="http://tableless.com.br/contato">Contato</a></li>
        <li><a href="http://tableless.com.br/anuncie">Anuncie no site</a></li>
        <li><a href="http://tableless.com.br/seja-um-autor">Seja um Autor</a></li>
        <li><a href="http://tableless.com.br/sobre">Sobre</a></li>
      </ul>
    </nav>
    <nav>
      <h6>Interaja</h6>
      <ul>
        <li><a href="http://forum.tableless.com.br/">Fórum</a></li>
        <li><a href="http://tableless.com.br/feed">Feed</a></li>
        <li><a href="http://twitter.com/tableless">Twitter</a></li>
        <li><a href="http://facebook.com/tablelessbr">Facebook</a></li>
      </ul>
    </nav>
    <nav>
      <h6>Comunidade</h6>
      <ul>
        <li><a href="http://femug.github.io/femug/">Femug</a></li>
        <li><a href="http://www.meetup.com/pt-BR/CSS-SP/">MeetupCSS SP</a></li>
        <li><a href="http://zofe.com.br">Zofe</a></li>
        <li><a href="https://braziljs.org">BrazilJS</a></li>
        <li><a href="http://www.meetup.com/pt-BR/FrontUX/">FrontUX</a></li>
      </ul>
    </nav>
    <p class="tb-done-community">Feito pela e para a comunidade web brasileira. <a href="<?php bloginfo('url'); ?>/seja-um-autor?utm_source=footer&utm_medium=link&utm_campaign=linkAjudeFooter">Ajude</a>. <span class="tb-love">♥</span></p>
  </div>
</footer>

<script type="text/javascript">
// CrazyEgg
  setTimeout(function(){var a=document.createElement("script");
  var b=document.getElementsByTagName("script")[0];
  a.src=document.location.protocol+"//script.crazyegg.com/pages/scripts/0000/6090.js?"+Math.floor(new Date().getTime()/3600000);
  a.async=false;a.type="text/javascript";b.parentNode.insertBefore(a,b)}, 1);
</script>


<script
        src="http://code.jquery.com/jquery-3.0.0.min.js"
        integrity="sha256-JmvOoLtYsmqlsWxa7mDSLMwa6dZ9rrIdtrrVYRnDRH0="
        crossorigin="anonymous"></script>

        <?php if (is_single()) :?>
          <script id="dsq-count-scr" src="//tableless.disqus.com/count.js" async="async"></script>
        <? endif;?>

  <script type='text/javascript'>
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


<?php wp_footer();?>

<!-- 
// TypeForm
<script type="text/javascript">(function(){var qs,js,q,s,d=document,gi=d.getElementById,ce=d.createElement,gt=d.getElementsByTagName,id='typef_orm',b='https://s3-eu-west-1.amazonaws.com/share.typeform.com/';if(!gi.call(d,id)){js=ce.call(d,'script');js.id=id;js.src=b+'share.js';q=gt.call(d,'script')[0];q.parentNode.insertBefore(js,q)}id=id+'_';if(!gi.call(d,id)){qs=ce.call(d,'link');qs.rel='stylesheet';qs.id=id;qs.href=b+'share-button.css';s=gt.call(d,'head')[0];s.appendChild(qs,s)}})();</script> -->




</body>
</html>
