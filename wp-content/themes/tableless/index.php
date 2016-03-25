<?php get_header();?>
 
<section class="tb-section tb-category">
<div class="tb-container">

  <?php if ( have_posts() ) : ?>
    <h2 class="tb-title-section tb-align-center">
      <?php single_cat_title(); ?>
    </h2>
   <p class="tb-lead-paragraph tb-divider tb-align-center"><?php echo category_description(); ?></p>

   <div class="tb-category-posts">
  <?php while(have_posts()) : the_post(); ?>
    <?php get_template_part('tb-box');?>
  <?php endwhile;  else : ?>
  <p>Nada encontrado.</p>
  <?php endif; wp_reset_query();?>
  </div>
  <div class="tb-pagination">
  <?php if (  $wp_query->max_num_pages > 1 ) : ?>
    <?php next_posts_link( __( '&larr; Posts antigos', 'tableless' ) ); ?>
    <?php previous_posts_link( __( 'Posts recentes &rarr;', 'tableless' ) ); ?>
  <?php endif; ?>
  </div>
</div>
</section>

<div class="tb-pagination">
<?php if (  $wp_query->max_num_pages > 1 ) : ?>
  <?php next_posts_link( __( '&larr; Posts antigos', 'twentyten' ) ); ?>
  <?php previous_posts_link( __( 'Posts recentes &rarr;', 'twentyten' ) ); ?>
<?php endif; ?>
</div>


<?php get_footer();?>
