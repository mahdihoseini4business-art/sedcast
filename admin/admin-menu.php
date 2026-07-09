<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', 'mpc_admin_menu' );

function mpc_admin_menu() {
    // The main CPT menu already shows as "پادکست" (from post type labels).
    // We add a Settings submenu under it.
    add_submenu_page(
        'edit.php?post_type=mpc_episode',
        'تنظیمات پادکست',
        'تنظیمات',
        'manage_options',
        'mpc-settings',
        'mpc_render_settings_page'
    );
}

// Rename "Add New" in the submenu to Persian
add_filter( 'post_updated_messages', 'mpc_episode_updated_messages' );
function mpc_episode_updated_messages( $messages ) {
    $messages['mpc_episode'] = [
        0  => '',
        1  => 'اپیزود به‌روزرسانی شد.',
        2  => 'فیلد سفارشی ذخیره شد.',
        3  => 'فیلد سفارشی حذف شد.',
        4  => 'اپیزود به‌روزرسانی شد.',
        5  => 'نسخه بازیابی شد.',
        6  => 'اپیزود منتشر شد.',
        7  => 'اپیزود ذخیره شد.',
        8  => 'اپیزود ارسال شد.',
        9  => 'اپیزود زمان‌بندی شد.',
        10 => 'پیش‌نویس اپیزود به‌روزرسانی شد.',
    ];
    return $messages;
}
