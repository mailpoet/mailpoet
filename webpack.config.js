var webpack = require('webpack');
var webpackManifestPlugin = require('webpack-manifest-plugin');
var webpackMD5HashPlugin = require('webpack-md5-hash');
var webpackCleanPlugin = require('clean-webpack-plugin');
var webpackUglifyJsPlugin = require('uglifyjs-webpack-plugin');
var _ = require('underscore');
var path = require('path');
var globalPrefix = 'MailPoetLib';
var PRODUCTION_ENV = process.env.NODE_ENV === 'production';
var manifestCache = {};

// Base config
var baseConfig = {
  cache: true,
  context: __dirname,
  watchOptions: {
    aggregateTimeout: 300,
    poll: true
  },
  output: {
    path: path.join(__dirname, 'assets/js'),
    filename: (PRODUCTION_ENV) ? '[name].[hash:8].js' : '[name].js',
    chunkFilename: (PRODUCTION_ENV) ? '[name].[hash:8].chunk.js' : '[name].chunk.js'
  },
  resolve: {
    modules: [
      'node_modules',
      'assets/js/src',
    ],
    alias: {
      'handlebars': 'handlebars/dist/handlebars.js',
      'backbone.marionette': 'backbone.marionette/lib/backbone.marionette',
      'backbone.supermodel$': 'backbone.supermodel/build/backbone.supermodel.js',
      'sticky-kit': 'vendor/jquery.sticky-kit.js',
      'interact$': 'interact.js/interact.js',
      'spectrum$': 'spectrum-colorpicker/spectrum.js',
      'wp-js-hooks': 'WP-JS-Hooks/src/event-manager.js',
      'blob$': 'blob-tmp/Blob.js',
      'papaparse': 'papaparse/papaparse.min.js',
      'html2canvas': 'html2canvas/dist/html2canvas.js',
      'asyncqueue': 'vendor/jquery.asyncqueue.js'
    },
  },
  node: {
    fs: 'empty'
  },
  plugins: [
    new webpackCleanPlugin([
      './assets/js/*.*',
    ])
  ],
  module: {
    rules: [
      {
        test: /\.jsx$/,
        exclude: /node_modules/,
        loader: 'babel-loader',
      },
      {
        test: /form_editor\.js$/,
        loader: 'expose-loader?WysijaForm',
      },
      {
        include: require.resolve('codemirror'),
        loader: 'expose-loader?CodeMirror',
      },
      {
        include: require.resolve('backbone'),
        loader: 'expose-loader?Backbone',
      },
      {
        include: require.resolve('underscore'),
        loader: 'expose-loader?_',
      },
      {
        include: require.resolve('react-tooltip'),
        loader: 'expose-loader?' + globalPrefix + '.ReactTooltip',
      },
      {
        include: require.resolve('history'),
        loader: 'expose-loader?' + globalPrefix + '.History',
      },
      {
        include: require.resolve('react'),
        loader: 'expose-loader?' + globalPrefix + '.React',
      },
      {
        include: require.resolve('react-dom'),
        loader: 'expose-loader?' + globalPrefix + '.ReactDOM',
      },
      {
        include: require.resolve('react-router'),
        use: 'expose-loader?' + globalPrefix + '.ReactRouter',
      },
      {
        include: require.resolve('react-string-replace'),
        loader: 'expose-loader?' + globalPrefix + '.ReactStringReplace',
      },
      {
        test: /wp-js-hooks/i,
        use: [
          'expose-loader?' + globalPrefix + '.Hooks',
          'exports-loader?wp.hooks',
        ]
      },
      {
        test: /listing.jsx/i,
        use: [
          'expose-loader?' + globalPrefix + '.Listing',
          'babel-loader'
        ],
      },
      {
        test: /form.jsx/i,
        use: [
          'expose-loader?' + globalPrefix + '.Form',
          'babel-loader'
        ]
      },
      {
        include: path.resolve(__dirname, 'assets/js/src/newsletters/listings/mixins.jsx'),
        use: [
          'expose-loader?' + globalPrefix + '.NewslettersListingsMixins',
          'babel-loader',
        ]
      },
      {
        include: path.resolve(__dirname, 'assets/js/src/newsletters/listings/tabs.jsx'),
        use: [
          'expose-loader?' + globalPrefix + '.NewslettersListingsTabs',
          'babel-loader',
        ]
      },
      {
        include: path.resolve(__dirname, 'assets/js/src/newsletters/listings/heading.jsx'),
        use: [
          'expose-loader?' + globalPrefix + '.NewslettersListingsHeading',
          'babel-loader',
        ]
      },
      {
        include: path.resolve(__dirname, 'assets/js/src/form/fields/selection.jsx'),
        use: [
          'expose-loader?' + globalPrefix + '.FormFieldSelection',
          'babel-loader',
        ]
      },
      {
        include: path.resolve(__dirname, 'assets/js/src/form/fields/text.jsx'),
        use: [
          'expose-loader?' + globalPrefix + '.FormFieldText',
          'babel-loader',
        ]
      },
      {
        include: path.resolve(__dirname, 'assets/js/src/newsletters/scheduling/common.jsx'),
        use: [
          'expose-loader?' + globalPrefix + '.NewsletterSchedulingCommonOptions',
          'babel-loader',
        ]
      },
      {
        include: path.resolve(__dirname, 'assets/js/src/newsletters/badges/stats.jsx'),
        use: [
          'expose-loader?' + globalPrefix + '.StatsBadge',
          'babel-loader',
        ]
      },
      {
        include: path.resolve(__dirname, 'assets/js/src/newsletters/breadcrumb.jsx'),
        use: [
          'expose-loader?' + globalPrefix + '.NewsletterCreationBreadcrumb',
          'babel-loader',
        ]
      },
      {
        include: path.resolve(__dirname, 'assets/js/src/newsletters/types/automatic_emails/events_list.jsx'),
        use: [
          'expose-loader?' + globalPrefix + '.AutomaticEmailEventsList',
          'babel-loader',
        ]
      },
      {
        include: path.resolve(__dirname, 'assets/js/src/newsletters/types/automatic_emails/breadcrumb.jsx'),
        use: [
          'expose-loader?' + globalPrefix + '.AutomaticEmailsBreadcrumb',
          'babel-loader',
        ]
      },
      {
        include: /Blob.js$/,
        loader: 'exports-loader?window.Blob',
      },
      {
        test: /backbone.supermodel/,
        loader: 'exports-loader?Backbone.SuperModel',
      },
      {
        include: require.resolve('handlebars'),
        loader: 'expose-loader?Handlebars',
      },
      {
        include: /html2canvas.js$/,
        loader: 'expose-loader?html2canvas',
      },
      {
        include: require.resolve('velocity-animate'),
        loader: 'imports-loader?jQuery=jquery',
      },
      {
        include: require.resolve('classnames'),
        use: [
          'expose-loader?' + globalPrefix + '.ClassNames',
          'babel-loader',
        ]
      },
    ]
  }
};

