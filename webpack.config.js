const webpack = require('webpack');
const { WebpackManifestPlugin } = require('webpack-manifest-plugin');
const webpackTerserPlugin = require('terser-webpack-plugin');
const webpackCopyPlugin = require('copy-webpack-plugin');
const path = require('path');
const del = require('del');
const globalPrefix = 'MailPoetLib';
const PRODUCTION_ENV = process.env.NODE_ENV === 'production';
const manifestSeed = {};

// Base config
const baseConfig = {
  mode: PRODUCTION_ENV ? 'production' : 'development',
  devtool: PRODUCTION_ENV ? undefined : 'eval-source-map',
  cache: true,
  bail: PRODUCTION_ENV,
  context: __dirname,
  watchOptions: {
    aggregateTimeout: 300,
    poll: true
  },
  optimization: {
    minimizer: [
      new webpackTerserPlugin({
        terserOptions: {
          // preserve identifier names for easier debugging & support
          mangle: false,
        },
        parallel: false,
      }),
    ],
  },
  output: {
    path: path.join(__dirname, 'assets/dist/js'),
    filename: (PRODUCTION_ENV) ? '[name].[hash:8].js' : '[name].js',
    chunkFilename: (PRODUCTION_ENV) ? '[name].[hash:8].chunk.js' : '[name].chunk.js',
  },
  resolve: {
    modules: [
      'node_modules',
      'assets/js/src',
    ],
    fallback: {
      fs: false,
      path: false, // path is used in css module, but we don't use the functionality which requires it
    },
    extensions: ['.js', '.jsx', '.ts', '.tsx'],
    alias: {
      'handlebars': 'handlebars/dist/handlebars.js',
      'backbone.marionette': 'backbone.marionette/lib/backbone.marionette',
      'backbone.supermodel$': 'backbone.supermodel/build/backbone.supermodel.js',
      'sticky-kit': 'vendor/jquery.sticky-kit.js',
      'interact$': 'interact.js/interact.js',
      'spectrum$': 'spectrum-colorpicker/spectrum.js',
      'wp-js-hooks': path.resolve(__dirname, 'assets/js/src/hooks.js'),
      'blob$': 'blob-tmp/Blob.js',
      'papaparse': 'papaparse/papaparse.min.js',
      'html2canvas': 'html2canvas/dist/html2canvas.js',
      'asyncqueue': 'vendor/jquery.asyncqueue.js',
    },
  },
  plugins: [],
  module: {
    noParse: /node_modules\/lodash\/lodash\.js/,
    rules: [
      {
        test: /\.(j|t)sx?$/,
        exclude: /(node_modules|src\/vendor)/,
        loader: 'babel-loader',
      },
      {
        test: /form_editor\.js$/,
        loader: 'expose-loader',
        options: {
          exposes: 'WysijaForm',
        },
      },
      {
        include: require.resolve('codemirror'),
        loader: 'expose-loader',
        options: {
          exposes: 'CodeMirror',
        },
      },
      {
        include: require.resolve('backbone'),
        loader: 'expose-loader',
        options: {
          exposes: 'Backbone',
        },
      },
      {
        include: require.resolve('underscore'),
        loader: 'expose-loader',
        options: {
          exposes: '_',
        },
      },
      {
        include: require.resolve('react-tooltip'),
        loader: 'expose-loader',
        options: {
          exposes: globalPrefix + '.ReactTooltip',
        },
      },
      {
        include: require.resolve('react'),
        loader: 'expose-loader',
        options: {
          exposes: globalPrefix + '.React',
        },
      },
      {
        include: require.resolve('react-dom'),
        loader: 'expose-loader',
        options: {
          exposes: globalPrefix + '.ReactDOM',
        },
      },
      {
        include: require.resolve('react-router-dom'),
        loader: 'expose-loader',
        options: {
          exposes: globalPrefix + '.ReactRouter',
        },
      },
      {
        include: require.resolve('react-string-replace'),
        loader: 'expose-loader',
        options: {
          exposes: globalPrefix + '.ReactStringReplace',
        },
      },
      {
        include: path.resolve(__dirname, 'assets/js/src/hooks.js'),
        loader: 'expose-loader',
        options: {
          exposes: {
            globalName: globalPrefix + '.Hooks',
            override: true,
          },
        },
      },
      {
        test: /listing.jsx/i,
        use: [
          {
            loader: 'expose-loader',
            options: {
              exposes: globalPrefix + '.Listing',
            },
          },
          'babel-loader'
        ],
      },
      {
        include: path.resolve(__dirname, 'assets/js/src/help-tooltip.jsx'),
        use: [
          {
            loader: 'expose-loader',
            options: {
              exposes: globalPrefix + '.HelpTooltip',
            },
          },
          'babel-loader',
        ]
      },
      {
        include: path.resolve(__dirname, 'assets/js/src/common/index.ts'),
        use: [
          {
            loader: 'expose-loader',
            options: {
              exposes: globalPrefix + '.Common',
            },
          },
          'babel-loader',
        ]
      },
      {
        include: /Blob.js$/,
        loader: 'exports-loader',
        options: {
          exports: 'default window.Blob',
        }
      },
      {
        test: /backbone.supermodel/,
        loader: 'exports-loader',
        options: {
          exports: 'default Backbone.SuperModel',
        }
      },
      {
        include: require.resolve('handlebars'),
        loader: 'expose-loader',
        options: {
          exposes: 'Handlebars',
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
        include: require.resolve('classnames'),
        use: [
          {
            loader: 'expose-loader',
            options: {
              exposes: globalPrefix + '.ClassNames',
            },
          },
          'babel-loader',
        ]
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
              search: 'tinymce\\.util',
              replace: 'window.mailpoetTinymce.util',
              flags: 'g',
            },
            {
              search: 'resolve\\(\'tinymce',
              replace: 'resolve(\'mailpoetTinymce',
              flags: 'g',
            },
          ],
        },
      },
    ]
  }
};

