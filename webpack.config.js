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
      'asyncqueue': 'vendor/jquery.asyncqueue.js',
      'intro.js': 'intro.js/intro.js',
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
        include: require.resolve('react'),
        loader: 'expose-loader?' + globalPrefix + '.React',
      },
      {
        include: require.resolve('react-dom'),
        loader: 'expose-loader?' + globalPrefix + '.ReactDOM',
      },
      {
        include: require.resolve('react-router-dom'),
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
      'handlebars', // no side effect - this just explicitly requires Handlebars
      'handlebars_helpers', // side effect - extends Handlebars, assigns to window
      'wp-js-hooks' // side effect - assigns to window
    ],
    mailpoet: [
      'mailpoet', // side effect - assigns MailPoet to window
      'jquery.serialize_object', // side effect - extends jQuery
      'parsleyjs', // side effect - extends jQuery
      'dismissible-notice.jsx', // side effect - adds jQuery event
    ],
    admin_vendor: [
      'newsletter_editor/initializer.jsx', // side effect - calls Hooks.addAction()
    ],
    admin: [
      'subscribers/subscribers.jsx', // side effect - renders ReactDOM to document
      'newsletters/newsletters.jsx', // side effect - renders ReactDOM to window
      'segments/segments.jsx', // side effect - renders ReactDOM to document
      'forms/forms.jsx', // side effect - renders ReactDOM to document
      'settings/tabs.js', // side effect - assigns to MailPoet.Router, executes code on doc ready
      'help/help.jsx', // side effect - renders ReactDOM to document
      'intro.jsx', // side effect - assigns to MailPoet.showIntro
      'settings/reinstall_from_scratch.js', // side effect - adds event handler to document
      'subscribers/importExport/import.js', // side effect - executes on doc ready, adds events
      'subscribers/importExport/export.js', // side effect - executes on doc ready
      'welcome_wizard/wizard.jsx', // side effect - renders ReactDOM to document
      'settings/announcement.jsx', // side effect - renders ReactDOM to document
      'nps_poll.jsx' // side effect - calls setImmediate()
    ],
    form_editor: [
      'form_editor/form_editor.js', // side effect - calls document.observe()
      'codemirror', // side effect - has to be loaded here, used in 'editor.html'
      'codemirror/mode/css/css' // side effect - has to be loaded here, used in 'editor.html'
    ],
    newsletter_editor: [
      'sticky-kit', // side effect - extends jQuery
      'velocity-animate', // side effect - assigns to window
      'newsletter_editor/App', // side effect - assigns to window
      'newsletter_editor/components/config.js', // side effect - calls App.on()
      'newsletter_editor/components/styles.js', // side effect - calls App.on()
      'newsletter_editor/components/sidebar.js', // side effect - calls App.on()
      'newsletter_editor/components/content.js', // side effect - calls App.on()
      'newsletter_editor/components/heading.js', // side effect - calls App.on()
      'newsletter_editor/components/save.js', // side effect - calls App.on()
      'newsletter_editor/components/communication.js', // side effect - calls App.on()
      'newsletter_editor/behaviors/BehaviorsLookup.js', // side effect - assings to window and Marionette
      'newsletter_editor/behaviors/ColorPickerBehavior.js', // side effect - assigns to BehaviorsLookup
      'newsletter_editor/behaviors/ContainerDropZoneBehavior.js', // side effect - assigns to BehaviorsLookup
      'newsletter_editor/behaviors/DraggableBehavior.js', // side effect - assigns to BehaviorsLookup
      'newsletter_editor/behaviors/HighlightContainerBehavior.js', // side effect - assigns to BehaviorsLookup
      'newsletter_editor/behaviors/HighlightEditingBehavior.js', // side effect - assigns to BehaviorsLookup
      'newsletter_editor/behaviors/MediaManagerBehavior.js', // side effect - assigns to BehaviorsLookup
      'newsletter_editor/behaviors/ResizableBehavior.js', // side effect - assigns to BehaviorsLookup
      'newsletter_editor/behaviors/SortableBehavior.js', // side effect - assigns to BehaviorsLookup
      'newsletter_editor/behaviors/ShowSettingsBehavior.js', // side effect - assigns to BehaviorsLookup
      'newsletter_editor/behaviors/TextEditorBehavior.js', // side effect - assigns to BehaviorsLookup
      'newsletter_editor/blocks/container.js', // side effect - calls App.on()
      'newsletter_editor/blocks/button.js', // side effect - calls App.on()
      'newsletter_editor/blocks/image.js', // side effect - calls App.on()
      'newsletter_editor/blocks/divider.js', // side effect - calls App.on()
      'newsletter_editor/blocks/text.js', // side effect - calls App.on()
      'newsletter_editor/blocks/spacer.js', // side effect - calls App.on()
      'newsletter_editor/blocks/footer.js', // side effect - calls App.on()
      'newsletter_editor/blocks/header.js', // side effect - calls App.on()
      'newsletter_editor/blocks/automatedLatestContent.js', // side effect - calls App.on()
      'newsletter_editor/blocks/automatedLatestContentLayout.js', // side effect - calls App.on()
      'newsletter_editor/blocks/posts.js', // side effect - calls App.on()
      'newsletter_editor/blocks/social.js' // side effect - calls App.on()
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
      'mailpoet', // side effect - assigns MailPoet to window
      'jquery.serialize_object', // side effect - extends jQuery
      'public.js' // side effect - assigns to window, sets up form validation, etc.
    ]
  },
  plugins: [
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
      'mailpoet',

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
  plugins: [
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
      config.plugins.push(
        new webpackUglifyJsPlugin({
          mangle: false,
        })
      );
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
