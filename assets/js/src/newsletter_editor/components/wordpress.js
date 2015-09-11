define([
    'newsletter_editor/App',
    'underscore',
    'mailpoet',
    'ajax'
  ], function(App, _, MailPoet) {

  var Module = {};
  var postTypesCache,
      taxonomiesCache = {},
      termsCache = {},
      postsCache = {},
      transformedPostsCache = {};

  Module.getPostTypes = function() {
    if (!postTypesCache) {
      postTypesCache = MailPoet.Ajax.post({
        endpoint: 'wordpress',
        action: 'getPostTypes',
        data: {},
      }).then(function(types) {
        return _.values(types);
      });
    }

    return postTypesCache;
  };

  Module.getTaxonomies = function(postType) {
    if (!taxonomiesCache[postType]) {
      taxonomiesCache[postType] = MailPoet.Ajax.post({
        endpoint: 'wordpress',
        action: 'getTaxonomies',
        data: {
          postType: postType,
        },
      });
    }

    return taxonomiesCache[postType];
  };

  Module.getTerms = function(options) {
    var key = JSON.stringify(options);
    if (!termsCache[key]) {
      termsCache[key] = MailPoet.Ajax.post({
        endpoint: 'wordpress',
        action: 'getTerms',
        data: options || {},
      });
    }

    return termsCache[key];
  };

  Module.getPosts = function(options) {
    var key = JSON.stringify(options);
    if (!postsCache[key]) {
      postsCache[key] = MailPoet.Ajax.post({
        endpoint: 'wordpress',
        action: 'getPosts',
        data: options || {},
      });
    }

    return postsCache[key];
  };

  Module.getTransformedPosts = function(options) {
    var key = JSON.stringify(options);
    if (!transformedPostsCache[key]) {
      transformedPostsCache[key] = MailPoet.Ajax.post({
        endpoint: 'wordpress',
        action: 'getTransformedPosts',
        data: options || {},
      });
    }

    return transformedPostsCache[key];
  };

  App.on('start', function(options) {
    // Prefetch post types
    Module.getPostTypes();
  });

  return Module;
});
