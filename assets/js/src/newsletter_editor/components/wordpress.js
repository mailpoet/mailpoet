define('newsletter_editor/components/wordpress', [
    'newsletter_editor/App',
    'backbone',
    'backbone.marionette',
    'mailpoet',
    'ajax'
  ], function(EditorApplication, Backbone, Marionette, MailPoet) {

  EditorApplication.module("components.wordpress", function(Module, App, Backbone, Marionette, $, _) {
    "use strict";

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

    Module.getTerms = function(taxonomies) {
      if (!termsCache[taxonomies]) {
        termsCache[taxonomies] = MailPoet.Ajax.post({
          endpoint: 'wordpress',
          action: 'getTerms',
          data: {
            taxonomies: taxonomies,
          },
        });
      }

      return termsCache[taxonomies];
    };

    App.on('start', function(options) {
      // Prefetch post types
      Module.getPostTypes();
    });
  });
});
