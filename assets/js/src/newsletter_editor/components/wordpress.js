define([
    'newsletter_editor/App',
    'mailpoet',
    'ajax'
  ], function(EditorApplication, MailPoet) {

  var Module = {};
  var postTypesCache,
      taxonomiesCache = [],
      termsCache = [];

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

  EditorApplication.on('start', function(options) {
    // Prefetch post types
    Module.getPostTypes();
  });

  return Module;
});
