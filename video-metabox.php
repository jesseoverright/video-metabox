<?php
/**
 * Plugin Name: Video Metabox
 * Plugin URI: http://wordpress.org/support/plugin/video-metabox
 * Description: Adds a video metabox plugin to your site.
 * Version: 1.1.3
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

include_once( dirname( __FILE__ ) . '/lib/class-wp-post-metabox.php' );

if ( !class_exists('Video_Metabox') ) :

class Video_Metabox {

    private static $instance;

    protected $postmeta;

    protected $supported_types = array ( 'vimeo', 'youtube', 'pbs' );

    public static function get_instance() {
        if ( !isset( self::$instance ) ) {
            $class = __CLASS__;
            self::$instance = new $class();
        }

        return self::$instance;
    }
    
    protected function __construct() {
        $this->postmeta = array (
            'video_url' => WP_PostMetaboxFactory::create( 'video_url', array( 'type' => 'url') ),
            'video_id' => WP_PostMetaboxFactory::create( 'video_id', array( 'type' => 'int' ) ),
            'video_type' => WP_PostMetaboxFactory::create( 'video_type', array( 'type' => 'select', 'choices' => $this->supported_types ) )
        );

        // add actions for creating and saving video metabox
        add_action('admin_init', array($this,'add_video_metabox') );
        add_action('save_post', array($this, 'save') );

        // enqueue video metabox css
        add_action('init', array($this, 'add_video_metabox_css') );

        // hook into the_content and display the video when applicable.
        add_filter( 'the_content' , array($this, 'display_video') );
    }

    public function add_video_metabox () {
        add_meta_box( 'video-metabox', 'Video', array($this,'video_metabox'), 'post', 'normal', 'high');
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

    public function video_metabox () {
        global $post;

        // Verify data hasn't been tampered with
        ?>
        <input type="hidden" name="video_noncename" id="video_noncename" value="<?php echo wp_create_nonce( plugin_basename(__FILE__) )?>" />

        <?php
        $video_url = get_post_meta($post->ID, 'video_url', true);
        $video_id = get_post_meta($post->ID, 'video_id', true);
        $video_type = get_post_meta($post->ID, 'video_type', true);

        // display rendered video in metabox
        if ($video_id != '' && $video_type != '') {
            $this->render_video($video_id, $video_type);
        }
        ?>
        <label>Video URL:
        <input type="url" name="video_url" value="<?php echo esc_url($video_url); ?>" size="40" /></label>
        <?php
    }

    public function save( $post_id ) {    
        // Verify data hasn't been tampered with
        if ( !wp_verify_nonce( $_POST['video_noncename'], plugin_basename(__FILE__) ))
            return $post_id;

        $data = esc_url_raw( $_POST['video_url']) ;

        $this->postmeta['video_url']->update( $post_id, $data );

        // srape url for video id & type
        $video_details = $this->scrape_url($data);

        $this->postmeta['video_id']->update( $post_id, $video_details[ 'video_id' ] );

        $this->postmeta['video_type']->update( $post_id, $video_details[ 'video_type' ] );
      
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