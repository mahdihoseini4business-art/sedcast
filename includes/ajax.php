<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_ajax_mpc_search',        'mpc_ajax_search' );
add_action( 'wp_ajax_nopriv_mpc_search', 'mpc_ajax_search' );

function mpc_ajax_search() {
    check_ajax_referer( 'mpc_nonce', 'nonce' );

    $query    = sanitize_text_field( $_POST['query']  ?? '' );
    $season   = absint( $_POST['season']              ?? 0  );
    $sort     = sanitize_key( $_POST['sort']          ?? 'newest' );

    $orderby = 'date';
    $order   = 'DESC';
    $meta_key = '';

    switch ($sort) {
        case 'oldest':
            $orderby = 'date'; $order = 'ASC';  break;
        case 'number_asc':
            $orderby = 'meta_value_num'; $order = 'ASC';  $meta_key = '_mpc_ep_number'; break;
        case 'number_desc':
            $orderby = 'meta_value_num'; $order = 'DESC'; $meta_key = '_mpc_ep_number'; break;
    }

    $args = [
        'post_type'      => 'mpc_episode',
        'posts_per_page' => 50,
        'post_status'    => 'publish',
        's'              => $query,
        'orderby'        => $orderby,
        'order'          => $order,
    ];

    if ($meta_key) $args['meta_key'] = $meta_key;

    if ($season) {
        $args['tax_query'] = [[
            'taxonomy' => 'mpc_season',
            'field'    => 'term_id',
            'terms'    => $season,
        ]];
    }

    $episodes = get_posts($args);

    if ( empty($episodes) ) {
        wp_send_json_success(['html' => '', 'count' => 0]);
    }

    ob_start();
    foreach ($episodes as $ep) {
        $seasons = get_the_terms($ep->ID, 'mpc_season');
        $season_obj = ($seasons && !is_wp_error($seasons)) ? $seasons[0] : null;
        mpc_render_episode_row($ep, $season_obj);
    }
    $html = ob_get_clean();

    wp_send_json_success(['html' => $html, 'count' => count($episodes)]);
}
