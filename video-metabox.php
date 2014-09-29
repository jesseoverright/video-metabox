<?php
/**
 * Plugin Name: Video Metabox
 * Plugin URI: http://wordpress.org/support/plugin/video-metabox
 * Description: Adds a video metabox to blog posts and pages.
 * Version: 1.2.4
 * Author: Jesse Overright
 * Author URI: http://jesseoverright.com
 * License: GPL2
 */

/*  Copyright 2013  Jesse Overright  (email : jesseoverright@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


// check for presence of wp-metabox plugin
function video_metabox_init() {

    include_once( dirname( __FILE__ ) . '/includes/wp-video-metabox.php' );
    // only load included metabox framework if wp-metabox plugin isn't active
    if ( ! function_exists( 'WP_Metabox' ) ) {
        include_once( dirname( __FILE__ ) . '/includes/wp-video-postmeta.php' );
    }
    
    if ( !class_exists('Video_Metabox') ) :

    class Video_Metabox extends WP_VideoMetabox {

        private static $instance;
        protected $_post_meta_factory;

        protected $supported_types = array (
            'vimeo' => 'vimeo',
            'youtube' => 'youtube',
            'pbs' => 'pbs'
        );

        public static function get_instance() {
            if ( !isset( self::$instance ) ) {
                $class = __CLASS__;
                if ( class_exists ( 'WP_PostMetaFactory' ) ) {
                    self::$instance = new $class( 'video-metabox', WP_PostMetaFactory::get_instance() );
                } else {
                    self::$instance = new $class( 'video-metabox', Video_PostMetaFactory::get_instance() );
                }
            }

            return self::$instance;
        }
        
        public function __construct( $key, PostMetaFactory $post_meta_factory, $args = array() ) {
            parent::__construct( $key, $post_meta_factory, array(
                'label' => 'Video',
                'posttype' => 'post',
                )
            );

            $this->metadata = array (
                'video_url' => $this->_post_meta_factory->create(
                    'video_url',
                    array(
                        'type' => 'url',
                        'label' => 'Video URL'
                    )
                ),
                'video_id' => $this->_post_meta_factory->create( 'video_id', array( 'type' => 'int' ) ),
                'video_type' => $this->_post_meta_factory->create( 
                    'video_type', 
                    array( 
                        'type' => 'select',
                        'choices' => $this->supported_types
                    )
                )
            );

            // enqueue video metabox css
            add_action('init', array($this, 'add_video_metabox_css') );

            // hook into the_content and display the video when applicable.
            add_filter( 'the_content' , array($this, 'display_video') );
        }

        public function add_metabox () {

            parent::add_metabox();

            $this->add_video_metabox_css();
        }

        public function add_video_metabox_css() {
            wp_enqueue_style( 'video-metabox-css', plugins_url( '/video-metabox/video-metabox.css' ) );
        }

        public function display_video( $content ) {
            global $post;
            if (get_post_meta($post->ID,'video_id',true) != '') {
                $content = $this->render_video(get_post_meta($post->ID,'video_id',true),get_post_meta($post->ID,'video_type',true),640) . $content;
            }
            return $content;
        }

        public function display_metabox () {
            global $post;

            echo '<input type="hidden" name="' . $this->key . '_nonce" id="' . $this->key . '_nonce" value="' . wp_create_nonce( $this->key . '_save' ) . '" />';

            $video_id = get_post_meta($post->ID, 'video_id', true);
            $video_type = get_post_meta($post->ID, 'video_type', true);

            // display rendered video in metabox
            if ($video_id != '' && $video_type != '') {
                $this->render_video($video_id, $video_type);
            }

            $this->metadata['video_url']->display_postmeta( $post->ID );
        }

        public function save( $post_id ) {    
            
            if ( !wp_verify_nonce( $_POST[ $this->key . '_nonce'], $this->key . '_save' ) )
                return $post_id;

            $this->metadata['video_url']->update( $post_id, $_POST['video_url'] );

            // srape url for video id & type
            $video_details = $this->scrape_url( get_post_meta( $post_id, 'video_url', true ) );

            $this->metadata['video_id']->update( $post_id, $video_details[ 'video_id' ] );

            $this->metadata['video_type']->update( $post_id, $video_details[ 'video_type' ] );
          
        }

        protected function scrape_url($video_url) {
            # validate user input for url structure
            if ( filter_var($video_url, FILTER_VALIDATE_URL) === FALSE )
                return false;

            # get url query string, url path and host
            $parsed_url = parse_url( $video_url );

            switch ($parsed_url['host']) {
                case 'vimeo.com':
                case 'www.vimeo.com':
                    $video_details = array (
                        'video_id' => ltrim($parsed_url['path'],'/'),
                        'video_type' => 'vimeo',
                    );
                    break;
                case 'youtu.be':
                    $video_details = array (
                        'video_id' => ltrim($parsed_url['path'],'/'),
                        'video_type' => 'youtube',
                    );
                    break;
                case 'youtube.com':
                case 'www.youtube.com':
                    parse_str( $parsed_url['query'], $query_vars);

                    $video_details = array (
                        'video_id' => $query_vars['v'],
                        'video_type' => 'youtube',
                    );
                    break;
                case 'video.pbs.org':
                case 'video.klru.tv':
                    $url_path = explode('/', rtrim($parsed_url['path'],'/') );

                    $video_details = array (
                        'video_id' => $url_path[count($url_path)-1],
                        'video_type' => 'pbs',
                    );
                 break;
            }    

            return $video_details;

        }

        protected function render_video($video_id, $video_type, $return_rendered_video = false) {
            
            switch ($video_type) {
                case 'vimeo':
                    $embed = "<div class=\"video-metabox\"><iframe src=\"http://player.vimeo.com/video/{$video_id}?title=0&byline=0&portrait=0&color=ffffff\" frameborder=\"0\" webkitAllowFullScreen allowFullScreen></iframe></div>";
                    break;
                case 'youtube':
                    $embed = "<div class=\"video-metabox\"><iframe src=\"http://www.youtube.com/embed/{$video_id}/?modestbranding=1&rel=0&showinfo=0\" frameborder=\"0\" allowfullscreen></iframe></div>";
                    break;
                case 'pbs':
                    $embed = "<div class=\"video-metabox\"><iframe src=\"http://video.pbs.org/viralplayer/{$video_id}\" frameborder=\"0\" marginwidth=\"0\" marginheight=\"0\" scrolling=\"no\" seamless></iframe></div>";
                    break;
                default:
                    $embed = '';
                    break;  
            }

            // validate video, if no video id has been sent, don't render video
            if ($video_id == '') return;
            elseif ($return_rendered_video) return $embed;
            else echo $embed;
        }
    }

    Video_Metabox::get_instance();

    endif;

}

// init plugin after others are loaded to manage dependencies
add_action( 'plugins_loaded', 'video_metabox_init' );
