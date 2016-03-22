<?php get_header(); ?>

<div class="tb-post-page">
  
  <?php if(has_post_thumbnail()) :?>
    <figure class="tb-featured-image"><?php echo get_the_post_thumbnail( $post_id, 'full' ); ?></figure>
  <?php endif;?>
  
  <div class="tb-post-content">
    
    <section class="tb-post-area">

      <article class="tb-post-text">
        <header>
         
          <h1>
            Criando efeitos de página em livros com front-end
          </h1>
          <span class="tb-author-info">
            <?php echo get_avatar( get_the_author_id() , 100 ); ?>
            por <?php the_author_posts_link(); ?>
          </span>
        </header>
        
        <p>
          Caros amigos, a execução dos pontos do programa nos obriga à análise das diversas correntes de pensamento. No mundo atual, a complexidade dos estudos efetuados representa uma abertura para a melhoria das posturas dos órgãos dirigentes com relação às suas atribuições.
        </p>
        <p>
          Evidentemente, <a href="#">a valorização de fatores subjetivos ainda</a> não demonstrou convincentemente que vai participar na mudança de todos os recursos funcionais envolvidos. Não obstante, a estrutura atual da organização auxilia a preparação e a composição dos relacionamentos verticais entre as hierarquias.
        </p>
        <p>
          Acima de tudo, é fundamental ressaltar que o <a href="#">desenvolvimento contínuo de distintas formas de</a> atuação causa impacto indireto na reavaliação das novas proposições.
        </p>
        <h2>Confira abaixo o código completo</h2>
        <p>
          As experiências acumuladas demonstram que a crescente influência da mídia é uma das consequências do impacto na agilidade decisória.
        </p>
        <ul>
          <li><a href="#">Uma Opção</a></li>
          <li><a href="#">Duas Opção</a></li>
          <li><a href="#">Três Opção</a></li>
          <li><a href="#">Quatro Opção</a></li>
        </ul>
        <p>
          O empenho em analisar a percepção das dificuldades estimula a padronização das diretrizes de desenvolvimento para o futuro. Do mesmo modo, a consolidação das estruturas estende o alcance e a importância das condições inegavelmente apropriadas. 
        </p>
        <p>
          Gostaria de enfatizar que a mobilidade dos capitais internacionais oferece uma interessante oportunidade para verificação do levantamento das variáveis envolvidas. Ainda assim, existem dúvidas a respeito de como o início da atividade geral de formação de atitudes cumpre um papel essencial na formulação dos procedimentos normalmente adotados.
        </p>

        <footer>
            <span class="tb-post-time">Publicado no dia <time datetime="<?php the_time('Y-m-d g:i') ?>"> <?php the_time('j') ?> de <?php the_time('F') ?> de <?php the_time('Y') ?></time></span>
        </footer>

      </article>


      <div class="tb-social-links">
        <ul>
          <li> <a href="https://twitter.com/share" class="tb-social-twitter" data-via="tableless" data-related="tableless" data-hashtags="soudev">Tweet</a> <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script></li>
          <li><a class="tb-social-facebook" href="http://www.facebook.com/sharer/sharer.php?u=<?php the_permalink();?>" rel="nofollow" onclick="window.open(this.href, 'tb-face-popup', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,left=980,top=370,width=400,height=200'); return false;">Facebook</a></li>
          <li><a href="https://plus.google.com/+tableless" class="tb-social-google">Google+</a></li>
        </ul>
      </div>

      <div class="tb-post-author">
        <?php echo get_avatar( get_the_author_id() , 120 ); ?>
        <h3><?php the_author(); ?></h3>
        <p><?php the_author_meta('description'); ?></p>
        <p><a href="<?php bloginfo('url'); ?>/?author=<?php the_author_ID(); ?>">Veja mais artigos deste autor</a></p>
      </div>

      <section class="tb-related-posts">
        <h2>Leia também:</h2>
        <p>Aproveite para ler estes posts também:</p>
        <?php
          $latestPostsargs = array(
            'posts_per_page' => 3, // Quantidade de posts
            'order'=> 'DESC',
            'meta_query' => array(
              array(
               'key' => '_thumbnail_id',
               'compare' => 'EXISTS'
              )
            ),
          );
          $latestPosts = get_posts( $latestPostsargs );
          foreach ( $latestPosts as $post ) : setup_postdata( $post ); ?>

            <a href="<?php the_permalink();?>" class="tb-post-box">
              <?php if(has_post_thumbnail()) :?>
                <figure><?php echo get_the_post_thumbnail( $post_id, 'medium' ); ?></figure>
              <?php endif;?>
              <time class="tb-post-time" datetime="<?php the_time('Y-m-d g:i') ?>"> <?php the_time('j') ?> <?php the_time('M') ?> <?php the_time('Y') ?></time>
              <h2><?php the_title();?></h2>
            </a>

          <?php endforeach; wp_reset_postdata(); ?>

      </section>


      <div class="tb-comments">
        <div id="disqus_thread"></div>
      </div>
    </section>

    <?php get_sidebar();?>

  </div>


</div>


<?php get_footer(); ?>