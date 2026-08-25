<?php
/**
 * Help Guides Plugin
 * Display current WP Admin Screen ID in admin notices
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_notices', function() {
    $screen = get_current_screen();
    if ( ! $screen ) {
        return;
    }
    echo '<p id="screen-id" style="float:right; padding:5px 10px; margin:0; font-size:12px; line-height:1.6666;">' . esc_html( $screen->id ) . '</p>';
} );

add_action( 'admin_head', function() {
    ?>
    <style>
    #screen-id {
        float: right;
        padding: 5px 10px;
        margin: 0;
        font-size: 12px;
        line-height: 1.6666;
    }
    @media screen and (max-width: 782px) {
        #screen-id {
            float: none;
            padding-left: 0;
            padding-right: 0;
        }
    }
    </style>
    <?php
} );
