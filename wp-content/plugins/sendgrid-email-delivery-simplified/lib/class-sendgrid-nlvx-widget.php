<?php 

require_once plugin_dir_path( __FILE__ ) . 'class-sendgrid-tools.php';
require_once plugin_dir_path( __FILE__ ) . 'class-sendgrid-nlvx.php';
require_once plugin_dir_path( __FILE__ ) . '../vendor/punycode/Punycode.php';

use SendGridTrueBV\Punycode;

class SendGrid_NLVX_Widget extends WP_Widget {
    const DEFAULT_TITLE                 = 'Newsletter Subscription';
    const DEFAULT_MESSAGE               = 'If you want to subscribe to our monthly newsletter, please submit the form below.';
    const DEFAULT_ERROR_MESSAGE         = 'An error occured when processing your details. Please try again.';
    const DEFAULT_ERROR_EMAIL_MESSAGE   = 'Invalid email address.';
    const DEFAULT_SUBSCRIBE_MESSAGE     = 'An email has been sent to your address. Please check your inbox in order to confirm your subscription.';
    const INVALID_EMAIL_ERROR           = 'email_invalid';
    const SUCCESS_EMAIL_SEND            = 'email_sent';
    const ERROR_EMAIL_SEND              = 'email_error_send';
    
    /**
     * Widget class constructor
     *
     * @return  void
     */
    function __construct() {
      parent::__construct(
        'sendgrid_nlvx_widget', 
        'SendGrid Subscription Widget', 
        array(
          'description' => 'SendGrid Marketing Campaigns Subscription Widget'
        )
      );
    }

    /**
     * Method called to render the back-end form (dashboard form)
     *
     * @param   mixed   $instance      the widget instance
     *
     * @return  void
     */
    public function form( $instance ) {
      if ( isset( $instance['title'] ) ) {
        $title = $instance['title'];
      } else {
        $title = self::DEFAULT_TITLE;
      }

      if ( isset( $instance['text'] ) ) {
        $text = $instance['text'];
      } else {
        $text = self::DEFAULT_MESSAGE;
      }

      if ( isset( $instance['error_text'] ) ) {
        $error_text = $instance['error_text'];
      } else {
        $error_text = self::DEFAULT_ERROR_MESSAGE;
      }

      if ( isset( $instance['error_email_text'] ) ) {
        $error_email_text = $instance['error_email_text'];
      } else {
        $error_email_text = self::DEFAULT_ERROR_EMAIL_MESSAGE;
      }

      if ( isset( $instance['success_text'] ) ) {
        $success_text = $instance['success_text'];
      } else {
        $success_text = self::DEFAULT_SUBSCRIBE_MESSAGE;
      }

      // Widget title input
      echo '<p>';
      echo '<label for="' . $this->get_field_id( 'title' ) . '">' . _e( 'Title:' ) . '</label>'; 
      echo '<input class="widefat" id="'. $this->get_field_id( 'title' ) . '" name="' . $this->get_field_name( 'title' ) . ' type="text" value="' . esc_attr( $title ) . '" />';
      echo '</p>';

      // Widget text input
      echo '<p>';
      echo '<label for="' . $this->get_field_id( 'text' ) . '">' . _e( 'Message to display before subscription form:' ) . '</label>'; 
      echo '<input class="widefat" id="' . $this->get_field_id( 'text' ) . '" name="' . $this->get_field_name( 'text' ). '" type="text" value="' . esc_attr( $text ) . '" />';
      echo '</p>';

      // Widget error text input
      echo '<p>';
      echo '<label for="' . $this->get_field_id( 'error_text' ) . '">' . _e( 'Message to display for errors:' ) . '</label>'; 
      echo '<input class="widefat" id="' . $this->get_field_id( 'error_text' ) . '" name="' . $this->get_field_name( 'error_text' ). '" type="text" value="' . esc_attr( $error_text ) . '" />';
      echo '</p>';

      // Widget email error text input
      echo '<p>';
      echo '<label for="' . $this->get_field_id( 'error_email_text' ) . '">' . _e( 'Message to display for invalid email address:' ) . '</label>'; 
      echo '<input class="widefat" id="' . $this->get_field_id( 'error_email_text' ) . '" name="' . $this->get_field_name( 'error_email_text' ). '" type="text" value="' . esc_attr( $error_email_text ) . '" />';
      echo '</p>';

      // Widget success text input
      echo '<p>';
      echo '<label for="' . $this->get_field_id( 'success_text' ) . '">' . _e( 'Message to display for success:' ) . '</label>'; 
      echo '<input class="widefat" id="' . $this->get_field_id( 'success_text' ) . '" name="' . $this->get_field_name( 'success_text' ). '" type="text" value="' . esc_attr( $success_text ) . '" />';
      echo '</p>';
    }
    
