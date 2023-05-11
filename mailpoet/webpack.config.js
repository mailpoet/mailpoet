const webpack = require('webpack');
const { WebpackManifestPlugin } = require('webpack-manifest-plugin');
const WebpackTerserPlugin = require('terser-webpack-plugin');
const WebpackCopyPlugin = require('copy-webpack-plugin');
const path = require('path');
const wpScriptConfig = require('@wordpress/scripts/config/webpack.config');
const DependencyExtractionWebpackPlugin = require('@wordpress/dependency-extraction-webpack-plugin');
const ForkTsCheckerWebpackPlugin = require('fork-ts-checker-webpack-plugin');

const globalPrefix = 'MailPoetLib';
const PRODUCTION_ENV = process.env.NODE_ENV === 'production';
const manifestSeed = {};

const stats = {
  preset: 'minimal',
  assets: false,
  modules: false,
  chunks: true,
};

// Base config
const baseConfig = {
  stats,
  ignoreWarnings: [
    (warnings) => {
      // Todo: remove this if statement per MAILPOET-4544
      if (
        warnings &&
        [
          'AssetsOverSizeLimitWarning',
          'EntrypointsOverSizeLimitWarning',
          'NoAsyncChunksWarning',
        ].includes(warnings.name)
      ) {
        return true;
      }

      // only show warnings when watching
      if (process.env.WEBPACK_WATCH || process.argv.includes('--watch')) {
        return false;
      }

      if (warnings) {
        // eslint-disable-next-line
        console.warn(warnings);
        process.emitWarning(warnings); // emit for listeners
        process.exit(1);
      }

      return false;
    },
  ],
  mode: PRODUCTION_ENV ? 'production' : 'development',
  devtool: PRODUCTION_ENV ? undefined : 'eval-source-map',
  cache: true,
  bail: PRODUCTION_ENV,
  context: __dirname,
  watchOptions: {
    aggregateTimeout: 300,
    poll: true,
  },
  optimization: {
    minimizer: [
      new WebpackTerserPlugin({
        terserOptions: {
          // preserve identifier names for easier debugging & support
          mangle: false,
        },
        parallel: false,
      }),
    ],
  },
  output: {
    publicPath: '', // This is needed to have correct names in WebpackManifestPlugin
    path: path.join(__dirname, 'assets/dist/js'),
    filename: '[name].js',
    chunkFilename: '[name].chunk.js',
  },
  resolve: {
    modules: ['node_modules', 'assets/js/src'],
    fallback: {
      fs: false,
      path: false, // path is used in css module, but we don't use the functionality which requires it
    },
    extensions: ['.js', '.jsx', '.ts', '.tsx'],
    alias: {
      handlebars: 'handlebars/dist/handlebars.js',
      'backbone.marionette': 'backbone.marionette/lib/backbone.marionette',
      'backbone.supermodel$':
        'backbone.supermodel/build/backbone.supermodel.js',
      'sticky-kit': 'vendor/jquery.sticky-kit.js',
      interact$: 'interact.js/interact.js',
      spectrum$: 'spectrum-colorpicker/spectrum.js',
      'wp-js-hooks': path.resolve(__dirname, 'assets/js/src/hooks.js'),
      blob$: 'blob-tmp/Blob.js',
      chai: 'chai/index.js',
      papaparse: 'papaparse/papaparse.min.js',
      html2canvas: 'html2canvas/dist/html2canvas.js',
      asyncqueue: 'vendor/jquery.asyncqueue.js',
    },
  },
  plugins: PRODUCTION_ENV ? [] : [new ForkTsCheckerWebpackPlugin()],
  module: {
    noParse: /node_modules\/lodash\/lodash\.js/,
    rules: [
      {
        test: /\.(j|t)sx?$/,
        exclude: /(node_modules|src\/vendor)/,
        loader: 'babel-loader',
        resolve: {
          fullySpecified: false,
        },
      },
      {
        include: path.resolve(
          __dirname,
          'assets/js/src/webpack_admin_expose.js',
        ),
        loader: 'expose-loader',
        options: { exposes: globalPrefix },
      },
      {
        include: require.resolve('underscore'),
        loader: 'expose-loader',
        options: {
          exposes: '_',
        },
      },
      {
        include: /Blob.js$/,
        loader: 'exports-loader',
        options: {
          exports: 'default window.Blob',
        },
      },
      {
        test: /backbone.supermodel/,
        loader: 'exports-loader',
        options: {
          exports: 'default Backbone.SuperModel',
        },
      },
      {
        include: require.resolve('velocity-animate'),
        loader: 'imports-loader',
        options: {
          imports: {
            name: 'jQuery',
            moduleName: 'jquery',
          },
        },
      },
      {
        test: /node_modules\/tinymce/,
        loader: 'string-replace-loader',
        options: {
          // prefix TinyMCE to avoid conflicts with other plugins
          multiple: [
            {
              search: 'window\\.tinymce',
              replace: 'window.mailpoetTinymce',
              flags: 'g',
            },
            {
              search: 'window\\.tinyMCE',
              replace: 'window.mailpoetTinyMCE',
              flags: 'g',
            },
            {
              search: 'tinymce\\.util',
              replace: 'window.mailpoetTinymce.util',
              flags: 'g',
            },
            {
              search: "resolve\\('tinymce",
              replace: "resolve('mailpoetTinymce",
              flags: 'g',
            },
          ],
        },
      },
    ],
  },
};

