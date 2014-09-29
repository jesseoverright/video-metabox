<?php

if ( ! interface_exists( 'Metabox' ) ) {
    interface Metabox {
        public function __construct( $key, PostMetaFactory $post_meta_factory, $args = array() );

        public function add_metabox();

        public function display_metabox();

        public function save( $post_id );
    }
}

class WP_VideoMetabox implements Metabox {
    protected $key;
    protected $metadata;
    protected $label;
    protected $posttype;
    protected $_post_meta_factory;


    public function __construct( $key, PostMetaFactory $post_meta_factory, $args = array() ) {
        $this->_post_meta_factory = $post_meta_factory;

        $this->key = $key;
        $this->label = $args['label'];
        if ( $args['posttype'] ) $this->posttype = $args['posttype']; else $this->posttype = 'post';

        add_action( 'admin_init', array( $this, 'add_metabox' ) );
        add_action( 'save_post', array( $this, 'save' ) );

    }

    public function add_metabox() {
        add_meta_box( $this->key, $this->label, array( $this, 'display_metabox' ), $this->posttype, 'normal', 'high');
    }

    public function display_metabox() {
        global $post;

        echo '<input type="hidden" name="' . $this->key . '_nonce" id="' . $this->key . '_nonce" value="' . wp_create_nonce( $this->key . '_save' ) . '" />';

        foreach ( $this->metadata as $key => $meta ) {
            $meta->display_postmeta( $post->ID );
        }
    }


    public function save( $post_id ) {    
        if ( !wp_verify_nonce( $_POST[ $this->key . '_nonce'], $this->key . '_save' ) )
            return false;

        if ( !current_user_can( 'edit_post', $post_id )) {
            return false;
        }

        foreach ( $this->metadata as $key => $meta ) {
            $meta->update( $post_id, $_POST[ $key ] );
        }
      
    }
    
}