    /**
     * Method called to update the widget parameters in the back-end
     *
     * @param   mixed   $new_instance      the new widget instance
     * @param   mixed   $old_instance      the old widget instance
     *
     * @return  mixed   the widget instace to save
     */
    public function update( $new_instance, $old_instance ) {
      $instance = array();
      $instance['title']            = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
      $instance['text']             = ( ! empty( $new_instance['text'] ) ) ? $new_instance['text'] : '';
      $instance['error_text']       = ( ! empty( $new_instance['error_text'] ) ) ? $new_instance['error_text'] : '';
      $instance['error_email_text'] = ( ! empty( $new_instance['error_email_text'] ) ) ? $new_instance['error_email_text'] : '';
      $instance['success_text']     = ( ! empty( $new_instance['success_text'] ) ) ? $new_instance['success_text'] : '';

      return $instance;
    }
    
    /**
     * Method called to render the front-end of the widget
     *
     * @param   mixed   $args       wordpress provided arguments
     * @param   mixed   $instance   the widget instance
     *
     * @return  void
     */
    public function widget( $args, $instance ) {
      $title = self::DEFAULT_TITLE;
      if ( isset( $instance['title'] ) ) {
        $title = apply_filters( 'widget_title', $instance['title'] );
      }

      $text = self::DEFAULT_MESSAGE;
      if ( isset( $instance['text'] ) ) {
        $text = apply_filters( 'widget_text', $instance['text'] );
      }

      $error_text = self::DEFAULT_ERROR_MESSAGE;
      if ( isset( $instance['error_text'] ) ) {
        $error_text = apply_filters( 'widget_text', $instance['error_text'] );
      }

      $error_email_text = self::DEFAULT_ERROR_EMAIL_MESSAGE;
      if ( isset( $instance['error_email_text'] ) ) {
        $error_email_text = apply_filters( 'widget_text', $instance['error_email_text'] );
      }

      $success_text = self::DEFAULT_SUBSCRIBE_MESSAGE;
      if ( isset( $instance['success_text'] ) ) {
        $success_text = apply_filters( 'widget_text', $instance['success_text'] );
      }

      // Theme style
      echo $args['before_widget'];

      if ( ! empty( $title ) ) {
        echo $args['before_title'] . $title . $args['after_title'];
      }

      // Form was submitted
      if ( isset( $_POST['sendgrid_mc_email'] ) ) {
        $process_form_reponse = $this->process_subscription( $_POST );
        if ( self::SUCCESS_EMAIL_SEND == $process_form_reponse ) {
          echo '<p class="sendgrid_widget_text"> ' . $success_text . ' </p>';
        } elseif ( self::INVALID_EMAIL_ERROR == $process_form_reponse ) {
          echo '<p class="sendgrid_widget_text"> ' . $error_email_text . ' </p>';
          $this->display_form();
        } else {
          echo '<p class="sendgrid_widget_text"> ' . $error_text . ' </p>';
          $this->display_form();
        }
      } else {
        // Display form
        if ( ! empty( $text ) ) {
          echo '<p class="sendgrid_widget_text">' . $text . '</p>';
        }

        $this->display_form();
      }

      // Theme style
      echo $args['after_widget'];
    }

    /**
     * Method that processes the subscription params
     *
     * @param   mixed   $params   array of parameters from $_POST
     *
     * @return  void
     */
    private function process_subscription( $params ) {  
      $email_split = explode( "@", $_POST['sendgrid_mc_email'] );

      if ( isset( $email_split[1] ) ) {
        $email_domain = $email_split[1];
        
        try {
          $Punycode = new Punycode();
          $email_domain = $Punycode->decode( $email_split[1] );
        }
        catch ( Exception $e ) { 
        }

        $email = $email_split[0] . '@' . $email_domain;
      } else {
        $email = $_POST['sendgrid_mc_email'];
      }

      // Bad call
      if ( ! isset( $email ) or ! Sendgrid_Tools::is_valid_email( $email ) ) {
        return self::INVALID_EMAIL_ERROR;
      }

      if ( 'true' == Sendgrid_Tools::get_mc_opt_req_fname_lname() and 'true' == Sendgrid_Tools::get_mc_opt_incl_fname_lname() ) {
        if ( ! isset( $_POST['sendgrid_mc_first_name'] ) or empty( $_POST['sendgrid_mc_first_name'] ) ) {
          return self::ERROR_EMAIL_SEND;
        }
        if ( ! isset( $_POST['sendgrid_mc_last_name'] ) or empty( $_POST['sendgrid_mc_last_name'] ) ) {
          return self::ERROR_EMAIL_SEND;
        }
      }

      if ( isset( $_POST['sendgrid_mc_first_name'] ) and isset( $_POST['sendgrid_mc_last_name'] ) ) {
        Sendgrid_OptIn_API_Endpoint::send_confirmation_email( $email, $_POST['sendgrid_mc_first_name'], $_POST['sendgrid_mc_last_name'] );
      } else {
        Sendgrid_OptIn_API_Endpoint::send_confirmation_email( $email );
      }

      return self::SUCCESS_EMAIL_SEND;
    }

