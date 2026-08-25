<?php
/**
 * Help Guides Plugin
 * Adds Wiki Help Tab in WP Admin screens based on ACF repeater screen_ids field
 * Also adds a "Create Help Guide" button that pre-fills repeater with current screen ID
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_head', function() {
    $screen = get_current_screen();
    if ( ! $screen ) {
        return;
    }
    $screen_id = $screen->id;

    $args = [
        'post_type'      => 'wiki',  // Your CPT
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ];
    $wiki_posts = get_posts( $args );
    if ( empty( $wiki_posts ) ) {
        return;
    }

    $matched_wikis = [];

    foreach ( $wiki_posts as $wiki_post ) {
        $rows = get_field( 'screen_ids', $wiki_post->ID );
        if ( ! empty( $rows ) && is_array( $rows ) ) {
            foreach ( $rows as $row ) {
                if ( isset( $row['screen_id'] ) && $row['screen_id'] === $screen_id ) {
                    $matched_wikis[] = $wiki_post;
                    break;
                }
            }
        }
    }
    if ( empty( $matched_wikis ) ) {
        return;
    }

    usort( $matched_wikis, function( $a, $b ) {
        return strcasecmp( $a->post_title, $b->post_title );
    } );

    $content = '<p>Here are Wiki Pages relating to this screen:</p><ul class="wiki-help-thumbnails">';
    foreach ( $matched_wikis as $wiki_post ) {
        $content .= '<li><a href="/wp-admin/admin.php?page=editor-wiki-browser&wiki_id=' . $wiki_post->ID . '" target="_wiki_help">' . esc_html( $wiki_post->post_title ) . '</a></li>';
    }
    $content .= '</ul>';

    // Add "Create Help Guide" button with screen_id pre-fill
    $create_url = add_query_arg( [
        'post_type' => 'wiki',
        'pre_fill_screen_id' => $screen_id,
    ], admin_url( 'post-new.php' ) );

    $content .= '<p><a href="' . esc_url( $create_url ) . '" class="button button-primary">Create Help Guide</a></p>';

    $content .= '<style>.wiki-help-thumbnails li { margin-bottom: 0.5em; }</style>';

    $screen->add_help_tab( [
        'id'      => 'wiki_help_tab',
        'title'   => 'Wiki Pages',
        'content' => $content,
    ] );
} );


// Hook to prefill screen_ids repeater on new wiki post creation
// Prefill repeater field on new posts via URL param
add_filter('acf/load_value/name=screen_ids', function($value, $post_id, $field) {
    // If the field already has a value, keep it
    if ( !empty($value) ) {
        return $value;
    }

    if ( ! isset($_GET['pre_fill_screen_id']) ) {
        return $value;
    }

    // Prefill only on new posts — post_id can be 'new_post' (string) or 0
    if ( $post_id !== 'new_post' && intval($post_id) > 0 ) {
        // Existing post — don't override
        return $value;
    }

    $screen_id = sanitize_text_field(wp_unslash($_GET['pre_fill_screen_id']));

    return [
        [
            'screen_id' => $screen_id,
        ],
    ];
}, 10, 3);


// Optional: Prefill on save post if still empty (fallback)
add_action( 'acf/save_post', function( $post_id ) {
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    $post = get_post( $post_id );
    if ( ! $post || $post->post_type !== 'wiki' ) {
        return;
    }

    if ( empty( get_field('screen_ids', $post_id) ) && ! empty( $_GET['pre_fill_screen_id'] ) ) {
        $screen_id = sanitize_text_field( wp_unslash( $_GET['pre_fill_screen_id'] ) );

        $value = [
            [
                'screen_id' => $screen_id,
            ],
        ];

        update_field( 'screen_ids', $value, $post_id );
    }
}, 20 );

