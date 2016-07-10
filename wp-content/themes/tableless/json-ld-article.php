<?php // JSON-LD for Wordpress Home Articles and Author Pages written by Pete Wailes and Richard Baxter 

function get_post_data() { global $post;
 return $post;
} // stuff for any page 
  
$payload["@context"] = "http://schema.org/"; // this has all the data of the post/page etc 
$post_data = get_post_data(); // stuff for any page, if it exists 
$category = get_the_category(); // stuff for specific pages 

  if (is_single()) { // this gets the data for the user who wrote that particular item 
    $author_data = get_userdata($post_data->post_author); 
    $post_url = get_permalink(); 
    $post_thumb = wp_get_attachment_url(get_post_thumbnail_id($post->ID)); 
    $payload["@type"] = "Article"; 
    $payload["url"] = $post_url; 
    $payload["author"] = array( "@type" => "Person", "name" => $author_data->display_name, ); 
    $payload["headline"] = $post_data->post_title; 
    $payload["datePublished"] = $post_data->post_date; 
    $payload["image"] = "http://tableless.com.br/wp-content/themes/tableless/images/missing-img.png"; 
    $payload["ArticleSection"] = $category[0]->cat_name; 
    $payload["Publisher"] = array( "@type" => "Organization", "name" => "Tableless", "logo" => "http://tableless.com.br/wp-content/themes/tableless/images/missing-img.png"); 
    $payload["dateModified"] = $post_data->post_date;
    // $payload["logo"] = "http://tableless.com.br/wp-content/themes/tableless/images/missing-img.png";
  } // we do all this separately so we keep the right things for organization together 

  if (is_front_page()) { 
    $payload["@type"] = "Organization";
    $payload["name"] = "Tableless";
    $payload["logo"] = "http://tableless.com.br/wp-content/themes/tableless/images/missing-img.png";
    $payload["url"] = "http://tableless.com.br/";
    $payload["sameAs"] = array( "https://twitter.com/tableless", "https://www.facebook.com/tabelessbr" );
    $payload["contactPoint"] = array( array( "@type" => "ContactPoint", "email" => "contato@tableless.com.br", "contactType" => "contact" ) );
  } 


  if (is_author()) { 
    // this gets the data for the user who wrote that particular item 
    $author_data = get_userdata($post_data->post_author);

    // some of you may not have all of these data points in your user profiles - delete as appropriate 
    // fetch twitter from author meta and concatenate with full twitter URL 
    $twitter_url = " https://twitter.com/";
    $twitterHandle = get_the_author_meta('twitter');
    $twitterHandleURL = $twitter_url . $twitterHandle;
    $websiteHandle = get_the_author_meta('url');
    $payload["@type"] = "Person";
    $payload["name"] = $author_data->display_name;
    $payload["email"] = $author_data->user_email;
    $payload["sameAs"] = array( $twitterHandleURL, $websiteHandle );
  } 
?>