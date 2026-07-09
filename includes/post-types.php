<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', 'mpc_register_post_types' );

function mpc_register_post_types() {

    // ── Episode ──────────────────────────────────────────────────────────────
    register_post_type( 'mpc_episode', [
        'labels' => [
            'name'               => 'اپیزودها',
            'singular_name'      => 'اپیزود',
            'add_new'            => 'افزودن اپیزود',
            'add_new_item'       => 'افزودن اپیزود جدید',
            'edit_item'          => 'ویرایش اپیزود',
            'new_item'           => 'اپیزود جدید',
            'view_item'          => 'مشاهده اپیزود',
            'search_items'       => 'جستجو در اپیزودها',
            'not_found'          => 'اپیزودی یافت نشد',
            'not_found_in_trash' => 'اپیزودی در زباله‌دان نیست',
            'menu_name'          => 'پادکست',
            'all_items'          => 'همه اپیزودها',
        ],
        'public'             => true,
        'has_archive'        => true,
        'rewrite'            => [ 'slug' => 'podcast', 'with_front' => false ],
        'menu_icon'          => 'dashicons-microphone',
        'menu_position'      => 5,
        'supports'           => [ 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ],
        'show_in_rest'       => true,
        'taxonomies'         => [ 'mpc_season' ],
    ] );
}