// Admin config
const adminConfig = {
  name: 'admin',
  entry: {
    vendor: 'webpack_vendor_index.jsx',
    mailpoet: 'webpack_mailpoet_index.jsx',
    admin_vendor: ['prop-types', 'lodash', 'webpack_admin_expose.js'], // libraries shared between free and premium plugin
    admin: 'webpack_admin_index.tsx',
    automation: 'automation/automation.tsx',
    automation_editor: 'automation/editor/index.tsx',
    automation_analytics:
      'automation/integrations/mailpoet/analytics/index.tsx',
    automation_templates: 'automation/templates/index.tsx',
    newsletter_editor: 'newsletter_editor/webpack_index.jsx',
    form_editor: 'form_editor/form_editor.jsx',
    settings: 'settings/index.tsx',
  },
  plugins: [
    ...baseConfig.plugins,

    new WebpackCopyPlugin({
      patterns: [
        {
          from: 'node_modules/tinymce/skins/ui/oxide',
          to: 'skins/ui/oxide',
        },
      ],
    }),
  ],
  optimization: {
    runtimeChunk: 'single',
    splitChunks: {
      cacheGroups: {
        commons: {
          name: 'commons',
          chunks: 'initial',
          minChunks: 2,
        },
      },
    },
  },
  externals: {
    jquery: 'jQuery',
  },
};

// Public config
const publicConfig = {
  name: 'public',
  entry: {
    public: 'webpack_public_index.jsx',
  },
  plugins: [
    ...baseConfig.plugins,

    // replace MailPoet definition with a smaller version for public
    new webpack.NormalModuleReplacementPlugin(
      /mailpoet\.ts/,
      './mailpoet_public.ts',
    ),
  ],
  externals: {
    jquery: 'jQuery',
  },
};

// Newsletter Editor Tests Config
const testConfig = {
  name: 'test',
  entry: {
    vendor: 'webpack_vendor_index.jsx',
    testNewsletterEditor: [
      'webpack_mailpoet_index.jsx',
      'newsletter_editor/webpack_index.jsx',

      'components/config.spec.js',
      'components/content.spec.js',
      'components/heading.spec.js',
      'components/history.spec.js',
      'components/save.spec.js',
      'components/sidebar.spec.js',
      'components/styles.spec.js',
      'components/communication.spec.js',

      'blocks/automatedLatestContentLayout.spec.js',
      'blocks/button.spec.js',
      'blocks/container.spec.js',
      'blocks/coupon.spec.js',
      'blocks/divider.spec.js',
      'blocks/footer.spec.js',
      'blocks/header.spec.js',
      'blocks/image.spec.js',
      'blocks/posts.spec.js',
      'blocks/products.spec.js',
      'blocks/social.spec.js',
      'blocks/spacer.spec.js',
      'blocks/text.spec.js',
    ],
  },
  output: {
    path: path.join(
      __dirname,
      'tests/javascript_newsletter_editor/testBundles',
    ),
    filename: '[name].js',
  },
  plugins: [
    ...baseConfig.plugins,

    // replace MailPoet definition with a smaller version for public
    new webpack.NormalModuleReplacementPlugin(
      /mailpoet\.js/,
      './mailpoet_tests.js',
    ),
  ],
  resolve: {
    modules: [
      'node_modules',
      'assets/js/src',
      'tests/javascript_newsletter_editor/newsletter_editor',
    ],
    extensions: ['.js', '.jsx', '.ts', '.tsx'],
    alias: {
      handlebars: 'handlebars/dist/handlebars.js',
      'sticky-kit': 'vendor/jquery.sticky-kit.js',
      'backbone.marionette': 'backbone.marionette/lib/backbone.marionette',
      'backbone.supermodel$':
        'backbone.supermodel/build/backbone.supermodel.js',
      blob$: 'blob-tmp/Blob.js',
      'wp-js-hooks': path.resolve(__dirname, 'assets/js/src/hooks.js'),
    },
    fallback: {
      fs: false,
    },
  },
  externals: {
    jquery: 'jQuery',
    interact: 'interact',
    spectrum: 'spectrum',
  },
};

