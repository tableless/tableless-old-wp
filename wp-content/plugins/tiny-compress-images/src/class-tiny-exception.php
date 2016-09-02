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

class Tiny_Exception extends Exception {
	protected $type;
	protected $status;

	public function __construct( $message, $type = null, $status = null ) {
		if ( ! is_string( $message ) || ($type && ! is_string( $type )) ) {
			throw new InvalidArgumentException(
				'First two arguments must be strings'
			);
		}

		$this->type = $type;
		$this->status = $status;

		parent::__construct( $message );
	}

	public function get_type() {
		return $this->type;
	}

	public function get_status() {
		return $this->status;
	}

	public function get_message() {
		return $this->getMessage();
	}
}
