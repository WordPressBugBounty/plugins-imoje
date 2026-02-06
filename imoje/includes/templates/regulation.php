<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var string $regulation */
/** @var string $iodo */

?>

<div class="imoje-regulations">
	<span>
	<?php

	printf(
		esc_html__( 'I declare that I have read and accept the %1$s and %2$s.', 'imoje' ),
		sprintf(
			'<a href="%1$s" target="_blank">%2$s</a>',
			esc_html( $regulation ),
			esc_html__( 'Regulations of imoje', 'imoje' )
		),
		sprintf(
			'<a href="%1$s" target="_blank">%2$s</a>',
			esc_html( $iodo ),
			esc_html__( 'Information on personal data imoje', 'imoje' )
		) );
	?></span>
</div>
