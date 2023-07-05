<?php 
/**
 * Plugin Name: RSSfeed-Parts
 * Plugin URI: https://basekix.com
 * Description: WordPress用のRSSフィード表示ウィジェット
 * Version: 1.0.0
 * Author: Kasiri
 */

// プラグインのコードを記述する場所

// 管理メニューを追加するフックのアクションを登録
add_action('admin_menu', 'rssfeed_parts_add_menu');

// 管理メニューを作成するコールバック関数
function rssfeed_parts_add_menu() {
    // 管理メニューを追加するための関数
    add_menu_page(
        'RSSfeed-Parts Settings',   // ページのタイトル
        'RSSfeed-Parts',            // メニューのタイトル
        'manage_options',           // アクセス権限
        'rssfeed_parts_settings',   // ページのスラッグ
        'rssfeed_parts_settings_page', // コールバック関数
        'dashicons-rss',            // アイコン
        99                          // メニューの位置
    );
}

// CSSファイルの読み込み
function rssfeed_parts_load_custom_css() {
    $css_file_url = plugin_dir_url(__FILE__) . 'assets/css/rssfeed-parts-style.css';
    wp_enqueue_style('rssfeed_parts_custom_css', $css_file_url, array(), '1.0.0');
}
add_action('wp_enqueue_scripts', 'rssfeed_parts_load_custom_css');

// 設定ページのコールバック関数
function rssfeed_parts_settings_page() {
    // フィードの削除処理
    if (isset($_POST['rssfeed_parts_delete_feed'])) {
        $deleted_feed_url = $_POST['rssfeed_parts_delete_feed'];
        $registered_feeds = get_option('rssfeed_parts_feeds', array());

        // フィードを削除
        foreach ($registered_feeds as $key => $feed) {
            if ($feed['feed_url'] === $deleted_feed_url) {
                unset($registered_feeds[$key]);
                break;
            }
        }

        update_option('rssfeed_parts_feeds', $registered_feeds);
        echo '<div class="updated"><p>フィードが削除されました。</p></div>';
    }

    // フィードの登録処理
    if (isset($_POST['rssfeed_parts_feed_url'])) {
        $feed_url = $_POST['rssfeed_parts_feed_url'];
        $display_count = isset($_POST['rssfeed_parts_display_count']) && !empty($_POST['rssfeed_parts_display_count']) ? $_POST['rssfeed_parts_display_count'] : 20; // デフォルト値として20を設定

        // フィードを登録
        $registered_feeds = get_option('rssfeed_parts_feeds', array());
        $registered_feeds[] = array(
            'feed_url' => $feed_url,
            'display_count' => $display_count,
        );
        update_option('rssfeed_parts_feeds', $registered_feeds);
        echo '<div class="updated"><p>RSSフィードが登録されました。</p></div>';
    }

    // CSSの保存処理
    if (isset($_POST['rssfeed_parts_custom_css'])) {
        $custom_css = $_POST['rssfeed_parts_custom_css'];
        rssfeed_parts_save_custom_css_file($custom_css); // CSSファイルを保存する関数を呼び出す
        update_option('rssfeed_parts_custom_css', $custom_css);
        echo '<div class="updated"><p>CSSが保存されました。</p></div>';
    }

    ?>

    <div class="wrap">
        <h1>RSSfeed-Partsの設定</h1>

        <h2>ショートコード</h2>
        <p>以下のショートコードを使用して、プレビューを記事などで表示できます。</p>
        <p><code>[rssfeed_parts_preview]</code></p>
        
        <h2>登録されたフィード</h2>
        <?php
        $registered_feeds = get_option('rssfeed_parts_feeds', array());

        if (!empty($registered_feeds)) :
            ?>
            <table class="wp-list-table widefat fixed striped rssfeed-parts-table">
                <thead>
                    <tr>
                        <th>サイト名</th>
                        <th>RSSフィードURL</th>
                        <th>表示数</th>
                        <th>削除</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registered_feeds as $feed) : ?>
                        <tr>
                            <td><?php echo get_site_title_from_feed($feed['feed_url']); ?></td>
                            <td><?php echo esc_html($feed['feed_url']); ?></td>
                            <td><?php echo isset($feed['display_count']) ? esc_html($feed['display_count']) : 'N/A'; ?></td>
                            <td>
                                <form method="post" onsubmit="return confirm('このフィードを削除してもよろしいですか？');">
                                    <input type="hidden" name="rssfeed_parts_delete_feed" value="<?php echo esc_attr($feed['feed_url']); ?>">
                                    <button type="submit" class="button">削除</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>登録されたフィードはありません。</p>
        <?php endif; ?>

        <h2>新しいフィードを追加</h2>
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th><label for="rssfeed_parts_feed_url">RSSフィードURL:</label></th>
                    <td><input type="text" name="rssfeed_parts_feed_url" id="rssfeed_parts_feed_url" required></td>
                </tr>
                <tr>
                    <th><label for="rssfeed_parts_display_count">表示数:</label></th>
                    <td><input type="number" name="rssfeed_parts_display_count" id="rssfeed_parts_display_count" min="1"></td>
                </tr>
            </table>

            <button type="submit" name="submit_register_feed" class="button">フィードを登録</button>
        </form>

        <h2>CSS設定</h2>
        <p>以下のテキストエリアにCSSを指定して、プレビューのスタイルをカスタマイズできます。</p>
        <p>プラグイン独自のクラスを使用して要素を選択し、適用するスタイルを指定してください。</p>
        <p><strong>例：</strong> .rssfeed-parts-preview-container { background-color: #f1f1f1; }</p>
        <p>※ CSSの詳細な書き方についてはCSSリファレンスをご参照ください。</p>
        
        <form method="post" action="">
            <textarea name="rssfeed_parts_custom_css" rows="10" style="width: 100%;"><?php echo esc_textarea(get_option('rssfeed_parts_custom_css', '')); ?></textarea>
            <p><em>※ 変更したCSSはプラグインのアップデート時に上書きされる可能性があるため、バックアップを取るか、テーマのカスタマイズ用のCSSファイルに追記することをおすすめします。</em></p>
            <button type="submit" name="submit_custom_css" class="button button-primary">CSSを保存</button>
        </form>

        <h2>プレビュー</h2>
        <?php echo do_shortcode('[rssfeed_parts_preview]'); ?>

        <hr>
        <a href="https://basekix.com" target="_blank"><img src="<?php echo plugin_dir_url(__FILE__) . 'assets/img/bgt.png'; ?>" alt="Basekix" /></a>
    </div>
    <?php
}

