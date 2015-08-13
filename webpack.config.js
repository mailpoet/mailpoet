var path = require('path'),
    fs = require('fs'),
    webpack = require("webpack"),
    _ = require('underscore'),
    baseConfig;

baseConfig = {
  name: 'main',
  context: __dirname ,
  entry: {
    vendor: ['handlebars', 'handlebars_helpers'],
    mailpoet: ['mailpoet', 'ajax', 'modal', 'notice'],
    admin: 'admin.js',
  },
  output: {
    path: './assets/js/src',
    filename: '[name].js',
  },
  plugins: [
    new webpack.optimize.CommonsChunkPlugin(/* chunkName= */"vendor", /* filename= */"vendor.js")
  ],
  loaders: [
    {
      test: /\.js$/i,
      loader: 'js'
    },
    {
      test: /\.css$/i,
      loader: 'css'
    },
    {
      test: /\.jpe?g$|\.gif$|\.png$|\.svg$|\.woff$|\.ttf$|\.wav$|\.mp3$/i,
      loader: 'file'
    }
  ],
  resolve: {
    modulesDirectories: [
      'node_modules',
      'assets/js',
      'assets/css/lib'
    ],
    fallback: path.join(__dirname, 'node_modules'),
    alias: {
      'handlebars': 'handlebars/runtime.js'
    }
  },
  resolveLoader: {
    fallback: path.join(__dirname, 'node_modules'),
    alias: {
      'hbs': 'handlebars-loader'
    }
  },
  externals: {
    'jquery': 'jQuery',
  }
};

module.exports = [
  baseConfig,

  // Configuration specific for testing
  _.extend({}, baseConfig, {
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
        'assets/js',
        'tests/javascript/newsletter_editor'
      ],
      fallback: path.join(__dirname, 'node_modules'),
      alias: {
        'handlebars': 'handlebars/runtime.js'
      }
    },
  })
];
