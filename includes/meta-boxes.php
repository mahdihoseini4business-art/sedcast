<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'add_meta_boxes', 'mpc_add_meta_boxes' );
add_action( 'save_post_mpc_episode', 'mpc_save_episode_meta', 10, 2 );

function mpc_add_meta_boxes() {
    add_meta_box(
        'mpc_episode_details',
        'اطلاعات اپیزود',
        'mpc_episode_details_cb',
        'mpc_episode',
        'normal',
        'high'
    );

    add_meta_box(
        'mpc_episode_season',
        'فصل',
        'mpc_episode_season_cb',
        'mpc_episode',
        'side',
        'high'
    );

    add_meta_box(
        'mpc_episode_seo',
        'سئو و اشتراک‌گذاری',
        'mpc_episode_seo_cb',
        'mpc_episode',
        'normal',
        'low'
    );
}

// ── Episode Details ───────────────────────────────────────────────────────────
function mpc_episode_details_cb( $post ) {
    wp_nonce_field( 'mpc_save_meta', 'mpc_meta_nonce' );

    $audio_url    = get_post_meta( $post->ID, '_mpc_audio_url',    true );
    $duration     = get_post_meta( $post->ID, '_mpc_duration',     true );
    $ep_number    = get_post_meta( $post->ID, '_mpc_ep_number',    true );
    $ep_type      = get_post_meta( $post->ID, '_mpc_ep_type',      true ) ?: 'full';
    $explicit     = get_post_meta( $post->ID, '_mpc_explicit',     true );
    $file_size    = get_post_meta( $post->ID, '_mpc_file_size',    true );
    $show_notes   = get_post_meta( $post->ID, '_mpc_show_notes',   true );
    ?>
    <div class="mpc-meta-grid">

        <div class="mpc-meta-row mpc-full">
            <label>فایل صوتی</label>
            <div class="mpc-audio-field">
                <input type="url" name="_mpc_audio_url" id="_mpc_audio_url"
                       value="<?php echo esc_url( $audio_url ); ?>"
                       placeholder="https://example.com/audio.mp3" class="widefat">
                <button type="button" class="button mpc-upload-audio-btn" data-target="_mpc_audio_url">
                    📁 انتخاب از کتابخانه
                </button>
            </div>
            <?php if ( $audio_url ): ?>
            <div class="mpc-audio-preview">
                <audio controls src="<?php echo esc_url($audio_url); ?>" style="width:100%;margin-top:8px;"></audio>
            </div>
            <?php endif; ?>
        </div>

        <div class="mpc-meta-row">
            <label for="_mpc_ep_number">شماره اپیزود</label>
            <input type="number" name="_mpc_ep_number" id="_mpc_ep_number"
                   value="<?php echo esc_attr($ep_number); ?>" min="0" class="small-text">
        </div>

        <div class="mpc-meta-row">
            <label for="_mpc_duration">مدت زمان</label>
            <input type="text" name="_mpc_duration" id="_mpc_duration"
                   value="<?php echo esc_attr($duration); ?>"
                   placeholder="مثال: ۱:۲۳:۴۵" class="regular-text">
        </div>

        <div class="mpc-meta-row">
            <label for="_mpc_ep_type">نوع اپیزود</label>
            <select name="_mpc_ep_type" id="_mpc_ep_type">
                <option value="full"  <?php selected($ep_type,'full'); ?>>کامل</option>
                <option value="bonus" <?php selected($ep_type,'bonus'); ?>>جایزه</option>
                <option value="trailer" <?php selected($ep_type,'trailer'); ?>>تیزر</option>
            </select>
        </div>

        <div class="mpc-meta-row">
            <label for="_mpc_file_size">حجم فایل (بایت)</label>
            <input type="number" name="_mpc_file_size" id="_mpc_file_size"
                   value="<?php echo esc_attr($file_size); ?>" class="regular-text"
                   placeholder="به صورت خودکار محاسبه می‌شود">
        </div>

        <div class="mpc-meta-row">
            <label>محتوای صریح</label>
            <label class="mpc-toggle">
                <input type="checkbox" name="_mpc_explicit" value="1" <?php checked($explicit,'1'); ?>>
                <span>این اپیزود محتوای صریح دارد</span>
            </label>
        </div>

        <div class="mpc-meta-row mpc-full">
            <label for="_mpc_show_notes">یادداشت‌های برنامه (Show Notes)</label>
            <p class="description">لینک‌ها و منابع ذکرشده در این اپیزود</p>
            <textarea name="_mpc_show_notes" id="_mpc_show_notes" rows="6" class="widefat"
                      placeholder="- [منبع ۱](https://example.com)&#10;- [منبع ۲](https://example.com)"
            ><?php echo esc_textarea($show_notes); ?></textarea>
        </div>

    </div>
    <?php
}

