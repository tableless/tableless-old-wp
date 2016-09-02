<?php

if ( ! defined( 'TINY_DEBUG' ) ) {
	define( 'TINY_DEBUG', null );
}

class Tiny_Config {
	/* URL is only used by fopen driver. */
	const URL = 'https://api.tinify.com/shrink';
	const MONTHLY_FREE_COMPRESSIONS = 500;
}
