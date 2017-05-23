<?php
/*
Plugin Name:        Popups Extended
Plugin URI:         http://genero.fi
Description:        Extensions to the Popup plugin (SPU)
Version:            0.0.1
Author:             Genero
Author URI:         http://genero.fi/
License:            MIT License
License URI:        http://opensource.org/licenses/MIT
*/

if (!defined('ABSPATH')) {
  exit;
}

class PopupsExtended
{
    const VERSION = '0.0.1';

    protected $spu_settings = [];
    protected $info = [];

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init() {
        $this->info['wpml_lang'] = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : '';
        $this->spu_settings = apply_filters('spu/settings_page/opts', get_option('spu_settings'));

        $spu = SocialPopup::get_instance();
        // Remove core actions.
        remove_action('init', [$spu, 'register_spu_ajax'], 11);
        remove_action('wp_footer', [$spu, 'print_boxes']);
        // Add our own actions using our own template.
        add_action('init', [$this, 'register_ajax'], 11);
        if (empty($this->spu_settings['ajax_mode'])) {
            add_action('wp_footer', [$this, 'print_boxes']);
        }

        // Admin
        add_action('admin_head', [$this, 'admin_head']);
        add_action('admin_footer', [$this, 'admin_footer']);
        add_action('do_meta_boxes', [$this, 'hide_meta_boxes']);

        // TinyMCE
        add_filter('mce_external_plugins', [$this, 'add_tinymce_plugin']);
        add_filter('mce_buttons', [$this, 'add_tinymce_buttons']);

        // Assets.
        add_action('wp_enqueue_scripts', [$this, 'register_scripts'], 11);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts'], 11);

        // Modifications.
        add_filter('spu/metaboxes/default_options', [$this, 'metabox_default_options']);
        add_filter('spu/metaboxes/positions', [$this, 'metabox_positions']);
        add_filter('spu/metaboxes/trigger_options', [$this, 'metabox_trigger_options']);
        add_filter('spu/metaboxes/before_display_options', [$this, 'metabox_extra_before']);
        add_filter('spu/metaboxes/after_display_options', [$this, 'metabox_extra_after']);

        // Remove some notices.
        remove_action('admin_notices', [$spu, 'rate_plugin']);
    }

    public function add_tinymce_plugin($plugins) {
        $current_screen = get_current_screen();
        if ($current_screen->post_type == 'spucpt') {
            $plugins['popups_extended'] = plugins_url('js/tinymce.js', __FILE__);
        }
        return $plugins;
    }
    public function add_tinymce_buttons($buttons) {
        array_push($buttons, '|', 'popups_extended_convert_button', 'popups_extended_close_button');
        return $buttons;
    }

    public function register_scripts() {
        $js_url = plugins_url('js/popups-extended.js', __FILE__);
        $css_url = plugins_url('css/popups-extended.css', __FILE__);
        $vendor_url = plugins_url('js/vendor', __FILE__);
        wp_register_script('imagesloaded', $vendor_url . '/imagesloaded.js', ['jquery'], '4.1.2', true);
        wp_register_script('js-cookie', $vendor_url . '/js.cookie.js', [], '2.1.4', true);
        wp_register_script('bounceback', $vendor_url . '/bounceback.js', [], '1.0.0', true);
        wp_register_script('popups-extended/js', $js_url, ['jquery', 'imagesloaded', 'js-cookie', 'bounceback'], self::VERSION, true);
        wp_register_style('popups-extended/css', $css_url, [], self::VERSION);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('imagesloaded');
        wp_enqueue_script('js-cookie');
        wp_enqueue_script('bounceback');
        wp_enqueue_style('popups-extended/css');
        wp_enqueue_script('popups-extended/js');
        wp_localize_script('popups-extended/js', 'spuvar',
            [
                'is_admin' => current_user_can(apply_filters( 'spu/capabilities/testmode', 'administrator')),
                'disable_style' => isset($this->spu_settings['shortcodes_style'] ) ? esc_attr($this->spu_settings['shortcodes_style']) : '',
                'safe_mode' => isset($this->spu_settings['safe'] ) ? esc_attr($this->spu_settings['safe']) : '',
                'ajax_mode' => isset($this->spu_settings['ajax_mode'] ) ? esc_attr($this->spu_settings['ajax_mode']) :'',
                'ajax_url' => admin_url('admin-ajax.php'),
                'ajax_mode_url' => site_url('/?spu_action=spu_load&lang='. $this->info['wpml_lang']),
                'pid' => get_queried_object_id(),
                'is_front_page' => is_front_page(),
                'is_category' => is_category(),
                'site_url' => site_url(),
                'is_archive' => is_archive(),
                'is_search' => is_search(),
                'seconds_confirmation_close' => apply_filters('spu/spuvar/seconds_confirmation_close', 5),
            ]
        );
        // Dequeue core scripts
        wp_dequeue_script('spu-public');
        wp_dequeue_script('spu-public-debug');
        wp_dequeue_style('spu-public-css');
        wp_dequeue_script('spu-facebook');
        wp_dequeue_script('spu-google');
        wp_dequeue_script('spu-twitter');
    }