// ── Season Selector ───────────────────────────────────────────────────────────
function mpc_episode_season_cb( $post ) {
    $seasons  = get_terms( [ 'taxonomy' => 'mpc_season', 'hide_empty' => false ] );
    $current  = wp_get_post_terms( $post->ID, 'mpc_season', [ 'fields' => 'ids' ] );
    $selected = ! empty($current) ? $current[0] : 0;

    echo '<div class="mpc-season-picker">';
    echo '<select name="mpc_season_term" id="mpc_season_term" style="width:100%">';
    echo '<option value="0">— بدون فصل —</option>';

    foreach ( $seasons as $season ) {
        $num = get_term_meta( $season->term_id, 'season_number', true );
        printf(
            '<option value="%d" %s>%s%s</option>',
            $season->term_id,
            selected( $selected, $season->term_id, false ),
            $num ? 'فصل ' . $num . ': ' : '',
            esc_html( $season->name )
        );
    }

    echo '</select>';

    if ( current_user_can('manage_categories') ) {
        echo '<p style="margin-top:8px"><a href="' . admin_url('edit-tags.php?taxonomy=mpc_season&post_type=mpc_episode') . '">+ افزودن فصل جدید</a></p>';
    }
    echo '</div>';
}

// ── SEO Meta ─────────────────────────────────────────────────────────────────
function mpc_episode_seo_cb( $post ) {
    $keywords   = get_post_meta( $post->ID, '_mpc_keywords',   true );
    $og_image   = get_post_meta( $post->ID, '_mpc_og_image',   true );
    $transcript = get_post_meta( $post->ID, '_mpc_transcript', true );
    ?>
    <div class="mpc-meta-grid">
        <div class="mpc-meta-row mpc-full">
            <label for="_mpc_keywords">کلمات کلیدی</label>
            <input type="text" name="_mpc_keywords" id="_mpc_keywords"
                   value="<?php echo esc_attr($keywords); ?>" class="widefat"
                   placeholder="کلمات کلیدی را با کاما جدا کنید">
        </div>

        <div class="mpc-meta-row mpc-full">
            <label>تصویر Open Graph (اشتراک‌گذاری)</label>
            <div class="mpc-img-selector">
                <input type="hidden" name="_mpc_og_image" id="_mpc_og_image" value="<?php echo esc_url($og_image); ?>">
                <button type="button" class="button mpc-upload-btn" data-target="_mpc_og_image">انتخاب تصویر</button>
                <div id="_mpc_og_image_preview" class="mpc-img-preview">
                    <?php if ($og_image): ?><img src="<?php echo esc_url($og_image); ?>" style="max-width:300px;margin-top:8px;"><?php endif; ?>
                </div>
            </div>
        </div>

        <div class="mpc-meta-row mpc-full">
            <label for="_mpc_transcript">متن پیاده‌شده (Transcript)</label>
            <p class="description">برای سئو و دسترس‌پذیری — در صورت تمایل می‌توانید متن کامل اپیزود را وارد کنید</p>
            <textarea name="_mpc_transcript" id="_mpc_transcript" rows="8" class="widefat"
            ><?php echo esc_textarea($transcript); ?></textarea>
        </div>
    </div>
    <?php
}

// ── Save Meta ─────────────────────────────────────────────────────────────────
function mpc_save_episode_meta( $post_id, $post ) {
    // Security checks
    if ( ! isset( $_POST['mpc_meta_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['mpc_meta_nonce'], 'mpc_save_meta' ) ) return;
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    // Text fields
    $text_fields = [ '_mpc_duration', '_mpc_ep_type', '_mpc_keywords' ];
    foreach ( $text_fields as $field ) {
        if ( isset( $_POST[$field] ) ) {
            update_post_meta( $post_id, $field, sanitize_text_field( $_POST[$field] ) );
        }
    }

    // Textarea fields
    $textarea_fields = [ '_mpc_show_notes', '_mpc_transcript' ];
    foreach ( $textarea_fields as $field ) {
        if ( isset( $_POST[$field] ) ) {
            update_post_meta( $post_id, $field, sanitize_textarea_field( $_POST[$field] ) );
        }
    }

    // URL fields
    $url_fields = [ '_mpc_audio_url', '_mpc_og_image' ];
    foreach ( $url_fields as $field ) {
        if ( isset( $_POST[$field] ) ) {
            update_post_meta( $post_id, $field, esc_url_raw( $_POST[$field] ) );
        }
    }

    // Integer fields
    foreach ( ['_mpc_ep_number', '_mpc_file_size'] as $field ) {
        if ( isset( $_POST[$field] ) ) {
            update_post_meta( $post_id, $field, absint( $_POST[$field] ) );
        }
    }

    // Boolean
    update_post_meta( $post_id, '_mpc_explicit', isset($_POST['_mpc_explicit']) ? '1' : '0' );

    // Season taxonomy
    if ( isset($_POST['mpc_season_term']) ) {
        $term_id = absint($_POST['mpc_season_term']);
        if ( $term_id > 0 ) {
            wp_set_post_terms( $post_id, [$term_id], 'mpc_season' );
        } else {
            wp_set_post_terms( $post_id, [], 'mpc_season' );
        }
    }

    // Auto-detect file size if not set
    if ( empty($_POST['_mpc_file_size']) && ! empty($_POST['_mpc_audio_url']) ) {
        $url = esc_url_raw( $_POST['_mpc_audio_url'] );
        $response = wp_remote_head( $url, ['timeout' => 10] );
        if ( ! is_wp_error($response) ) {
            $size = wp_remote_retrieve_header( $response, 'content-length' );
            if ( $size ) update_post_meta( $post_id, '_mpc_file_size', absint($size) );
        }
    }
}
