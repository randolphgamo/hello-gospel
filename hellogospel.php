<?php

/*
Plugin Name: Hello Gospel
Plugin URI: http://wordpress.org/plugins/hello-gospel/
Description: Populate your website with the Good News
Author: Jesus Christ
Version: 0.1
Author URI: https://caj.cm/
*/




/*  class name is Hello Gospel Jesus Plugin */

class HgjPlugin {

    function __construct () {

        add_action( 'init', array( $this, 'create_post_type' ) );

        register_activation_hook(__FILE__, array($this, 'hgj_activate_plugin'));

        //add deactivation hook to remove cron
        register_deactivation_hook(__FILE__, array($this, 'hgj_deactivate_plugin' ));


        add_action ('daily_gospel_hook', array($this, 'daily_generate_gospel'));

        //to call our function only when users are in admin section.
        add_action ('admin_init', array($this, 'daily_generate_gospel'));


        //to add shortcode properties
        add_shortcode( 'gospel', array( $this, 'shortcodeGospel' ) );

        //filter hook to add new columns
        add_filter( 'manage_gospel_posts_columns', array( $this, 'columns_head' ) );

        //action hook to populate new colums
           
        add_action( 'manage_gospel_posts_custom_column', array( $this, 'columns_content' ), 10, 2 );

        //create menu for the plugin
        add_action('admin_menu',  array($this, 'ourMenu'));

        //to register our settings for the Gospel Notification
        add_action('admin_init', array($this, 'ourSettings'));
        
    }


    //function to register our settings
    function ourSettings() {
      

    //create a setting
    register_setting('notificationFields',
                     'gospel-option'  //option name in database
                    );


    //create a section for our settings
    add_settings_section  ('notification-text-section',
                    null, //label for the section
                    null,
                    'gospel-options' //page slug
);

    //add the settings to the section
    add_settings_field('notification-option',
                        'Get email notification everyday?', //label
                        array ($this, 'optionFieldHTML'), //fxn to output the html for our label
                        'gospel-options', //page slug to show this field
                        'notification-text-section' //section where the field will be added
                      );

    }

    //this function is used by add_settings_field to create the html for our field
    function optionFieldHTML() { ?>
        
        <select name = "gospel-option">
          <option value="0" <?php /*Use php to load with corrected selected value */ selected(get_option('gospel-option'), '0')  ?>>NO</option>
          <option value="1" <?php /*Use php to load with corrected selected value */ selected(get_option('gospel-option'), '1')  ?>>YES</option>
        </select>

        <p class="description">Leave value to NO to not receive the gospel publication notification everyday</p>
    <?php }

    
      //menu for plugin
      function  ourMenu() {
       
       // to create submenu for options
        add_submenu_page (
                      'edit.php?post_type=gospel',
                      'Gospel Options',
                      'Options',
                      'manage_options',
                      'gospel-options',
                      array($this, 'optionsSubPage')
        );
      
      }

      function optionsSubPage () { ?>
        <div class="wrap">
          <h1>Gospel Notification Options</h1>
          <form action="options.php" method="POST">
            <?php

              //wordpress function to display messages when user Saves changes
              settings_errors();

              //to output the settings registered using register_setting
              settings_fields('notificationFields');

              //output sections
              do_settings_sections('gospel-options');

              //this is a WordPress function to have submit button
              submit_button();
            ?>
          </form>
        </div>
      <?php } 

    function shortcodeGospel() {

      
      $dateEvangileFrench = date('d-m-Y');
      $path = $dateEvangileFrench;

      $post = get_page_by_path($path, OBJECT, 'gospel');
      $content = apply_filters('the_content', $post->post_content);
      echo wp_kses($content, 'post');

    } //end function shortcodeGospel




