<footer class="tb-footer">
  <div class="tb-container">

  <div class="tb-footer-post">
    <h5>Leia também:</h5>
    <?php $posts = get_posts('orderby=rand&numberposts=1'); foreach($posts as $post) { ?>
        <a href="<?php the_permalink();?>">
          <p class="tb-lead-text"><?php echo get_the_excerpt(); ?></p>
        </a>
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
    <p class="tb-done-community">Escrito pela e para a comunidade web brasileira. <a href="<?php bloginfo('url'); ?>/seja-um-autor?utm_source=footer&utm_medium=link&utm_campaign=linkAjudeFooter">Ajude</a>. <span class="tb-love">♥</span></p>
  </div>
</footer>

<script
        src="https://code.jquery.com/jquery-3.0.0.min.js"
        integrity="sha256-JmvOoLtYsmqlsWxa7mDSLMwa6dZ9rrIdtrrVYRnDRH0="
        crossorigin="anonymous"></script>
<script id="dsq-count-scr" src="//tableless.disqus.com/count.js" async="async"></script>

  <script type='text/javascript'>
    (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
      (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
      m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
    })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

    ga('create', 'UA-335027-1', 'tableless.com.br');
    ga('send', 'pageview');
  </script>

<script type="text/javascript">
  $(function(){
    $( ".contact-form" ).on('submit', function( event ) {
      if ( $( ".robotDefend" ).val() === "4" ) {
        return;
      } else {
        event.preventDefault();
      }
    });

  })
</script>



<?php wp_footer();?>


</body>
</html>
