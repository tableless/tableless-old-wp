<div class="tiny-account-status" id="tiny-account-status" data-state="pending">
	<div class="status status-loading">
		<p class="status"><?php echo esc_html__(
			'Retrieving account status',
			'tiny-compress-images'
		); ?></p>

		<input type="hidden" id="<?php echo esc_attr( self::get_prefixed_name( 'api_key' ) ); ?>"
			name="<?php echo esc_attr( self::get_prefixed_name( 'api_key' ) ); ?>" size="35" spellcheck="false"
			value="<?php echo esc_attr( $key ); ?>">
	</div>
</div>
