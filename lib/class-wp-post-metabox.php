<?php

interface Metabox {

    public function __construct( $key, $options);

    public function update($post_id, $data);
}

class WP_PostMetabox implements Metabox {

    protected $key;

    public function __construct($meta_key, $options = NULL) {
        $this->key = $meta_key;
    }

    public function update($post_id, $data) {

        if(get_post_meta($post_id, $this->key) == '') {
            add_post_meta($post_id, $this->key, $data, true);
        }
        elseif($data != get_post_meta($post_id, $this->key, true)) {
            update_post_meta($post_id, $this->key, $data);
        }
        elseif($data == '') {
            delete_post_meta($post_id, $this->key, get_post_meta($post_id, $this->key, true));
        }

    }

}

class WP_TextMetabox extends WP_PostMetabox {

}

class WP_URLMetabox extends WP_PostMetabox {
    // updates go here
}

class WP_SelectMetabox extends WP_PostMetabox {
    protected $choices;

    public function __construct( $meta_key, $options = NULL ) {
        
        $this->choices = array();
        
        if ( $options['choices'] ) $this->choices = $options['choices'];

        parent::__construct( $meta_key, $options );

    }

    public function update( $post_id, $data ) {

        if ( in_array( $data, $this->choices ) ) $data = $data; else $data = '';

        parent::update( $post_id, $data );
    }

}

// WP_TextareaMetabox
// WP_MediaMetabox

class WP_PostMetaboxFactory {

    public static function create( $key, $options ) {

        if ( $options['type'] ) $type = $options['type']; else $type = 'text';
    
        switch ( $metabox_type ) {
            case 'url':
                            $PostMetabox = new WP_URLMetabox( $key, $options );
                            break;
            case 'select':
                            $PostMetabox = new WP_SelectMetabox( $key, $options );
                            break;
            case 'text':
            case 'int':
            default:
                            $PostMetabox = new WP_TextMetabox( $key, $options );
        }

        return $PostMetabox;
    }
}