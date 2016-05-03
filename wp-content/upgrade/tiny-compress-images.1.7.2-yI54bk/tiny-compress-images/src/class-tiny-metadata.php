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

class Tiny_Metadata {
    const META_KEY = 'tiny_compress_images';
    const ORIGINAL = 0;

    private $id;
    private $name;
    private $images = array();

    public static function is_original($size) {
        return $size === self::ORIGINAL;
    }

    public function __construct($id, $wp_metadata=null) {
        $this->id = $id;

        if (is_null($wp_metadata)) {
            $wp_metadata = wp_get_attachment_metadata($id);
        }
        $this->parse_wp_metadata($wp_metadata);

        $values = get_post_meta($this->id, self::META_KEY, true);
        if (!is_array($values)) {
            $values = array();
        }
        foreach ($values as $size => $meta) {
            if (!isset($this->images[$size])) {
                $this->images[$size] = new Tiny_Metadata_Image();
            }
            $this->images[$size]->meta = $meta;
        }
    }

    private function parse_wp_metadata($wp_metadata) {
        if (!is_array($wp_metadata)) {
            return;
        }

        $path_info = pathinfo($wp_metadata['file']);
        $upload_dir = wp_upload_dir();
        $path_prefix = $upload_dir['basedir'] . '/';
        $url_prefix = $upload_dir['baseurl'] . '/';
        if (isset($path_info['dirname'])) {
            $path_prefix .= $path_info['dirname'] .'/';
            $url_prefix .= $path_info['dirname'] .'/';
        }

        $this->name = $path_info['basename'];

        $this->images[self::ORIGINAL] = new Tiny_Metadata_Image(
            "$path_prefix${path_info['basename']}",
            "$url_prefix${path_info['basename']}");

        $unique_sizes = array();
        if (isset($wp_metadata['sizes']) && is_array($wp_metadata['sizes'])) {
            foreach ($wp_metadata['sizes'] as $size => $info) {
                $filename = $info['file'];

                if (!isset($unique_sizes[$filename])) {
                    $this->images[$size] = new Tiny_Metadata_Image(
                        "$path_prefix$filename", "$url_prefix$filename");
                }
            }
        }
    }

    public function get_image($size=self::ORIGINAL, $create=false) {
        if (isset($this->images[$size]))
            return $this->images[$size];
        elseif ($create)
            return new Tiny_Metadata_Image();
        else
            return null;
    }

    public function update_wp_metadata($wp_metadata) {
        $original = $this->get_image();
        if (is_null($original) || !is_array($original->meta)) {
            return $wp_metadata;
        }

        $m = $original->meta;
        if (isset($m['output']) && isset($m['output']['width']) && isset($m['output']['height'])) {
            $wp_metadata['width'] = $m['output']['width'];
            $wp_metadata['height'] = $m['output']['height'];
        }
        return $wp_metadata;
    }

    public function update() {
        $values = array();
        foreach ($this->images as $size => $image) {
            if (is_array($image->meta)) {
                $values[$size] = $image->meta;
            }
        }
        update_post_meta($this->id, self::META_KEY, $values);
    }

    public function get_id() {
        return $this->id;
    }

    public function get_name() {
        return $this->name;
    }

    public function can_be_compressed() {
        return in_array($this->get_mime_type(), array("image/jpeg", "image/png"));
    }

    public function get_mime_type() {
        return get_post_mime_type($this->id);
    }

    public function get_images() {
        $original = isset($this->images[self::ORIGINAL])
            ? array(self::ORIGINAL => $this->images[self::ORIGINAL])
            : array();
        $compressed = array();
        $uncompressed = array();
        foreach ($this->images as $size => $image) {
            if (self::is_original($size)) continue;
            if ($image->has_been_compressed()) {
                $compressed[$size] = $image;
            } else {
                $uncompressed[$size] = $image;
            }
        }
        ksort($compressed);
        ksort($uncompressed);
        return $original + $compressed + $uncompressed;
    }

    public function filter_images($method, $sizes=null) {
        $selection = array();
        if (is_null($sizes)) {
            $sizes = array_keys($this->images);
        }
        foreach ($sizes as $size) {
            if (!isset($this->images[$size])) continue;
            $image = $this->images[$size];
            if ($image->$method()) {
                $selection[$size] = $image;
            }
        }
        return $selection;
    }

    public function get_count($methods, $sizes=null) {
        $stats = array_fill_keys($methods, 0);
        if (is_null($sizes)) {
            $sizes = array_keys($this->images);
        }
        foreach ($sizes as $size) {
            if (!isset($this->images[$size])) continue;
            foreach ($methods as $method) {
                if ($this->images[$size]->$method()) {
                    $stats[$method]++;
                }
            }
        }
        return $stats;
    }

    public function get_latest_error() {
        $last_time = null;
        $message = null;
        foreach ($this->images as $size => $image) {
            if (!is_array($image->meta)) continue;
            $m = $image->meta;
            if (isset($m['error']) && isset($m['message']) && ($last_time === null || $last_time < $m['timestamp'])) {
                $last_time = $m['timestamp'];
                $message = $m['message'];
            }
        }
        return $message;
    }

    public function get_savings() {
        $result = array(
            'input' => 0,
            'output' => 0,
            'count' => 0
        );
        foreach ($this->images as $size => $image) {
            if (!is_array($image->meta)) continue;
            $m = $image->meta;
            if (isset($m['input']) && isset($m['output'])) {
                $result['count']++;
                $result['input'] += $m['input']['size'];
                $result['output'] += $m['output']['size'];
            }
        }
        return $result;
    }
}
