const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const WooCommerceDependencyExtractionWebpackPlugin = require( '@woocommerce/dependency-extraction-webpack-plugin' );

module.exports = {
    ...defaultConfig,
    entry: {
        'js/imoje': '/assets/js/checkout/imoje-block',
        'js/imoje_common': '/assets/js/checkout/imoje-common-block',
        'js/imoje_blik': '/assets/js/checkout/imoje-blik-block',
        'js/imoje_cards': '/assets/js/checkout/imoje-cards-block',
        'js/imoje_installments': '/assets/js/checkout/imoje-installments-block',
        'js/imoje_leasenow': '/assets/js/checkout/imoje-leasenow-block',
        'js/imoje_paylater': '/assets/js/checkout/imoje-paylater-block',
        'js/imoje_pbl': '/assets/js/checkout/imoje-pbl-block',
        'js/imoje_visa': '/assets/js/checkout/imoje-visa-block',
        'js/imoje_wallet': '/assets/js/checkout/imoje-wallet-block',
        'js/imoje_wt': '/assets/js/checkout/imoje-wt-block',

    },
    plugins: [
        ...defaultConfig.plugins.filter(
            ( plugin ) =>
                plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
        ),
        new WooCommerceDependencyExtractionWebpackPlugin(),
    ],
};
