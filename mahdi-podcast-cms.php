<?php
/**
 * Plugin Name: Mahdi Podcast CMS
 * Plugin URI:  https://mahdipodcast.ir
 * Description: سیستم مدیریت اختصاصی پادکست با پشتیبانی از فصل‌ها، پلیر حرفه‌ای و سئو
 * Version:     1.0.0
 * Author:      Mahdi
 * Text Domain: mahdi-podcast
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ─── Constants ───────────────────────────────────────────────────────────────
define( 'MPC_VERSION',   '1.0.0' );
define( 'MPC_DIR',       plugin_dir_path( __FILE__ ) );
define( 'MPC_URL',       plugin_dir_url( __FILE__ ) );
define( 'MPC_BASENAME',  plugin_basename( __FILE__ ) );

// ─── Core includes ───────────────────────────────────────────────────────────
require_once MPC_DIR . 'includes/post-types.php';
require_once MPC_DIR . 'includes/taxonomies.php';
require_once MPC_DIR . 'includes/meta-boxes.php';
require_once MPC_DIR . 'includes/shortcode.php';
require_once MPC_DIR . 'includes/schema.php';
require_once MPC_DIR . 'includes/ajax.php';
require_once MPC_DIR . 'admin/admin-menu.php';
require_once MPC_DIR . 'admin/settings.php';

// ─── Activation / Deactivation ───────────────────────────────────────────────
register_activation_hook( __FILE__, 'mpc_activate' );
register_deactivation_hook( __FILE__, 'mpc_deactivate' );

// Flush rewrite rules on version change (fixes 404 on single pages / archives)
add_action( 'init', 'mpc_maybe_flush_rewrite' );
function mpc_maybe_flush_rewrite() {
    $stored = get_option( 'mpc_version', '0' );
    if ( $stored !== MPC_VERSION ) {
        mpc_register_post_types();
        mpc_register_taxonomies();
        flush_rewrite_rules();
        update_option( 'mpc_version', MPC_VERSION );
    }
}

function mpc_activate() {
    mpc_register_post_types();
    mpc_register_taxonomies();
    flush_rewrite_rules();

    // Default options
    $defaults = [
        'primary_color'    => '#C9A84C',
        'secondary_color'  => '#1a1a1a',
        'accent_color'     => '#F5F0E8',
        'episodes_per_page'=> 10,
        'show_search'      => 1,
        'show_filter'      => 1,
        'show_latest'      => 1,
        'player_position'  => 'bottom',
        'podcast_title'    => get_bloginfo('name') . ' پادکست',
        'podcast_author'   => '',
        'podcast_email'    => get_option('admin_email'),
        'podcast_category' => 'Technology',
        'podcast_language' => 'fa',
    ];
    foreach ( $defaults as $key => $val ) {
        if ( false === get_option( 'mpc_' . $key ) ) {
            update_option( 'mpc_' . $key, $val );
        }
    }
}

function mpc_deactivate() {
    flush_rewrite_rules();
}

// ─── Enqueue frontend assets ─────────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', 'mpc_enqueue_frontend' );
function mpc_enqueue_frontend() {
    wp_enqueue_style(
        'mpc-frontend',
        MPC_URL . 'public/css/frontend.css',
        [],
        MPC_VERSION
    );

    wp_enqueue_script(
        'mpc-player',
        MPC_URL . 'public/js/player.js',
        [ 'jquery' ],
        MPC_VERSION,
        true
    );

    wp_enqueue_script(
        'mpc-frontend',
        MPC_URL . 'public/js/frontend.js',
        [ 'jquery', 'mpc-player' ],
        MPC_VERSION,
        true
    );

    wp_localize_script( 'mpc-frontend', 'MPC', [
        'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
        'nonce'          => wp_create_nonce( 'mpc_nonce' ),
        'primaryColor'   => get_option( 'mpc_primary_color',   '#C9A84C' ),
        'secondaryColor' => get_option( 'mpc_secondary_color', '#1a1a1a' ),
        'accentColor'    => get_option( 'mpc_accent_color',    '#F5F0E8' ),
        'strings'        => [
            'play'         => 'پخش',
            'pause'        => 'توقف',
            'loading'      => 'در حال بارگذاری...',
            'readMore'     => 'مشاهده بیشتر',
            'readLess'     => 'بستن',
            'searchPlaceholder' => 'جستجو در اپیزودها...',
            'noResults'    => 'اپیزودی یافت نشد.',
        ],
    ] );
}

// ─── Enqueue admin assets ────────────────────────────────────────────────────
add_action( 'admin_enqueue_scripts', 'mpc_enqueue_admin' );
function mpc_enqueue_admin( $hook ) {
    $screens = [ 'post.php', 'post-new.php', 'edit.php', 'toplevel_page_mpc-settings' ];
    $is_mpc  = isset( $_GET['post_type'] ) && $_GET['post_type'] === 'mpc_episode';
    $is_post = get_post_type( get_the_ID() ) === 'mpc_episode';
    $is_settings = isset( $_GET['page'] ) && $_GET['page'] === 'mpc-settings';

    if ( ! in_array( $hook, $screens ) && ! $is_mpc && ! $is_post && ! $is_settings ) return;

    wp_enqueue_media();

    wp_enqueue_style(
        'mpc-admin',
        MPC_URL . 'admin/css/admin.css',
        [],
        MPC_VERSION
    );

    wp_enqueue_script(
        'mpc-admin',
        MPC_URL . 'admin/js/admin.js',
        [ 'jquery' ],
        MPC_VERSION,
        true
    );
}
