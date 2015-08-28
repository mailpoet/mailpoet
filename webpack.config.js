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
    ]
  },
  node: {
    fs: 'empty'
  },
  module: {
    loaders: [
      {
        test: /\.jsx$/,
        loader: 'babel-loader'
      }
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
    ]
  },
  plugins: [
    new webpack.optimize.CommonsChunkPlugin('vendor', 'vendor.js'),
  ],
  externals: {
    'jquery': 'jQuery'
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
