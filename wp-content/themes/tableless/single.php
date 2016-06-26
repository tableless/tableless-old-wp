<?php get_header(); ?>

<div class="tb-post-page">

  <?php if(has_post_thumbnail()) :?>
    <figure class="tb-featured-image"><?php echo get_the_post_thumbnail( $post_id, 'medium' ); ?></figure>
  <?php endif;?>

  <div class="tb-post-content">

    <section class="tb-post-area">

      <article class="tb-post-text">
        <header>
        <?php edit_post_link(); ?>

          <h1>
            <?php the_title();?>
          </h1>

          <a href="<?php the_permalink(); ?>#disqus_thread" class="tb-comment-count">Seja o primeiro a comentar</a>

          <span class="tb-author-info">
            <?php echo get_avatar( get_the_author_meta('ID') , 100 ); ?>
            por <?php the_author_posts_link(); ?>
          </span>
        </header>

        <?php the_content();?>

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
        <?php echo get_avatar( get_the_author_meta('ID') , 120 ); ?>
        <h3><?php the_author(); ?></h3>
        <p><?php the_author_meta('description'); ?></p>
        <p><a href="<?php bloginfo('url'); ?>/?author=<?php the_author_meta('ID'); ?>">Veja mais artigos deste autor</a></p>
      </div>

      <section class="tb-related-posts">
        <h2 class="tb-title-section">Leia também</h2>
        <p class="tb-lead-paragraph">Aproveite para ler estes posts também:</p>

        <div class="tb-related-group">
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

            <?php get_template_part('tb-box');?>

          <?php endforeach; wp_reset_postdata(); ?>
        </div>
      </section>


      <div class="tb-comments">
        <div id="disqus_thread"></div>

      </div>
    </section>

    <?php get_sidebar();?>

  </div>


</div>

<?php get_footer(); ?>
