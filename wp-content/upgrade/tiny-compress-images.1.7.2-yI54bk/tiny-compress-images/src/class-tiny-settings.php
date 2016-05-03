<?php
/*
* Tiny Compress Images - WordPress plugin.
* Copyright (C) 2015-2016 Voormedia B.V.
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the Free
* Software Foundation; either version 2 of the License, or (at your option)
* any later version.
*
* This program is distributed in the hope that it will be useful, but WITHOUT
* ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
* FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
* more details.
*
* You should have received a copy of the GNU General Public License along
* with this program; if not, write to the Free Software Foundation, Inc., 51
* Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

class Tiny_Settings extends Tiny_WP_Base {
    const DUMMY_SIZE = '_tiny_dummy';

    private $sizes;
    private $tinify_sizes;
    private $compressor;
    private $notices;

    public function __construct() {
        parent::__construct();
        $this->notices = new Tiny_Notices();
    }

    private function init_compressor() {
        $this->compressor = Tiny_Compress::get_compressor($this->get_api_key(), $this->get_method('after_compress_callback'));
    }

    public function xmlrpc_init() {
        try {
            $this->init_compressor();
        } catch (Tiny_Exception $e) {
        }
    }

    public function admin_init() {
        if (current_user_can('manage_options') && !$this->get_api_key()) {
            $link = sprintf('<a href="options-media.php#%s">%s</a>', self::NAME,
                esc_html__('Please fill in an API key to start compressing images', 'tiny-compress-images'));
            $this->notices->show('setting', $link, 'error', false);
        }
         try {
            $this->init_compressor();
        } catch (Tiny_Exception $e) {
            $this->notices->show('compressor_exception', esc_html__($e->getMessage(), 'tiny-compress-images'), 'error', false);
        }

        $section = self::get_prefixed_name('settings');
        add_settings_section($section, __('PNG and JPEG optimization', 'tiny-compress-images'), $this->get_method('render_section'), 'media');

        $field = self::get_prefixed_name('api_key');
        register_setting('media', $field);
        add_settings_field($field, __('TinyPNG API key', 'tiny-compress-images'), $this->get_method('render_api_key'), 'media', $section, array('label_for' => $field));

        $field = self::get_prefixed_name('sizes');
        register_setting('media', $field);
        add_settings_field($field, __('File compression', 'tiny-compress-images'), $this->get_method('render_sizes'), 'media', $section);

        $field = self::get_prefixed_name('resize_original');
        register_setting('media', $field);
        add_settings_field($field, __('Original image', 'tiny-compress-images'), $this->get_method('render_resize'), 'media', $section);

        $field = self::get_prefixed_name('status');
        register_setting('media', $field);
        add_settings_field($field, __('Connection status', 'tiny-compress-images'), $this->get_method('render_pending_status'), 'media', $section);

        $field = self::get_prefixed_name('savings');
        register_setting('media', $field);
        add_settings_field($field, __('Savings', 'tiny-compress-images'), $this->get_method('render_pending_savings'), 'media', $section);

        $field = self::get_prefixed_name('preserve_data');
        register_setting('media', $field);

        add_action('wp_ajax_tiny_image_sizes_notice', $this->get_method('image_sizes_notice'));
        add_action('wp_ajax_tiny_compress_status', $this->get_method('connection_status'));
        add_action('wp_ajax_tiny_compress_savings', $this->get_method('total_savings_status'));
    }

    public function image_sizes_notice() {
        $this->render_image_sizes_notice($_GET["image_sizes_selected"], isset($_GET["resize_original"]));
        exit();
    }

    public function connection_status() {
        $this->render_status();
        exit();
    }

    public function total_savings_status() {
        $this->render_total_savings();
        exit();
    }

    public function get_compressor() {
        return $this->compressor;
    }

    public function set_compressor($compressor) {
        $this->compressor = $compressor;
    }

    public function get_status() {
        return intval(get_option(self::get_prefixed_name('status')));
    }

    protected function get_api_key() {
        if (defined('TINY_API_KEY')) {
            return TINY_API_KEY;
        } else {
            return get_option(self::get_prefixed_name('api_key'));
        }
    }

    protected static function get_intermediate_size($size) {
        # Inspired by http://codex.wordpress.org/Function_Reference/get_intermediate_image_sizes
        global $_wp_additional_image_sizes;

        $width  = get_option($size . '_size_w');
        $height = get_option($size . '_size_h');
        if ($width && $height) {
            return array($width, $height);
        }

        if (isset($_wp_additional_image_sizes[$size])) {
            $sizes = $_wp_additional_image_sizes[$size];
            return array(
                isset($sizes['width']) ? $sizes['width'] : null,
                isset($sizes['height']) ? $sizes['height'] : null,
            );
        }
        return array(null, null);
    }

    public function get_sizes() {
        if (is_array($this->sizes)) {
            return $this->sizes;
        }

        $setting = get_option(self::get_prefixed_name('sizes'));

        $size = Tiny_Metadata::ORIGINAL;
        $this->sizes = array($size => array(
            'width' => null, 'height' => null,
            'tinify' => !is_array($setting) || (isset($setting[$size]) && $setting[$size] === 'on'),
        ));

        foreach (get_intermediate_image_sizes() as $size) {
            if ($size === self::DUMMY_SIZE) {
                continue;
            }
            list($width, $height) = self::get_intermediate_size($size);
            if ($width || $height) {
                $this->sizes[$size] = array(
                    'width' => $width, 'height' => $height,
                    'tinify' => !is_array($setting) || (isset($setting[$size]) && $setting[$size] === 'on'),
                );
            }
        }
        return $this->sizes;
    }

    public function get_active_tinify_sizes() {
        if (is_array($this->tinify_sizes)) {
            return $this->tinify_sizes;
        }

        $this->tinify_sizes = array();
        foreach ($this->get_sizes() as $size => $values) {
            if ($values['tinify']) {
                $this->tinify_sizes[] = $size;
            }
        }
        return $this->tinify_sizes;
    }

    public function get_resize_enabled() {
        $setting = get_option(self::get_prefixed_name('resize_original'));
        return isset($setting['enabled']) && $setting['enabled'] === 'on';
    }

    public function get_preserve_enabled($name) {
        $setting = get_option(self::get_prefixed_name('preserve_data'));
        return isset($setting[$name]) && $setting[$name] === 'on';
    }

    public function get_preserve_options() {
        $settings = get_option(self::get_prefixed_name('preserve_data'));
        $options = array();
        if ($settings) {
            $keys = array_keys($settings);
            foreach ($keys as &$key) {
                if ($settings[$key] === "on") {
                    array_push($options, $key);
                }
            }
        }
        return $options;
    }

    public function get_resize_options() {
        $setting = get_option(self::get_prefixed_name('resize_original'));
        if (!$this->get_resize_enabled()) {
            return false;
        }

        $width = intval($setting['width']);
        $height = intval($setting['height']);
        $method = $width > 0 && $height > 0 ? 'fit' : 'scale';

        $options['method'] = $method;
        if ($width > 0) {
            $options['width'] = $width;
        }
        if ($height > 0) {
            $options['height'] = $height;
        }

        return sizeof($options) >= 2 ? $options : false;
    }

    public function render_section() {
        echo '<span id="' . self::NAME . '"></span>';
    }

    public function render_api_key() {
        $field = self::get_prefixed_name('api_key');
        $key = $this->get_api_key();

        if (defined('TINY_API_KEY')) {
            echo '<p>' . sprintf(__('The API key has been configured in %s', 'tiny-compress-images'), 'wp-config.php') . '.</p>';
        } else {
            echo '<input type="text" id="' . $field . '" name="' . $field . '" value="' . htmlspecialchars($key) . '" size="40" />';
        }
        echo '<p>';
        $link = '<a href="https://tinypng.com/developers" target="_blank">' . esc_html__('TinyPNG Developer section', 'tiny-compress-images') . '</a>';
        if (empty($key)) {
            printf(esc_html__('Visit %s to get an API key.', 'tiny-compress-images'), $link);
        } else {
            printf(esc_html__('Visit %s to view or upgrade your account.', 'tiny-compress-images'), $link);
        }
        echo '</p>';
    }

    public function render_sizes() {
        echo '<p>';
        esc_html_e('Choose sizes to compress. Remember each selected size counts as a compression.', 'tiny-compress-images');
        echo '</p>';
        echo '<input type="hidden" name="' . self::get_prefixed_name('sizes[' . self::DUMMY_SIZE . ']') . '" value="on"/>';
        foreach ($this->get_sizes() as $size => $option) {
            $this->render_size_checkbox($size, $option);
        }
        echo '<br>';
        echo '<div id="tiny-image-sizes-notice">';
        $this->render_image_sizes_notice(count(self::get_active_tinify_sizes()), self::get_resize_enabled());
        echo '</div>';
    }

    private function render_size_checkbox($size, $option) {
        $id = self::get_prefixed_name("sizes_$size");
        $name = self::get_prefixed_name("sizes[$size]");
        $checked = ( $option['tinify'] ? ' checked="checked"' : '' );
        if (Tiny_Metadata::is_original($size)) {
            $label = esc_html__('original', 'tiny-compress-images') . ' (' . esc_html__('overwritten by compressed image', 'tiny-compress-images') . ')';
        } else {
            $label = $size . ' - ' . $option['width'] . 'x' . $option['height'];
        }
        echo '<p>';
        echo '<input type="checkbox" id="' . $id . '" name="' . $name . '" value="on" ' . $checked . '/>';
        echo '<label for="' . $id . '">' . $label . '</label>';
        echo '</p>';
    }

    public function render_image_sizes_notice($active_image_sizes_count, $resize_original_enabled) {
        echo '<p>';
        if ($resize_original_enabled) {
            $active_image_sizes_count++;
        }
        if ($active_image_sizes_count < 1) {
            esc_html_e('With these settings no images will be compressed.', 'tiny-compress-images');
        } else {
            $free_images_per_month = floor( Tiny_Config::MONTHLY_FREE_COMPRESSIONS / $active_image_sizes_count );
            printf(__('With these settings you can compress <strong> at least %s images </strong> for free each month.', 'tiny-compress-images'), $free_images_per_month);
        }
        echo '</p>';
    }

    public function render_total_savings() {
        global $wpdb;

        $total_savings = 0;
        $result = $wpdb->get_results("SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND (post_mime_type = 'image/jpeg' OR post_mime_type = 'image/png') ORDER BY ID DESC", ARRAY_A);
        for ($i = 0; $i < sizeof($result); $i++) {
            $tiny_metadata = new Tiny_Metadata($result[$i]["ID"]);
            $savings = $tiny_metadata->get_savings();
            $total_savings += ($savings['input'] - $savings['output']);
        }

        echo '<p>';
        if ($total_savings > 0) {
            printf(esc_html__('You have saved a total of %s on images!', 'tiny-compress-images'), '<strong>' . size_format($total_savings) . '</strong>' );
        } else {
            $link = '<a href="upload.php?page=tiny-bulk-compress">' . esc_html__('Compress All Images', 'tiny-compress-images') . '</a>';
            printf(esc_html__('No images compressed yet. Use %s to compress existing images.', 'tiny-compress-images'), $link);
        }
        echo '</p>';
    }

    public function render_resize() {
        echo '<p class="tiny-resize-unavailable" style="display: none">';
        esc_html_e('Enable compression of the original image size for more options.', 'tiny-compress-images');
        echo '</p>';

        $id = self::get_prefixed_name("resize_original_enabled");
        $name = self::get_prefixed_name("resize_original[enabled]");
        $checked = ( $this->get_resize_enabled() ? ' checked="checked"' : '' );
        $label = esc_html__('Resize and compress the original image', 'tiny-compress-images');

        echo '<p class="tiny-resize-available">';
        echo '<input  type="checkbox" id="' . $id . '" name="' . $name . '" value="on" '. $checked . '/>';
        echo '<label for="' . $id . '">' . $label . '</label>';
        echo '<br>';
        echo '</p>';

        echo '<p class="tiny-resize-available tiny-resize-resolution">';
        printf("%s: ", esc_html__('Max Width', 'tiny-compress-images'));
        $this->render_resize_input('width');
        printf("%s: ", esc_html__('Max Height', 'tiny-compress-images'));
        $this->render_resize_input('height');
        echo '</p>';

        echo '<p class="tiny-resize-available tiny-resize-resolution">';
        esc_html_e('Resizing takes 1 additional compression for each image that is larger.', 'tiny-compress-images');
        echo '</p>';

        echo '<br>';
        $this->render_preserve_input("creation", 'Preserve creation date and time in the original image (JPEG only)') .'<br>';
        $this->render_preserve_input("copyright", 'Preserve copyright information in the original image') .'<br>';
        $this->render_preserve_input("location", 'Preserve GPS location in the original image (JPEG only)') .'<br>';
    }

    public function render_preserve_input($name, $description) {
        echo '<p class="tiny-preserve">';
        $id = sprintf(self::get_prefixed_name('preserve_data_%s'), $name);
        $field = sprintf(self::get_prefixed_name('preserve_data[%s]'), $name);
        $checked = ( $this->get_preserve_enabled($name) ? ' checked="checked"' : '' );
        $label = esc_html__($description, 'tiny-compress-images');
        echo '<input type="checkbox" id="' . $id . '" name="' . $field . '" value="on" ' . $checked . '/>';
        echo '<label for="' . $id . '">' . $label . '</label>';
        echo '<br>';
        echo '</p>';
    }

    public function render_resize_input($name) {
        $id = sprintf(self::get_prefixed_name('resize_original_%s'), $name);
        $field = sprintf(self::get_prefixed_name('resize_original[%s]'), $name);
        $settings = get_option(self::get_prefixed_name('resize_original'));
        $value = isset($settings[$name]) ? $settings[$name] : "2048";
        echo '<input type="number" id="'. $id .'" name="' . $field . '" value="' . $value . '" size="5" />';
    }

    public function get_compression_count() {
        $field = self::get_prefixed_name('status');
        return get_option($field);
    }

    public function after_compress_callback($details, $headers) {
        if (isset($headers['compression-count'])) {
            $count = $headers['compression-count'];
            $field = self::get_prefixed_name('status');
            update_option($field, $count);

            if (isset($details['error']) && $details['error'] == 'TooManyRequests') {
                $link = '<a href="https://tinypng.com/developers" target="_blank">' . esc_html__('TinyPNG API account', 'tiny-compress-images') . '</a>';
                $this->notices->add('limit-reached',
                    sprintf(esc_html__('You have reached your limit of %s compressions this month.', 'tiny-compress-images'), $count) .
                    sprintf(esc_html__('Upgrade your %s if you like to compress more images.', 'tiny-compress-images'), $link));
            } else {
                $this->notices->remove('limit-reached');
            }
        }
    }

    public function render_status() {
        $details = null;
        try {
            $status = $this->compressor->get_status($details);
        } catch (Tiny_Exception $e) {
            $status = false;
            $details = array('message' => $e->getMessage());
        }

        echo '<p>';
        if ($status) {
            echo '<img src="images/yes.png"> ';
            echo esc_html__('API connection successful', 'tiny-compress-images');
        } else {
            echo '<img src="images/no.png"> ';
            if ($status === false) {
                echo esc_html__('API connection unsuccessful', 'tiny-compress-images') . '<br>';
                if (isset($details['message'])) {
                    echo esc_html__('Error', 'tiny-compress-images') . ': ' . esc_html__($details['message'], 'tiny-compress-images');
                }
            } else {
                esc_html_e('API status could not be checked, enable cURL for more information', 'tiny-compress-images');
            }
        }
        echo '</p>';

        if ($status) {
            $compressions = self::get_compression_count();
            echo '<p>';
            // It is not possible to check if a subscription is free or flexible.
            if ( $compressions == Tiny_Config::MONTHLY_FREE_COMPRESSIONS ) {
                $link = '<a href="https://tinypng.com/developers" target="_blank">' . esc_html__('TinyPNG API account', 'tiny-compress-images') . '</a>';
                printf(esc_html__('You have reached your limit of %s compressions this month.', 'tiny-compress-images'), $compressions);
                echo '<br>';
                printf(esc_html__('If you need to compress more images you can change your %s.', 'tiny-compress-images'), $link);
            } else {
               printf(esc_html__('You have made %s compressions this month.', 'tiny-compress-images'), self::get_compression_count());
            }
            echo '</p>';
        }
    }

    public function render_pending_status() {
        echo '<div id="tiny-compress-status"><div class="spinner"></div></div>';
    }

    public function render_pending_savings() {
        echo '<div id="tiny-compress-savings"><div class="spinner"></div></div>';
    }
}
