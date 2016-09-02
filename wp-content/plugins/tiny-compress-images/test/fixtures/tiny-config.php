<?php

if ( ! defined( 'TINY_DEBUG' ) ) {
	define( 'TINY_DEBUG', null );
}

class Tiny_Config {
	/* URL is only used by fopen driver. */
	const URL = 'http://webservice/shrink';
	const MONTHLY_FREE_COMPRESSIONS = 500;
}