// Admin config
var adminConfig = {
  name: 'admin',
  entry: {
    vendor: [
      'handlebars',
      'handlebars_helpers',
      'wp-js-hooks'
    ],
    mailpoet: [
      'mailpoet',
      'ajax',
      'date',
      'i18n',
      'modal',
      'notice',
      'num',
      'jquery.serialize_object',
      'parsleyjs',
      'analytics_event',
      'help-tooltip.jsx',
      'help-tooltip',
      'dismissible-notice.jsx',
    ],
    admin_vendor: [
      'react',
      'react-dom',
      require.resolve('react-router'),
      'react-string-replace',
      'prop-types',
      'listing/listing.jsx',
      'form/form.jsx',
      'intro.js',
      'newsletters/badges/stats.jsx',
      'newsletters/breadcrumb.jsx',
      'newsletters/listings/tabs.jsx',
      'newsletters/listings/mixins.jsx',
      'newsletters/listings/heading.jsx',
      'newsletters/types/automatic_emails/events_list.jsx',
      'newsletters/types/automatic_emails/breadcrumb.jsx',
      'newsletters/types/welcome/scheduling.jsx',
      'newsletter_editor/initializer.jsx',
      'history',
      'classnames'
    ],
    admin: [
      'subscribers/subscribers.jsx',
      'newsletters/newsletters.jsx',
      'segments/segments.jsx',
      'forms/forms.jsx',
      'settings/tabs.js',
      'help/help.jsx',
      'intro.jsx',
      'settings/reinstall_from_scratch.js',
      'subscribers/importExport/import.js',
      'subscribers/importExport/export.js',
      'welcome_wizard/wizard.jsx',
      'settings/announcement.jsx',
      'nps_poll.jsx'
    ],
    form_editor: [
      'form_editor/form_editor.js',
      'codemirror',
      'codemirror/mode/css/css'
    ],
    newsletter_editor: [
      'underscore',
      'backbone',
      'backbone.marionette',
      'backbone.supermodel',
      'interact.js',
      'backbone.radio',
      'select2',
      'spectrum',
      'sticky-kit',
      'blob',
      'file-saver',
      'velocity-animate',
      'newsletter_editor/App',
      'newsletter_editor/components/config.js',
      'newsletter_editor/components/styles.js',
      'newsletter_editor/components/sidebar.js',
      'newsletter_editor/components/content.js',
      'newsletter_editor/components/heading.js',
      'newsletter_editor/components/save.js',
      'newsletter_editor/components/communication.js',
      'newsletter_editor/behaviors/BehaviorsLookup.js',
      'newsletter_editor/behaviors/ColorPickerBehavior.js',
      'newsletter_editor/behaviors/ContainerDropZoneBehavior.js',
      'newsletter_editor/behaviors/DraggableBehavior.js',
      'newsletter_editor/behaviors/HighlightContainerBehavior.js',
      'newsletter_editor/behaviors/HighlightEditingBehavior.js',
      'newsletter_editor/behaviors/MediaManagerBehavior.js',
      'newsletter_editor/behaviors/ResizableBehavior.js',
      'newsletter_editor/behaviors/SortableBehavior.js',
      'newsletter_editor/behaviors/ShowSettingsBehavior.js',
      'newsletter_editor/behaviors/TextEditorBehavior.js',
      'newsletter_editor/blocks/base.js',
      'newsletter_editor/blocks/container.js',
      'newsletter_editor/blocks/button.js',
      'newsletter_editor/blocks/image.js',
      'newsletter_editor/blocks/divider.js',
      'newsletter_editor/blocks/text.js',
      'newsletter_editor/blocks/spacer.js',
      'newsletter_editor/blocks/footer.js',
      'newsletter_editor/blocks/header.js',
      'newsletter_editor/blocks/automatedLatestContent.js',
      'newsletter_editor/blocks/automatedLatestContentLayout.js',
      'newsletter_editor/blocks/posts.js',
      'newsletter_editor/blocks/social.js'
    ]
  },
  plugins: [
    new webpack.optimize.CommonsChunkPlugin({
      name: 'admin_vendor',
      fileName: 'admin_vendor.js',
      chunks: ['admin_vendor', 'admin'],
      minChunks: Infinity
    }),
    new webpack.optimize.CommonsChunkPlugin({
      name: 'vendor',
      fileName: 'vendor.js',
      minChunks: Infinity
    })
  ],
  externals: {
    'jquery': 'jQuery',
    'tinymce': 'tinymce'
  }
};

