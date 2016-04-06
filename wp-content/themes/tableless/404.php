<?php get_header();?>
 
<section class="tb-section">
  
  <div class="tb-container">
    <h2 class="tb-title-section tb-align-center">
      Ixi! Não encontramos a página
    </h2>
    <p class="tb-lead-paragraph tb-divider tb-align-center">Mas confira alguns outros posts incríveis que você pode gostar</p>
  </div>
  
</section>

<section class="tb-latest-posts no-divider">
  
    <div class="tb-container">
  
      <?php
        $latestPostsargs = array(
          'posts_per_page' => 8, // Quantidade de posts
          'order'=> 'DESC',
        );
        $latestPosts = get_posts( $latestPostsargs );
        foreach ( $latestPosts as $post ) : setup_postdata( $post ); ?>
  
          <?php get_template_part('tb-box');?>
  
  
        <?php endforeach; wp_reset_postdata(); ?>
  
  
    </div>
    <a href="<?php echo get_page_link(42486); ?>" class="tb-btn-big">Mais artigos</a>
  
</section>



<?php get_footer();?>