    public function register_ajax() {
        if (empty($_REQUEST['spu_action']) || $_REQUEST['spu_action'] != 'spu_load') {
            return;
        }
        define('DOING_AJAX', TRUE);
        // Remove theme output
        ob_clean();
        // Force 200.
        http_response_code(200);
        // Print the popup code.
        $this->print_boxes();
        die();
    }

    /**
     * Retrieve the original post id across languages.
     */
    function get_original_post_id($post_id) {
        global $sitepress;

        if (isset($sitepress)) {
            $trid = $sitepress->get_element_trid($post_id, 'post_spucpt');
            $translations = $sitepress->get_element_translations($trid, 'post_spucpt');
            if (!empty($translations)) {
                foreach ($translations as $lang => $translation) {
                    if ($translation->original == true) {
                        return $translation->element_id;
                        break;
                    }
                }
            }
        }
        // @todo polylang.
        return $post_id;
    }

    function print_boxes() {
        $spu_matches = SocialPopup::get_instance()->check_for_matches();
        // if we have matches continue
        if (!empty($spu_matches)) {
            foreach ($spu_matches as $spu_id) {
                $post = get_post($spu_id);
                if ($post->post_status != 'publish') {
                    continue;
                }
                // Variables accessable in template.
                $helper = new Spu_Helper;
                $options = $helper->get_box_options($spu_id);
                $content = apply_filters('spu/popup/content', $post->post_content, $post);
                // Normalize ID's to the original language.
                $spu_id = $this->get_original_post_id($spu_id);

                $templates = [
                    'popup--' . $spu_id . '.twig',
                    'popup-' . $spu_id . '.php',
                    'popup.twig',
                    'popup.php',
                ];
                $templates = apply_filters('spu_template_hierarchy', array_merge($templates, array_map(function ($template) {
                  return 'popups/' . $template;
                }, $templates)));

                $template = locate_template($templates);
                $template = apply_filters('spu_template', $template);
                // Template was found.
                if ($template = apply_filters('spu_template_include', $template)) {
                    // If it's not twig, simply include.
                    if (substr($template, -5) != '.twig') {
                        include $template;
                    } else {
                        // If the path is absolute, use the relative path from the theme.
                        $template = str_replace(TEMPLATEPATH, '', $template);

                        $context['spu_id'] = $spu_id;
                        $context['post'] = Timber\PostGetter::get_post($post);
                        $context['content'] = $content;
                        $context['options'] = $helper->get_box_options($spu_id);
                        Timber::render($template, $context);
                    }
                }
                // Fallback to template provided in this plugin.
                else {
                    include __DIR__ . '/views/popup.php';
                }

            }
        }
    }

    public function metabox_extra_after($opts) {
        $is_genero_analytics_active = is_plugin_active('wp-genero-analytics/genero-analytics.php');
        ?>
        <?php if ($is_genero_analytics_active): ?>
            <tr valign="top">
                <th><label for="spu_event_category"><?php _e('Analytics Event Category', 'popups-extended'); ?></label></th>
                <td>
                    <input id="spu_event_category" name="spu[event_category]" class="widefat" value="<?php echo $opts['event_category']; ?>">
                </td>
                <td colspan="2"></td>
            </tr>
            <tr valign="top">
                <th><label for="spu_event_label"><?php _e('Analytics Event Label', 'popups-extended'); ?></label></th>
                <td>
                    <input id="spu_event_label" name="spu[event_label]" class="widefat" value="<?php echo $opts['event_label']; ?>">
                </td>
                <td colspan="2"></td>
            </tr>
        <?php endif; ?>
        <?php
    }