// Form preview config
const formPreviewConfig = {
  name: 'form_preview',
  entry: {
    form_preview: 'form_editor/form_preview.ts',
  },
  externals: {
    jquery: 'jQuery',
  },
};

// Block config
const postEditorBlock = {
  name: 'post_editor_block',
  entry: {
    post_editor_block: 'post_editor_block/blocks.jsx',
  },
};

// Marketing Optin config
function requestToExternal(request) {
  const wcDepMap = {
    '@woocommerce/settings': ['wc', 'wcSettings'],
    '@woocommerce/blocks-checkout': ['wc', 'blocksCheckout'],
  };
  if (wcDepMap[request]) {
    return wcDepMap[request];
  }
  // DependencyExtractionWebpackPlugin has native handling for @wordpress/*
  // packages, for that handling to kick in, we must not return anything from
  // function.
  /* eslint-disable-next-line consistent-return, no-useless-return */
  return;
}

function requestToHandle(request) {
  const wcHandleMap = {
    '@woocommerce/settings': 'wc-settings',
    '@woocommerce/blocks-checkout': 'wc-blocks-checkout',
  };
  if (wcHandleMap[request]) {
    return wcHandleMap[request];
  }
  // DependencyExtractionWebpackPlugin has native handling for @wordpress/*
  // packages, for that handling to kick in, we must not return anything from
  // function.
  /* eslint-disable-next-line consistent-return, no-useless-return */
  return;
}
const marketingOptinBlock = Object.assign({}, wpScriptConfig, {
  stats,
  name: 'marketing_optin_block',
  entry: {
    'marketing-optin-block': '/assets/js/src/marketing_optin_block/index.tsx',
    'marketing-optin-block-frontend':
      '/assets/js/src/marketing_optin_block/frontend.ts',
  },
  output: {
    filename: '[name].js',
    path: path.join(__dirname, 'assets/dist/js/marketing_optin_block'),
  },
  module: Object.assign({}, wpScriptConfig.module, {
    rules: [
      ...wpScriptConfig.module.rules,
      {
        test: /\.(t|j)sx?$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader?cacheDirectory',
          options: {
            presets: ['@wordpress/babel-preset-default'],
            plugins: [
              require.resolve('@babel/plugin-proposal-class-properties'),
              require.resolve(
                '@babel/plugin-proposal-nullish-coalescing-operator',
              ),
            ].filter(Boolean),
          },
        },
      },
    ],
  }),
  resolve: {
    extensions: ['.js', '.jsx', '.ts', '.tsx'],
  },
  // use only needed plugins from wpScriptConfig and add the custom ones
  plugins: [
    ...wpScriptConfig.plugins.filter(
      (plugin) =>
        plugin.constructor.pluginName === 'mini-css-extract-plugin' ||
        plugin.constructor.name === 'CleanWebpackPlugin',
    ),
    new DependencyExtractionWebpackPlugin({
      injectPolyfill: true,
      requestToExternal,
      requestToHandle,
    }),
    new WebpackCopyPlugin({
      patterns: [
        {
          from: 'assets/js/src/marketing_optin_block/block.json',
          to: 'block.json',
        },
      ],
    }),
  ],
});

const configs = [
  publicConfig,
  adminConfig,
  formPreviewConfig,
  testConfig,
  postEditorBlock,
  marketingOptinBlock,
];

module.exports = configs.map((conf) => {
  const config = Object.assign({}, conf);
  if (config.name === 'marketing_optin_block') {
    return config;
  }
  if (config.name !== 'test') {
    config.plugins = config.plugins || [];
    config.plugins.push(
      new WebpackManifestPlugin({
        // create single manifest file for all Webpack configs
        seed: manifestSeed,
      }),
    );
  }
  return Object.assign({}, baseConfig, config);
});
