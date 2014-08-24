<?php

interface WP_PostMetabox {

    public function __construct( $key, $options);

    public function update($post_id, $data);
}

class WP_TextMetabox implements WP_PostMetabox {
    
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

class WP_URLMetabox extends WP_TextMetabox {
    // updates go here
}

/*class WP_SelectMetabox impements WP_PostMetabox {

} */

// WP_TextareaMetabox
// WP_MediaMetabox

class WP_PostMetaboxFactory {

    public static function create( $key, $options ) {

        if ( $options['type'] ) $type = $options['type']; else $type = 'text';
    
        switch ( $metabox_type ) {
            case 'url':
                        $PostMetabox = new WP_URLMetabox( $key, $options );
                        break;
            case 'text':
            case 'int':
            default:
                        $PostMetabox = new WP_TextMetabox( $key, $options );
        }

        return $PostMetabox;
    }
}