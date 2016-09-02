<?php get_header();?>

<section class="tb-author-page">
<div class="tb-container">

  <div class="tb-post-author">
    <?php echo get_avatar( get_the_author_meta('ID') , 600 ); ?>
    <h3 class="tb-title-section"><?php the_author(); ?></h3>
    <p class="tb-lead-paragraph tb-divider"><?php the_author_meta('description'); ?> 
    
      <a href="http://twitter.com/<?php the_author_meta('twitter'); ?>" class="tb-twitter-lnk"><?php the_author_meta('twitter'); ?></a></p>
  </div>

  <div class="tb-box-list">
    <?php if(have_posts()) : while(have_posts()) : the_post(); ?>

      <?php get_template_part('tb-box');?>

    <?php endwhile; endif; wp_reset_query();?>
  </div>

  <div class="tb-pagination">
  <?php if (  $wp_query->max_num_pages > 1 ) : ?>
    <?php next_posts_link( __( '&larr; Posts antigos', 'twentyten' ) ); ?>
    <?php previous_posts_link( __( 'Posts recentes &rarr;', 'twentyten' ) ); ?>
  <?php endif; ?>
  </div>


</div>
</section>
<?php get_footer();?>
