    <a href="<?php the_permalink();?>" class="tb-box">
      <?php if(has_post_thumbnail()) :?>
        <figure><?php echo get_the_post_thumbnail( $post_id, 'medium' ); ?></figure>
      <?php else : ?>
        <figure><img src="<?php bloginfo('template_url');?>/images/missing-img.png"></figure>
      <?php endif; ?>
      <time class="tb-post-time" datetime="<?php the_time('Y-m-d g:i') ?>"> <?php the_time('j') ?> <?php the_time('M') ?> <?php the_time('Y') ?></time>
      <h2><?php the_title();?></h2>
    </a>