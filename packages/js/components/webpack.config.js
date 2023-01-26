import path from 'node:path';
import { default as defaultConfig } from '@wordpress/scripts/config/webpack.config.js';
import { WebpackEmitAllPlugin } from '../../../tools/webpack/webpack-emit-all-plugin.js';

const dirname = new URL('.', import.meta.url).pathname;

const plugins = [
  ...defaultConfig.plugins.filter(
    (plugin) => plugin.constructor.name !== 'DependencyExtractionWebpackPlugin',
  ),
  new WebpackEmitAllPlugin({ context: path.join(dirname, 'src') }),
];

export default {
  ...defaultConfig,
  plugins,
  devtool: 'source-map',
  context: dirname,
  entry: { index: path.join(dirname, 'src', 'index.ts') },
  output: {
    ...defaultConfig.output,
    path: path.join(dirname, 'build-module'),
    environment: { module: true },
    library: { type: 'module' },
  },
  externals: {
    react: 'React',
    'react-dom': 'ReactDOM',
    'react/jsx-runtime': 'ReactJSX',
  },
  experiments: { outputModule: true },
};