    /**
     * Method that displays the subscription form
     *
     * @return  void
     */
    private function display_form() {
      $email_label = htmlspecialchars( Sendgrid_Tools::get_mc_email_label() );
      if ( false == $email_label ) {
        $email_label = Sendgrid_Settings::DEFAULT_EMAIL_LABEL;
      }

      $first_name_label = htmlspecialchars( Sendgrid_Tools::get_mc_first_name_label() );
      if ( false == $first_name_label ) {
        $first_name_label = Sendgrid_Settings::DEFAULT_FIRST_NAME_LABEL;
      }

      $last_name_label = htmlspecialchars( Sendgrid_Tools::get_mc_last_name_label() );
      if ( false == $last_name_label ) {
        $last_name_label = Sendgrid_Settings::DEFAULT_LAST_NAME_LABEL;
      }

      $subscribe_label = htmlspecialchars( Sendgrid_Tools::get_mc_subscribe_label() );
      if ( false == $subscribe_label ) {
        $subscribe_label = Sendgrid_Settings::DEFAULT_SUBSCRIBE_LABEL;
      }

      $input_padding = "padding: ";
      $input_padding .= Sendgrid_Tools::get_mc_input_padding_by_position( 'top' ) . 'px ';
      $input_padding .= Sendgrid_Tools::get_mc_input_padding_by_position( 'right' ) . 'px ';
      $input_padding .= Sendgrid_Tools::get_mc_input_padding_by_position( 'bottom' ) . 'px ';
      $input_padding .= Sendgrid_Tools::get_mc_input_padding_by_position( 'left' ) . 'px;';

      $button_padding = "margin: ";
      $button_padding .= Sendgrid_Tools::get_mc_button_padding_by_position( 'top' ) . 'px ';
      $button_padding .= Sendgrid_Tools::get_mc_button_padding_by_position( 'right' ) . 'px ';
      $button_padding .= Sendgrid_Tools::get_mc_button_padding_by_position( 'bottom' ) . 'px ';
      $button_padding .= Sendgrid_Tools::get_mc_button_padding_by_position( 'left' ) . 'px;';

      $require_fname_lname = '';

      echo '<form method="post" id="sendgrid_mc_email_form" class="mc_email_form" action="#sendgrid_mc_email_subscribe" style="padding-top: 10px;">';
        
      if ( 'true' == Sendgrid_Tools::get_mc_opt_incl_fname_lname() ) {
        if ( 'true' == Sendgrid_Tools::get_mc_opt_req_fname_lname() ) {
          $require_fname_lname = "required";
          $first_name_label .= "<sup>*</sup>";
          $last_name_label .= "<sup>*</sup>";
        }

        echo '<div class="sendgrid_mc_fields" style="' . $input_padding . '">';  
        echo '  <div class="sendgrid_mc_label_div">';
        echo '    <label for="sendgrid_mc_first_name" class="sendgrid_mc_label sendgrid_mc_label_first_name">' . $first_name_label . ' : </label>';
        echo '  </div>';
        echo '  <div class="sendgrid_mc_input_div">';
        echo '    <input class="sendgrid_mc_input sendgrid_mc_input_first_name" id="sendgrid_mc_first_name" name="sendgrid_mc_first_name" type="text" value=""' . $require_fname_lname . ' />';
        echo '  </div>';
        echo '</div>';
        echo '<div class="sendgrid_mc_fields" style="' . $input_padding . '">';
        echo '  <div class="sendgrid_mc_label_div">';
        echo '    <label for="sendgrid_mc_last_name" class="sendgrid_mc_label sendgrid_mc_label_last_name">' . $last_name_label . ' : </label>';
        echo '  </div>';
        echo '  <div class="sendgrid_mc_input_div">';
        echo '    <input class="sendgrid_mc_input sendgrid_mc_input_last_name" id="sendgrid_mc_last_name" name="sendgrid_mc_last_name" type="text" value="" ' . $require_fname_lname . '/>';
        echo '  </div>';
        echo '</div>';
      }

      echo '<div class="sendgrid_mc_fields" style="' . $input_padding . '">';
      echo '  <div class="sendgrid_mc_label_div">';
      echo '    <label for="sendgrid_mc_email" class="sendgrid_mc_label sendgrid_mc_label_email">' . $email_label . '<sup>*</sup> :</label>';
      echo '  </div>';
      echo '  <div class="sendgrid_mc_input_div">';
      echo '    <input class="sendgrid_mc_input sendgrid_mc_input_email" id="sendgrid_mc_email" name="sendgrid_mc_email" type="text" value="" required/>';
      echo '  </div>';
      echo '</div>';

      echo '<div class="sendgrid_mc_button_div">';      
      echo '  <input style="' . $button_padding . '" class="sendgrid_mc_button" type="submit" id="sendgrid_mc_email_submit" value="' . $subscribe_label . '" />';
      echo '</div>';
      echo '</form>';
    }
}
