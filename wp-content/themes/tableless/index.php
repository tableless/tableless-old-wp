<?php get_header();?>

  <header class="tb-intro-top">
    <?php $post = $posts[0]; // Hack. Set $post so that the_date() works. ?>
      <?php /* If this is a category archive */ if (is_category()) { ?>
      <h1 class="tb-big-title"><?php single_cat_title(); ?></h1>
      <div class="tb-lead-text"><?php echo category_description(); ?></div>
      <?php /* If this is a tag archive */ } elseif( is_search() ) { ?>
      <h1 class="tb-title-1"><?php printf( __( 'Busca por %s', 'tableless-2014' ), '<strong><em>' . get_search_query() . '</em></strong>' ); ?> <small>(<?php global $wp_query; $total_results = $wp_query->found_posts - 1; echo $total_results ?>)</small></h1>
      <?php /* If this is a tag archive */ } elseif( is_tag() ) { ?>
      <h1 class="tb-title-1"><small>Posts tagueados com</small> <?php single_tag_title(); ?></h1>
      <?php /* If this is a daily archive */ } elseif (is_day()) { ?>
      <h1 class="tb-title-1"><small>Arquivo de</small> <?php the_time('F jS, Y'); ?></h1>
      <?php /* If this is a monthly archive */ } elseif (is_month()) { ?>
      <h1 class="tb-title-1"><small>Arquivo de</small> <?php the_time('F, Y'); ?></h1>
      <?php /* If this is a yearly archive */ } elseif (is_year()) { ?>
      <h1 class="tb-title-1"><small>Arquivo de</small> <?php the_time('Y'); ?></h1>
      <?php /* If this is an author archive */ } elseif (is_author()) { ?>

      <div class="author-page">
        <a href="<?php the_author_meta('user_url'); ?>" id="author-image"><?php echo get_avatar( get_the_author_id() , 240 ); ?></a>
        <h1 class="tb-big-title"><small>Artigos de</small> <?php the_author(); ?></h1>
        <p class="tb-lead-text"><?php the_author_meta('description'); ?></p>
        <p class="tb-lead-text"><a href="<?php the_author_meta('url'); ?>"><?php the_author_meta('url'); ?></a></p>
        <p class="tb-lead-text"><a href="http://twitter.com/<?php the_author_meta('twitter'); ?>"><?php the_author_meta('twitter'); ?></a></p>
      </div>
      <?php /* If this is a paged archive */ } elseif (isset($_GET['paged']) && !empty($_GET['paged'])) { ?>
      <h1 class="tb-lead-text"><small>Arquivo do</small> Blog</h1>
      <?php } ?>
</header>

<section class="tb-square-list">

  <?php if(have_posts()) : while(have_posts()) : the_post(); ?>

    <div>

      <a href="<?php the_permalink();?>">
        <h2 class="tb-title-2"><?php the_title(); ?></h2>
        <!-- <p class="tb-lead-text"><?php echo get_the_excerpt(); ?></p> -->
      </a>
      <a href="<?php bloginfo('url'); ?>/?author=<?php the_author_ID(); ?>" rel="author" class="tb-author">Por <?php the_author(); ?></a>
    </div>

  <?php endwhile; endif; wp_reset_query();?>
</section>

<div class="tb-pagination">
<?php if (  $wp_query->max_num_pages > 1 ) : ?>
  <?php next_posts_link( __( '&larr; Posts antigos', 'twentyten' ) ); ?>
  <?php previous_posts_link( __( 'Posts recentes &rarr;', 'twentyten' ) ); ?>
<?php endif; ?>
</div>



<?php get_footer();?>
