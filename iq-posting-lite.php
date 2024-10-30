<?php
/**
Plugin Name: iQ Posting Lite
Plugin URI: https://iqposting.com
Description: Create a social media funnel to your site in minutes. The Free Licensed version adds styling settings plus other features
Tags: opengraph, funnel, social, social media, share
Version: 1.1.1
Author: iQ Marketers
Author URI: https://iqmarketers.com
License: GPLv2 or later
Text Domain: iq-posting-lite
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class iQ_Posting_Lite
{

    private $iq_posting_fields;

    /**
     * Constructor.
     */
    public function __construct()
    {

        register_activation_hook(__FILE__, array($this, 'check_iq_version'));

        if (is_admin()) {
            add_action('load-post.php', array($this, 'init_metabox'));
            add_action('load-post-new.php', array($this, 'init_metabox'));
            add_action('wp_ajax_iq_posting_action', array($this, 'iq_posting_action'));
            add_action('wp_ajax_nopriv_iq_posting_action', array($this, 'iq_posting_action'));
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
            add_action('admin_init', array($this, 'admin_init'));
            add_action('admin_menu', array($this, 'postingiq_add_admin_menu'));
            add_action('admin_init', array($this, 'postingiq_settings_init'));
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'postingiq_action_links'));
            add_action('wp_dashboard_setup', array($this, 'iqposting_add_dashboard'));
            add_filter('plugin_row_meta', array($this, 'iq_posting_plugin_row'), 10, 2);
            add_action('admin_notices', array($this, 'iq_admin_notice'));

        }

        add_action('wp_enqueue_scripts', array($this, 'styles'));

    }

    public function check_iq_version()
    {
        add_option('iq_posting_notice', 'yes');

        if (is_plugin_active('iq-posting/iq-posting.php')) {
            wp_die(__('It looks like you have the licensed version of iQ Posting installed.<br> We recommend you <b>delete iQ Posting Lite</b> at this point.', 'iq-posting-lite'));
        }
    }

    /**
     * Adds admin stylesheet
     */
    public static function admin_init()
    {
        add_editor_style(plugin_dir_url(__FILE__) . 'iq-posting-editor.css');

        if (isset($_GET['iq-posting-dismiss']))
            delete_option('iq_posting_notice');
    }

    /**
     * Enqueues frontend styles
     */
    public static function styles()
    {
        if (is_singular()) {
            wp_register_style('iq-posting-css', plugins_url('iq-posting.css', __FILE__));
            wp_enqueue_style('iq-posting-css');
            wp_register_script('iq-posting-js', plugins_url('iq-posting.js', __FILE__), array('jquery'));
            wp_enqueue_script('iq-posting-js');
        }
    }

    /**
     * Meta box initialization.
     */
    public function init_metabox()
    {
        add_action('add_meta_boxes', array($this, 'add_postingiq_metabox'));
    }

    /**
     * Adds the meta box.
     */
    public function add_postingiq_metabox()
    {
        $options = get_option('postingiq_settings');
        $post_types = $options['iq_posting_post_types'];
        $allowed = array();
        foreach ($post_types as $post_type) :
            $allowed[$post_type] = $post_type;
        endforeach;

        add_meta_box(
            'iq-posting-meta-box',
            __('iQ Posting Lite', 'textdomain'),
            array($this, 'postingiq_metabox'),
            $allowed,
            'side',
            'high'
        );

    }

    private static function is_gutenberg_page()
    {
        if (function_exists('is_gutenberg_page') &&
            is_gutenberg_page()
        ) {
            // The Gutenberg plugin is on.
            return true;
        }
        $current_screen = get_current_screen();
        if (method_exists($current_screen, 'is_block_editor') &&
            $current_screen->is_block_editor()
        ) {
            return true;
        }
        return false;
    }

    /**
     * Renders the meta box.
     */
    public function postingiq_metabox($post)
    {
        wp_nonce_field('postingiq_nonce_action', 'postingiq_nonce');

        $block_editor = self::is_gutenberg_page();

        ($block_editor === true)
            ? $image_label = 'Save Image in Media Library <br /><span class="description">You can manually set it as a Featured Image afterwards</span>'
            : $image_label = 'Add Image as Featured Image';

        ?>
        <style type="text/css">.iq_posting_error {
                display: block;
                color: #cc0000;
                font-size: .9em;
            }</style>
        <p class="iq_posting_field">
            <label for="iq_posting_url">
                <?php _e('Link you want to post:'); ?>
            </label>
            <input placeholder="https://www.domain.com/" type="text" id="iq_posting_url" name="iq_posting_url"
                   style="width: 100%" value=""/>
        </p>
        <p class="iq_posting_field">
            <input autocomplete="off" type="checkbox" id="iq_posting_image"
                   name="iq_posting_image" value="yes" checked="checked" />
            <label for="iq_posting_image"><?php echo $image_label ?></label>
            <br/>
            <small><a href="https://www.iqposting.com/" target="_blank"><strong style="font-weight:700">Change box and background colors</strong> by getting a FREE license key!</a></small>
        </p>
        <p class="iq_posting_field">
            <input autocomplete="off" type="checkbox" id="iq_posting_text" name="iq_posting_text" value="yes"
                   checked="checked"/>
            <label for="iq_posting_text">Add Title and Description to Post Content?</label>
        </p>
        <p class="iq_posting_field">
            <input type="radio" id="iq_posting_popup" name="iq_posting_link" value="popup" checked="checked"/>
            <label for="iq_posting_popup">Open as modal pop-up</label>
            <br/>
            <input type="radio" id="iq_posting_link" name="iq_posting_link" value="link"/>
            <label for="iq_posting_link">Open as link</label>
            <br/>
            <input type="radio" id="iq_posting_nolink" name="iq_posting_link" value="none"/>
            <label for="iq_posting_nolink">No link</label>
        </p>
        <p class="iq_posting_field">
            <button id="iq-posting-link" class="button button-primary button-large">Post Link</button>
            <a target="_blank" href="https://www.iqposting.com" class="more_from_iq">Check Out Our Other Plugins</a>
        </p>

        <?php
    }

    public function iqposting_add_dashboard()
    {
        wp_add_dashboard_widget('iqposting_dashboard', 'iQ Posting Lite', array($this, 'iqposting_dashboard_widget'));
    }

    public function iqposting_dashboard_widget()
    {
        $return = '';

        $news = self::iq_posting_news(3, true);

        $return .= '<h3 class="feed-title">Subscribe to our Newsletter</h3>';

        $return .= '<form action="https://email.iqemailmarketing.com/subscribe" method="POST" accept-charset="utf-8">
<label for="name">Name</label><br/>
<input type="text" name="name" id="name"/>
<br/>
<label for="email">Email</label><br/>
<input type="email" name="email" id="email"/><br/><br/>
<input type="checkbox" name="gdpr" id="gdpr"/>
<label for="gdpr"><strong>Marketing permission</strong>: I give my consent to to be in touch with me via email using the information I have provided in this form for the purpose of news, updates and marketing.</label>
<br/><br/>
<div style="display:none;">
<label for="hp">HP</label><br/>
<input type="text" name="hp" id="hp"/>
</div>
<input type="hidden" name="list" value="NNfVV32j4N4JrhO1aLgDqQ"/>
<input type="hidden" name="subform" value="yes"/>
<input type="submit" name="submit" id="submit" value="subscribe" class="button button-primary" />
</form>';
        $return .= '<ul class="iqposting-dashboard">' . $news . '</ul>';

        $return .= '<a target="_blank" href="https://iqposting.com/" class="iq-posting-logo"><img alt="iQ Marketers" src="' . plugin_dir_url(__FILE__) . 'iqlogo.png' . '" /></a>
                        <br>Go to <a target="_blank" href="https://iqposting.com/">iqposting.com</a>
                        <br>This is a product of iQ Marketers. <a href="https://iqmarketers.com/">More from iqmarketers.com</a>';

        echo $return;
    }

    /**
     * Retrieves the OpenGraph info
     * from remote site
     *
     * @param $url
     * @return array|mixed
     */
    private function get_og_data($url, $post_id = 0)
    {

        if (!class_exists('tiny_OpenGraph')) {
            require_once(plugin_dir_path(__FILE__) . 'opengraph.php');
        }

        $data = wp_remote_retrieve_body(wp_remote_get($url));
        $data = mb_convert_encoding($data, 'HTML-ENTITIES', 'auto,ISO-8859-1');

        $result = array();

        if ($data) {
            $graph = tiny_OpenGraph::parse($data);
            if ($graph) {
                foreach ($graph as $key => $value) {
                    $result[$key] = $value;
                }
            }
        }

        if ($data && !$result) {
            $result = self::get_og_data_fallback($data);
        }

        if ($result) {

            $result['url'] = $url;
            if (!isset($result['site_name'])) {
                $result['site_name'] = wp_parse_url($url, PHP_URL_HOST);
            }
            if ( isset( $result['image'] ) && $result['image'] ) {
                $result['image'] = self::force_absolute_url( $result['image'], $url );
                $image_data      = self::cache_image( $result['image'], $post_id );
                $result          = array_merge( $result, $image_data );
            }
        }

        foreach ($result as $key => $value) {
            $result[$key] = strip_tags($value);
        }

        return $result;

    }

    private static function get_og_data_fallback($data)
    {
        $result = array();

        $title = false;
        $description = false;

        $old_libxml_error = libxml_use_internal_errors(true);
        $doc = new DOMDocument;
        $doc->loadHTML($data);
        libxml_use_internal_errors($old_libxml_error);


        $title_dom = $doc->getElementsByTagName('title');
        if ($title_dom->item(0)) {
            $title = $title_dom->item(0)->textContent;
        };

        $xpath = new DOMXPath($doc);
        $description_dom = $xpath->query('//meta[@name="description"]/@content');

        if ($description_dom->item(0)) {
            $description = $description_dom->item(0)->value;
        }

        $result = array();

        if($title) $result['title'] = $title;
        if($description) $result['description'] = $description;

        return $result;
    }

    private static function force_absolute_url($url, $site_url)
    {
        if ($url && !filter_var($url, FILTER_VALIDATE_URL) && !filter_var('http:' . $url, FILTER_VALIDATE_URL)) {
            $url_parts = wp_parse_url($site_url);
            $site_url = $url_parts['scheme'] . "://" . $url_parts['host'] . "/";
            if (0 !== strpos($url, '/')) {
                $url = '/' . $url;
            }
            $url = untrailingslashit($site_url) . $url;
        }
        return $url;
    }

    private function cache_image( $image_url, $post_id ) {

        require_once( ABSPATH . "wp-admin" . '/includes/image.php' );
        require_once( ABSPATH . "wp-admin" . '/includes/file.php' );
        require_once( ABSPATH . "wp-admin" . '/includes/media.php' );

        $temp_file = download_url( $image_url );
        if ( ! is_wp_error( $temp_file ) ) {
            $allowed_mime_types = array(
                'image/jpeg',
                'image/gif',
                'image/png',
                'image/bmp',
                'image/tiff',
                'image/x-icon',
            );
            $mime               = self::_get_mime_type( $temp_file );
            preg_match( '/[^\?]+\.(jpg|jpe|jpeg|gif|png|ico)/i', $image_url, $matches );
            if ( in_array( $mime, $allowed_mime_types ) ) {
                $filename = basename( $matches[0] );
                $filename = urldecode( $filename );
                $filename = explode( '.', $filename );
                foreach ( $filename as $key => $value ) {
                    $filename[ $key ] = sanitize_title( $value );
                }
                $filename  = implode( '.', $filename );
                $file      = array(
                    'name'     => $filename,
                    'type'     => $mime,
                    'tmp_name' => $temp_file,
                    'error'    => 0,
                    'size'     => filesize( $temp_file ),
                );
                $overrides = array(
                    'test_form'   => false,
                    'test_size'   => true,
                    'test_upload' => true,
                );
                $a         = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], false );
                $movefile  = wp_handle_sideload( $file, $overrides );
                if ( $movefile && ! isset( $movefile['error'] ) ) {
                    $wp_upload_dir = wp_upload_dir();
                    $attachment    = array(
                        'guid'           => $wp_upload_dir['url'] . '/' . basename( $movefile['file'] ),
                        'post_mime_type' => $movefile['type'],
                        'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $movefile['file'] ) ),
                        'post_content'   => '',
                        'post_status'    => 'inherit'
                    );
                    $attach_id     = wp_insert_attachment( $attachment, $movefile['file'], $post_id );
                    require_once( ABSPATH . 'wp-admin/includes/image.php' );
                    $attach_data = wp_generate_attachment_metadata( $attach_id, $movefile['file'] );
                    wp_update_attachment_metadata( $attach_id, $attach_data );

                    $image_src = wp_get_attachment_image_src( $attach_id, 'post-thumbnail' );

                    $result = array(
                        'attachment_id'  => $attach_id,
                        'attachment_src' => $image_src['0']
                    );

                    return $result;
                }
            }
        }

        return false;
    }

    private static function _get_mime_type($file)
    {
        $mtype = false;
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mtype = finfo_file($finfo, $file);
            finfo_close($finfo);
        } elseif (function_exists('mime_content_type')) {
            $mtype = mime_content_type($file);
        }
        return $mtype;
    }

    // Ajax part
    public function iq_posting_action()
    {
        $postID = intval( $_POST['post_id'] );
        $url    = esc_url( $_POST['url'] );
        $link   = wp_strip_all_tags($_POST['link']);
        $nonce  = $_POST['nonce'];

        if (!wp_verify_nonce($nonce, 'postingiq_nonce_action')) {

            wp_send_json_error(array('output' => 'Not Allowed'));

        }

        /**
         * $output = array(
         * 'attachment_id' => 0,
         * 'attachment_src' => '',
         * 'title' => '',
         * 'description'    => '',
         * 'url'    => ''
         * );
         **/
        $headers = self::check_headers($url);

        if ($headers['status'] === 1) {
            if ($headers['popup'] === 0 and $link === 'popup') {
                $error = 'Can not open in a pop-up. Please choose another option.';
                $output = array(
                    'error' => $error
                );
                wp_send_json_error(array('output' => $output));
            } else {
                $output = iQ_Posting_Lite::get_og_data($url, $postID);
                $output['popup'] = $headers['popup'];
                wp_send_json_success(array('output' => $output));
            }

        } else {
            if ($headers['status'] === 0) $error = 'Link not found';
            $output = array(
                'error' => $error
            );
            wp_send_json_error(array('output' => $output));

        }

    }

    public static function admin_scripts($hook)
    {
        global $iq_admin_hook;

        wp_register_script('iq-posting-admin', plugins_url('iq-posting-admin.js', __FILE__), array('jquery'));
        wp_enqueue_script('iq-posting-admin');
        wp_enqueue_style('iq-posting-admin-css', plugins_url('iq-posting-admin.css', __FILE__));
    }

    public static function check_headers($url = '')
    {

        if (empty($url))
            return false;

        $response = wp_remote_get($url);
        $xframe = wp_remote_retrieve_header($response, 'X-Frame-Options');
        $retcode = wp_remote_retrieve_response_code($response);

        $result = array(
            'status' => 0,
            'popup' => 1
        );

        if ($retcode) {
            $result['status'] = 1;
        }

        if ($xframe === 'SAMEORIGIN' or $xframe === 'sameorigin')
            $result['popup'] = 0;

        return $result;
    }

    function postingiq_add_admin_menu()
    {
        global $iq_admin_hook;
        $iq_admin_hook = add_menu_page('iQ Posting Lite', 'iQ Posting Lite', 'manage_options', 'iq-posting', array($this, 'postingiq_options_page'), 'dashicons-format-aside');
    }


    function postingiq_settings_init()
    {

        register_setting('postingIQ', 'postingiq_settings');

        add_settings_section(
            'postingiq_postingIQ_section',
            '',
            '',
            'postingIQ'
        );

        add_settings_field(
            'iq_posting_post_types',
            __('Show on post types:', 'postingiq'),
            array($this, 'postingiq_setting_post_types_render'),
            'postingIQ',
            'postingiq_postingIQ_section'
        );

    }

    function postingiq_setting_post_types_render()
    {

        $options = get_option('postingiq_settings');
        $post_types = self::get_iq_post_types();

        $i = 0;
        foreach ($post_types as $post_type) {
            $checked = (in_array($post_type, $options['iq_posting_post_types']) ? 'checked' : "");
            if (empty($options) and ($post_type === 'post' or $post_type === 'page')) $checked = 'checked';
            echo '<input ' . $checked . ' type="checkbox" name="postingiq_settings[iq_posting_post_types][]" value="' . $post_type . '" id="iq_posting_' . $post_type . '"><label for="iq_posting_' . $post_type . '">' . $post_type . '</label><br />';
            $i++;
        }

        ?>
        <?php

    }

    function postingiq_options_page()
    {
        $news = self::iq_posting_news(); ?>

        <div id="iq-posting-wrapper">
            <div id="iq-posting">
                <div class="postbox">
                    <div class="inside">
                        <div class="iq-posting-columns">
                            <div class="piq-main-column">
                                <a href="https://iqmarketers.com/" class="iq-posting-logo">
                                    <img alt="iQ Marketers"
                                         src="<?php echo plugin_dir_url(__FILE__) . 'iqlogo.png' ?>"/>
                                </a>
                                <br>Go to <a target="_blank" href="https://iqposting.com/">iqposting.com</a>
                                <br>This is a product of iQ Marketers. <a href="https://iqmarketers.com/">More from
                                    iqmarketers.com</a>
                                <h3>iQ Posting Lite</h3>
                                <div id="iq-upgrade">
                                    <a href="https://iqposting.com" target="_blank" class="iq-button">FREE Upgrade!</a>
                                    <div class="iq-upgrade-benefits">With the <b>FREE Licensed</b> version of iQ
                                        Posting, you can style your pop-up and the embed box, re-use what you already shared in other posts and more.
                                    </div>
                                </div>
                                <div class="iq-newsletter">
                                    <h3 class="feed-title">Subscribe to our Newsletter</h3>
                                    <form action="https://email.iqemailmarketing.com/subscribe" method="POST"
                                          accept-charset="utf-8">
                                        <div class="iq-newsletter-fields">
                                            <input placeholder="Your Name" class="iq-fields" type="text" name="name"
                                                   id="name"/>
                                            <input placeholder="Your E-mail" class="iq-fields" type="email" name="email"
                                                   id="email"/>
                                            <input type="submit" name="submit" id="submit" value="Subscribe"
                                                   class="iq-button"/>
                                        </div>
                                        <input type="checkbox" name="gdpr" id="gdpr"/>
                                        <label for="gdpr"><strong>Marketing permission</strong>: I give my consent to to
                                            be in touch with me via email using the information I have provided in this
                                            form for the purpose of news, updates and marketing.</label>
                                        <br/><br/>
                                        <div style="display:none;">
                                            <label for="hp">HP</label><br/>
                                            <input type="text" name="hp" id="hp"/>
                                        </div>
                                        <input type="hidden" name="list" value="NNfVV32j4N4JrhO1aLgDqQ"/>
                                        <input type="hidden" name="subform" value="yes"/>
                                    </form>
                                </div>
                            </div>
                            <div class="piq-video">
                                <h3><span>Step # 1</span> Just copy the link you want to share</h3>
                                <div class="iq-help-video">
                                    <iframe width="100%" height="315" src="https://www.youtube.com/embed/7VUvfhW_6I0"
                                            frameborder="0"
                                            allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                                            allowfullscreen></iframe>
                                </div>
                                <h3><span>Step # 2</span> Add Copy, Media, Forms, Links, Then Post And Publish To Social
                                    Media And Watch The Traffic Come In!</h3>
                                <div class="iq-help-video">
                                    <iframe width="100%" height="315" src="https://www.youtube.com/embed/FBwZG2QhVMc"
                                            frameborder="0"
                                            allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                                            allowfullscreen></iframe>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="postbox">
                    <div class="inside">
                        <h2>iQ Posting News</h2>
                        <ul class="iq-posting-news">
                            <?php echo $news ?>
                        </ul>
                    </div>
                </div>
                <div class="postbox">
                    <div class="inside">
                        <h2>Settings</h2>
                        <form action='options.php' method='post'>
                            <?php
                            settings_fields('postingIQ');
                            do_settings_sections('postingIQ');
                            submit_button(); ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php

    }

    private static function get_iq_post_types()
    {
        $args = array(
            'public' => true
        );

        $output = 'names'; // names or objects, note names is the default
        $operator = 'and'; // 'and' or 'or'

        $post_types = get_post_types($args, $output, $operator);
        unset($post_types['attachment']);
        unset($post_types['elementor_library']);

        return $post_types;
    }

    function postingiq_action_links($links)
    {
        $mylinks = array(
            '<a href="' . admin_url('options-general.php?page=iq-posting') . '">Settings</a>',
        );
        return array_merge($links, $mylinks);
    }

    private static function iq_posting_news($max_item_cnt = 3, $dashboard = false)
    {

        $result = '';
        $cached = get_transient('iq_posting_news');
        $force = $_GET['iq_force_feed'];
        if (isset($force)) $force = wp_strip_all_tags($force);

        if ($cached === false or $force === 'yes') {
            // get feeds and parse items
            $rss = new DOMDocument();
            // load from file or load content
            $feed_url = 'https://iqmarketers.com/category/iq-posting/feed/';
            $rss->load($feed_url);

            $feed = array();
            foreach ($rss->getElementsByTagName('item') as $node) {
                $item = array(
                    'title' => $node->getElementsByTagName('title')->item(0)->nodeValue,
                    'desc' => $node->getElementsByTagName('description')->item(0)->nodeValue,
                    'content' => $node->getElementsByTagName('description')->item(0)->nodeValue,
                    'link' => $node->getElementsByTagName('link')->item(0)->nodeValue,
                    'date' => $node->getElementsByTagName('pubDate')->item(0)->nodeValue,
                );
                $content = $node->getElementsByTagName('encoded'); // <content:encoded>
                if ($content->length > 0) {
                    $item['content'] = $content->item(0)->nodeValue;
                }
                array_push($feed, $item);
            }
            // real good count
            if ($max_item_cnt > count($feed)) {
                $max_item_cnt = count($feed);
            }
            for ($x = 0; $x < $max_item_cnt; $x++) {
                $title = str_replace(' & ', ' &amp; ', $feed[$x]['title']);
                $link = $feed[$x]['link'];
                $result .= '<li class="feed-item">';
                if ($dashboard) {
                    $result .= '<div class="draft-title"><a href="' . $link . '" title="' . $title . '" target="_blank">' . $title . '</a></div>';
                } else {
                    $result .= '<h3 class="feed-title"><a href="' . $link . '" title="' . $title . '" target="_blank">' . $title . '</a></h3>';
                }
                $description = $feed[$x]['desc'];
                $content = $feed[$x]['content'];
                // find the img
                $has_image = preg_match('/<img.+src=[\'"](?P<src>.+?)[\'"].*>/i', $content, $image);
                // no html tags
                $description = strip_tags(preg_replace('/(<(script|style)\b[^>]*>).*?(<\/\2>)/s', "$1$3", $description), '');
                // add img if it exists
                if ($has_image == 1 and !$dashboard) {
                    $description = '<img class="feed-item-image" src="' . $image['src'] . '" />' . $description;
                }

                if ($dashboard) {
                    $result .= '<p>' . $description . '</p>';
                } else {
                    $result .= '<div class="feed-description"><a href="' . $link . '" title="' . $title . '" target="_blank">' . $description . '</a>' . '</div>';
                }
                $result .= '</li>';
            }
            set_transient('iq_posting_news', $result, 12 * HOUR_IN_SECONDS);
        } else {
            $result = $cached;
        }
        return $result;
    }

    public function iq_admin_page()
    {
        global $my_admin_page;
        $screen = get_current_screen();

        /*
         * Check if current screen is My Admin Page
         * Don't add help tab if it's not
         */
        if ($screen->id != $my_admin_page)
            return;

        // Add my_help_tab if current screen is My Admin Page
        $screen->add_help_tab(array(
            'id' => 'my_help_tab',
            'title' => __('My Help Tab'),
            'content' => '<p>' . __('Descriptive content that will show in My Help Tab-body goes here.') . '</p>',
        ));
    }

    public function iq_posting_plugin_row($links, $file)
    {

        if (strpos($file, 'iq-posting-lite.php') !== false) {

            $new_links = array(
                'upgrade' => '<a href="https://iqposting.com/#buy" target="_blank"><strong>FREE Upgrade!</strong></a>',
            );

            $links = array_merge($links, $new_links);
        }

        return $links;
    }

    public function iq_admin_notice()
    {
        $notice = get_option('iq_posting_notice');
        if ($notice)
            echo '<div class="notice notice-info is-dismissible">
              <p>Upgrade iQ Posting with a <a target="_blank" href="https://iqposting.com">FREE License</a> and style your pop-up and the embed box, re-use what you already shared in other posts and more. <a href="?iq-posting-dismiss">Dismiss</a></p>
             </div>';

    }

}

$iQ_Posting_Lite = new iQ_Posting_Lite();