define([
    'newsletter_editor/App',
    'newsletter_editor/components/wordpress'
  ], function(EditorApplication, Wordpress) {

  describe('getPostTypes', function() {
    var injector;
    beforeEach(function() {
      injector = require('amd-inject-loader!newsletter_editor/components/wordpress');
    });

    it('fetches post types from the server', function() {
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
        expect(types).to.eql(['val1', 'val2']);
      });
    });

    it('caches results', function() {
      var deferred = jQuery.Deferred(),
          mock = sinon.mock({ post: function() {} }).expects('post').once().returns(deferred),
          module = injector({
            "mailpoet": {
              Ajax: {
                post: mock,
              },
            },
          });
      deferred.resolve({
        'post': 'val1',
        'page': 'val2',
      });
      module.getPostTypes();
      module.getPostTypes();

      mock.verify();
    });
  });

  describe('getTaxonomies', function() {
    var injector;
    beforeEach(function() {
      injector = require('amd-inject-loader!newsletter_editor/components/wordpress');
    });

    it('sends post type to endpoint', function() {
      var spy,
          post = function(params) {
            var deferred = jQuery.Deferred();
            deferred.resolve({
              'category': 'val1',
              'post_tag': 'val2',
            });
            return deferred;
          },
          module;
      spy = sinon.spy(post);
      module = injector({
        "mailpoet": {
          Ajax: {
            post: spy,
          },
        },
      });

      module.getTaxonomies('post');
      expect(spy.args[0][0].data.postType).to.equal('post');
    });

    it('fetches post types from the server', function() {
      var module = injector({
          "mailpoet": {
            Ajax: {
              post: function() {
                var deferred = jQuery.Deferred();
                deferred.resolve({ 'category': 'val1' });
                return deferred;
              }
            },
          },
        });
      module.getTaxonomies('page').done(function(types) {
        expect(types).to.eql({ 'category': 'val1' });
      });
    });

    it('caches results', function() {
      var deferred = jQuery.Deferred(),
          mock = sinon.mock({ post: function() {} }).expects('post').once().returns(deferred),
          module = injector({
            "mailpoet": {
              Ajax: {
                post: mock,
              },
            },
          });
      deferred.resolve({ 'category': 'val1' });
      module.getTaxonomies('page');
      module.getTaxonomies('page');

      mock.verify();
    });
  });

  describe('getTerms', function() {
    var injector;
    beforeEach(function() {
      injector = require('amd-inject-loader!newsletter_editor/components/wordpress');
    });

    it('sends terms to endpoint', function() {
      var spy,
          post = function(params) {
            var deferred = jQuery.Deferred();
            deferred.resolve({});
            return deferred;
          },
          module;
      spy = sinon.spy(post);
      module = injector({
        "mailpoet": {
          Ajax: {
            post: spy,
          },
        },
      });

      module.getTerms({
        taxonomies: ['category', 'post_tag'],
      });
      expect(spy.args[0][0].data.taxonomies).to.eql(['category', 'post_tag']);
    });

    it('fetches terms from the server', function() {
      var module = injector({
          "mailpoet": {
            Ajax: {
              post: function() {
                var deferred = jQuery.Deferred();
                deferred.resolve({ 'term1': 'term1val1', 'term2': 'term2val2' });
                return deferred;
              }
            },
          },
        });
      module.getTerms({ taxonomies: ['category'] }).done(function(types) {
        expect(types).to.eql({ 'term1': 'term1val1', 'term2': 'term2val2' });
      });
    });

    it('caches results', function() {
      var deferred = jQuery.Deferred(),
          mock = sinon.mock({ post: function() {} }).expects('post').once().returns(deferred),
          module = injector({
            "mailpoet": {
              Ajax: {
                post: mock,
              },
            },
          });
      deferred.resolve({ 'term1': 'term1val1', 'term2': 'term2val2' });
      module.getTerms({ taxonomies: ['category'] });
      module.getTerms({ taxonomies: ['category'] });

      mock.verify();
    });
  });
});
