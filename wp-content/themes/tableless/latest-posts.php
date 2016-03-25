<?php 
/*
Template Name: Latest posts
*/
get_header();?>

<section class="tb-section">
<div class="tb-container">
    <h2 class="tb-title-section tb-align-center">
      Últimos posts
    </h2>
   <p class="tb-lead-paragraph tb-divider tb-align-center">Consuma com moderação</p>

<div class="tb-latest-posts-list">
<?php
     // Argumentos para buscar os posts
     $args = array(
     'posts_per_page' => 50, // Quantidade de posts
     'order'=> 'DESC'
  );

    $myposts = get_posts( $args );
    foreach ( $myposts as $post ) : setup_postdata( $post ); ?>

    <?php get_template_part('tb-box');?>
  
    <?php endforeach;
    wp_reset_postdata(); ?>

</div>
  
  <div class="tb-pagination">
  <?php if (  $wp_query->max_num_pages > 1 ) : ?>
    <?php next_posts_link( __( '&larr; Posts antigos', 'tableless' ) ); ?>
    <?php previous_posts_link( __( 'Posts recentes &rarr;', 'tableless' ) ); ?>
  <?php endif; ?>
  </div>

</div>
</section>
<?php get_footer();?>