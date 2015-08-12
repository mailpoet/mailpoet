var path = require('path'),
    fs = require('fs');

// webpack.config.js
module.exports = {
  context: __dirname ,
  entry: {
    mailpoet: './assets/js/mailpoet',
  },
  output: {
    path: './assets/js/src',
    filename: '[name].js',
  },
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
  }
};
