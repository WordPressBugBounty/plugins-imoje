<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var array $args */

$imoje_regulation_url = isset( $args['regulation'] ) ? $args['regulation'] : '';
$imoje_iodo_url       = isset( $args['iodo'] ) ? $args['iodo'] : '';

?>

<div class="imoje-regulations">
	<span>
	<?php

	/** @noinspection HtmlUnknownTarget */
	printf(
		esc_html__( 'I declare that I have read and accept the %1$s and %2$s.', 'imoje' ),
		sprintf(
			'<a href="%1$s" target="_blank">%2$s</a>',
			esc_url( $imoje_regulation_url ),
			esc_html__( 'Regulations of ING Pay', 'imoje' )
		),
		sprintf(
			'<a href="%1$s" target="_blank">%2$s</a>',
			esc_url( $imoje_iodo_url ),
			esc_html__( 'Information on personal data ING Pay', 'imoje' )
		) );
	?></span>
</div>
