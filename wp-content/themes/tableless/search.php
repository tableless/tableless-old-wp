<?php get_header();?>

<section class="tb-search-content">
  <?php if ( have_posts() ) : ?>

    <header class="tb-seartch-title">
      <h3 class="tb-title-1"><?php printf( __( 'Resultado da busca por %s', 'tableless' ), '<span>' . get_search_query() . '</span>' ); ?></h3>
    </header>

    <div class="tb-search-results">
  <?php while(have_posts()) : the_post(); ?>
    <a href="<?php the_permalink();?>" class="tb-box">
      <?php if(has_post_thumbnail()) :?>
        <figure><?php echo get_the_post_thumbnail( $post_id, 'medium' ); ?></figure>
      <?php endif;?>
      <time class="tb-post-time" datetime="<?php the_time('Y-m-d g:i') ?>"> <?php the_time('j') ?> <?php the_time('M') ?> <?php the_time('Y') ?></time>
      <h2><?php the_title();?></h2>
    </a>
  <?php endwhile; ?>
  </div>

  <?php else : ?>
    <header class="tb-seartch-title">
      <h3 class="tb-title-1"><?php printf( __( 'Nada encontrado buscando por %s', 'tableless' ), '<span>' . get_search_query() . '</span>' ); ?></h3>
    </header>
  <?php endif; wp_reset_query();?>

  <?php /*
  <div class="tb-pagination">
  <?php if (  $wp_query->max_num_pages > 1 ) : ?>
    <?php next_posts_link( __( '&larr; Posts antigos', 'twentyten' ) ); ?>
    <?php previous_posts_link( __( 'Posts recentes &rarr;', 'twentyten' ) ); ?>
  <?php endif; ?>
  </div>
  */?>

</section>


<?php get_footer();?>
