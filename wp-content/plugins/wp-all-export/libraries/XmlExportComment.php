<?php

if ( ! class_exists('XmlExportComment') )
{
	final class XmlExportComment
	{			
		private $init_fields = array(			
			array(
				'label' => 'comment_ID',
				'name'  => 'ID',
				'type'  => 'comment_ID'
			),						
			array(
				'label' => 'comment_author_email',
				'name'  => 'Author Email',
				'type'  => 'comment_author_email'
			),			 			
			array(
				'label' => 'comment_content',
				'name'  => 'Content',
				'type'  => 'comment_content'
			)			
		);

		private $default_fields = array(
			array(
				'label' => 'comment_ID',
				'name'  => 'ID',
				'type'  => 'comment_ID'
			),
			array(
				'label' => 'comment_post_ID',
				'name'  => 'Post ID',
				'type'  => 'comment_post_ID'
			),			
			array(
				'label' => 'comment_author',
				'name'  => 'Author',
				'type'  => 'comment_author'
			),
			array(
				'label' => 'comment_author_email',
				'name'  => 'Author Email',
				'type'  => 'comment_author_email'
			),
			array(
				'label' => 'comment_author_url',
				'name'  => 'Author URL',
				'type'  => 'comment_author_url'
			),
			array(
				'label' => 'comment_author_IP',
				'name'  => 'Author IP',
				'type'  => 'comment_author_IP'
			),
			array(
				'label' => 'comment_date',
				'name'  => 'Date',
				'type'  => 'comment_date'
			),
			array(
				'label' => 'comment_content',
				'name'  => 'Content',
				'type'  => 'comment_content'
			),
			array(
				'label' => 'comment_karma',
				'name'  => 'Karma',
				'type'  => 'comment_karma'
			),
			array(
				'label' => 'comment_approved',
				'name'  => 'Approved',
				'type'  => 'comment_approved'
			),
			array(
				'label' => 'comment_agent',
				'name'  => 'Agent',
				'type'  => 'comment_agent'
			),
			array(
				'label' => 'comment_type',
				'name'  => 'Type',
				'type'  => 'comment_type'
			),
			array(
				'label' => 'comment_parent',
				'name'  => 'Comment Parent',
				'type'  => 'comment_parent'
			),
			array(
				'label' => 'user_id',
				'name'  => 'User ID',
				'type'  => 'user_id'
			)

		);		

		private $advanced_fields = array(					
			
		);			

		public static $is_active = true;

		public function __construct()
		{			

			if ( ( XmlExportEngine::$exportOptions['export_type'] == 'specific' and ! in_array('comments', XmlExportEngine::$post_types) ) 
					or ( XmlExportEngine::$exportOptions['export_type'] == 'advanced' and XmlExportEngine::$exportOptions['wp_query_selector'] != 'wp_comment_query' ) ){ 
				self::$is_active = false;
				return;
			}				
			
			add_filter("wp_all_export_available_sections", 	array( &$this, "filter_available_sections" ), 10, 1);
			add_filter("wp_all_export_init_fields", 		array( &$this, "filter_init_fields"), 10, 1);
			add_filter("wp_all_export_default_fields", 		array( &$this, "filter_default_fields"), 10, 1);
			add_filter("wp_all_export_other_fields", 		array( &$this, "filter_other_fields"), 10, 1);			
		}

		// [FILTERS]			

			/**
			*
			* Filter Init Fields
			*
			*/
			public function filter_init_fields($init_fields){
				return $this->init_fields;
			}

			/**
			*
			* Filter Default Fields
			*
			*/
			public function filter_default_fields($default_fields){
				return $this->default_fields;
			}

			/**
			*
			* Filter Other Fields
			*
			*/
			public function filter_other_fields($other_fields){
				return $this->advanced_fields;
			}	

			/**
			*
			* Filter Sections in Available Data
			*
			*/
			public function filter_available_sections($sections){	
										
				unset($sections['cats']);
				unset($sections['media']);
				unset($sections['other']);

				$sections['cf']['title'] = __("Comment meta", "wp_all_export_plugin");				

				return $sections;
			}					

		// [\FILTERS]

		public function init( & $existing_meta_keys = array() )
		{
			if ( ! self::$is_active ) return;

			global $wp_version;
					
			if ( ! empty(XmlExportEngine::$exportQuery)){
				if ( version_compare($wp_version, '4.2.0', '>=') ) 
				{
					$comments = XmlExportEngine::$exportQuery->get_comments();
				}
				else
				{
					$comments = XmlExportEngine::$exportQuery;
				}
			}

			if ( ! empty( $comments ) ) {
				foreach ( $comments as $comment ) {
					$comment_meta = get_comment_meta($comment->comment_ID, '');					
					if ( ! empty($comment_meta)){
						foreach ($comment_meta as $record_meta_key => $record_meta_value) {
							if ( ! in_array($record_meta_key, $existing_meta_keys) ){
								$to_add = true;
								foreach ($this->default_fields as $default_value) {
									if ( $record_meta_key == $default_value['name'] || $record_meta_key == $default_value['type'] ){
										$to_add = false;
										break;
									}
								}
								if ( $to_add ){
									foreach ($this->advanced_fields as $advanced_value) {
										if ( $record_meta_key == $advanced_value['name'] || $record_meta_key == $advanced_value['type']){
											$to_add = false;
											break;
										}
									}
								}
								if ( $to_add ) $existing_meta_keys[] = $record_meta_key;
							}
						}
					}		
				}
			}	
		}		

		/**
	     * __get function.
	     *
	     * @access public
	     * @param mixed $key
	     * @return mixed
	     */
	    public function __get( $key ) {
	        return $this->get( $key );
	    }	

	    /**
	     * Get a session variable
	     *
	     * @param string $key
	     * @param  mixed $default used if the session variable isn't set
	     * @return mixed value of session variable
	     */
	    public function get( $key, $default = null ) {        
	        return isset( $this->{$key} ) ? $this->{$key} : $default;
	    }
	}
}