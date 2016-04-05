<?php get_header();?>
 
<section class="tb-section tb-category">
<div class="tb-container">

  
      <div class="tb-post-author">
        <?php echo get_avatar( get_the_author_id() , 120 ); ?>
        <h3><?php the_author(); ?></h3>
        <p><?php the_author_meta('description'); ?></p>
        <p><a href="<?php bloginfo('url'); ?>/?author=<?php the_author_ID(); ?>">Veja mais artigos deste autor</a></p>
      </div>


<section class="tb-box-list">
  <?php if(have_posts()) : while(have_posts()) : the_post(); ?>

    <?php get_template_part('tb-box');?>

  <?php endwhile; endif; wp_reset_query();?>
</section>

<div class="tb-pagination">
<?php if (  $wp_query->max_num_pages > 1 ) : ?>
  <?php next_posts_link( __( '&larr; Posts antigos', 'twentyten' ) ); ?>
  <?php previous_posts_link( __( 'Posts recentes &rarr;', 'twentyten' ) ); ?>
<?php endif; ?>
</div>


</div>
</section>  
<?php get_footer();?>
