<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'mahdi_podcast', 'mpc_render_shortcode' );

function mpc_render_shortcode( $atts ) {
    $atts = shortcode_atts( [
        'season'    => '',   // show specific season ID
        'limit'     => -1,
        'orderby'   => 'meta_value_num',
        'order'     => 'ASC',
        'show_search' => get_option('mpc_show_search', 1),
        'show_filter' => get_option('mpc_show_filter', 1),
    ], $atts, 'mahdi_podcast' );

    ob_start();

    $primary = get_option('mpc_primary_color',   '#C9A84C');
    $accent  = get_option('mpc_accent_color',    '#F5F0E8');
    $dark    = get_option('mpc_secondary_color', '#1a1a1a');

    echo '<style>
    :root {
        --mpc-gold:   ' . esc_attr($primary) . ';
        --mpc-cream:  ' . esc_attr($accent)  . ';
        --mpc-dark:   ' . esc_attr($dark)    . ';
    }
    </style>';

    // Render search & filter bar
    if ( $atts['show_search'] || $atts['show_filter'] ) {
        mpc_render_search_bar( $atts );
    }

    // Latest episode
    if ( get_option('mpc_show_latest', 1) && empty($atts['season']) ) {
        mpc_render_latest_episode();
    }

    // Get seasons
    $seasons = get_terms( [
        'taxonomy'   => 'mpc_season',
        'hide_empty' => true,
        'orderby'    => 'meta_value_num',
        'meta_key'   => 'season_number',
        'order'      => 'ASC',
    ] );

    echo '<div class="mpc-podcast-wrapper" dir="rtl" id="mpc-podcast-wrapper">';

    if ( ! empty($seasons) && ! is_wp_error($seasons) ) {
        foreach ( $seasons as $season ) {
            if ( $atts['season'] && $season->term_id != $atts['season'] ) continue;
            mpc_render_season( $season, $atts );
        }
    } else {
        // No seasons — show all episodes flat
        mpc_render_flat_episodes( $atts );
    }

    echo '</div>'; // .mpc-podcast-wrapper

    // Bottom sticky player container
    echo '<div id="mpc-sticky-player" class="mpc-sticky-player mpc-hidden" aria-hidden="true">
        <div class="mpc-sticky-inner">
            <div class="mpc-sticky-cover">
                <img id="mpc-sticky-cover-img" src="" alt="">
            </div>
            <div class="mpc-sticky-info">
                <span class="mpc-sticky-title" id="mpc-sticky-title">—</span>
                <span class="mpc-sticky-season" id="mpc-sticky-season"></span>
            </div>
            <div class="mpc-sticky-controls">
                <button class="mpc-ctrl mpc-ctrl-prev"  id="mpc-prev"  aria-label="قبلی">⏮</button>
                <button class="mpc-ctrl mpc-ctrl-rw"    id="mpc-rw"    aria-label="۱۵ ثانیه عقب">⏪</button>
                <button class="mpc-ctrl mpc-ctrl-play"  id="mpc-play"  aria-label="پخش / توقف">▶</button>
                <button class="mpc-ctrl mpc-ctrl-fw"    id="mpc-fw"    aria-label="۱۵ ثانیه جلو">⏩</button>
                <button class="mpc-ctrl mpc-ctrl-next"  id="mpc-next"  aria-label="بعدی">⏭</button>
            </div>
            <div class="mpc-sticky-progress">
                <span class="mpc-time-current" id="mpc-time-current">0:00</span>
                <div class="mpc-progress-bar" id="mpc-progress-bar" role="slider" tabindex="0">
                    <div class="mpc-progress-fill" id="mpc-progress-fill"></div>
                    <div class="mpc-progress-thumb" id="mpc-progress-thumb"></div>
                </div>
                <span class="mpc-time-total" id="mpc-time-total">0:00</span>
            </div>
            <div class="mpc-sticky-extra">
                <select id="mpc-speed" aria-label="سرعت پخش">
                    <option value="0.75">۰.۷۵×</option>
                    <option value="1" selected>۱×</option>
                    <option value="1.25">۱.۲۵×</option>
                    <option value="1.5">۱.۵×</option>
                    <option value="2">۲×</option>
                </select>
                <button class="mpc-ctrl mpc-ctrl-volume" id="mpc-mute" aria-label="صدا">🔊</button>
                <input type="range" id="mpc-volume" min="0" max="1" step="0.05" value="1" aria-label="صدا">
                <button class="mpc-ctrl" id="mpc-close-player" aria-label="بستن پلیر">✕</button>
            </div>
        </div>
        <audio id="mpc-audio-engine" preload="none"></audio>
    </div>';

    return ob_get_clean();
}

