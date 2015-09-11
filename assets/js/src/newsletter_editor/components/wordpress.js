define([
    'newsletter_editor/App',
    'underscore',
    'mailpoet',
    'ajax'
  ], function(App, _, MailPoet) {

  var Module = {};

  Module._cachedQuery = _.memoize(function(args) {
    return MailPoet.Ajax.post({
      endpoint: 'wordpress',
      action: args.action,
      data: args.options || {},
    });
  }, JSON.stringify);

  Module.getPostTypes = function() {
    return Module._cachedQuery({
      action: 'getPostTypes',
      options: {},
    }).then(function(types) {
      return _.values(types);
    });
  };

  Module.getTaxonomies = function(postType) {
    return Module._cachedQuery({
      action: 'getTaxonomies',
      options: {
        postType: postType,
      },
    });
  };

  Module.getTerms = function(options) {
    return Module._cachedQuery({
      action: 'getTerms',
      options: options,
    });
  };

  Module.getPosts = function(options) {
    return Module._cachedQuery({
      action: 'getPosts',
      options: options,
    });
  };

  Module.getTransformedPosts = function(options) {
    return Module._cachedQuery({
      action: 'getTransformedPosts',
      options: options,
    });
  };

  App.on('start', function(options) {
    // Prefetch post types
    Module.getPostTypes();
  });

  return Module;
});
