const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		millisettings: path.resolve( __dirname, 'assets/js/millisettings.js' ),
	},
};
