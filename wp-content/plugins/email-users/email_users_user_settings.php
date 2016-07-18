<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 *  Copyright 2012 Mike Walsh - mpwalsh8@gmail.com
 *
 *  This code is derived from the Custom List Table Example plugin.
 *
 *  @see http://codex.wordpress.org/Class_Reference/WP_List_Table
 *  @see http://wordpress.org/extend/plugins/custom-list-table-example/
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
?>

<?php 
    if (!current_user_can('manage_options')) {
        wp_die(printf('<div class="error fade"><p>%s</p></div>',
            __('You are not allowed to view the user settings.', MAILUSERS_I18N_DOMAIN)));
    } 

/*************************** LOAD THE BASE CLASS *******************************
 *******************************************************************************
 * The WP_List_Table class isn't automatically available to plugins, so we need
 * to check if it's available and load it if necessary.
 */
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


/************************** CREATE A PACKAGE CLASS *****************************
 *******************************************************************************
 * Create a new list table package that extends the core WP_List_Table class.
 * WP_List_Table contains most of the framework for generating the table, but we
 * need to define and override some methods so that our data can be displayed
 * exactly the way we need it to be.
 * 
 * To display this example on a page, you will first need to instantiate the class,
 * then call $yourInstance->prepare_items() to handle any data manipulation, then
 * finally call $yourInstance->display() to render the table to the page.
 * 
 */
class MailUsers_List_Table extends WP_List_Table {
    
    /** ************************************************************************
     * REQUIRED. Set up a constructor that references the parent constructor. We 
     * use the parent reference to set some default configs.
     ***************************************************************************/
    function __construct() {
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'user',     //singular name of the listed records
            'plural'    => 'users',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
        
    }
    
    
    /** ************************************************************************
     * Recommended. This method is called when the parent class can't find a method
     * specifically build for a given column. Generally, it's recommended to include
     * one method for each column you want to render, keeping your package class
     * neat and organized. For example, if the class needs to process a column
     * named 'last_name', it would first see if a method named $this->column_last_name() 
     * exists - if it does, that method will be used. If it doesn't, this one will
     * be used. Generally, you should try to use custom column methods as much as 
     * possible. 
     * 
     * Since we have defined a column_last_name() method later on, this method doesn't
     * need to concern itself with any column with a name of 'last_name'. Instead, it
     * needs to handle everything else.
     * 
     * For more detailed insight into how columns are handled, take a look at 
     * WP_List_Table::single_row_columns()
     * 
     * @param array $item A singular item (one full row's worth of data)
     * @param array $column_name The name/slug of the column to be processed
     * @return string Text or HTML to be placed inside the column <td>
     **************************************************************************/
    function column_default($item, $column_name) {
        switch($column_name){
            //case 'last_name':
            //case 'first_name':
            case 'display_name':
            case 'user_login':
            case 'user_email':
                return $item->$column_name;
            //case 'notifications':
            //case 'massemail':
            case MAILUSERS_ACCEPT_NOTIFICATION_USER_META:
            case MAILUSERS_ACCEPT_MASS_EMAIL_USER_META:
                return ($item->$column_name == 'true') ? __('On', MAILUSERS_I18N_DOMAIN) : __('Off', MAILUSERS_I18N_DOMAIN) ;
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }
    
        
    /** ************************************************************************
     * Recommended. This is a custom column method and is responsible for what
     * is rendered in any column with a name/slug of 'last_name'. Every time the class
     * needs to render a column, it first looks for a method named 
     * column_{$column_last_name} - if it exists, that method is run. If it doesn't
     * exist, column_default() is called instead.
     * 
     * This example also illustrates how to implement rollover actions. Actions
     * should be an associative array formatted as 'slug'=>'link html' - and you
     * will need to generate the URLs yourself. You could even ensure the links
     * 
     * 
     * @see WP_List_Table::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (last_name only)
     **************************************************************************/
    function column_last_name($item) {
        
        //Build row actions
        $actions = array(
		    'edit' => sprintf('<a href="%s%s%s">Edit User Profile</a>',
		        get_admin_url(), 'user-edit.php?user_id=',$item->ID),
        );
        
        //Return the last_name contents
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            /*$1%s*/ $item->last_name,
            /*$2%s*/ $item->ID,
            /*$3%s*/ $this->row_actions($actions)
        );
    }
    
