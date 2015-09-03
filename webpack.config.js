var webpack = require('webpack'),
    _ = require('underscore'),
    path = require('path'),
    baseConfig = {},
    config = [];

baseConfig = {
  context: __dirname,
  output: {
    path: './assets/js',
    filename: '[name].js',
  },
  resolve: {
    modulesDirectories: [
      'node_modules',
      'assets/js/src'
    ],
    alias: {
      'handlebars': 'handlebars/dist/handlebars.js',
      'backbone.marionette': 'backbone.marionette/lib/backbone.marionette',
      'backbone.supermodel$': 'backbone.supermodel/build/backbone.supermodel.js',
      'sticky-kit': 'sticky-kit/jquery.sticky-kit',
      'interact$': 'interact.js/interact.js',
      'spectrum$': 'spectrum-colorpicker/spectrum.js',
    },
  },
  node: {
    fs: 'empty'
  },
  module: {
    loaders: [
      {
        test: /\.jsx$/,
        loader: 'babel-loader'
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
        include: require.resolve('backbone.supermodel'),
        loader: 'imports-loader?Backbone=backbone!exports-loader?Backbone.SuperModel',
      },
      {
        include: require.resolve('handlebars'),
        loader: 'expose-loader?Handlebars',
      },
    ]
  }
};

// Admin
config.push(_.extend({}, baseConfig, {
  name: 'admin',
  entry: {
    vendor: ['handlebars', 'handlebars_helpers'],
    mailpoet: ['mailpoet', 'ajax', 'modal', 'notice'],
    admin: [
      'settings.jsx',
      'subscribers/subscribers.jsx',
      'newsletters/newsletters.jsx'
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

      'newsletter_editor/communicationsFix.js',
      'newsletter_editor/App',
      'newsletter_editor/components/config.js',
      'newsletter_editor/components/styles.js',
      'newsletter_editor/components/sidebar.js',
      'newsletter_editor/components/content.js',
      'newsletter_editor/components/heading.js',
      'newsletter_editor/components/save.js',
      'newsletter_editor/behaviors/BehaviorsLookup.js',
      'newsletter_editor/behaviors/ColorPickerBehavior.js',
      'newsletter_editor/behaviors/ContainerDropZoneBehavior.js',
      'newsletter_editor/behaviors/DraggableBehavior.js',
      'newsletter_editor/behaviors/ResizableBehavior.js',
      'newsletter_editor/behaviors/SortableBehavior.js',
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
    ],
  },
  plugins: [
    new webpack.optimize.CommonsChunkPlugin('vendor', 'vendor.js'),
  ],
  externals: {
    'jquery': 'jQuery',
    'tinymce': 'tinymce'
  }
}));

// Public
config.push(_.extend({}, baseConfig, {
  name: 'public',
  entry: {
    public: ['mailpoet', 'ajax', 'public.js']
  },
  externals: {
    'jquery': 'jQuery'
  }
}));

// Test
config.push(_.extend({}, baseConfig, {
  name: 'test',
  entry: {
    vendor: ['handlebars', 'handlebars_helpers'],
    testAjax: 'testAjax.js',
    testNewsletterEditor: [
      'underscore',
      'backbone',
      'backbone.marionette',
      'backbone.supermodel',
      'backbone.radio',
      'select2',

      'newsletter_editor/communicationsFix.js',
      'newsletter_editor/App',
      'newsletter_editor/components/config.js',
      'newsletter_editor/components/styles.js',
      'newsletter_editor/components/sidebar.js',
      'newsletter_editor/components/content.js',
      'newsletter_editor/components/heading.js',
      'newsletter_editor/components/save.js',
      'newsletter_editor/behaviors/BehaviorsLookup.js',
      'newsletter_editor/behaviors/ColorPickerBehavior.js',
      'newsletter_editor/behaviors/ContainerDropZoneBehavior.js',
      'newsletter_editor/behaviors/DraggableBehavior.js',
      'newsletter_editor/behaviors/ResizableBehavior.js',
      'newsletter_editor/behaviors/SortableBehavior.js',
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
      'sticky-kit': 'sticky-kit/jquery.sticky-kit',
      'backbone.marionette': 'backbone.marionette/lib/backbone.marionette',
      'backbone.supermodel$': 'backbone.supermodel/build/backbone.supermodel.js',
    },
  },
  externals: {
    'jquery': 'jQuery',
    'tinymce': 'tinymce',
    'interact': 'interact',
    'spectrum': 'spectrum',
  }
}));

module.exports = config;