    public function metabox_extra_before($opts) {
        // Media Uploader
        wp_enqueue_media();

        $types = apply_filters('popups-extended/types', []);
        $themes = apply_filters('popups-extended/themes', []);
        $sizes = apply_filters('popups-extended/sizes', [
            '' => __('Default', 'popups-extended'),
            'tiny' => __('Tiny', 'popups-extended'),
            'small' => __('Small', 'popups-extended'),
            'medium' => __('Medium', 'popups-extended'),
            'large' => __('Large', 'popups-extended'),
            'full' => __('Full', 'popups-extended'),
        ]);

        ?>
        <?php if (!empty($types)) : ?>
            <tr valign="top">
                <th><label for="spu_type"><?php _e('Type', 'popups-extended'); ?></label></th>
                <td>
                    <select id="spu_type" name="spu[type]" class="widefat">
                        <?php foreach ($types as $key => $name) : ?>
                            <option value="<?php echo $key; ?>" <?php selected($opts['type'], $key); ?>><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td colspan="2"></td>
            </tr>
        <?php endif; ?>
        <?php if (!empty($themes)) : ?>
            <tr valign="top">
                <th><label for="spu_theme"><?php _e('Theme', 'popups-extended'); ?></label></th>
                <td>
                    <select id="spu_theme" name="spu[theme]" class="widefat">
                        <?php foreach ($themes as $key => $name) : ?>
                            <option value="<?php echo $key; ?>" <?php selected($opts['theme'], $key); ?>><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td colspan="2"></td>
            </tr>
        <?php endif; ?>
        <?php if (!empty($sizes)) : ?>
            <tr valign="top">
                <th><label for="spu_size"><?php _e('Size', 'popups-extended'); ?></label></th>
                <td>
                    <select id="spu_size" name="spu[size]" class="widefat">
                        <?php foreach ($sizes as $key => $name) : ?>
                            <option value="<?php echo $key; ?>" <?php selected($opts['size'], $key); ?>><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td colspan="2"></td>
            </tr>
        <?php endif; ?>
        <tr valign="top">
            <th><label for="spu_overlay"><?php _e('Show overlay', 'popups-extended'); ?></label></th>
            <td colspan="3">
                <label><input type="radio" id="spu_overlay_1" name="spu[overlay]" value="1" <?php checked($opts['overlay'], 1); ?> /> <?php _e('Yes'); ?></label> &nbsp;
                <label><input type="radio" id="spu_overlay_0" name="spu[overlay]" value="0" <?php checked($opts['overlay'], 0); ?> /> <?php _e('No'); ?></label> &nbsp;
                <p class="help"><?php _e('Allows the modal to generate an overlay div, which will cover the view when modal opens.', 'popups-extended'); ?></p>
            </td>
        </tr>
        <tr valign="top">
            <th><label for="spu_close_on_click"><?php _e('Close on click', 'popups-extended'); ?></label></th>
            <td colspan="3">
                <label><input type="radio" id="spu_close_on_click_1" name="spu[close_on_click]" value="1" <?php checked($opts['close_on_click'], 1); ?> /> <?php _e('Yes'); ?></label> &nbsp;
                <label><input type="radio" id="spu_close_on_click_0" name="spu[close_on_click]" value="0" <?php checked($opts['close_on_click'], 0); ?> /> <?php _e('No'); ?></label> &nbsp;
                <p class="help"><?php _e('Allows a click on the body/overlay to close the modal.', 'popups-extended'); ?></p>
            </td>
        </tr>
        <tr valign="top">
            <th><label for="spu_background_image"><?php _e('Background image', 'popups-extended'); ?></label></th>
            <td colspan="3">
                <img class="spu_background_image" src="<?php echo $opts['background_image']; ?>" style="margin:0;padding:0;max-width:50px;float:left;display:<?php echo !empty($opts['background_image']) ? 'inline-block' : 'none'; ?>;margin-right:10px;" />
                <input type="hidden" class="spu_background_image_url" name="spu[background_image]" id="spu_background_image" value="<?php echo $opts['background_image']; ?>">
                <input type="button" value="<?php _e('Upload Image', 'popups-extended'); ?>" class="button spu_background_image_upload" id="spu_background_image_uploader"/>
                <input type="button" value="<?php _e('Remove', 'popups-extended'); ?>" class="button spu_background_image_remove" id="spu_background_image_remove"/>
            </td>
        </tr>
        <?php
    }

