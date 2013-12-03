<?php

if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) exit();

delete_post_meta_by_key( 'video_type' );
delete_post_meta_by_key( 'video_id' );
delete_post_meta_by_key( 'video_url' );