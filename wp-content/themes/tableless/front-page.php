<?php get_header();?>

<section class="tb-featured-posts">

  <div class="tb-featured-inner">
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
    <div class="tb-featured-post" id="<?php echo $post->ID; ?>" style="background-image: url(<?php echo $urlImage; ?>)">
    <?php else :?>
    <div class="tb-featured-post" id="<?php echo $post->ID; ?>">
    <?php endif;?>
      <a href="<?php the_permalink();?>" class="tb-lnk-featured">

        <h1 class="tb-title-1"><?php $tituloPersonalizado = get_post_meta($post->ID, 'titulo_personalizado', true); echo $tituloPersonalizado; ?></h1>

      </a>

    </div>
<?php endwhile; wp_reset_postdata(); ?>
  </div>

    <div class="tb-container tb-thumb-list">

      <div class="tb-box-title">
        <h3>Desta-<br>ques</h3>
        <p>Você precisa ler!</p>
      </div>

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
          while($featurePost->have_posts()) : $featurePost->the_post();?>
            <a href="<?php the_permalink(); ?>" class="tb-thumb-box" data-target="<?php echo $post->ID; ?>">
              <?php echo get_the_post_thumbnail( $post_id, 'medium' ); ?>
            </a>
        <?php endwhile; wp_reset_postdata(); ?>


    </div>

  <a href="<?php echo get_page_link(42486); ?>" class="tb-read-more">Ver últimos posts</a>

</section>

    <div class="tb-choose-category">

      <h1 class="tb-title-section">Encontre um assunto</h1>
      <p class="tb-lead-paragraph tb-divider">Filtre pelo assunto do seu interesse</p>

      <ul class="tb-category-list">
        <li>
          <a href="<?php echo get_category_link( 258 ); ?>" class="tb-icon-html">HTML</a>
        </li>
        <li>
          <a href="<?php echo get_category_link( 172 ); ?>" class="tb-icon-js">JavaScript</a>
        </li>
        <li>
          <a href="<?php echo get_category_link( 40 ); ?>" class="tb-icon-css">CSS/SASS</a>
        </li>
        <li>
          <a href="<?php echo get_category_link( 21 ); ?>" class="tb-icon-mobile">Mobile</a>
        </li>
        <li>
          <a href="<?php echo get_category_link( 403 ); ?>" class="tb-icon-ux">UX</a>
        </li>
        <li>
          <a href="<?php echo get_category_link( 753 ); ?>" class="tb-icon-backend">Back-end</a>
        </li>
        <li>
          <a href="<?php echo get_category_link( 54 ); ?>" class="tb-icon-wordpress">Wordpress</a>
        </li>
        <li>
          <a href="<?php echo get_category_link( 403 ); ?>" class="tb-icon-design">Design</a>
        </li>
        <li>
          <a href="<?php echo get_category_link( 106 ); ?>" class="tb-icon-seo">SEO</a>
        </li>
        <li>
          <a href="http://tableless.github.io/iniciantes/" class="tb-icon-iniciantes">Iniciantes</a>
        </li>
      </ul>
    </div>

<section class="tb-latest-posts">
  <h1 class="tb-title-section">Últimos posts</h1>
  <p class="tb-lead-paragraph tb-divider">Os melhores textos, pelos melhores autores.</p>

  <?php wp_nav_menu(array('menu' => 'Menu Categorias', 'container' => 'nav', 'container_class' => 'tb-categ-menu','menu_class' => 'tb-categ-list', 'depth' => 2)); ?>

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

<section class="tb-section tb-forum-call">
<div class="tb-container">

  <div class="tb-text-call">
    <h1 class="tb-title-1">Fórum</h1>
    <p class="tb-lead-paragraph">Cadastre-se no nosso fórum e compartilhe seu conhecimento com outros centenas entusiastas de desenvolvimento.</p>

    <a href="http://forum.tableless.com.br/" class="tb-btn-wired">Confira!</a>
  </div>

  <figure>
    <img src="<?php bloginfo('template_url');?>/images/img-forum.jpg">
  </figure>
</div>
</section>

<?php get_footer();?>