// Admin config
const adminConfig = {
  name: 'admin',
  entry: {
    vendor: 'webpack_vendor_index.jsx',
    mailpoet: 'webpack_mailpoet_index.jsx',
    admin_vendor: [
      'react',
      'react-dom',
      require.resolve('react-router-dom'),
      'react-string-replace',
      'prop-types',
      'classnames',
      'lodash',
      '@emotion/react',
      '@emotion/styled',
      'help-tooltip.jsx',
      'listing/listing.jsx',
      'common/index.ts',
    ],
    admin: 'webpack_admin_index.jsx',
    newsletter_editor: 'newsletter_editor/webpack_index.jsx',
    form_editor: 'form_editor/form_editor.jsx',
    settings: 'settings/index.tsx'
  },
  plugins: [
    ...baseConfig.plugins,

    new webpackCopyPlugin({
      patterns: [
        {
          from: 'node_modules/tinymce/skins/ui/oxide',
          to: 'skins/ui/oxide'
        },
      ],
    }),
  ],
  optimization: {
    runtimeChunk: 'single',
    splitChunks: {
      cacheGroups: {
        chunks: 'all',
      },
    }
  },
  externals: {
    'jquery': 'jQuery',
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
      /mailpoet\.js/,
      './mailpoet_public.js'
    ),
  ],
  externals: {
    'jquery': 'jQuery'
  }
};

// Migrator config
const migratorConfig = {
  name: 'mp2migrator',
  entry: {
    mp2migrator: [
      'mp2migrator.js'
    ]
  },
  externals: {
    'jquery': 'jQuery',
    'mailpoet': 'MailPoet'
  }
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
    path: path.join(__dirname, 'tests/javascript_newsletter_editor/testBundles'),
    filename: '[name].js',
  },
  plugins: [
    ...baseConfig.plugins,

    // replace MailPoet definition with a smaller version for public
    new webpack.NormalModuleReplacementPlugin(
      /mailpoet\.js/,
      './mailpoet_tests.js'
    ),
  ],
  resolve: {
    modules: [
      'node_modules',
      'assets/js/src',
      'tests/javascript_newsletter_editor/newsletter_editor'
    ],
    extensions: ['.js', '.jsx', '.ts', '.tsx'],
    alias: {
      'handlebars': 'handlebars/dist/handlebars.js',
      'sticky-kit': 'vendor/jquery.sticky-kit.js',
      'backbone.marionette': 'backbone.marionette/lib/backbone.marionette',
      'backbone.supermodel$': 'backbone.supermodel/build/backbone.supermodel.js',
      'blob$': 'blob-tmp/Blob.js',
      'wp-js-hooks': path.resolve(__dirname, 'assets/js/src/hooks.js'),
    },
    fallback: {
      fs: false,
    },
  },
  externals: {
    'jquery': 'jQuery',
    'interact': 'interact',
    'spectrum': 'spectrum',
  },
};

// Form preview config
const formPreviewConfig = {
  name: 'form_preview',
  entry: {
    form_preview: 'form_editor/form_preview.ts',
  },
  externals: {
    'jquery': 'jQuery',
  },
};

// Block config
const postEditorBlock = {
  name: 'post_editor_block',
  entry: {
    post_editor_block: 'post_editor_block/blocks.jsx',
  },
};

module.exports = [adminConfig, publicConfig, migratorConfig, formPreviewConfig, testConfig, postEditorBlock].map((config) => {
  if (config.name !== 'test') {
    config.plugins = config.plugins || [];
    config.plugins.push(
      new WebpackManifestPlugin({
        // create single manifest file for all Webpack configs
        seed: manifestSeed,
      })
    );
  }
  // Clean output paths before build
  if (config.output && config.output.path) {
    del.sync([path.resolve(config.output.path, '**/*')]);
  }
  return Object.assign({}, baseConfig, config);
});
