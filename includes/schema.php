<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Inject schema on single episode pages
add_action( 'wp_head', 'mpc_inject_episode_schema' );
// Inject podcast series schema on archive
add_action( 'wp_head', 'mpc_inject_series_schema' );

function mpc_inject_episode_schema() {
    if ( ! is_singular('mpc_episode') ) return;

    global $post;
    $audio    = get_post_meta($post->ID, '_mpc_audio_url',  true);
    $duration = get_post_meta($post->ID, '_mpc_duration',   true);
    $ep_num   = get_post_meta($post->ID, '_mpc_ep_number',  true);
    $ep_type  = get_post_meta($post->ID, '_mpc_ep_type',    true) ?: 'full';
    $explicit = get_post_meta($post->ID, '_mpc_explicit',   true);
    $filesize = get_post_meta($post->ID, '_mpc_file_size',  true);
    $keywords = get_post_meta($post->ID, '_mpc_keywords',   true);
    $cover    = get_the_post_thumbnail_url($post->ID, 'large');
    $seasons  = get_the_terms($post->ID, 'mpc_season');

    // Convert duration HH:MM:SS -> ISO 8601 PT#H#M#S
    $iso_duration = '';
    if ($duration) {
        // Convert Farsi/Arabic numerals to Western Arabic
        $farsi  = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $arabic = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
        $latin  = ['0','1','2','3','4','5','6','7','8','9'];
        $duration = str_replace($farsi, $latin, str_replace($arabic, $latin, $duration));

        $parts = explode(':', $duration);
        $h = isset($parts[0]) ? (int)$parts[0] : 0;
        $m = isset($parts[1]) ? (int)$parts[1] : 0;
        $s = isset($parts[2]) ? (int)$parts[2] : 0;
        $iso_duration = 'PT' . ($h ? $h.'H' : '') . ($m ? $m.'M' : '') . ($s ? $s.'S' : '');
    }

    $schema = [
        '@context'        => 'https://schema.org',
        '@type'           => 'PodcastEpisode',
        'name'            => get_the_title(),
        'url'             => get_permalink(),
        'datePublished'   => get_the_date('c'),
        'dateModified'    => get_the_modified_date('c'),
        'description'     => wp_strip_all_tags(get_the_excerpt()),
        'episodeNumber'   => $ep_num ?: null,
        'contentRating'   => $explicit === '1' ? 'explicit' : 'clean',
        'partOfSeries'    => [
            '@type' => 'PodcastSeries',
            'name'  => get_option('mpc_podcast_title', get_bloginfo('name') . ' پادکست'),
            'url'   => get_post_type_archive_link('mpc_episode'),
        ],
    ];

    if ($cover) $schema['image'] = $cover;
    if ($iso_duration) $schema['duration'] = $iso_duration;
    if ($keywords) $schema['keywords'] = array_map('trim', explode(',', $keywords));

    if ($seasons && !is_wp_error($seasons)) {
        $season = $seasons[0];
        $season_num = get_term_meta($season->term_id, 'season_number', true);
        $schema['partOfSeason'] = [
            '@type'         => 'PodcastSeason',
            'name'          => $season->name,
            'seasonNumber'  => $season_num ?: null,
            'url'           => get_term_link($season),
        ];
    }

    if ($audio) {
        $schema['associatedMedia'] = [
            '@type'       => 'MediaObject',
            'contentUrl'  => $audio,
            'encodingFormat' => 'audio/mpeg',
        ];
        if ($filesize) $schema['associatedMedia']['contentSize'] = $filesize;
        if ($iso_duration) $schema['associatedMedia']['duration'] = $iso_duration;
    }

    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>' . "\n";
}

function mpc_inject_series_schema() {
    if ( ! is_post_type_archive('mpc_episode') ) return;

    $schema = [
        '@context'       => 'https://schema.org',
        '@type'          => 'PodcastSeries',
        'name'           => get_option('mpc_podcast_title', get_bloginfo('name') . ' پادکست'),
        'url'            => get_post_type_archive_link('mpc_episode'),
        'description'    => get_bloginfo('description'),
        'author'         => [
            '@type' => 'Person',
            'name'  => get_option('mpc_podcast_author', get_bloginfo('name')),
        ],
        'inLanguage'     => get_option('mpc_podcast_language', 'fa'),
    ];

    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>' . "\n";
}
