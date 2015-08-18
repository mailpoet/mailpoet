var webpack = require('webpack');

module.exports = [
  {
    context: __dirname,
    entry: {
      vendor: ['handlebars', 'handlebars_helpers'],
      mailpoet: ['mailpoet', 'ajax', 'modal', 'notice'],
      admin: 'admin.js',
      //settings: 'settings.jsx'
    },
    output: {
      path: './assets/js',
      filename: '[name].js',
    },
    module: {
      loaders: [
      {
        test: /\.jsx$/,
        loader: 'babel-loader'
      }
      ]
    },
    plugins: [
      new webpack.optimize.CommonsChunkPlugin('vendor', 'vendor.js')
    ],
    externals: {
      'jquery': 'jQuery'
    },
    resolve: {
      modulesDirectories: [
        'node_modules',
        'assets/js/src'
      ],
    }
  },
  {
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
  }
];