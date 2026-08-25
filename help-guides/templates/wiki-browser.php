<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap thg-wrap">
    <div class="thg-heading">
        <h1><span class="dashicons dashicons-info" aria-hidden="true"></span><?php echo esc_html__('Wiki Pages', 'help-guides'); ?></h1>
        <?php if (current_user_can('edit_posts')) : ?>
            <a class="page-title-action" href="<?php echo esc_url(add_query_arg(array('post_type' => 'wiki', 'pre_fill_screen_id' => 'toplevel_page_editor-wiki-browser'), admin_url('post-new.php'))); ?>"><?php echo esc_html__('Add New Page', 'help-guides'); ?></a>
        <?php endif; ?>
    </div>
    <div class="thg-browser">
        <nav aria-label="<?php echo esc_attr__('Wiki page navigation', 'help-guides'); ?>">
            <div class="thg-browser-header">
                <h2><?php echo esc_html__('Wiki Tree', 'help-guides'); ?></h2>
                <a class="button" href="<?php echo esc_url(admin_url('edit.php?post_type=wiki')); ?>"><?php echo esc_html__('Organise Pages', 'help-guides'); ?></a>
            </div>
            <?php
            echo wp_kses(
                thg_render_wiki_tree($posts_by_parent, 0, $selected_id),
                array(
                    'ul'     => array('class' => true, 'hidden' => true),
                    'li'     => array(),
                    'button' => array('type' => true, 'class' => true, 'aria-expanded' => true),
                    'span'   => array('class' => true, 'aria-hidden' => true),
                    'a'      => array('href' => true, 'class' => true, 'aria-current' => true),
                )
            );
            ?>
        </nav>
        <main>
            <?php if ($selected_post) : ?>
                <h1><?php echo esc_html(get_the_title($selected_post)); ?></h1>
                <div class="thg-guide-content"><?php echo wp_kses_post(apply_filters('the_content', $selected_post->post_content)); ?></div>
                <?php if (current_user_can('edit_post', $selected_post->ID)) : ?>
                    <p><a href="<?php echo esc_url(get_edit_post_link($selected_post->ID)); ?>" class="button button-primary"><?php echo esc_html__('Edit guide', 'help-guides'); ?></a></p>
                <?php endif; ?>
            <?php elseif ($selected_id) : ?>
                <p><?php echo esc_html__('The requested wiki page was not found.', 'help-guides'); ?></p>
            <?php else : ?>
                <p><?php echo esc_html__('Select a wiki page from the navigation to view its content.', 'help-guides'); ?></p>
            <?php endif; ?>
        </main>
    </div>
</div>
