<?php get_header();?>

<section class="tb-search-content">
  <?php if ( have_posts() ) : ?>

    <header class="tb-seartch-title">
      <h3 class="tb-title-1"><?php printf( __( 'Resultado da busca por %s', 'tableless' ), '<span>' . get_search_query() . '</span>' ); ?></h3>
    </header>

  <div class="tb-search-results">
  <?php while(have_posts()) : the_post(); ?>
    <?php get_template_part('tb-box');?>
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
