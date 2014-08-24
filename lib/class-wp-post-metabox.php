<?php

interface PostMeta {

    public function __construct( $key, $options);

    public function update($post_id, $data);
}

class WP_PostMeta implements PostMeta {

    protected $key;

    public function __construct($meta_key, $options = NULL) {
        $this->key = $meta_key;
    }

    public function update( $post_id, $data ) {

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

class WP_TextMeta extends WP_PostMeta {
    // add basic text validation
}

class WP_URLMeta extends WP_PostMeta {
    // url validation goes here
}

class WP_ArrayMeta extends WP_PostMeta {
    // does word press serialize?
}

class WP_SelectMeta extends WP_PostMeta {
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

// WP_TextareaMeta
// WP_MediaMeta

class WP_PostMetaFactory {

    public static function create( $key, $options ) {

        if ( $options['type'] ) $type = $options['type']; else $type = 'text';
    
        switch ( $meta_type ) {
            case 'url':
                            $PostMeta = new WP_URLMeta( $key, $options );
                            break;
            case 'select':
                            $PostMeta = new WP_SelectMeta( $key, $options );
                            break;
            case 'text':
            case 'int':
            default:
                            $PostMeta = new WP_TextMeta( $key, $options );
        }

        return $PostMeta;
    }
}