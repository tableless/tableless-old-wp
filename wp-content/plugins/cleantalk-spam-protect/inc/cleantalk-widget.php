<?php

class cleantalk_widget extends WP_Widget
{

	function __construct()
	{
		parent::__construct(
			// Base ID of your widget
			'cleantalk_widget', 
		
			// Widget name will appear in UI
			__('CleanTalk Widget', 'cleantalk'), 
		
			// Widget description
			array( 'description' => __( 'CleanTalk widget', 'cleantalk' ), ) 
		);
	}

	// Creating widget front-end
	// This is where the action happens
	public function widget( $args, $instance )
	{
		$title = apply_filters( 'widget_title', $instance['title'] );
		// before and after widget arguments are defined by themes
		echo $args['before_widget'];
		if ( ! empty( $title ) )
		{
			echo $args['before_title'] . $title . $args['after_title'];
		}
		global $ct_data;
		$ct_data=ct_get_data();
		if(!isset($ct_data['admin_blocked']))
		{
			$blocked=0;
		}
		else
		{
			$blocked=$ct_data['admin_blocked'];
		}
		$blocked = number_format($blocked, 0, ',', ' ');

		// This is where you run the code and display the output
		?>
		<div style="width:auto;">
			<a href="http://cleantalk.org" target="_blank" title="" style="background: #3090C7;    background-image: -moz-linear-gradient(0% 100% 90deg,#2060a7,#3090C7);    background-image: -webkit-gradient(linear,0% 0,0% 100%,from(#3090C7),to(#2060A7));    border: 1px solid #33eeee;    border-radius: 5px;    color: #AFCA63;    cursor: pointer;    display: block;    font-weight: normal;    height: 100%;    -moz-border-radius: 5px;    padding: 5px 0 5px;    text-align: center;    text-decoration: none;    -webkit-border-radius: 5px;    width: 100%;"><strong style="color: #FFF;display: block;font-size: 15px; line-height: 16px; padding: 0 13px;  white-space: nowrap;"><b><?php print $blocked; ?></b> spam</strong> blocked by <strong>CleanTalk</strong></a>
		</div>
		<?php
		echo $args['after_widget'];
	}
		
	// Widget Backend 
	public function form( $instance )
	{
		if ( isset( $instance[ 'title' ] ) )
		{
			$title = $instance[ 'title' ];
		}
		else
		{
			$title = __( 'Spam blocked', 'cleantalk' );
		}
		// Widget admin form
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php 
	}
		
	// Updating widget replacing old instances with new
	public function update( $new_instance, $old_instance )
	{
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		return $instance;
	}
} // Class cleantalk_widget ends here

// Register and load the widget
function cleantalk_load_widget()
{
	register_widget( 'cleantalk_widget' );
}
add_action( 'widgets_init', 'cleantalk_load_widget' );
?>