    /** ************************************************************************
     * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
     * is given special treatment when columns are processed. It ALWAYS needs to
     * have it's own method.
     * 
     * @see WP_List_Table::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (movie title only)
     **************************************************************************/
    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("last_name")
            /*$2%s*/ $item->ID                //The value of the checkbox should be the record's id
        );
    }
    
    
    /** ************************************************************************
     * REQUIRED! This method dictates the table's columns and titles. This should
     * return an array where the key is the column slug (and class) and the value 
     * is the column's title text. If you need a checkbox for bulk actions, refer
     * to the $columns array below.
     * 
     * The 'cb' column is treated differently than the rest. If including a checkbox
     * column in your table you must create a column_cb() method. If you don't need
     * bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
     * 
     * @see WP_List_Table::::single_row_columns()
     * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
     **************************************************************************/
    function get_columns(){
        $columns = array(
            'cb'            => '<input type="checkbox" />', //Render a checkbox instead of text
            //'last_name'     => __('Last Name', MAILUSERS_I18N_DOMAIN),
            //'first_name'    => __('First Name', MAILUSERS_I18N_DOMAIN),
            'display_name'    => __('Display Name', MAILUSERS_I18N_DOMAIN),
            'user_login'    => __('Username', MAILUSERS_I18N_DOMAIN),
            'user_email'    => __('E-Mail Address', MAILUSERS_I18N_DOMAIN),
            //'notifications' => __('Notifications', MAILUSERS_I18N_DOMAIN),
            //'massemail'     => __('Mass Email', MAILUSERS_I18N_DOMAIN)
            MAILUSERS_ACCEPT_NOTIFICATION_USER_META => __('Notifications', MAILUSERS_I18N_DOMAIN),
            MAILUSERS_ACCEPT_MASS_EMAIL_USER_META   => __('Mass Email', MAILUSERS_I18N_DOMAIN)
        );
        return $columns;
    }
    
    /** ************************************************************************
     * Optional. If you want one or more columns to be sortable (ASC/DESC toggle), 
     * you will need to register it here. This should return an array where the 
     * key is the column that needs to be sortable, and the value is db column to 
     * sort by. Often, the key and value will be the same, but this is not always
     * the case (as the value is a column name from the database, not the list table).
     * 
     * This method merely defines which columns should be sortable and makes them
     * clickable - it does not handle the actual sorting. You still need to detect
     * the ORDERBY and ORDER querystring variables within prepare_items() and sort
     * your data accordingly (usually by modifying your query).
     * 
     * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
     **************************************************************************/
    function get_sortable_columns() {
        $sortable_columns = array(
            //'last_name'     => array('last_name', true),     //true means its already sorted
            //'first_name'    => array('first_name', true),
            'display_name'    => array('display_name', true),
            'user_login'    => array('user_login', true),
            'user_email'    => array('user_email', false),
            //'notifications' => array('notifications', false),
            //'massemail'     => array('massemail', false)
            MAILUSERS_ACCEPT_NOTIFICATION_USER_META => array(MAILUSERS_ACCEPT_NOTIFICATION_USER_META, true),
            MAILUSERS_ACCEPT_MASS_EMAIL_USER_META   => array(MAILUSERS_ACCEPT_MASS_EMAIL_USER_META, true)
        );
        return $sortable_columns;
    }
    
    
    /** ************************************************************************
     * Optional. If you need to include bulk actions in your list table, this is
     * the place to define them. Bulk actions are an associative array in the format
     * 'slug'=>'Visible Title'
     * 
     * If this method returns an empty value, no bulk action will be rendered. If
     * you specify any bulk actions, the bulk actions box will be rendered with
     * the table automatically on display().
     * 
     * Also note that list tables are not automatically wrapped in <form> elements,
     * so you will need to create those manually in order for bulk actions to function.
     * 
     * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
     **************************************************************************/
    function get_bulk_actions() {
        $actions = array(
            'notifications_on'            => __('Notifications On', MAILUSERS_I18N_DOMAIN),
            'notifications_off'           => __('Notifications Off', MAILUSERS_I18N_DOMAIN),
            'mass_email_on'               => __('Mass Email On', MAILUSERS_I18N_DOMAIN),
            'mass_email_off'              => __('Mass Email Off', MAILUSERS_I18N_DOMAIN),
            'notifications_on_email_on'   => __('Notifications On & Mass Email On', MAILUSERS_I18N_DOMAIN),
            'notifications_on_email_off'  => __('Notifications On & Mass Email Off', MAILUSERS_I18N_DOMAIN),
            'notifications_off_email_on'  => __('Notifications Off & Mass Email On', MAILUSERS_I18N_DOMAIN),
            'notifications_off_email_off' => __('Notifications Off & Mass Email Off', MAILUSERS_I18N_DOMAIN)
        );
        return $actions;
    }
    
    
    /** ************************************************************************
     * Optional. You can handle your bulk actions anywhere or anyhow you prefer.
     * For this example package, we will handle it in the class to keep things
     * clean and organized.
     * 
     * @see $this->prepare_items()
     **************************************************************************/
    function process_bulk_action() {
        
        $c = 0 ;
        $actions = $this->get_bulk_actions() ;

        //printf('<pre>%s</pre>', print_r($_GET, true)) ;

        if (($this->current_action() !== false) && array_key_exists('user', $_GET))
        {
            error_log(print_r($_GET['user'], true)) ;
            foreach ($_GET['user'] as $user)
            {
                switch ($this->current_action())
                {
                    case 'notifications_on':
                        $c++ ;
                        update_user_meta($user, MAILUSERS_ACCEPT_NOTIFICATION_USER_META, 'true');
                        break ;

                    case 'notifications_off':
                        $c++ ;
                        update_user_meta($user, MAILUSERS_ACCEPT_NOTIFICATION_USER_META, 'false');
                        break ;

                    case 'mass_email_on':
                        $c++ ;
		                update_user_meta($user, MAILUSERS_ACCEPT_MASS_EMAIL_USER_META, 'true');
                        break ;

                    case 'mass_email_off':
                        $c++ ;
		                update_user_meta($user, MAILUSERS_ACCEPT_MASS_EMAIL_USER_META, 'false');
                        break ;

                    case 'notifications_on_email_on':
                        $c++ ;
		                update_user_meta($user, MAILUSERS_ACCEPT_MASS_EMAIL_USER_META, 'true');
                        update_user_meta($user, MAILUSERS_ACCEPT_NOTIFICATION_USER_META, 'true');
                        break ;

                    case 'notifications_on_email_off':
                        $c++ ;
		                update_user_meta($user, MAILUSERS_ACCEPT_MASS_EMAIL_USER_META, 'false');
                        update_user_meta($user, MAILUSERS_ACCEPT_NOTIFICATION_USER_META, 'true');
                        break ;

                    case 'notifications_off_email_on':
                        $c++ ;
		                update_user_meta($user, MAILUSERS_ACCEPT_MASS_EMAIL_USER_META, 'true');
                        update_user_meta($user, MAILUSERS_ACCEPT_NOTIFICATION_USER_META, 'false');
                        break ;

                    case 'notifications_off_email_off':
                        $c++ ;
		                update_user_meta($user, MAILUSERS_ACCEPT_MASS_EMAIL_USER_META, 'false');
                        update_user_meta($user, MAILUSERS_ACCEPT_NOTIFICATION_USER_META, 'false');
                        break ;
                }
            }

            printf('<div class="updated fade"><h4>%s for %d user%s.</h4></div>',
                $actions[$this->current_action()], $c, $c == 1 ? '' : 's') ;
        }
        else if ($this->current_action() !== false)
        {
            printf('<div class="error fade"><h4>%s.</h4></div>',
                __('No users selected', MAILUSERS_I18N_DOMAIN)) ;
        }
    }
    
    //  This is a work-in-progress function which uses get_users()
    //  instead of an SQL query.  The sorting doesn't work correctly
    //  so it may not work as a replacement for the SQL based version.

    /***************************************************************************
     * REQUIRED! This is where you prepare your data for display. This method will
     * usually be used to query the database, sort and filter the data, and generally
     * get it ready to be displayed. At a minimum, we should set $this->items and
     * $this->set_pagination_args(), although the following properties and methods
     * are frequently interacted with here...
     * 
     * @uses $this->_column_headers
     * @uses $this->items
     * @uses $this->get_columns()
     * @uses $this->get_sortable_columns()
     * @uses $this->get_pagenum()
     * @uses $this->set_pagination_args()
     **************************************************************************/
    function prepare_items() {
        global $wpdb, $_wp_column_headers;
        $screen = get_current_screen() ;
        
        /**
         * First, lets decide how many records per page to show
         */
        $per_page = mailusers_get_user_settings_table_rows() ;

        if ($per_page === false) $per_page = 10 ;

        /* -- Pagination parameters -- */
        //Number of elements in your table?
        $totalitems = count_users() ;

        //Search?
        $search = !empty($_GET['s']) ? esc_sql($_GET['s']) : '';

        //Which page is this?
        $paged = !empty($_GET['paged']) ? esc_sql($_GET['paged']) : '';

        //Page Number
        if (empty($paged) || !is_numeric($paged) || $paged <= 0 ) $paged=1;

        //How many pages do we have in total?
        $totalpages = ceil($totalitems['total_users']/$per_page);

        //adjust the query to take pagination into account
        if(!empty($paged) && !empty($per_page)){
            $offset=($paged-1)*$per_page;
            //$query.=' LIMIT '.(int)$offset.','.(int)$per_page;
        }
 
        /* -- Ordering parameters -- */
        //Parameters that are going to be used to order the result
        $orderby = !empty($_GET['orderby']) ? esc_sql($_GET['orderby']) : 'display_name';
        $order = !empty($_GET['order']) ? esc_sql($_GET['order']) : 'ASC';
 
        /* -- Register the pagination -- */
        $this->set_pagination_args( array(
            'total_items' => $totalitems['total_users'],
            'total_pages' => $totalpages,
            'per_page' => $per_page,
        ) );
        //The pagination links are automatically built according to those parameters
 
        /**
         * REQUIRED. Now we need to define our column headers. This includes a complete
         * array of columns to be displayed (slugs & titles), a list of columns
         * to keep hidden, and a list of columns that are sortable. Each of these
         * can be defined in another method (as we've done here) before being
         * used to build the value for our _column_headers property.
         */
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        
        /**
         * REQUIRED. Finally, we build an array to be used by the class for column 
         * headers. The $this->_column_headers property takes an array which contains
         * 3 other arrays. One for all columns, one for hidden columns, and one
         * for sortable columns.
         */
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        
        /**
         * Optional. You can handle your bulk actions however you see fit. In this
         * case, we'll handle them within our package just to keep things clean.
         */
        $this->process_bulk_action();
        
        
        /* -- Fetch the items -- */
        $args = array(
            'fields' => 'all_with_meta'
           ,'order' => $order
           ,'orderby' => $orderby
           ,'number' => (int)$per_page
           ,'offset' => (int)$offset
           ,'search' => $search
           ,'search_columns' => array( 'user_login', 'user_email', 'display_name', 'user_nicename' )
           ,'count_total' => true
        );

        //  Retrieve data
        $this->items = get_users($args) ;

        //  Need to adjust pagination?
        //  Only when doing a search as results will not match original total item count.

        if (!empty($search))
        {
            //  Don't limit the query
            unset($args['number']) ;

            /* -- Pagination parameters -- */
            //Number of elements in your results?
            $totalitems = count(get_users($args)) ;

            //How many pages do we have in total?
            $totalpages = ceil($totalitems/$per_page);

            /* -- Register the pagination -- */
            $this->set_pagination_args( array(
                'total_items' => $totalitems,
                'total_pages' => $totalpages,
                'per_page' => $per_page,
            ) );
            //The pagination links are automatically built according to those parameters
        }
    }
}


/***************************** RENDER PAGE CONTENT ********************************
 **********************************************************************************
 * This function renders the admin page and the example list table. Although it's
 * possible to call prepare_items() and display() from the constructor, there
 * are often times where you may need to include logic here between those steps,
 * so we've instead called those methods explicitly. It keeps things flexible, and
 * it's the way the list tables are used in the WordPress core.
 */
function mailusers_render_list_page(){
    
    //Create an instance of our package class...
    $mailusersListTable = new MailUsers_List_Table();

    //Fetch, prepare, sort, and filter our data...
    $mailusersListTable->prepare_items();

    ?>
    <div class="wrap">
        
        <div id="icon-users" class="icon32"><br/></div>
        <h2>Email Users</h2>
        
        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        <form id="email-users-filter" method="get">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <!-- Now we can render the completed list table -->
            <?php $mailusersListTable->search_box(__('Search', MAILUSERS_I18N_DOMAIN), 'search_id'); ?>
            <?php $mailusersListTable->display() ; ?>
        </form>
        
    </div>
    <?php
}

mailusers_render_list_page() ;
?>
