<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Bob_Stats {
    public function __construct() {
        // implement later  
    }    

    public function bob_stats_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'bob_ai_stats';

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            meta_description text NOT NULL,
            word_count int(11) NOT NULL,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    private function print_sortable_column_header($label, $column, $current_orderby, $current_order)
    {
        $url = add_query_arg(
            array(
                'orderby' => $column,
                'order' => ($current_orderby === $column && $current_order === 'asc') ? 'desc' : 'asc'
            )
        );

        $sorted_class = ($current_orderby === $column) ? "sorted" : "sortable";
        $order_class = ($current_orderby === $column) ? $current_order : "asc";

        printf(
            '<th class="%s %s"><a href="%s"><span>%s</span><span class="sorting-indicator"></span></a></th>',
            $sorted_class,
            $order_class,
            esc_url($url),
            esc_html($label)
        );
    }

    public function bob_render_stats_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
    
        global $wpdb;
        $table_name = $wpdb->prefix . 'bob_ai_stats';
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        $offset = ($current_page - 1) * $per_page;
        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY updated_at DESC LIMIT $per_page OFFSET $offset", ARRAY_A);
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    
        $total_words = array_sum(array_column($results, 'word_count'));
    
        ?>
        <div class="wrap bob-stats-wrap">
            <h1><?php esc_html_e('Bob Stats', 'bob-ai'); ?></h1>
            <?php wp_nonce_field('bob_stats_nonce'); ?>
    
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Post', 'bob-ai'); ?></th>
                        <th><?php esc_html_e('Meta Description', 'bob-ai'); ?></th>
                        <th><?php esc_html_e('Word Count', 'bob-ai'); ?></th>
                        <th><?php esc_html_e('Modified', 'bob-ai'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $result) : ?>
                        <tr>
                            <td><a href="<?php echo get_permalink($result['post_id']); ?>"><?php echo esc_html(get_the_title($result['post_id'])); ?></a></td>
                            <td><?php echo esc_html($result['meta_description']); ?></td>
                            <td><?php echo esc_html($result['word_count']); ?></td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($result['updated_at']))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
    
            <?php
            $pagenum_link = html_entity_decode(get_pagenum_link());
            $url_parts = explode('?', $pagenum_link);
            if (isset($url_parts[1])) {
                wp_parse_str($url_parts[1], $query_args);
                $pagenum = isset($query_args['paged']) ? absint($query_args['paged']) : 1;
                unset($query_args['paged']);
                $pagenum_link = remove_query_arg(array_keys($query_args), $pagenum_link);
                $pagenum_link = add_query_arg('paged', '%#%', $pagenum_link);
            } else {
                $pagenum = 1;
                $pagenum_link = trailingslashit($pagenum_link) . 'paged/%#%/';
            }
    
            echo '<div class="tablenav bottom">';
            echo '<div class="tablenav-pages">';

            echo '<p>Total Updated Posts: ' . esc_html(number_format_i18n($total_items)) . '</p>';

            $pagination_args = array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'total' => ceil($total_items / $per_page),
                'current' => max(1, $current_page),
                'show_all' => false,
                'prev_next' => true,
                'prev_text' => __('&laquo; Previous'),
                'next_text' => __('Next &raquo;'),
                'type' => 'plain',
            );

            echo paginate_links($pagination_args);
            
            echo '</div>';
            echo '</div>';
            ?> 
    
            <h2><?php esc_html_e('Total Words Generated by OpenAI:', 'bob-ai'); ?> <?php echo esc_html(number_format_i18n($total_words)); ?></h2>
        </div>
        <?php
    }

    // delete table  when uninstalled
    public function delete_bob_stats_table() {
        global $wpdb;
    
        $table_name = $wpdb->prefix . 'bob_ai_stats';
        $sql = "DROP TABLE IF EXISTS {$table_name};";
        $wpdb->query($sql);
    } 
    
}