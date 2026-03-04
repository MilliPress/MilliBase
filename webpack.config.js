const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		millibase: path.resolve( __dirname, 'assets/js/millibase.js' ),
	},
};