// ── Search Bar ────────────────────────────────────────────────────────────────
function mpc_render_search_bar( $atts ) {
    $seasons = get_terms(['taxonomy' => 'mpc_season', 'hide_empty' => true]);
    ?>
    <div class="mpc-toolbar" dir="rtl">
        <div class="mpc-search-wrap">
            <span class="mpc-search-icon">🔍</span>
            <input type="search" id="mpc-search-input" placeholder="جستجو در اپیزودها..."
                   autocomplete="off" aria-label="جستجو">
        </div>
        <?php if ($atts['show_filter'] && !empty($seasons) && !is_wp_error($seasons)): ?>
        <div class="mpc-filter-wrap">
            <select id="mpc-season-filter" aria-label="فیلتر فصل">
                <option value="">همه فصل‌ها</option>
                <?php foreach ($seasons as $s):
                    $num = get_term_meta($s->term_id, 'season_number', true); ?>
                    <option value="<?php echo esc_attr($s->term_id); ?>">
                        <?php echo $num ? 'فصل ' . $num . ': ' : ''; echo esc_html($s->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div class="mpc-sort-wrap">
            <select id="mpc-sort-select" aria-label="مرتب‌سازی">
                <option value="newest">جدیدترین</option>
                <option value="oldest">قدیمی‌ترین</option>
                <option value="number_asc">شماره: صعودی</option>
                <option value="number_desc">شماره: نزولی</option>
            </select>
        </div>
    </div>
    <div id="mpc-no-results" class="mpc-no-results mpc-hidden">
        <p>🔍 اپیزودی با این مشخصات یافت نشد.</p>
    </div>
    <?php
}

// ── Latest Episode ────────────────────────────────────────────────────────────
function mpc_render_latest_episode() {
    $latest = get_posts([
        'post_type'      => 'mpc_episode',
        'posts_per_page' => 1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);
    if ( empty($latest) ) return;

    $ep       = $latest[0];
    $audio    = get_post_meta($ep->ID, '_mpc_audio_url', true);
    $duration = get_post_meta($ep->ID, '_mpc_duration',  true);
    $ep_num   = get_post_meta($ep->ID, '_mpc_ep_number', true);
    $cover    = get_the_post_thumbnail_url($ep->ID, 'large') ?: MPC_URL . 'assets/default-cover.svg';
    $seasons  = get_the_terms($ep->ID, 'mpc_season');
    $season_name = ($seasons && !is_wp_error($seasons)) ? $seasons[0]->name : '';

    ?>
    <div class="mpc-latest" data-ep-id="<?php echo $ep->ID; ?>"
         data-audio="<?php echo esc_url($audio); ?>"
         data-cover="<?php echo esc_url($cover); ?>"
         data-title="<?php echo esc_attr($ep->post_title); ?>"
         data-season="<?php echo esc_attr($season_name); ?>">
        <div class="mpc-latest-badge">✨ آخرین اپیزود</div>
        <div class="mpc-latest-inner">
            <div class="mpc-latest-cover">
                <img src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr($ep->post_title); ?>" loading="lazy">
                <button class="mpc-latest-play-btn mpc-play-episode"
                        data-audio="<?php echo esc_url($audio); ?>"
                        data-title="<?php echo esc_attr($ep->post_title); ?>"
                        data-cover="<?php echo esc_url($cover); ?>"
                        data-season="<?php echo esc_attr($season_name); ?>"
                        data-ep-id="<?php echo $ep->ID; ?>"
                        aria-label="پخش <?php echo esc_attr($ep->post_title); ?>">
                    <span class="mpc-play-icon">▶</span>
                </button>
            </div>
            <div class="mpc-latest-info">
                <?php if ($ep_num): ?>
                    <span class="mpc-ep-badge">اپیزود <?php echo esc_html($ep_num); ?></span>
                <?php endif; ?>
                <?php if ($season_name): ?>
                    <span class="mpc-season-badge"><?php echo esc_html($season_name); ?></span>
                <?php endif; ?>
                <h2 class="mpc-latest-title">
                    <a href="<?php echo get_permalink($ep->ID); ?>"><?php echo esc_html($ep->post_title); ?></a>
                </h2>
                <p class="mpc-latest-excerpt"><?php echo wp_trim_words(get_the_excerpt($ep), 25); ?></p>
                <div class="mpc-latest-meta">
                    <?php if ($duration): ?>
                        <span class="mpc-duration">⏱ <?php echo esc_html($duration); ?></span>
                    <?php endif; ?>
                    <span class="mpc-date">📅 <?php echo get_the_date('j F Y', $ep->ID); ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// ── Season Block ──────────────────────────────────────────────────────────────
function mpc_render_season( $season, $atts ) {
    $num    = get_term_meta($season->term_id, 'season_number',      true);
    $desc   = get_term_meta($season->term_id, 'season_description', true);
    $cover  = get_term_meta($season->term_id, 'season_cover',       true);
    $color  = get_term_meta($season->term_id, 'season_color',       true) ?: '#C9A84C';
    $open   = get_term_meta($season->term_id, 'season_open',        true);

    $episodes = get_posts([
        'post_type'      => 'mpc_episode',
        'posts_per_page' => $atts['limit'],
        'tax_query'      => [['taxonomy'=>'mpc_season','field'=>'term_id','terms'=>$season->term_id]],
        'meta_key'       => '_mpc_ep_number',
        'orderby'        => $atts['orderby'],
        'order'          => $atts['order'],
    ]);

    if ( empty($episodes) ) return;

    $is_open = $open ? 'mpc-open' : '';
    ?>
    <div class="mpc-season <?php echo esc_attr($is_open); ?>"
         data-season-id="<?php echo $season->term_id; ?>"
         style="--season-color: <?php echo esc_attr($color); ?>">

        <div class="mpc-season-header" role="button" tabindex="0"
             aria-expanded="<?php echo $open ? 'true' : 'false'; ?>"
             aria-controls="season-body-<?php echo $season->term_id; ?>">
            <?php if ($cover): ?>
            <div class="mpc-season-cover">
                <img src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr($season->name); ?>" loading="lazy">
            </div>
            <?php endif; ?>
            <div class="mpc-season-heading">
                <span class="mpc-season-label" style="color:<?php echo esc_attr($color); ?>">
                    <?php echo $num ? 'فصل ' . esc_html($num) : ''; ?>
                </span>
                <h3 class="mpc-season-name"><?php echo esc_html($season->name); ?></h3>
                <span class="mpc-season-count"><?php echo count($episodes); ?> اپیزود</span>
            </div>
            <div class="mpc-season-toggle" aria-hidden="true">
                <span class="mpc-chevron">▼</span>
            </div>
        </div>

        <div class="mpc-season-body" id="season-body-<?php echo $season->term_id; ?>"
             <?php if(!$open): ?>style="display:none"<?php endif; ?>>
            <?php if ($desc): ?>
            <div class="mpc-season-desc"><?php echo wp_kses_post(wpautop($desc)); ?></div>
            <?php endif; ?>
            <div class="mpc-episodes-list">
                <?php foreach ($episodes as $ep): mpc_render_episode_row($ep, $season); endforeach; ?>
            </div>
        </div>

    </div>
    <?php
}

// ── Episode Row ───────────────────────────────────────────────────────────────
function mpc_render_episode_row( $ep, $season = null ) {
    $audio    = get_post_meta($ep->ID, '_mpc_audio_url',  true);
    $duration = get_post_meta($ep->ID, '_mpc_duration',   true);
    $ep_num   = get_post_meta($ep->ID, '_mpc_ep_number',  true);
    $cover    = get_the_post_thumbnail_url($ep->ID, 'medium') ?: MPC_URL . 'assets/default-cover.svg';
    $excerpt  = get_the_excerpt($ep);
    $season_name = $season ? $season->name : '';
    $permalink   = get_permalink($ep->ID);
    ?>
    <article class="mpc-episode-row"
             data-ep-id="<?php echo $ep->ID; ?>"
             data-audio="<?php echo esc_url($audio); ?>"
             data-title="<?php echo esc_attr($ep->post_title); ?>"
             data-cover="<?php echo esc_url($cover); ?>"
             data-season="<?php echo esc_attr($season_name); ?>"
             data-number="<?php echo esc_attr($ep_num); ?>"
             data-date="<?php echo get_the_date('U', $ep->ID); ?>">

        <div class="mpc-ep-cover-wrap">
            <img src="<?php echo esc_url($cover); ?>"
                 alt="<?php echo esc_attr($ep->post_title); ?>" loading="lazy"
                 class="mpc-ep-cover">
            <?php if ($audio): ?>
            <button class="mpc-ep-play-btn mpc-play-episode"
                    data-audio="<?php echo esc_url($audio); ?>"
                    data-title="<?php echo esc_attr($ep->post_title); ?>"
                    data-cover="<?php echo esc_url($cover); ?>"
                    data-season="<?php echo esc_attr($season_name); ?>"
                    data-ep-id="<?php echo $ep->ID; ?>"
                    aria-label="پخش <?php echo esc_attr($ep->post_title); ?>">
                <span class="mpc-ep-play-icon" aria-hidden="true">▶</span>
            </button>
            <?php endif; ?>
        </div>

        <div class="mpc-ep-info">
            <div class="mpc-ep-badges">
                <?php if ($ep_num): ?>
                <span class="mpc-badge mpc-badge-num">اپیزود <?php echo esc_html($ep_num); ?></span>
                <?php endif; ?>
            </div>
            <h4 class="mpc-ep-title">
                <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($ep->post_title); ?></a>
            </h4>
            <?php if ($excerpt): ?>
            <div class="mpc-ep-excerpt-wrap">
                <p class="mpc-ep-excerpt"><?php echo esc_html(wp_trim_words($excerpt, 20)); ?></p>
                <?php if (str_word_count($excerpt) > 20): ?>
                <button class="mpc-read-more-btn" aria-expanded="false"
                        aria-controls="ep-full-<?php echo $ep->ID; ?>">
                    <span class="mpc-read-more-text">مشاهده بیشتر ↓</span>
                </button>
                <div class="mpc-ep-full-desc mpc-hidden" id="ep-full-<?php echo $ep->ID; ?>">
                    <p><?php echo esc_html($excerpt); ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="mpc-ep-meta">
                <?php if ($duration): ?>
                <span class="mpc-ep-duration">⏱ <?php echo esc_html($duration); ?></span>
                <?php endif; ?>
                <span class="mpc-ep-date">📅 <?php echo get_the_date('j F Y', $ep->ID); ?></span>
                <a href="<?php echo esc_url($permalink); ?>" class="mpc-ep-link">مشاهده کامل ←</a>
            </div>
        </div>

    </article>
    <?php
}

// ── Flat Episodes (no seasons) ────────────────────────────────────────────────
function mpc_render_flat_episodes( $atts ) {
    $episodes = get_posts([
        'post_type'      => 'mpc_episode',
        'posts_per_page' => $atts['limit'],
        'meta_key'       => '_mpc_ep_number',
        'orderby'        => $atts['orderby'],
        'order'          => $atts['order'],
    ]);
    if ( empty($episodes) ) {
        echo '<div class="mpc-empty"><p>هنوز اپیزودی منتشر نشده است.</p></div>';
        return;
    }
    echo '<div class="mpc-episodes-list mpc-flat">';
    foreach ($episodes as $ep) {
        mpc_render_episode_row($ep);
    }
    echo '</div>';
}
