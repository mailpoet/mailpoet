var amdInjectLoader = require('amd-inject-loader');

// fix original 'amd-inject-loader' for Webpack 4 by adding missing 'options' field to its context
module.exports = function amdInjectLoaderFixed(input) {
  return amdInjectLoader.call(Object.assign({}, this, { options: {} }), input);
};