// プレビューの表示ショートコード
function rssfeed_parts_preview_shortcode($atts) {
    ob_start();
    rssfeed_parts_display_previews();
    return ob_get_clean();
}
add_shortcode('rssfeed_parts_preview', 'rssfeed_parts_preview_shortcode');

// CSSファイルの保存
function rssfeed_parts_save_custom_css_file($custom_css) {
    $css_file_path = plugin_dir_path(__FILE__) . 'assets/css/rssfeed-parts-style.css';

    // CSSファイルの書き込み
    if (is_writable($css_file_path)) {
        file_put_contents($css_file_path, $custom_css);
    } else {
        echo '<div class="error"><p>CSSファイルに書き込み権限がありません。</p></div>';
    }
}

// プレビューの表示
function rssfeed_parts_display_previews() {
    $registered_feeds = get_option('rssfeed_parts_feeds', array());

    if (!empty($registered_feeds)) {
        echo '<div class="rssfeed-parts-preview-container">';

        foreach ($registered_feeds as $feed) {
            $feed_url = $feed['feed_url'];
            $display_count = isset($feed['display_count']) ? $feed['display_count'] : 20; // デフォルト値として20を設定

            // RSSフィードを取得
            $rss = fetch_feed($feed_url);

            if (!is_wp_error($rss)) {
                $max_items = $rss->get_item_quantity($display_count); // 表示する最大アイテム数
                $rss_items = $rss->get_items(0, $max_items); // アイテムを取得

                foreach ($rss_items as $item) {
                    $item_title = esc_html($item->get_title());
                    $item_link = esc_url($item->get_permalink()); // リンク先URL
                    echo '<div class="rssfeed-parts-preview-item"><a href="' . $item_link . '">' . $item_title . '</a></div>';
                }
            } else {
                echo '<p>RSSフィードの取得に失敗しました: ' . esc_html($feed_url) . '</p>';
                echo '<p>エラー詳細: ' . esc_html($rss->get_error_message()) . '</p>';
            }
        }

        echo '</div>';
    } else {
        echo '<p>登録されたフィードはありません。</p>';
    }
}

// フィードのサイト名を取得
function get_site_title_from_feed($feed_url) {
    // RSSフィードを取得
    $rss = fetch_feed($feed_url);

    if (!is_wp_error($rss)) {
        $feed_site_title = $rss->get_channel_tags(SIMPLEPIE_NAMESPACE_RSS_20, 'title'); // RSS 2.0のタイトル要素からサイトタイトルを取得

        if (!empty($feed_site_title)) {
            return esc_html($feed_site_title[0]['data']);
        }
    }

    return '';
}
