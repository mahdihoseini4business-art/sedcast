<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_init', 'mpc_register_settings' );

function mpc_register_settings() {
    $fields = [
        'primary_color', 'secondary_color', 'accent_color',
        'episodes_per_page', 'show_search', 'show_filter', 'show_latest',
        'player_position', 'podcast_title', 'podcast_author',
        'podcast_email', 'podcast_category', 'podcast_language',
    ];
    foreach ($fields as $f) {
        register_setting( 'mpc_settings_group', 'mpc_' . $f, [
            'sanitize_callback' => 'mpc_sanitize_setting',
        ] );
    }
}

function mpc_sanitize_setting( $value ) {
    if ( is_array( $value ) ) {
        return array_map( 'sanitize_text_field', $value );
    }
    return sanitize_text_field( $value );
}

function mpc_render_settings_page() {
    if ( ! current_user_can('manage_options') ) return;
    ?>
    <div class="wrap mpc-settings-wrap" dir="rtl">
        <h1>تنظیمات پادکست</h1>
        <form method="post" action="options.php">
            <?php settings_fields('mpc_settings_group'); ?>

            <div class="mpc-settings-tabs">
                <button type="button" class="mpc-tab-btn active" data-tab="appearance">ظاهر</button>
                <button type="button" class="mpc-tab-btn" data-tab="podcast">اطلاعات پادکست</button>
                <button type="button" class="mpc-tab-btn" data-tab="display">نمایش</button>
                <button type="button" class="mpc-tab-btn" data-tab="shortcode">شورت‌کد</button>
            </div>

            <!-- Appearance Tab -->
            <div class="mpc-tab-content active" id="tab-appearance">
                <table class="form-table">
                    <tr>
                        <th>رنگ اصلی (طلایی)</th>
                        <td>
                            <input type="color" name="mpc_primary_color"
                                   value="<?php echo esc_attr(get_option('mpc_primary_color','#C9A84C')); ?>">
                            <p class="description">رنگ دکمه‌ها، هایلایت‌ها و عناصر اصلی</p>
                        </td>
                    </tr>
                    <tr>
                        <th>رنگ پس‌زمینه تیره</th>
                        <td>
                            <input type="color" name="mpc_secondary_color"
                                   value="<?php echo esc_attr(get_option('mpc_secondary_color','#1a1a1a')); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th>رنگ کِرِم / روشن</th>
                        <td>
                            <input type="color" name="mpc_accent_color"
                                   value="<?php echo esc_attr(get_option('mpc_accent_color','#F5F0E8')); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th>موقعیت پلیر</th>
                        <td>
                            <select name="mpc_player_position">
                                <option value="bottom" <?php selected(get_option('mpc_player_position'),'bottom'); ?>>پایین صفحه (Sticky)</option>
                                <option value="top"    <?php selected(get_option('mpc_player_position'),'top');    ?>>بالای صفحه</option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Podcast Info Tab -->
            <div class="mpc-tab-content" id="tab-podcast" style="display:none">
                <table class="form-table">
                    <tr>
                        <th>نام پادکست</th>
                        <td><input type="text" name="mpc_podcast_title" class="regular-text"
                                   value="<?php echo esc_attr(get_option('mpc_podcast_title','')); ?>"></td>
                    </tr>
                    <tr>
                        <th>نام مجری / تهیه‌کننده</th>
                        <td><input type="text" name="mpc_podcast_author" class="regular-text"
                                   value="<?php echo esc_attr(get_option('mpc_podcast_author','')); ?>"></td>
                    </tr>
                    <tr>
                        <th>ایمیل پادکست</th>
                        <td><input type="email" name="mpc_podcast_email" class="regular-text"
                                   value="<?php echo esc_attr(get_option('mpc_podcast_email','')); ?>"></td>
                    </tr>
                    <tr>
                        <th>دسته‌بندی</th>
                        <td>
                            <select name="mpc_podcast_category">
                                <?php
                                $cats = ['Arts','Business','Comedy','Education','Fiction','Government',
                                         'Health & Fitness','History','Kids & Family','Leisure','Music',
                                         'News','Religion & Spirituality','Science','Society & Culture',
                                         'Sports','Technology','True Crime','TV & Film'];
                                $cur = get_option('mpc_podcast_category','Technology');
                                foreach ($cats as $c): ?>
                                <option value="<?php echo esc_attr($c); ?>" <?php selected($cur,$c); ?>><?php echo esc_html($c); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>زبان</th>
                        <td>
                            <select name="mpc_podcast_language">
                                <option value="fa" <?php selected(get_option('mpc_podcast_language'),'fa'); ?>>فارسی (fa)</option>
                                <option value="en" <?php selected(get_option('mpc_podcast_language'),'en'); ?>>انگلیسی (en)</option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Display Tab -->
            <div class="mpc-tab-content" id="tab-display" style="display:none">
                <table class="form-table">
                    <tr>
                        <th>نمایش جستجو</th>
                        <td>
                            <label>
                                <input type="checkbox" name="mpc_show_search" value="1"
                                       <?php checked(get_option('mpc_show_search',1),'1'); ?>>
                                نوار جستجو نمایش داده شود
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th>نمایش فیلتر</th>
                        <td>
                            <label>
                                <input type="checkbox" name="mpc_show_filter" value="1"
                                       <?php checked(get_option('mpc_show_filter',1),'1'); ?>>
                                فیلتر فصل نمایش داده شود
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th>آخرین اپیزود</th>
                        <td>
                            <label>
                                <input type="checkbox" name="mpc_show_latest" value="1"
                                       <?php checked(get_option('mpc_show_latest',1),'1'); ?>>
                                بلوک "آخرین اپیزود" بالای صفحه نمایش داده شود
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th>تعداد اپیزود در هر صفحه</th>
                        <td>
                            <input type="number" name="mpc_episodes_per_page"
                                   value="<?php echo esc_attr(get_option('mpc_episodes_per_page',10)); ?>"
                                   min="1" max="100" class="small-text">
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Shortcode Tab -->
            <div class="mpc-tab-content" id="tab-shortcode" style="display:none">
                <div class="card" style="max-width:600px;padding:20px">
                    <h2>استفاده از شورت‌کد</h2>
                    <p>کافی است این شورت‌کد را در هر صفحه یا پستی (یا المنتور) قرار دهید:</p>
                    <code style="display:block;padding:12px;background:#f0f0f0;font-size:16px;border-radius:4px">[mahdi_podcast]</code>
                    <br>
                    <h3>پارامترهای اختیاری</h3>
                    <table class="widefat" style="margin-top:10px">
                        <thead><tr><th>پارامتر</th><th>مقدار</th><th>توضیح</th></tr></thead>
                        <tbody>
                            <tr><td><code>season</code></td><td>شناسه فصل</td><td>فقط یک فصل نمایش داده شود</td></tr>
                            <tr><td><code>limit</code></td><td>عدد (پیش‌فرض: -1)</td><td>تعداد اپیزود</td></tr>
                            <tr><td><code>order</code></td><td>ASC / DESC</td><td>ترتیب</td></tr>
                            <tr><td><code>show_search</code></td><td>0 / 1</td><td>نمایش جستجو</td></tr>
                            <tr><td><code>show_filter</code></td><td>0 / 1</td><td>نمایش فیلتر</td></tr>
                        </tbody>
                    </table>
                    <p style="margin-top:15px"><strong>مثال:</strong><br>
                    <code>[mahdi_podcast season="3" limit="5" order="DESC"]</code></p>
                </div>
            </div>

            <?php submit_button('ذخیره تنظیمات', 'primary large'); ?>
        </form>
    </div>
    <script>
    document.querySelectorAll('.mpc-tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.mpc-tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.mpc-tab-content').forEach(c => c.style.display = 'none');
            this.classList.add('active');
            document.getElementById('tab-' + this.dataset.tab).style.display = 'block';
        });
    });
    </script>
    <?php
}
