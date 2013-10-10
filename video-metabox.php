<?php
/**
 * Plugin Name: Video Metabox
 * Plugin URI: https://github.com/jesseoverright/video-metabox
 * Description: Adds a video metabox plugin to your site.
 * Version: 1.1
 * Author: Jesse Overright
 * Author URI: http://about.me/joverright
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

add_action('admin_init', 'add_video_metabox');
add_action('save_post', 'save_video_metabox');

add_action('init', 'add_video_metabox_css');

function add_video_metabox () {
     add_meta_box( 'video-metabox', 'Video', 'video_metabox', 'post', 'normal', 'high');
     wp_enqueue_style( 'video-metabox-css', plugins_url( 'video-metabox.css', __FILE__) );
}

function add_video_metabox_css() {
    wp_enqueue_style( 'video-metabox-css', plugins_url( 'video-metabox.css', __FILE__) );
}    

function video_metabox () {
    global $post;

    // Verify data hasn't been tampered with
    echo'<input type="hidden" name="video_noncename" id="video_noncename" value="'.wp_create_nonce( plugin_basename(__FILE__) ).'" />';
    
    $video_url = get_post_meta($post->ID, 'video_url', true);
    $video_id = get_post_meta($post->ID, 'video_id', true);
    $video_type = get_post_meta($post->ID, 'video_type', true);

    if ($video_id != '' && $video_type != '') {
        render_video($video_id, $video_type);
    }
    ?>
    <label>Video URL:
    <input type="text" name="video_url" value="<?php echo $video_url; ?>" size="40" /></label>
    <?php
}
    
function save_video_metabox( $post_id ) {    
    // Verify data hasn't been tampered with
    if ( !wp_verify_nonce( $_POST["video_noncename"], plugin_basename(__FILE__) ))
        return $post_id;

    // save url
    $data = $_POST['video_url'];
        
    if(get_post_meta($post_id, 'video_url') == "")
    add_post_meta($post_id, 'video_url', $data, true);
    elseif($data != get_post_meta($post_id, 'video_url', true))
    update_post_meta($post_id, 'video_url', $data);
    elseif($data == "")
    delete_post_meta($post_id, 'video_url', get_post_meta($post_id, 'video_url', true));

    // srape url for video id & type
    $video_details = scrape_url($data);
    
    // New, Update, and Delete
    $data = $video_details['video_id'];
        
    if(get_post_meta($post_id, 'video_id') == "")
    add_post_meta($post_id, 'video_id', $data, true);
    elseif($data != get_post_meta($post_id, 'video_id', true))
    update_post_meta($post_id, 'video_id', $data);
    elseif($data == "")
    delete_post_meta($post_id, 'video_id', get_post_meta($post_id, 'video_id', true));
    
    $data = $video_details['video_type'];

    if(get_post_meta($post_id, 'video_type') == "")
    add_post_meta($post_id, 'video_type', $data, true);
    elseif($data != get_post_meta($post_id, 'video_type', true))
    update_post_meta($post_id, 'video_type', $data);
    elseif($data == "")
    delete_post_meta($post_id, 'video_type', get_post_meta($post_id, 'video_type', true));  
}

function scrape_url($video_url) {

    if ( filter_var($video_url, FILTER_VALIDATE_URL) === FALSE )
        return false;

    # get url query string and host
    $parsed_url = parse_url( $video_url );

    switch ($parsed_url['host']) {
        case "vimeo.com":
        case "www.vimeo.com":
            $video_details = array (
                'video_id' => ltrim($parsed_url['path'],'/'),
                'video_type' => 'vimeo',
            );
        break;
        case "youtu.be":
            $video_details = array (
                'video_id' => ltrim($parsed_url['path'],'/'),
                'video_type' => 'youtube',
            );
        break;
        case "youtube.com":
        case "www.youtube.com":
            parse_str( $parsed_url['query'], $query_vars);

            $video_details = array (
                'video_id' => $query_vars['v'],
                'video_type' => 'youtube',
            );
        break;
    }    

    return $video_details;

}

function render_video($video_id, $video_type, $return_rendered_video = false) {
    
    switch ($video_type) {
        case 'vimeo':
            $embed = "<div class=\"video-metabox\"><iframe src=\"http://player.vimeo.com/video/{$video_id}?title=0&byline=0&portrait=0&color=ffffff\" frameborder=\"0\" webkitAllowFullScreen allowFullScreen></iframe></div>";
            break;
        case 'youtube':
            $embed = "<div class=\"video-metabox\"><iframe src=\"http://www.youtube.com/embed/{$video_id}/?modestbranding=1&rel=0&showinfo=0\" frameborder=\"0\" allowfullscreen></iframe></div>";
            break;
        default:
            $embed = '';
            break;  
    }
    if ($video_id == '') return; // validate video, if no video id has been sent, don't render video
    if ($return_rendered_video)
        return $embed;
    else
        echo $embed;
}

// hook into the_content and display the video when applicable.
add_filter( 'the_content' , 'video_metabox_content_filter');

function video_metabox_content_filter( $content ) {
    global $post;
    if (get_post_meta($post->ID,'video_id',true) != '') {
        $content = render_video(get_post_meta($post->ID,'video_id',true),get_post_meta($post->ID,'video_type',true),640) . $content;
    }

    return $content;
}