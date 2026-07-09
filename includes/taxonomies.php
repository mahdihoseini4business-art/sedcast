<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', 'mpc_register_taxonomies' );

function mpc_register_taxonomies() {

    // ── Season ───────────────────────────────────────────────────────────────
    register_taxonomy( 'mpc_season', 'mpc_episode', [
        'labels' => [
            'name'              => 'فصل‌ها',
            'singular_name'     => 'فصل',
            'search_items'      => 'جستجو در فصل‌ها',
            'all_items'         => 'همه فصل‌ها',
            'edit_item'         => 'ویرایش فصل',
            'update_item'       => 'به‌روزرسانی فصل',
            'add_new_item'      => 'افزودن فصل جدید',
            'new_item_name'     => 'نام فصل جدید',
            'menu_name'         => 'فصل‌ها',
        ],
        'hierarchical'      => true,
        'public'            => true,
        'rewrite'           => [ 'slug' => 'podcast/season', 'with_front' => false ],
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'meta_box_cb'       => false, // We use our own meta box
    ] );

    // Register season meta fields
    register_term_meta( 'mpc_season', 'season_number',     [ 'single' => true, 'type' => 'integer',  'show_in_rest' => true ] );
    register_term_meta( 'mpc_season', 'season_description',[ 'single' => true, 'type' => 'string',   'show_in_rest' => true ] );
    register_term_meta( 'mpc_season', 'season_cover',      [ 'single' => true, 'type' => 'string',   'show_in_rest' => true ] );
    register_term_meta( 'mpc_season', 'season_color',      [ 'single' => true, 'type' => 'string',   'show_in_rest' => true ] );
    register_term_meta( 'mpc_season', 'season_open',       [ 'single' => true, 'type' => 'boolean',  'show_in_rest' => true ] );
}

// ── Season term meta form fields ─────────────────────────────────────────────
add_action( 'mpc_season_add_form_fields',  'mpc_season_add_fields' );
add_action( 'mpc_season_edit_form_fields', 'mpc_season_edit_fields' );
add_action( 'created_mpc_season',          'mpc_save_season_meta' );
add_action( 'edited_mpc_season',           'mpc_save_season_meta' );

function mpc_season_add_fields( $taxonomy ) { ?>
    <div class="form-field">
        <label for="season_number">شماره فصل</label>
        <input type="number" name="season_number" id="season_number" value="" min="1">
    </div>
    <div class="form-field">
        <label for="season_description">توضیحات فصل</label>
        <textarea name="season_description" id="season_description" rows="4"></textarea>
    </div>
    <div class="form-field">
        <label for="season_cover">تصویر کاور فصل</label>
        <input type="hidden" name="season_cover" id="season_cover" value="">
        <button type="button" class="button mpc-upload-btn" data-target="season_cover">انتخاب تصویر</button>
        <div id="season_cover_preview" class="mpc-img-preview"></div>
    </div>
    <div class="form-field">
        <label for="season_color">رنگ فصل</label>
        <input type="color" name="season_color" id="season_color" value="#C9A84C">
    </div>
    <div class="form-field">
        <label>
            <input type="checkbox" name="season_open" value="1">
            پیش‌فرض باز باشد (آکاردئون)
        </label>
    </div>
<?php }

function mpc_season_edit_fields( $term ) {
    $number = get_term_meta( $term->term_id, 'season_number',      true );
    $desc   = get_term_meta( $term->term_id, 'season_description', true );
    $cover  = get_term_meta( $term->term_id, 'season_cover',       true );
    $color  = get_term_meta( $term->term_id, 'season_color',       true ) ?: '#C9A84C';
    $open   = get_term_meta( $term->term_id, 'season_open',        true );
    ?>
    <tr class="form-field">
        <th><label for="season_number">شماره فصل</label></th>
        <td><input type="number" name="season_number" id="season_number" value="<?php echo esc_attr($number); ?>" min="1"></td>
    </tr>
    <tr class="form-field">
        <th><label for="season_description">توضیحات فصل</label></th>
        <td><textarea name="season_description" id="season_description" rows="4"><?php echo esc_textarea($desc); ?></textarea></td>
    </tr>
    <tr class="form-field">
        <th><label for="season_cover">تصویر کاور فصل</label></th>
        <td>
            <input type="hidden" name="season_cover" id="season_cover" value="<?php echo esc_url($cover); ?>">
            <button type="button" class="button mpc-upload-btn" data-target="season_cover">انتخاب تصویر</button>
            <div id="season_cover_preview" class="mpc-img-preview">
                <?php if ($cover): ?><img src="<?php echo esc_url($cover); ?>" style="max-width:200px;margin-top:8px;"><?php endif; ?>
            </div>
        </td>
    </tr>
    <tr class="form-field">
        <th><label for="season_color">رنگ فصل</label></th>
        <td><input type="color" name="season_color" id="season_color" value="<?php echo esc_attr($color); ?>"></td>
    </tr>
    <tr class="form-field">
        <th>حالت آکاردئون</th>
        <td>
            <label>
                <input type="checkbox" name="season_open" value="1" <?php checked($open, '1'); ?>>
                پیش‌فرض باز باشد
            </label>
        </td>
    </tr>
<?php }

function mpc_save_season_meta( $term_id ) {
    if ( isset( $_POST['season_number'] ) ) {
        update_term_meta( $term_id, 'season_number', absint( $_POST['season_number'] ) );
    } else {
        delete_term_meta( $term_id, 'season_number' );
    }

    if ( isset( $_POST['season_description'] ) ) {
        update_term_meta( $term_id, 'season_description', sanitize_textarea_field( $_POST['season_description'] ) );
    } else {
        delete_term_meta( $term_id, 'season_description' );
    }

    if ( isset( $_POST['season_cover'] ) ) {
        update_term_meta( $term_id, 'season_cover', esc_url_raw( $_POST['season_cover'] ) );
    } else {
        delete_term_meta( $term_id, 'season_cover' );
    }

    if ( isset( $_POST['season_color'] ) ) {
        update_term_meta( $term_id, 'season_color', sanitize_hex_color( $_POST['season_color'] ) );
    } else {
        delete_term_meta( $term_id, 'season_color' );
    }

    update_term_meta( $term_id, 'season_open', isset( $_POST['season_open'] ) ? '1' : '0' );
}
