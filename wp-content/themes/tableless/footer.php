<footer class="tb-footer">
  <div class="tb-container">

  <div class="tb-footer-post">
    <h5>Leia também:</h5>
    <?php $posts = get_posts('orderby=rand&numberposts=1'); foreach($posts as $post) { ?>
        <a href="<?php the_permalink();?>">
          <!-- <h1 class="tb-big-title"><?php the_title(); ?></h1> -->
          <p class="tb-lead-text"><?php echo get_the_excerpt(); ?></p>
        </a>
        <!--a href="<?php bloginfo('url'); ?>/?author=<?php the_author_ID(); ?>" rel="author" class="tb-author">Por <?php the_author(); ?></a-->
  <?php } ?>
  </div>

    <nav>
      <h6>Sobre</h6>
      <ul>
        <li><a href="http://tableless.com.br/contato" title="Contato">Contato</a></li>
        <li><a href="http://tableless.com.br/anuncie" title="Anuncie no site">Anuncie no site</a></li>
        <li><a href="http://tableless.com.br/seja-um-autor" title="Seja um Autor">Seja um Autor</a></li>
        <li><a href="http://tableless.com.br/sobre" title="Sobre">Sobre</a></li>
      </ul>
    </nav>
    <nav>
      <h6>Interaja</h6>
      <ul>
        <li><a href="http://forum.tableless.com.br/" title="Fórum">Fórum</a></li>
        <li><a href="http://tableless.com.br/feed" title="Feed">Feed</a></li>
        <li><a href="http://twitter.com/tableless" title="Twitter">Twitter</a></li>
        <li><a href="http://facebook.com/tablelessbr" title="Facebook">Facebook</a></li>
      </ul>
    </nav>
    <nav>
      <h6>Comunidade</h6>
      <ul>
        <li><a href="https://sp.femug.com" title="femugSP">femugSP</a></li>
        <li><a href="http://www.meetup.com/pt-BR/CSS-SP/" title="MeetupCSS SP">MeetupCSS SP</a></li>
        <li><a href="http://zofe.com.br" title="Zofe">Zofe</a></li>
        <li><a href="http://www.meetup.com/pt-BR/FrontUX/" title="FrontUX">FrontUX</a></li>
      </ul>
    </nav>
    <p class="tb-done-community">Feito pela e para a comunidade web brasileira. <a href="<?php bloginfo('url'); ?>/seja-um-autor?utm_source=footer&utm_medium=link&utm_campaign=linkAjudeFooter">Ajude</a>. <span class="tb-love">♥</span></p>
  </div>
</footer>

<script type="text/javascript">
  setTimeout(function(){var a=document.createElement("script");
  var b=document.getElementsByTagName("script")[0];
  a.src=document.location.protocol+"//script.crazyegg.com/pages/scripts/0000/6090.js?"+Math.floor(new Date().getTime()/3600000);
  a.async=false;a.type="text/javascript";b.parentNode.insertBefore(a,b)}, 1);
</script>
<script   src="https://code.jquery.com/jquery-2.2.2.min.js"   integrity="sha256-36cp2Co+/62rEAAYHLmRCPIych47CvdM+uTBJwSzWjI=" crossorigin="anonymous"></script>
<script id="dsq-count-scr" src="//tableless.disqus.com/count.js" async></script>

<?php wp_footer();?>

<script type="text/javascript">(function(){var qs,js,q,s,d=document,gi=d.getElementById,ce=d.createElement,gt=d.getElementsByTagName,id='typef_orm',b='https://s3-eu-west-1.amazonaws.com/share.typeform.com/';if(!gi.call(d,id)){js=ce.call(d,'script');js.id=id;js.src=b+'share.js';q=gt.call(d,'script')[0];q.parentNode.insertBefore(js,q)}id=id+'_';if(!gi.call(d,id)){qs=ce.call(d,'link');qs.rel='stylesheet';qs.id=id;qs.href=b+'share-button.css';s=gt.call(d,'head')[0];s.appendChild(qs,s)}})();</script>


</body>
</html>
