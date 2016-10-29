<div class="tiny-account-status" id="tiny-account-status" data-state="missing">
	<div class="update">
		<h4><?php echo esc_html__( 'Change your API key', 'tiny-compress-images' ); ?></h4>
		<p class="introduction"><?php
			$link = sprintf( '<a href="https://tinypng.com/developers" target="_blank">%s</a>',
				esc_html__( 'TinyPNG developer section', 'tiny-compress-images' )
			);

			echo esc_html__( 'Enter your API key.', 'tiny-compress-images' );
			echo ' ';

			printf( esc_html__(
				'If you have lost your key, go to the %s to retrieve it.',
				'tiny-compress-images'
			), $link );
		?></p>

		<input type="text" id="<?php echo esc_attr( self::get_prefixed_name( 'api_key' ) ); ?>"
			name="<?php echo esc_attr( self::get_prefixed_name( 'api_key' ) ); ?>" size="35" spellcheck="false"
			value="<?php echo esc_attr( $key ); ?>">

		<button class="button button-primary" data-tiny-action="update-key"><?php
			echo esc_html__( 'Save', 'tiny-compress-images' );
		?></button>

		<p class="message"></p>

		<p><a href="#" onclick="jQuery('div.tiny-account-status div.update').toggle(); jQuery('div.tiny-account-status div.status').toggle(); return false"><?php
			echo esc_html__( 'Cancel', 'tiny-compress-images' );
		?></a></p>
	</div>
</div>
