define([
    'newsletter_editor/App',
    'newsletter_editor/components/wordpress'
  ], function(EditorApplication, Wordpress) {

  describe('getPostTypes', function() {
    it('fetches post types from the server', function() {
      var injector = require('amd-inject-loader!newsletter_editor/components/wordpress');
      var module = injector({
        "mailpoet": {
          Ajax: {
            post: function() {
              var deferred = jQuery.Deferred();
              deferred.resolve({
                'post': 'val1',
                'page': 'val2',
              });
              return deferred;
            }
          },
        },
      });
      module.getPostTypes().done(function(types) {
        expect(types).to.include.members(['val1', 'val2']);
      });
    });
  });
});
