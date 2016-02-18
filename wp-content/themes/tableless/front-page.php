<?php get_header();?>

<section class="tb-featured-posts">
  <div class="tb-container">
  
<?php
  $featuredPostsArgs = array(
    // 'post__in'  => get_option( 'sticky_posts' ), // Show only sticky-posts
    'order'=> 'DESC',
    'category_name' => 'destaques',
    'posts_per_page' => 5,
    'meta_query' => array(
      array(
       'key' => '_thumbnail_id',
       'compare' => 'EXISTS'
      ),
    )
  );
  $featurePost = new WP_Query($featuredPostsArgs);
  while($featurePost->have_posts()) : $featurePost->the_post();
  $urlImage = wp_get_attachment_url( get_post_thumbnail_id($post->ID) );?>

  <?php if(has_post_thumbnail()) :?>
  <div class="tb-featured-post" style="background-image: url(<?php echo $urlImage; ?>)">
  <?php else :?>
  <div class="tb-featured-post">
  <?php endif;?>

    <a href="<?php the_permalink();?>">
      
      <h1 class="tb-title-1"><?php $tituloPersonalizado = get_post_meta($post->ID, 'titulo_personalizado', true); echo $tituloPersonalizado; ?></h1>

    </a>
  </div>
<?php endwhile; wp_reset_postdata(); ?>

  </div>
</section>

<?php get_footer();?>
