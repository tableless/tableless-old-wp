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

class Tiny_Plugin extends Tiny_WP_Base {
    const MEDIA_COLUMN = self::NAME;
    const DATETIME_FORMAT = 'Y-m-d G:i:s';

    private $settings;
    private $twig;

    public static function jpeg_quality() {
        return 95;
    }

    public function __construct() {
        parent::__construct();

        $this->settings = new Tiny_Settings();
    }

    public function set_compressor($compressor) {
        $this->settings->set_compressor($compressor);
    }

    public function init() {
        add_filter('jpeg_quality', $this->get_static_method('jpeg_quality'));
        add_filter('wp_editor_set_quality', $this->get_static_method('jpeg_quality'));
        add_filter('wp_generate_attachment_metadata', $this->get_method('compress_attachment'), 10, 2);
        load_plugin_textdomain(self::NAME, false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function admin_init() {
        add_filter('manage_media_columns', $this->get_method('add_media_columns'));
        add_action('manage_media_custom_column', $this->get_method('render_media_column'), 10, 2);
        add_action('attachment_submitbox_misc_actions', $this->get_method('show_media_info'));
        add_action('wp_ajax_tiny_compress_image', $this->get_method('compress_image'));
        add_action('admin_action_tiny_bulk_compress', $this->get_method('bulk_compress'));
        add_action('admin_enqueue_scripts', $this->get_method('enqueue_scripts'));
        $plugin = plugin_basename(dirname(dirname(__FILE__)) . '/tiny-compress-images.php');
        add_filter("plugin_action_links_$plugin", $this->get_method('add_plugin_links'));
        add_thickbox();
    }

    public function admin_menu() {
        add_media_page(
            __('Compress JPEG & PNG Images', 'tiny-compress-images'), __('Compress All Images', 'tiny-compress-images'),
            'upload_files', 'tiny-bulk-compress', $this->get_method('bulk_compress_page')
        );
    }

    public function add_plugin_links($current_links) {
        $additional[] = sprintf('<a href="options-media.php#%s">%s</a>', self::NAME,
            esc_html__('Settings', 'tiny-compress-images'));
        return array_merge($additional, $current_links);
    }

    public function enqueue_scripts($hook) {
        wp_enqueue_style(self::NAME .'_admin', plugins_url('/styles/admin.css', __FILE__),
            array(), self::plugin_version());

        $handle = self::NAME .'_admin';
        wp_register_script($handle, plugins_url('/scripts/admin.js', __FILE__),
            array(), self::plugin_version(), true);

        // WordPress < 3.3 does not handle multidimensional arrays
        wp_localize_script($handle, 'tinyCompress', array(
            'nonce' => wp_create_nonce('tiny-compress'),
            'wpVersion' => self::wp_version(),
            'pluginVersion' => self::plugin_version(),
            'L10nAllDone' => __('All images are processed', 'tiny-compress-images'),
            'L10nBulkAction' => __('Compress All Images', 'tiny-compress-images'),
            'L10nCompressing' => __('Compressing', 'tiny-compress-images'),
            'L10nCompression' => __('compression', 'tiny-compress-images'),
            'L10nCompressions' => __('compressions', 'tiny-compress-images'),
            'L10nError' => __('Error', 'tiny-compress-images'),
            'L10nInternalError' => __('Internal error', 'tiny-compress-images'),
            'L10nOutOf' => __('out of', 'tiny-compress-images'),
            'L10nWaiting' => __('Waiting', 'tiny-compress-images'),
        ));
        wp_enqueue_script($handle);
    }

    private function compress($metadata, $attachment_id) {
        $mime_type = get_post_mime_type($attachment_id);
        $tiny_metadata = new Tiny_Metadata($attachment_id, $metadata);

        if ($this->settings->get_compressor() === null || !$tiny_metadata->can_be_compressed()) {
            return array($tiny_metadata, null);
        }

        $success = 0;
        $failed = 0;

        $compressor = $this->settings->get_compressor();
        $active_tinify_sizes = $this->settings->get_active_tinify_sizes();
        $uncompressed_images = $tiny_metadata->filter_images('uncompressed', $active_tinify_sizes);

        foreach ($uncompressed_images as $size => $image) {
            try {
                $image->add_request();
                $tiny_metadata->update();

                $resize = Tiny_Metadata::is_original($size) ? $this->settings->get_resize_options() : false;
                $preserve = count($this->settings->get_preserve_options()) > 0 ? $this->settings->get_preserve_options() : false;
                $response = $compressor->compress_file($image->filename, $resize, $preserve);

                $image->add_response($response);
                $tiny_metadata->update();
                $success++;
            } catch (Tiny_Exception $e) {
                $image->add_exception($e);
                $tiny_metadata->update();
                $failed++;
            }
        }

        return array($tiny_metadata, array('success' => $success, 'failed' => $failed));
    }

    public function compress_attachment($metadata, $attachment_id) {
        if (!empty($metadata)) {
            list($tiny_metadata, $result) = $this->compress($metadata, $attachment_id);
            return $tiny_metadata->update_wp_metadata($metadata);
        } else {
            return $metadata;
        }
    }

    public function compress_image() {
        if (!$this->check_ajax_referer()) {
            exit();
        }
        $json = !empty($_POST['json']) && $_POST['json'];
        if (!current_user_can('upload_files')) {
            $message = __("You don't have permission to work with uploaded files.", 'tiny-compress-images');
            echo $json ? json_encode(array('error' => $message)) : $message;
            exit();
        }
        if (empty($_POST['id'])) {
            $message = __('Not a valid media file.', 'tiny-compress-images');
            echo $json ? json_encode(array('error' => $message)) : $message;
            exit();
        }
        $id = intval($_POST['id']);
        $metadata = wp_get_attachment_metadata($id);
        if (!is_array($metadata)) {
            $message = __('Could not find metadata of media file.', 'tiny-compress-images');
            echo $json ? json_encode(array('error' => $message)) : $message;
            exit;
        }

        list($tiny_metadata, $result) = $this->compress($metadata, $id);
        wp_update_attachment_metadata($id, $tiny_metadata->update_wp_metadata($metadata));

        if ($json) {
            $result['message'] = $tiny_metadata->get_latest_error();
            $result['status'] = $this->settings->get_status();
            $result['thumbnail'] = $tiny_metadata->get_image('thumbnail', true)->url;
            echo json_encode($result);
        } else {
            echo $this->render_compress_details($tiny_metadata);
        }

        exit();
    }

    public function bulk_compress() {
        check_admin_referer('bulk-media');

        if (empty($_REQUEST['media']) || !is_array( $_REQUEST['media'])) {
            return;
        }

        $ids = implode('-', array_map('intval', $_REQUEST['media']));
        wp_redirect(add_query_arg(
            '_wpnonce',
            wp_create_nonce('tiny-bulk-compress'),
            admin_url("upload.php?page=tiny-bulk-compress&ids=$ids")
        ));
        exit();
    }

    public function add_media_columns($columns) {
        $columns[self::MEDIA_COLUMN] = __('Compression', 'tiny-compress-images');
        return $columns;
    }

    public function render_media_column($column, $id) {
        if ($column === self::MEDIA_COLUMN) {
            echo '<div class="tiny-ajax-container">';
            $this->render_compress_details(new Tiny_Metadata($id));
            echo '</div>';
        }
    }

    public function show_media_info() {
        global $post;
        echo '<div class="misc-pub-section tiny-compress-images">';
        echo '<h4>' . __('Compress JPEG & PNG Images', 'tiny-compress-images') . '</h4>';
        echo '<div class="tiny-ajax-container">';
        $this->render_compress_details(new Tiny_Metadata($post->ID));
        echo '</div></div>';
    }

    private function render_compress_details($tiny_metadata) {
        $available_sizes = array_keys($this->settings->get_sizes());
        $active_tinify_sizes = $this->settings->get_active_tinify_sizes();
        $in_progress = count($tiny_metadata->filter_images('in_progress'));
        if ($in_progress > 0) {
            include(dirname(__FILE__) . '/views/compress-details-processing.php');
        } else {
            include(dirname(__FILE__) . '/views/compress-details.php');
        }
    }

    public function bulk_compress_page() {
        global $wpdb;

        echo '<div class="wrap" id="tiny-bulk-compress">';
        echo '<h2>' . __('Compress JPEG & PNG Images', 'tiny-compress-images') . '</h2>';
        if (empty($_POST['tiny-bulk-compress']) && empty($_REQUEST['ids'])) {
            $result = $wpdb->get_results("SELECT COUNT(*) AS `count` FROM $wpdb->posts WHERE post_type = 'attachment' AND (post_mime_type = 'image/jpeg' OR post_mime_type = 'image/png') ORDER BY ID DESC", ARRAY_A);
            $image_count = $result[0]['count'];
            $sizes_count = count($this->settings->get_active_tinify_sizes());

            echo '<p>';
            esc_html_e('Use this tool to compress all images in your media library. Only images that have not been compressed will be compressed.', 'tiny-compress-images');
            echo '</p>';
            echo '<p>';
            echo sprintf(esc_html__('We have found %d images in your media library and for each image a maximum of %d sizes will be compressed.', 'tiny-compress-images'),
             $image_count, $sizes_count) . ' ';
            echo sprintf(esc_html__('This results in %d compressions at most.', 'tiny-compress-images'), $image_count * $sizes_count);
            echo '</p>';
            echo '<p>';
            esc_html_e('To begin, just press the button below.', 'tiny-compress-images');
            echo '</p>';

            echo '<form method="POST" action="?page=tiny-bulk-compress">';
            echo '<input type="hidden" name="_wpnonce" value="' . wp_create_nonce('tiny-bulk-compress') . '">';
            echo '<input type="hidden" name="tiny-bulk-compress" value="1">';
            echo '<p>';
            echo '<button class="button button-primary button-large" type="submit">';
            esc_html_e('Compress All Images', 'tiny-compress-images');
            echo '</button>';
            echo '</p>';
            echo '</form>';
        } else {
            check_admin_referer('tiny-bulk-compress');

            if (!empty($_REQUEST['ids'])) {
                $ids = implode(',', array_map('intval', explode('-', $_REQUEST['ids'])));
                $cond = "AND ID IN($ids)";
            } else {
                $cond = "";
            }

            // Get all ids and names of the images and not the whole objects which will only fill memory
            $items = $wpdb->get_results("SELECT ID, post_title FROM $wpdb->posts WHERE post_type = 'attachment' AND (post_mime_type = 'image/jpeg' OR post_mime_type = 'image/png') $cond ORDER BY ID DESC", ARRAY_A);

            echo '<p>';
            esc_html_e('Please be patient while the images are being optimized, it can take a while if you have many images. Do not navigate away from this page because it will stop the process.', 'tiny-compress-images');
            echo '</p><p>';
            esc_html_e('You will be notified via this page when the processing is done.', 'tiny-compress-images');
            echo "</p>";

            echo '<div id="tiny-status"><p>';
            esc_html_e('Compressions this month', 'tiny-compress-images');
            printf(' <span>%d</span></p></div>', $this->settings->get_status());

            echo '<div id="tiny-progress"><p>';

            /* translators: as in 'processing image X out of Y' */
            esc_html_e('Processing', 'tiny-compress-images');
            echo ' <span>0</span> ' . esc_html__('out of', 'tiny-compress-images') . sprintf(' %d </p></div>', count($items));
            echo '<div id="media-items">';
            echo '</div>';

            echo '<script type="text/javascript">jQuery(function() { tinyBulkCompress('. json_encode($items) . ')})</script>';
        }

        echo '</div>';
    }
}
