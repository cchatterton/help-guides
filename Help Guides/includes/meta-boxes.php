<?php
/**
 * Help Guides Plugin
 * Manage Meta Boxes and Register Repeater Fields for Wiki CPT
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Register the repeater ACF field group programmatically
add_action( 'acf/include_fields', function() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( array(
        'key' => 'group_675e69f52f970',
        'title' => 'Releates to',
        'fields' => array(
            array(
                'key' => 'field_675e69f57fe62',
                'label' => 'Screen Ids',
                'name' => 'screen_ids',
                'type' => 'repeater',
                'layout' => 'table',
                'button_label' => 'Add Screen ID',
                'wrapper' => array(
						'width' => '33',
						'class' => '',
						'id' => '',
					),
                'sub_fields' => array(
                    array(
                        'key' => 'field_675e6a8957563',
                        'label' => 'Screen id',
                        'name' => 'screen_id',
                        'type' => 'text',
                    ),
                ),
            ),
            array(
				'key'           => 'field_wiki_context_targets',
				'label'         => 'Targets',
				'name'          => 'context_targets',
				'type'          => 'repeater',
				'layout'        => 'table',
				'button_label'  => 'Add Target',
				'wrapper' => array(
						'width' => '67',
						'class' => '',
						'id' => '',
					),
				'sub_fields'    => array(
					array(
						'key'           => 'field_wiki_target_selectors',
						'label'         => 'CSS Selector(s)',
						'name'          => 'css_selectors',
						'type'          => 'text',
						'new_lines'     => '', // preserve
						'wrapper'       => array( 'width' => '35' ),
					),
					array(
						'key'           => 'field_wiki_target_selectors_count',
						'label'         => 'Target Position',
						'name'          => 'css_position',
						'type'          => 'text',
						'new_lines'     => '', // preserve
						'wrapper'       => array( 'width' => '10' ),
					),
					array(
						'key'           => 'field_wiki_target_url_rules',
						'label'         => 'URL Rules',
						'name'          => 'url_rules',
						'type'          => 'repeater',
						'layout'        => 'table',
						'button_label'  => 'Add Rule',
						'wrapper'       => array( 'width' => '55' ),
						'sub_fields'    => array(
							array(
								'key'           => 'field_wiki_url_rule_mode',
								'label'         => 'Mode',
								'name'          => 'mode',
								'type'          => 'select',
								'choices'       => array(
									'include' => 'Include',
									'exclude' => 'Exclude',
								),
								'default_value' => 'include',
								'ui'            => 1,
								'allow_null'    => 0,
								'wrapper'       => array( 'width' => '25' ),
							),
							array(
								'key'          => 'field_wiki_url_rule_pattern',
								'label'        => 'Pattern (regex)',
								'name'         => 'pattern',
								'type'         => 'text',
								'wrapper'      => array( 'width' => '75' ),
							),
						),
					),
				),
			),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'wiki',
                ),
            ),
        ),
        'position' => 'normal',
        'style' => 'default',
        'active' => true,
    ) );
} );



// 2. Remove all meta boxes on wiki edit screen except the repeater meta box
add_action( 'add_meta_boxes', function() {
    $screen = get_current_screen();
    if ( ! $screen || $screen->post_type !== 'wiki' ) {
        return;
    }

    global $wp_meta_boxes;

    if ( empty( $wp_meta_boxes['wiki'] ) ) {
        return;
    }

    // Keep this meta box ID (replace if yours differs)
    $keep_meta_box_ids = array('acf-group_675e69f52f970','group_wiki_context_targets');

    foreach ( $wp_meta_boxes['wiki'] as $context => $priorities ) {
        foreach ( $priorities as $priority => $boxes ) {
            foreach ( $boxes as $id => $box ) {
                if ( !in_array($id, $keep_meta_box_ids )) {
                    remove_meta_box( $id, 'wiki', $context );
                }
            }
        }
    }
}, 100 );
