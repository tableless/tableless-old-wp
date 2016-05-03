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

class Tiny_Compress_Curl extends Tiny_Compress {
    private static $curl_version;

    protected static function curl_version() {
        if (is_null(self::$curl_version)) {
            self::$curl_version = curl_version();
        }
        return self::$curl_version['version'];
    }

    protected function shrink_options($input) {
        $options = array(
              CURLOPT_URL => Tiny_Config::URL,
              CURLOPT_USERPWD => 'api:' . $this->api_key,
              CURLOPT_POSTFIELDS => $input,
              CURLOPT_BINARYTRANSFER => true,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_HEADER => true,
              CURLOPT_CAINFO => self::get_ca_file(),
              CURLOPT_SSL_VERIFYPEER => true,
              CURLOPT_USERAGENT => Tiny_WP_Base::plugin_identification() . ' cURL/' . self::curl_version()
        );
        $options = $this->add_proxy_options(Tiny_Config::URL, $options);
        if (TINY_DEBUG) {
            $f = fopen(dirname(__FILE__) . '/curl.log', 'w');
            if (is_resource($f)) {
                $options[CURLOPT_VERBOSE] = true;
                $options[CURLOPT_STDERR] = $f;
            }
        }
        return $options;
    }

    protected function shrink($input) {
        $request = curl_init();
        curl_setopt_array($request, $this->shrink_options($input));

        $response = curl_exec($request);
        if ($response === false || $response === null) {
            return array(array(
                'error' => 'CurlError',
                'message' => sprintf("cURL: %s [%d]", curl_error($request), curl_errno($request))
              ), null, null
            );
        }

        $header_size = curl_getinfo($request, CURLINFO_HEADER_SIZE);
        $status_code = curl_getinfo($request, CURLINFO_HTTP_CODE);
        $headers = self::parse_headers(substr($response, 0, $header_size));
        curl_close($request);

        return array(self::decode(substr($response, $header_size)), $headers, $status_code);
    }

    protected function output_options($url, $resize_options, $preserve_options) {
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_CAINFO => self::get_ca_file(),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => Tiny_WP_Base::plugin_identification() . ' cURL/' . self::curl_version()
        );
        $this->add_proxy_options($url, $options);

        $body = array();

        if ($preserve_options) {
            $body['preserve'] = $preserve_options;
        }

        if ($resize_options) {
            $body['resize'] = $resize_options;
        }

        if ($resize_options || $preserve_options) {
            $options[CURLOPT_USERPWD] = 'api:' . $this->api_key;
            $options[CURLOPT_HTTPHEADER] = array('Content-Type: application/json');
            $options[CURLOPT_POSTFIELDS] = json_encode($body);
        }

        return $options;
    }

    protected function output($url, $resize_options, $preserve_options) {
        $request = curl_init();
        $options = $this->output_options($url, $resize_options, $preserve_options);
        curl_setopt_array($request, $options);

        $response = curl_exec($request);
        $header_size = curl_getinfo($request, CURLINFO_HEADER_SIZE);
        $status_code = curl_getinfo($request, CURLINFO_HTTP_CODE);
        $headers = self::parse_headers(substr($response, 0, $header_size));
        curl_close($request);

        return array(substr($response, $header_size), $headers, $status_code);
    }

    private function add_proxy_options($url, $options) {
        if ($this->proxy->is_enabled() && $this->proxy->send_through_proxy($url)) {
            $options[CURLOPT_PROXYTYPE] = CURLPROXY_HTTP;
            $options[CURLOPT_PROXY] = $this->proxy->host();
            $options[CURLOPT_PROXYPORT] = $this->proxy->port();

            if ($this->proxy->use_authentication()) {
                $options[CURLOPT_PROXYAUTH] = CURLAUTH_ANY;
                $options[CURLOPT_PROXYUSERPWD] = $this->proxy->authentication();
            }
        }
        return $options;
    }
}
