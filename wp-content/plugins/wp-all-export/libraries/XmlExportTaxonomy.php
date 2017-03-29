<?php

if ( ! class_exists('XmlExportTaxonomy') )
{
	final class XmlExportTaxonomy
	{			
		private $init_fields = array(			
			array(
				'label' => 'term_id',
				'name'  => 'Term ID',
				'type'  => 'term_id'
			),						
			array(
				'label' => 'term_name',
				'name'  => 'Term Name',
				'type'  => 'term_name'
			),			 			
			array(
				'label' => 'term_slug',
				'name'  => 'Term Slug',
				'type'  => 'term_slug'
			)			
		);

		private $default_fields = array(
            array(
                'label' => 'term_id',
                'name'  => 'Term ID',
                'type'  => 'term_id'
            ),
            array(
                'label' => 'term_name',
                'name'  => 'Term Name',
                'type'  => 'term_name'
            ),
            array(
                'label' => 'term_slug',
                'name'  => 'Term Slug',
                'type'  => 'term_slug'
            ),
			array(
				'label' => 'term_description',
				'name'  => 'Description',
				'type'  => 'term_description'
			),
			array(
				'label' => 'term_parent_id',
				'name'  => 'Parent ID',
				'type'  => 'term_parent_id'
			),
            array(
                'label' => 'term_parent_name',
                'name'  => 'Parent Name',
                'type'  => 'term_parent_name'
            ),
            array(
                'label' => 'term_parent_slug',
                'name'  => 'Parent Slug',
                'type'  => 'term_parent_slug'
            ),
            array(
                'label' => 'term_posts_count',
                'name'  => 'Count',
                'type'  => 'term_posts_count'
            ),
		);		

		private $advanced_fields = array(					
			
		);			

		public static $is_active = true;

		public function __construct()
		{			

			if ( XmlExportEngine::$exportOptions['export_type'] == 'specific' and ! in_array('taxonomies', XmlExportEngine::$post_types) or XmlExportEngine::$exportOptions['export_type'] == 'advanced'){
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

                unset($sections['media']['additional']['attachments']);
			    unset($sections['cats']);
				unset($sections['other']);

				$sections['cf']['title'] = __("Term Meta", "wp_all_export_plugin");

				return $sections;
			}					

		// [\FILTERS]

		public function init( & $existing_meta_keys = array() )
		{
			if ( ! self::$is_active ) return;

			if ( ! empty(XmlExportEngine::$exportQuery)){
                $terms = XmlExportEngine::$exportQuery->get_terms();
			}			

			if ( ! empty( $terms ) ) {
				foreach ( $terms as $term ) {
					$term_meta = get_term_meta($term->term_id, '');
					if ( ! empty($term_meta)){
						foreach ($term_meta as $record_meta_key => $record_meta_value) {
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