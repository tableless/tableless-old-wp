<?php
if ( ! class_exists( 'SGVirtualPage' ) )
{
  class SGVirtualPage
  {
    private $slug = NULL;
    private $title = NULL;
    private $content = NULL;
    private $author = NULL;
    private $date = NULL;
    private $type = NULL;

    public function __construct( $args )
    {
      if ( ! isset( $args['slug'] ) ) {
        throw new Exception( 'No slug given for virtual page' );
      }

      $this->slug     = $args['slug'];
      $this->title    = isset( $args['title'] ) ? $args['title'] : '';
      $this->content  = isset( $args['content'] ) ? $args['content'] : '';
      $this->author   = isset( $args['author'] ) ? $args['author'] : 1;
      $this->date     = isset( $args['date'] ) ? $args['date'] : current_time( 'mysql' );
      $this->dategmt  = isset( $args['date'] ) ? $args['date'] : current_time( 'mysql', 1 );
      $this->type     = isset( $args['type'] ) ? $args['type'] : 'page';

      add_filter( 'the_posts', array( &$this, 'virtualPage' ) );
    }

    /**
     * Filter to create virtual page content
     *
     * @param   mixed  $posts  posts saved in wp
     * @return  mixed  $posts  posts saved in wp
     */
    public function virtualPage( $posts )
    {
      global $wp, $wp_query;

      if ( count( $posts ) == 0 and
        ( strcasecmp( $wp->request, $this->slug ) == 0 or $wp->query_vars['page_id'] == $this->slug ) )
      {
        $post = new stdClass;

        $post->ID                     = -1;
        $post->post_author            = $this->author;
        $post->post_date              = $this->date;
        $post->post_date_gmt          = $this->dategmt;
        $post->post_content           = $this->content;
        $post->post_title             = $this->title;
        $post->post_excerpt           = '';
        $post->post_status            = 'publish';
        $post->comment_status         = 'closed';
        $post->ping_status            = 'closed';
        $post->post_password          = '';
        $post->post_name              = $this->slug;
        $post->to_ping                = '';
        $post->pinged                 = '';
        $post->modified               = $post->post_date;
        $post->modified_gmt           = $post->post_date_gmt;
        $post->post_content_filtered  = '';
        $post->post_parent            = 0;
        $post->guid                   = get_home_url('/' . $this->slug);
        $post->menu_order             = 0;
        $post->post_tyle              = $this->type;
        $post->post_mime_type         = '';
        $post->comment_count          = 0;

        $posts = array( $post );

        $wp_query->is_page      = TRUE;
        $wp_query->is_singular  = TRUE;
        $wp_query->is_home      = FALSE;
        $wp_query->is_archive   = FALSE;
        $wp_query->is_category  = FALSE;

        unset( $wp_query->query['error'] );
        $wp_query->query_vars['error']  = '';
        $wp_query->is_404               = FALSE;
      }

      return $posts;
    }
  }
}

/**
 * Filter to create general error page
 *
 * @return  void
 */
function sg_create_subscribe_general_error_page()
{
  $url = basename( $_SERVER['REQUEST_URI'] );

  if ( $url == 'sg-error' )
  {
    $args = array('slug' => 'sg-error',
              'title' => 'Subscribe error',
              'content' => 'Something went wrong while trying to send information.' );
    $pg = new SGVirtualPage( $args );
  }
}

/**
 * Filter to create invalid token error page
 *
 * @return  void
 */
function sg_create_subscribe_invalid_token_error_page()
{
  $url = basename( $_SERVER['REQUEST_URI'] );

  if ( $url == 'sg-subscription-invalid-token' )
  {
    $args = array( 'slug' => 'sg-subscription-invalid-token',
              'title' => 'Subscribe error',
              'content' => 'Token is invalid, you are not subscribed to our newsletter.' );
    $pg = new SGVirtualPage( $args );
  }
}

/**
 * Filter to create missing token error page
 *
 * @return  void
 */
function sg_create_subscribe_missing_token_error_page()
{
  $url = basename( $_SERVER['REQUEST_URI'] );

  if ( $url == 'sg-subscription-missing-token' )
  {
    $args = array( 'slug' => 'sg-subscription-missing-token',
              'title' => 'Subscribe error',
              'content' => 'Token is missing, you are not subscribed to our newsletter.' );
    $pg = new SGVirtualPage( $args );
  }
}

/**
 * Filter to create subscribe success page
 *
 * @return  void
 */
function sg_create_subscribe_success_page()
{
  $url = basename( $_SERVER['REQUEST_URI'] );

  if ( $url == 'sg-subscription-success' )
  {
    $args = array( 'slug' => 'sg-subscription-success',
          'title' => 'Subscribe success',
          'content' => 'You have been successfully subscribed to our newsletter.' );
    $pg = new SGVirtualPage( $args );
  }
}