// Public config
var publicConfig = {
  name: 'public',
  entry: {
    public: [
      'mailpoet',
      'i18n',
      'ajax',
      'iframe',
      'jquery.serialize_object',
      'public.js'
    ]
  },
  externals: {
    'jquery': 'jQuery'
  }
};

// Migrator config
var migratorConfig = {
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
// Test config
var testConfig = {
  name: 'test',
  entry: {
    vendor: ['handlebars', 'handlebars_helpers'],
    testNewsletterEditor: [
      'underscore',
      'backbone',
      'backbone.marionette',
      'backbone.supermodel',
      'backbone.radio',
      'select2',
      'blob',
      'file-saver',
      'velocity-animate',

      'mailpoet',
      'notice',
      'i18n',
      'help-tooltip',

      'newsletter_editor/App',
      'newsletter_editor/components/config.js',
      'newsletter_editor/components/styles.js',
      'newsletter_editor/components/sidebar.js',
      'newsletter_editor/components/content.js',
      'newsletter_editor/components/heading.js',
      'newsletter_editor/components/save.js',
      'newsletter_editor/components/communication.js',
      'newsletter_editor/behaviors/BehaviorsLookup.js',
      'newsletter_editor/behaviors/ColorPickerBehavior.js',
      'newsletter_editor/behaviors/ContainerDropZoneBehavior.js',
      'newsletter_editor/behaviors/DraggableBehavior.js',
      'newsletter_editor/behaviors/HighlightContainerBehavior.js',
      'newsletter_editor/behaviors/HighlightEditingBehavior.js',
      'newsletter_editor/behaviors/MediaManagerBehavior.js',
      'newsletter_editor/behaviors/ResizableBehavior.js',
      'newsletter_editor/behaviors/SortableBehavior.js',
      'newsletter_editor/behaviors/ShowSettingsBehavior.js',
      'newsletter_editor/behaviors/TextEditorBehavior.js',
      'newsletter_editor/blocks/base.js',
      'newsletter_editor/blocks/container.js',
      'newsletter_editor/blocks/button.js',
      'newsletter_editor/blocks/image.js',
      'newsletter_editor/blocks/divider.js',
      'newsletter_editor/blocks/text.js',
      'newsletter_editor/blocks/spacer.js',
      'newsletter_editor/blocks/footer.js',
      'newsletter_editor/blocks/header.js',
      'newsletter_editor/blocks/automatedLatestContent.js',
      'newsletter_editor/blocks/automatedLatestContentLayout.js',
      'newsletter_editor/blocks/posts.js',
      'newsletter_editor/blocks/social.js',

      'components/config.spec.js',
      'components/content.spec.js',
      'components/heading.spec.js',
      'components/save.spec.js',
      'components/sidebar.spec.js',
      'components/styles.spec.js',
      'components/communication.spec.js',

      'blocks/automatedLatestContent.spec.js',
      'blocks/button.spec.js',
      'blocks/container.spec.js',
      'blocks/divider.spec.js',
      'blocks/footer.spec.js',
      'blocks/header.spec.js',
      'blocks/image.spec.js',
      'blocks/posts.spec.js',
      'blocks/social.spec.js',
      'blocks/spacer.spec.js',
      'blocks/text.spec.js',
    ],
  },
  output: {
    path: path.join(__dirname, 'tests/javascript/testBundles'),
    filename: '[name].js',
  },
  resolve: {
    modules: [
      'node_modules',
      'assets/js/src',
      'tests/javascript/newsletter_editor'
    ],
    alias: {
      'sticky-kit': 'vendor/jquery.sticky-kit.js',
      'backbone.marionette': 'backbone.marionette/lib/backbone.marionette',
      'backbone.supermodel$': 'backbone.supermodel/build/backbone.supermodel.js',
      'blob$': 'blob-tmp/Blob.js'
    },
  },
  externals: {
    'jquery': 'jQuery',
    'tinymce': 'tinymce',
    'interact': 'interact',
    'spectrum': 'spectrum',
  }
};

module.exports = _.map([adminConfig, publicConfig, migratorConfig, testConfig], function (config) {
  if (config.name !== 'test') {
    config.plugins = config.plugins || [];
    if (PRODUCTION_ENV) {
      config.plugins.push(new webpackUglifyJsPlugin());
    }
    config.plugins.push(
      new webpackMD5HashPlugin(),
      new webpackManifestPlugin({
        cache: manifestCache
      })
    );
  }
  return _.extend({}, baseConfig, config);
});
