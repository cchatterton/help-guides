<?php
/**
 * Help Guides Plugin
 * Render the Wiki Browser admin page with sidebar tree and content
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render the Wiki Browser admin page (View Guides)
 */
function render_editor_wiki_page() {
    $wiki_cpt    = 'wiki';
    $selected_id = isset( $_GET['wiki_id'] ) ? intval( $_GET['wiki_id'] ) : 0;

    // Get all published wiki posts ordered by menu_order
    $all_wiki = get_posts( [
        'post_type'      => $wiki_cpt,
        'posts_per_page' => -1,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
        'post_status'    => 'publish',
    ] );

    // Build hierarchical tree
    $tree = build_post_tree( $all_wiki );

    ?>
    <style>
.wp-menu-image.dashicons-before.dashicons-info::before {
    color: #ff00c6 !important;
    background: #ffff;
    clip-path: content-box;
}
    .wiki-browser {
        display: flex;
        height: 90vh;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen,
            Ubuntu, Cantarell, "Open Sans", "Helvetica Neue", sans-serif;
        background: #fff;
        margin-top: 1rem;
    }
    .wiki-browser nav {
        width: 320px;
        overflow-y: auto;
        border-right: 1px solid #ddd;
        padding: 1em;
        background: #fff;
    }
    .wiki-browser main {
        flex: 1;
        padding: 1.5em;
        overflow-y: auto;
        background: #fff;
    }
    
    .wiki-browser ul {
        list-style: none;
        padding-left: 1em;
    }
    .wiki-browser nav > ul {
        padding-left: 0;
    }
    .wiki-browser li {
        margin-bottom: 0.25em;
        cursor: pointer;
    }
    .wiki-browser a {
        text-decoration: none;
        color: #0073aa;
    }
    .wiki-browser .header {
        display: flex;
        align-items: center;
        flex-wrap: nowrap;
        justify-content: flex-start;
        gap: 0.5rem;
    }
    .wiki-browser a.selected {
        font-weight: bold;
        color: #005177;
    }
    .toggle-children {
        cursor: pointer;
        font-size: 0.9em;
        user-select: none;
        margin-right: 0.25em;
    }
    .no-notices .notice {display: none;}
    h1 > span.dashicons.dashicons-info {
        font-size: 2rem;
        display: inline-flex;
        margin-right: 0.6rem;
        color: #ff00c6 !important;
    }
    </style>
<div class="wrap no-notices">
        <h1 class="wp-heading-inline"><span class="dashicons dashicons-info"></span> Wiki Pages</h1><a  class="page-title-action" href="/wp-admin/post-new.php?post_type=wiki&pre_fill_screen_id=toplevel_page_editor-wiki-browser">Add New Page</a>
    
        <div class="wiki-browser">
            <nav>
                <div class="header"><h2>Wiki Tree</h2><a  class="page-title-action" href="/wp-admin/edit.php?post_type=wiki">Sort/Organise Pages</a></div>
                <?php echo render_tree_html( $tree, $selected_id ); ?>
            </nav>
            <main>
                <?php
                if ( $selected_id ) {
                    $post = get_post( $selected_id );
                    if ( $post && $post->post_type === $wiki_cpt ) {
                        echo '<h1>' . esc_html( get_the_title( $post ) ) . '</h1>';
                        echo apply_filters( 'the_content', $post->post_content );
                        echo '<p><a href="' . esc_url( get_edit_post_link( $post->ID ) ) . '" class="button button-primary">Edit</a></p>';
                    } else {
                        echo '<p>Page not found or invalid post type.</p>';
                    }
                } else {
                    echo '<p>Select a wiki page from the sidebar to view its content.</p>';
                }
                ?>
            </main>
        </div>
    </div>

    <script>
    document.querySelectorAll('.toggle-children').forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const ul = toggle.parentNode.querySelector('ul');
            if (!ul) return;
            if (ul.style.display === 'none') {
                ul.style.display = 'block';
                toggle.textContent = '-';
            } else {
                ul.style.display = 'none';
                toggle.textContent = '+';
            }
        });
    });
    </script>
    <?php
}

/**
 * Build a hierarchical tree from a flat list of posts
 *
 * @param WP_Post[] $posts
 * @return WP_Post[] Hierarchical tree
 */
function build_post_tree( $posts ) {
    $tree = [];
    $refs = [];

    foreach ( $posts as $post ) {
        $post->children = [];
        $refs[ $post->ID ] = $post;
    }

    foreach ( $posts as $post ) {
        if ( $post->post_parent && isset( $refs[ $post->post_parent ] ) ) {
            $refs[ $post->post_parent ]->children[] = $post;
        } else {
            $tree[] = $post;
        }
    }

    return $tree;
}

/**
 * Recursively render the tree as nested UL list with toggle buttons
 *
 * @param WP_Post[] $nodes
 * @param int $selected_id
 * @return string
 */
function render_tree_html( $nodes, $selected_id ) {
    if ( empty( $nodes ) ) {
        return '';
    }

    $html = '<ul style="list-style:none;">';

    foreach ( $nodes as $node ) {
        $has_children = ! empty( $node->children );
        $is_selected  = $node->ID === $selected_id;
        $link         = admin_url( 'admin.php?page=editor-wiki-browser&wiki_id=' . $node->ID );

        $html .= '<li style="margin-bottom:0.3em;">';
        if ( $has_children ) {
            $html .= '<span class="toggle-children" style="cursor:pointer; margin-right:0.25em;">-</span>';
        } else {
            $html .= '<span style="display:inline-block; width: 1em;"></span>';
        }
        $html .= '<a href="' . esc_url( $link ) . '" class="' . ( $is_selected ? 'selected' : '' ) . '" style="' . ( $is_selected ? 'font-weight:bold;color:#0073aa;' : '' ) . '">';
        $html .= esc_html( get_the_title( $node ) );
        $html .= '</a>';

        if ( $has_children ) {
            $html .= render_tree_html( $node->children, $selected_id );
        }
        $html .= '</li>';
    }

    $html .= '</ul>';
    return $html;
}
