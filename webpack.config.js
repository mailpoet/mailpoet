var webpack = require('webpack');
var webpackManifestPlugin = require('webpack-manifest-plugin');
var webpackMD5HashPlugin = require('webpack-md5-hash');
var webpackCleanPlugin = require('clean-webpack-plugin');
var _ = require('underscore');
var path = require('path');
var globalPrefix = 'MailPoetLib';
var PRODUCTION_ENV = process.env.NODE_ENV === 'production';
var manifestCache = {};

// Base config
var baseConfig = {
  cache: true,
  context: __dirname,
  watch: {
    aggregateTimeout: 300,
    poll: true
  },
  output: {
    path: './assets/js',
    filename: (PRODUCTION_ENV) ? '[name].[chunkhash:8].js' : '[name].js',
    chunkFilename: (PRODUCTION_ENV) ? '[name].[chunkhash:8].chunk.js' : '[name].chunk.js'
  },
  resolve: {
    modulesDirectories: [
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
    preLoaders: [
      { test: /\.json$/, loader: "json-loader" },
    ],
    loaders: [
      {
        test: /\.jsx$/,
        loader: 'babel-loader'
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
        loader: 'expose-loader?' + globalPrefix + '.ReactRouter',
      },
      {
        include: require.resolve('react-string-replace'),
        loader: 'expose-loader?' + globalPrefix + '.ReactStringReplace',
      },
      {
        test: /wp-js-hooks/i,
        loader: 'expose-loader?' + globalPrefix + '.Hooks!exports-loader?wp.hooks',
      },
      {
        test: /listing.jsx/i,
        loader: 'expose-loader?' + globalPrefix + '.Listing!babel-loader',
      },
      {
        test: /form.jsx/i,
        loader: 'expose-loader?' + globalPrefix + '.Form!babel-loader',
      },
      {
        include: path.resolve(__dirname, 'assets/js/src/form/fields/selection.jsx'),
        loader: 'expose-loader?' + globalPrefix + '.FormFieldSelection!babel-loader',
      },
      {
        include: path.resolve(__dirname, 'assets/js/src/form/fields/text.jsx'),
        loader: 'expose-loader?' + globalPrefix + '.FormFieldText!babel-loader',
      },
      {
        include: path.resolve(__dirname, 'assets/js/src/newsletters/scheduling/common.jsx'),
        loader: 'expose-loader?' + globalPrefix + '.NewsletterSchedulingCommonOptions!babel-loader',
      },
      {
        include: path.resolve(__dirname, 'assets/js/src/newsletters/badges/stats.jsx'),
        loader: 'expose-loader?' + globalPrefix + '.StatsBadge!babel-loader',
      },
      {
        include: path.resolve(__dirname, 'assets/js/src/newsletters/types/welcome/scheduling.jsx'),
        loader: 'expose-loader?' + globalPrefix + '.NewsletterWelcomeNotificationScheduling!babel-loader',
      },
      {
        include: path.resolve(__dirname, 'assets/js/src/newsletters/breadcrumb.jsx'),
        loader: 'expose-loader?' + globalPrefix + '.NewsletterCreationBreadcrumb!babel-loader',
      },
      {
        include: path.resolve(__dirname, 'assets/js/src/newsletters/types/automatic_emails/events_list.jsx'),
        loader: 'expose-loader?' + globalPrefix + '.AutomaticEmailEventsList!babel-loader',
      },
      {
        include: path.resolve(__dirname, 'assets/js/src/newsletters/types/automatic_emails/breadcrumb.jsx'),
        loader: 'expose-loader?' + globalPrefix + '.AutomaticEmailsBreadcrumb!babel-loader',
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
    ],
    admin_vendor: [
      'react',
      'react-dom',
      'react-router',
      'react-string-replace',
      'listing/listing.jsx',
      'form/form.jsx',
      'newsletters/badges/stats.jsx',
      'newsletters/breadcrumb.jsx',
      'newsletters/types/automatic_emails/events_list.jsx',
      'newsletters/types/automatic_emails/breadcrumb.jsx',
      'newsletters/types/welcome/scheduling.jsx',
      'history',
    ],
    admin: [
      'subscribers/subscribers.jsx',
      'newsletters/newsletters.jsx',
      'segments/segments.jsx',
      'forms/forms.jsx',
      'settings/tabs.js',
      'help/help.jsx',
      'settings/reinstall_from_scratch.js',
      'subscribers/importExport/import.js',
      'subscribers/importExport/export.js',
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
      'newsletter_editor/communicationsFix.js',
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

      'newsletter_editor/communicationsFix.js',
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
    path: './tests/javascript/testBundles',
    filename: '[name].js',
  },
  resolve: {
    modulesDirectories: [
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
    config.plugins.push(
      new webpackMD5HashPlugin(),
      new webpackManifestPlugin({
        cache: manifestCache
      })
    );
  }
  return _.extend({}, baseConfig, config);
});
