<div class="tiny-account-status" id="tiny-account-status" data-state="complete">
	<div class="status <?php echo $status->ok ? ( $status->pending ? 'status-pending' : 'status-success' ) : 'status-failure'; ?>">
		<p class="status"><?php
		if ( $status->ok ) {
			if ( isset( $status->message ) ) {
				echo esc_html__( $status->message, 'tiny-compress-images' );
			} else {
				echo esc_html__( 'Your account is connected', 'tiny-compress-images' );
			}
		} else {
			echo esc_html__( 'Connection unsuccessful', 'tiny-compress-images' );
		}
		?></p>
		<p><?php
		if ( $status->ok ) {
			$compressions = self::get_compression_count();
			/* It is not possible to check if a subscription is free or flexible. */
			if ( Tiny_Config::MONTHLY_FREE_COMPRESSIONS == $compressions ) {
				$link = '<a href="https://tinypng.com/developers" target="_blank">' . esc_html__( 'TinyPNG API account', 'tiny-compress-images' ) . '</a>';
				printf( esc_html__(
					'You have reached your limit of %s compressions this month.',
					'tiny-compress-images'
				), $compressions );
				echo '<br>';
				printf( esc_html__(
					'If you need to compress more images you can change your %s.',
					'tiny-compress-images'
				), $link );
			} else {
				printf( esc_html__(
					'You have made %s compressions this month.',
					'tiny-compress-images'
				), $compressions );
			}
		} else {
			if ( isset( $status->message ) ) {
				echo esc_html__( 'Error', 'tiny-compress-images' ) . ': ';
				echo esc_html__( $status->message, 'tiny-compress-images' );
			} else {
				esc_html__(
					'API status could not be checked, enable cURL for more information',
					'tiny-compress-images'
				);
			}
		}
		?></p>
		<p><?php
		if ( defined( 'TINY_API_KEY' ) ) {
			echo sprintf( esc_html__(
				'The API key has been configured in %s',
				'tiny-compress-images'
			), 'wp-config.php' );
		} else {
			echo '<a href="#" onclick="jQuery(\'div.tiny-account-status div.update\').toggle(); jQuery(\'div.tiny-account-status div.status\').toggle(); return false">';
			echo esc_html__( 'Change API key', 'tiny-compress-images' );
			echo '</a>';
		}
		?></p>
	</div>

	<div class="update" style="display: none">
		<h4><?php echo esc_html__( 'Change your API key', 'tiny-compress-images' ); ?></h4>
		<p class="introduction"><?php
			$link = sprintf( '<a href="https://tinypng.com/developers" target="_blank">%s</a>',
				esc_html__( 'TinyPNG developer section', 'tiny-compress-images' )
			);
			printf( esc_html__(
				'Enter your API key. If you have lost your key, go to the %s to retrieve it.',
				'tiny-compress-images'
			), $link );
		?></p>

		<input type="text" id="tinypng_api_key"
			name="tinypng_api_key" size="35" spellcheck="false"
			value="<?php echo esc_attr( $key ); ?>">

		<button class="button button-primary" data-tiny-action="update-key"><?php
			echo esc_html__( 'Save' );
		?></button>

		<p class="message"></p>

		<p><a href="#" onclick="jQuery('div.tiny-account-status div.update').toggle(); jQuery('div.tiny-account-status div.status').toggle(); return false"><?php
			echo esc_html__( 'Cancel' );
		?></a></p>
	</div>
</div>
