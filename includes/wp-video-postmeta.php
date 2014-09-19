<?php

if ( ! interface_exists( 'PostMeta' ) ) {
    interface PostMeta {

        public function __construct( $key, $options = array() );

        public function display_postmeta( $post_id );

        public function update($post_id, $data);
    }
}

if ( ! interface_exists( 'PostMetaFactory' ) ) {
    interface PostMetaFactory {

        public static function get_instance();

        public function create( $key, $options = array() );
    }
}

class WP_VideoMeta implements PostMeta {

    protected $key;
    protected $label;
    protected $size = 40;

    protected $input_type = 'text';

    public function __construct($key, $options = array() ) {
        $this->key = $key;
        if ( $options['label'] ) $this->label = $options['label']; else $this->label = $this->key;
        if ( $options['label'] == 'none' ) $this->label = '';
    }

    public function display_postmeta( $post_id, $data = false ) {
        if ( ! $data ) $data = get_post_meta( $post_id, $this->key, true );
        
        if ( $this->label ) {
            echo '<label for="' . $this->key . '">' . $this->label . '</label>';
        }
        echo '<input type="' . $this->input_type . '" id ="'. $this->key . '" name="' . $this->key . '" value="' . $data . '" size="' . $this->size . '" class="widefat" >';
    }

    public function update( $post_id, $data ) {

        if ( get_post_meta($post_id, $this->key) == '') {
            add_post_meta($post_id, $this->key, $data, true);
        }
        elseif ( $data != get_post_meta($post_id, $this->key, true) ) {
            update_post_meta($post_id, $this->key, $data);
        }
        elseif ( $data == '' ) {
            delete_post_meta($post_id, $this->key, get_post_meta($post_id, $this->key, true));
        }

    }

}

class Video_PostMetaFactory implements PostMetaFactory {

    private static $instance;

    protected function __construct() {
    }

    public static function get_instance() {
        if ( !isset( self::$instance ) ) {
            $class = __CLASS__;
            self::$instance = new $class();
        }

        return self::$instance;
    }

    public function create( $key, $options = array() ) {

        if ( $options['type'] ) $type = $options['type']; else $type = 'text';
    
        switch ( $meta_type ) {
            default:  $PostMeta = new WP_VideoMeta( $key, $options );
        }

        return $PostMeta;
    }
}