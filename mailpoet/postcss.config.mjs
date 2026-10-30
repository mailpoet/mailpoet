import autoprefixer from 'autoprefixer';
import dsTokenFallbacks from '@wordpress/theme/postcss-plugins/postcss-ds-token-fallbacks';

export default {
  plugins: [autoprefixer, dsTokenFallbacks],
};
