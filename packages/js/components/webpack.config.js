import path from 'node:path';
import { default as defaultConfig } from '@wordpress/scripts/config/webpack.config.js';
import ForkTsCheckerWebpackPlugin from 'fork-ts-checker-webpack-plugin';
import { WebpackEmitAllPlugin } from '../../../tools/webpack/webpack-emit-all-plugin.js';

const dirname = new URL('.', import.meta.url).pathname;

const miniCssExtractPlugin = defaultConfig.plugins.find(
  (plugin) => plugin.constructor.name === 'MiniCssExtractPlugin',
);

miniCssExtractPlugin.options = {
  ...miniCssExtractPlugin.options,
  filename: path.join('../build-style', 'style.css'),
  experimentalUseImportModule: false,
};

const plugins = [
  ...defaultConfig.plugins.filter(
    (plugin) => plugin.constructor.name !== 'DependencyExtractionWebpackPlugin',
  ),
  new WebpackEmitAllPlugin({ context: path.join(dirname, 'src') }),
  new ForkTsCheckerWebpackPlugin({ typescript: { mode: 'write-dts' } }),
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
