<?php

//admin related tasks such as customizing admin columns
class HelloGospelAdmin {
    function __construct() {

        add_action( 'init', array( $this, 'create_post_type' ) );

        //to reorder columns and remove or add a few.
      //  add_filter( 'manage_gospel_posts_columns', array( $this, 'columns_head' ) );

    }
    function columns_head( $defaults ) {

      
        $defaults['reference_gospel'] = 'Lecture';
        
        return $defaults;
    }

    function create_post_type () {
        //to create gospel post type
    register_post_type('gospel', array(
        'public' => true, //make CPT visible to admins and backend users.
        'supports' => array('title', 'editor', 'thumbnail'), //thumbnail will allow feature images for this CPT
  
          'labels' => array(
            'name' => 'Gospel',
            'add_new_item' => 'Add New Gospel' ,
            'edit_item' => 'Edit Gospels',
            'all_items' => 'All Gospels',
            'singular_name' => 'Gospel'
          ),
          'menu_icon' => 'dashicons-welcome-learn-more',
          'show_in_rest' => true
      ));
    }
}

$helloGospelAdmin = new HelloGospelAdmin;

?>