    function daily_generate_gospel () {
        $dateEvangile = date('Y-m-d');

        //to use this date in slug
        $dateEvangileFrench = date('d-m-Y');
    
        $response = wp_remote_get( 'https://api.aelf.org/v1/messes/'.$dateEvangile.'/afrique' );
     
        if ( is_array( $response ) && ! is_wp_error( $response ) ) {
        $headers = $response['headers']; // array of http header lines
        $body    = json_decode($response['body'], true); // use the content
    
        
       $introLue = $body["messes"][0]["lectures"][2]["intro_lue"];
       $ref = $body["messes"][0]["lectures"][2]["ref"];

       //to get title in the form Evangile de Jésus Christ selon Jean(jn 12, 15)
       $title = $introLue . ' (' . $ref  .')';

        /******  create content composed up date, 
         * refpassage, then content
         */
        
        $content = '<strong>' . $this->hgj_date_french($dateEvangile, "l j F Y") . '</strong><br/><h3>' .$title . '</h3><br/>';
         

       $content .= $body["messes"][0]["lectures"][2]["contenu"];
    
    
        //to check if this gospel has not yet being added
            $existGospel = new WP_Query(array(
    
                'post_type' => 'gospel',
                'post_status'   => 'publish',
                'meta_query' => array(
                  array( 
                    'key' => 'reference_gospel',
                    'compare' => '=',
                    'value' => $ref
                  )
                )
                  ));
    
                  //if this gospel has not yet being added -> add it.
            if ($existGospel->found_posts == 0) {

              $cat_ID = get_cat_ID('gospel');

      
       wp_insert_post(array(
        'post_type' => 'gospel',
        'post_status' => 'publish',
        'post_content' => $content,
        'post_name' => $dateEvangileFrench,
        'post_category' => array($cat_ID),
      

        'meta_input' => array(
            'reference_gospel' => $ref
        )
    
    ));  

    //send mail after inserting new gospel
    $proofurl = esc_url(site_url('/evangile-du-jour'));
    $headers = array('Content-Type: text/html; charset=UTF-8');

    
    /*

    to check whether the user wants to receive notification about the gospel being published
    the 2nd option in get_option is the fallback value in case get_option for that
    value in db is empty */
    
    if (get_option('gospel-option','0') == '1') {
     wp_mail ( get_option('admin_email'), 
              'New Gospel for '.$dateEvangileFrench, 
              'This gospel was successfully created. Check <a href="'.$proofurl.'">here</a>',
              $headers);
    }


        


    } //end if gospel does not exists
} //end call to api
    } //end daily generate gospel api.

    function create_post_type () {
        //to create gospel post type
    register_post_type('gospel', array(
        'public' => true, //make CPT visible to admins and backend users.
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields' ), //thumbnail will allow feature images for this CPT
      //  'show_in_menu' => false, //to prevent CPT from showing and thus use Custom menu instead
          'labels' => array(
            'name' => 'Gospel',
            'add_new_item' => 'Add New Gospel' ,
            'edit_item' => 'Edit Gospels',
            'all_items' => 'All Gospels',
            'singular_name' => 'Gospel'
          ),
          'menu_icon' => 'dashicons-welcome-learn-more',
          'taxonomies' => array( 'category' ), //to support a category type.
          'show_in_rest' => true
      ));
    }

    function columns_head( $columns ) {

      

      //explanation https://www.smashingmagazine.com/2017/12/customizing-admin-columns-wordpress/ 
       // $columns['reference_gospel'] = 'Textes';

        $columns = array (
            'cb' => $columns['cb'],
            'date' => 'Date',
          //  'title' => 'Title',
            'ref_gospel' => 'Reference'
        );
        
        return $columns;
    }



    function columns_content ( $column, $post_id) {
      if ($column == 'ref_gospel') {
        
       
        echo '<a href="'.esc_url(get_edit_post_link($post_id)).'">'. esc_html(get_post_meta( $post_id, 'reference_gospel', true)) .'</a>';
      }

      

    }



function hgj_activate_plugin () {

    //cron job
    //https://developer.wordpress.org/reference/functions/wp_schedule_event/
    //wp_schedule_event(time(), 'daily', 'daily_gospel_hook');

    //wp_schedule_event(strtotime('midnight'),'daily', 'daily_gospel_hook');

    wp_schedule_event(strtotime('01:00 a.m.'),'daily', 'daily_gospel_hook');

    //wp_schedule_event(strtotime('12:00 a.m.'),'daily', 'daily_gospel_hook');

        /* create new category that will house all our gospels */  
        $cat_ID = get_cat_ID('gospel');

        //If not create new category
        if( !$cat_ID ) {
            $arg = array( 'description' => "The Gospel of our Lord Jesus", 'parent' => 0 );
            $cat_ID = wp_insert_term('gospel', "category", $arg);
        }

    

}
//this function would be called upon deactivation of plugin
function hgj_deactivate_plugin() {
  wp_clear_scheduled_hook('daily_gospel_hook');

}


function hgj_date_french($date, $format) 
{
    $english_days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
    $french_days = array('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche');
    $english_months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
    $french_months = array('janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre');
    return str_replace($english_months, $french_months, str_replace($english_days, $french_days, date($format, strtotime($date) ) ) );
}



  

    
}

$my_plugin = new HgjPlugin() ;






