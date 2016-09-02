<?php

$user = wp_get_current_user();
$name = trim( $user->user_firstname . ' ' . $user->user_lastname );
$email = trim( $user->user_email );

?><div class="tiny-account-status wide" id="tiny-account-status" data-state="missing">
	<div class="create">
		<h4><?php
			echo esc_html_e( 'Register new account', 'tiny-compress-images' );
		?></h4>

		<p class="introduction" class="description"><?php
			echo esc_html__(
				'Provide your name and email address to start optimizing images.',
				'tiny-compress-images'
			);
		?></p>

		<input type="text" id="tinypng_api_key_name" name="tinypng_api_key_name"
			placeholder="Your full name" value="<?php echo esc_attr( $name ); ?>">

		<input type="text" id="tinypng_api_key_email" name="tinypng_api_key_email"
			placeholder="Your email address" value="<?php echo esc_attr( $email ); ?>">

		<p class="message"></p>

		<button class="button button-primary" data-tiny-action="create-key">
			<?php echo esc_html__( 'Register Account', 'tiny-compress-images' ) ?>
		</button>
	</div>

	<div class="update">
		<h4><?php
			echo esc_html__( 'Already have an account?', 'tiny-compress-images' );
		?></h4>

		<p class="introduction"><?php
			$link = sprintf( '<a href="https://tinypng.com/developers" target="_blank">%s</a>',
				esc_html__( 'TinyPNG developer section', 'tiny-compress-images' )
			);
			printf( esc_html__(
				'Enter your API key. Go to the %s to retrieve it.',
				'tiny-compress-images'
			), $link );
		?></p>

		<input type="text" id="<?php echo esc_attr( self::get_prefixed_name( 'api_key' ) ); ?>"
			name="<?php echo esc_attr( self::get_prefixed_name( 'api_key' ) ); ?>">

		<p class="message"></p>

		<button class="button button-primary" data-tiny-action="update-key">
			<?php echo esc_html__( 'Save' ); ?>
		</button>
	</div>
</div>
