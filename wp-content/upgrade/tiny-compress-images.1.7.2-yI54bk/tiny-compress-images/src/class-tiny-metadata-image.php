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

class Tiny_Metadata_Image {
    public $filename;
    public $url;
    public $meta;

    /* Used more than once and not trivial, so we are memoizing these */
    private $_exists;
    private $_same_size;

    public function __construct($filename=null, $url=null) {
        $this->filename = $filename;
        $this->url = $url;
    }

    public function end_time() {
        if (!is_array($this->meta))
            return null;
        elseif (isset($this->meta['end']))
            return $this->meta['end'];
        elseif (isset($this->meta['timestamp']))
            return $this->meta['timestamp'];
        else
            return null;
    }

    public function add_request() {
        $this->meta = array('start' => time());
    }

    public function add_response($response) {
        if (is_array($this->meta)) {
            $this->meta = $response;
            $this->meta['end'] = time();
        }
    }

    public function add_exception($exception) {
        if (is_array($this->meta)) {
            $this->meta = array(
                'error'   => $exception->get_error(),
                'message' => $exception->getMessage(),
                'timestamp' => time()
            );
        }
    }

    public function has_been_compressed() {
        return is_array($this->meta) && isset($this->meta['output']);
    }

    public function never_compressed() {
        return !$this->has_been_compressed();
    }

    public function filesize() {
        return filesize($this->filename);
    }

    public function exists() {
        if (is_null($this->_exists)) {
            $this->_exists = $this->filename && file_exists($this->filename);
        }
        return $this->_exists;
    }

    private function same_size() {
        if (is_null($this->_same_size)) {
            $this->_same_size = $this->filesize() == $this->meta['output']['size'];
        }
        return $this->_same_size;
    }

    public function still_exists() {
        return $this->has_been_compressed() && $this->exists();
    }

    public function missing() {
        return $this->has_been_compressed() && !$this->exists();
    }

    public function compressed() {
        return $this->still_exists() && $this->same_size();
    }

    public function modified() {
        return $this->still_exists() && !$this->same_size();
    }

    public function uncompressed() {
        return $this->exists() && !(isset($this->meta['output']) && $this->same_size());
    }

    public function in_progress() {
        return is_array($this->meta) && isset($this->meta['start']) && !isset($this->meta['output']);
    }

    public function resized() {
        return is_array($this->meta) && isset($this->meta['output']) && isset($this->meta['output']['resized'])
            && $this->meta['output']['resized'];
    }
}