    public function metabox_trigger_options($opts) {
        $triggers = apply_filters('popups-extended/trigger_options', [
            'exit' => __('Exit intent', 'popups-extended'),
        ]);
        foreach ($triggers as $key => $name) {
            ?><option value="<?php echo $key; ?>" <?php selected($opts['trigger'], $key); ?>><?php echo $name; ?></option><?php
        }
    }

    public function metabox_positions($opts) {
        $positions = apply_filters('popups-extended/positions', [
            '' => __('Default', 'popups-extended'),
            'top' => __('Top', 'popups-extended'),
            'bottom' => __('Bottom', 'popups-extended'),
        ]);
        foreach ($positions as $key => $name) {
            ?><option value="<?php echo $key; ?>" <?php selected($opts['css']['position'], $key); ?>><?php echo $name; ?></option><?php
        }
    }

    public function metabox_default_options($defaults) {
        // Our custom options.
        $defaults['type'] = '';
        $defaults['theme'] = '';
        $defaults['position'] = '';
        $defaults['size'] = '';
        $defaults['overlay'] = 1;
        $defaults['close_on_click'] = 1;
        $defaults['background_image'] = '';
        $defaults['event_category'] = '';
        $defaults['event_label'] = '';
        // Changes to defaults.
        $defaults['css']['bgopacity'] = '1';
        return $defaults;
    }

    public function hide_meta_boxes() {
        remove_meta_box('spu-help', 'spucpt', 'normal');
        // Can't remove this as JavaScript depend on the elements existing,
        // hide with CSS instead.
        // remove_meta_box('spu-appearance', 'spucpt', 'normal');
        remove_meta_box('spu-premium', 'spucpt', 'normal');
        remove_meta_box('spu-support', 'spucpt', 'side');
        remove_meta_box('spu-donate', 'spucpt', 'side');
        remove_meta_box('spu-links', 'spucpt', 'side');
    }

    public static function isPostPage() {
        $current_screen = get_current_screen();
        if ($current_screen->post_type == 'spucpt' && $current_screen->base == 'post') {
            return true;
        }
        return false;
    }

    /**
     * Hide unnecessary options that we cannot completely unhook.
     */
    public function admin_head() {
        if (!self::isPostPage()) {
            return;
        }
        echo '<style>
            tr.powered_link,
            .spu-border-width, .spu-text-color, .spu-box-width,
            .spu-border-color, .spu-bg-opacity { display: none; }
        </style>';
    }

    /**
     * Media Uploader.
     */
    public function admin_footer() {
        if (!self::isPostPage()) {
            return;
        }
        ?><script type='text/javascript'>
            jQuery(document).ready(function() {
                function media_upload(button_class) {
                    var _custom_media = true,
                    _orig_send_attachment = wp.media.editor.send.attachment;
                    jQuery('body').on('click',button_class, function(e) {
                        var button_id = '#' + jQuery(this).attr('id');
                        var send_attachment_bkp = wp.media.editor.send.attachment;
                        var button = jQuery(button_id);
                        var id = button.attr('id').replace('_button', '');
                        _custom_media = true;
                        wp.media.editor.send.attachment = function(props, attachment) {
                            if (_custom_media) {
                                jQuery('.spu_background_image_url').val(attachment.url);
                                jQuery('.spu_background_image').attr('src', attachment.url).css('display','block');
                            } else {
                                return _orig_send_attachment.apply( button_id, [props, attachment] );
                            }
                        }
                        wp.media.editor.open(button);
                        return false;
                    });
                }
                media_upload('.spu_background_image_upload');
                jQuery('.spu_background_image_remove').on('click', function () {
                    jQuery('.spu_background_image_url').val('');
                    jQuery('.spu_background_image').css('display','none');
                });
            });
        </script><?php
    }

    public function activate() {
        if (!is_plugin_active('popups/popups.php') && current_user_can('activate_plugins')) {
            wp_die('Sorry, but this plugin requires the Popups plugin to be installed and active. <br><a href="' . admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a>');
        }
    }
}

register_activation_hook(__FILE__, [PopupsExtended::get_instance(), 'activate']);

add_action('plugins_loaded', [PopupsExtended::get_instance(), 'init'], 11);
