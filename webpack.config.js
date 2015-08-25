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
    }
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
      'subscribers/listing.jsx',
      'settings.jsx',
      'subscribers.jsx',
      'newsletters/newsletters.jsx',
      'newsletters/list.jsx',
      'newsletters/form.jsx'
    ],
    newsletter_editor: [
      'underscore',
      'backbone',
      'backbone.marionette',
      'backbone.supermodel',
      'interact.js',
      'backbone.radio',
      'select2',
      'spectrum-colorpicker',
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
    testAjax: 'testAjax.js',
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
    ]
  }
}));

module.exports = config;
