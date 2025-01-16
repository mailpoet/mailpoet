module.exports = {
	extends: [ 'plugin:@woocommerce/eslint-plugin/recommended' ],
	overrides: [
		{
			files: [ '**/*.js', '**/*.ts', '**/*.jsx', '**/*.tsx' ],
			rules: {
				'react/react-in-jsx-scope': 'off',
			},
		},
	],
};
