const expect = global.expect;
const jQuery = global.jQuery;
const sinon = global.sinon;

define([
  'newsletter_editor/App',
  'newsletter_editor/components/communication',
  'amd-inject-loader!newsletter_editor/components/communication'
], function (EditorApplication, Communication, CommunicationInjector) {

  describe('getPostTypes', function () {
    it('fetches post types from the server', function () {
      var module = CommunicationInjector({
        mailpoet: {
          Ajax: {
            post: function () {
              var deferred = jQuery.Deferred();
              deferred.resolve({
                data: {
                  post: 'val1',
                  page: 'val2'
                }
              });
              return deferred;
            }
          }
        }
      });
      module.getPostTypes().done(function (types) {
        expect(types).to.eql(['val1', 'val2']);
      });
    });

    it('caches results', function () {
      var deferred = jQuery.Deferred();
      var mock = sinon.mock({ post: function () {} }).expects('post').once().returns(deferred);
      var module = CommunicationInjector({
        mailpoet: {
          Ajax: {
            post: mock
          }
        }
      });
      deferred.resolve({
        post: 'val1',
        page: 'val2'
      });
      module.getPostTypes();
      module.getPostTypes();

      mock.verify();
    });
  });

  describe('getTaxonomies', function () {
    it('sends post type to endpoint', function () {
      var spy;
      var post = function () {
        var deferred = jQuery.Deferred();
        deferred.resolve({
          category: 'val1',
          post_tag: 'val2'
        });
        return deferred;
      };
      var module;
      spy = sinon.spy(post);
      module = CommunicationInjector({
        mailpoet: {
          Ajax: {
            post: spy
          }
        }
      });

      module.getTaxonomies('post');
      expect(spy.args[0][0].data.postType).to.equal('post');
    });

    it('fetches taxonomies from the server', function () {
      var module = CommunicationInjector({
        mailpoet: {
          Ajax: {
            post: function () {
              var deferred = jQuery.Deferred();
              deferred.resolve({
                data: {
                  category: 'val1'
                }
              });
              return deferred;
            }
          }
        }
      });
      module.getTaxonomies('page').done(function (types) {
        expect(types).to.eql({ category: 'val1' });
      });
    });

    it('caches results', function () {
      var deferred = jQuery.Deferred();
      var mock = sinon.mock({ post: function () {} }).expects('post').once().returns(deferred);
      var module = CommunicationInjector({
        mailpoet: {
          Ajax: {
            post: mock
          }
        }
      });
      deferred.resolve({ category: 'val1' });
      module.getTaxonomies('page');
      module.getTaxonomies('page');

      mock.verify();
    });
  });

  describe('getTerms', function () {
    it('sends terms to endpoint', function () {
      var spy;
      var post = function () {
        var deferred = jQuery.Deferred();
        deferred.resolve({});
        return deferred;
      };
      var module;
      spy = sinon.spy(post);
      module = CommunicationInjector({
        mailpoet: {
          Ajax: {
            post: spy
          }
        }
      });

      module.getTerms({
        taxonomies: ['category', 'post_tag']
      });
      expect(spy.args[0][0].data.taxonomies).to.eql(['category', 'post_tag']);
    });

    it('fetches terms from the server', function () {
      var module = CommunicationInjector({
        mailpoet: {
          Ajax: {
            post: function () {
              var deferred = jQuery.Deferred();
              deferred.resolve({
                data: {
                  term1: 'term1val1',
                  term2: 'term2val2'
                }
              });
              return deferred;
            }
          }
        }
      });
      module.getTerms({ taxonomies: ['category'] }).done(function (types) {
        expect(types).to.eql({ term1: 'term1val1', term2: 'term2val2' });
      });
    });

    it('caches results', function () {
      var deferred = jQuery.Deferred();
      var mock = sinon.mock({ post: function () {} }).expects('post').once().returns(deferred);
      var module = CommunicationInjector({
        mailpoet: {
          Ajax: {
            post: mock
          }
        }
      });
      deferred.resolve({ term1: 'term1val1', term2: 'term2val2' });
      module.getTerms({ taxonomies: ['category'] });
      module.getTerms({ taxonomies: ['category'] });

      mock.verify();
    });
  });

  describe('getPosts', function () {
    it('sends options to endpoint', function () {
      var spy;
      var post = function () {
        var deferred = jQuery.Deferred();
        deferred.resolve({});
        return deferred;
      };
      var module;
      spy = sinon.spy(post);
      module = CommunicationInjector({
        mailpoet: {
          Ajax: {
            post: spy
          }
        }
      });

      module.getPosts({
        type: 'posts',
        search: 'some search term'
      });
      expect(spy.args[0][0].data).to.eql({
        type: 'posts',
        search: 'some search term'
      });
    });

    it('fetches posts from the server', function () {
      var module = CommunicationInjector({
        mailpoet: {
          Ajax: {
            post: function () {
              var deferred = jQuery.Deferred();
              deferred.resolve({
                data: [
                    { post_title: 'title 1' },
                    { post_title: 'post title 2' }
                ]
              });
              return deferred;
            }
          }
        }
      });
      module.getPosts().done(function (posts) {
        expect(posts).to.eql([{ post_title: 'title 1' }, { post_title: 'post title 2' }]);
      });
    });

    it('caches results', function () {
      var deferred = jQuery.Deferred();
      var mock = sinon.mock({ post: function () {} }).expects('post').once().returns(deferred);
      var module = CommunicationInjector({
        mailpoet: {
          Ajax: {
            post: mock
          }
        }
      });
      deferred.resolve({
        type: 'posts',
        search: 'some search term'
      });
      module.getPosts({});
      module.getPosts({});

      mock.verify();
    });
  });

  describe('getTransformedPosts', function () {
    it('sends options to endpoint', function () {
      var spy;
      var post = function () {
        var deferred = jQuery.Deferred();
        deferred.resolve({});
        return deferred;
      };
      var module;
      spy = sinon.spy(post);
      module = CommunicationInjector({
        mailpoet: {
          Ajax: {
            post: spy
          }
        }
      });

      module.getTransformedPosts({
        type: 'posts',
        posts: [1, 2]
      });
      expect(spy.args[0][0].data).to.eql({
        type: 'posts',
        posts: [1, 2]
      });
    });

    it('fetches transformed posts from the server', function () {
      var module = CommunicationInjector({
        mailpoet: {
          Ajax: {
            post: function () {
              var deferred = jQuery.Deferred();
              deferred.resolve({
                data: [
                    { type: 'text', text: 'something' },
                    { type: 'text', text: 'something else' }
                ]
              });
              return deferred;
            }
          }
        }
      });
      module.getTransformedPosts().done(function (posts) {
        expect(posts).to.eql([{ type: 'text', text: 'something' }, { type: 'text', text: 'something else' }]);
      });
    });

    it('caches results', function () {
      var deferred = jQuery.Deferred();
      var mock = sinon.mock({ post: function () {} }).expects('post').once().returns(deferred);
      var module = CommunicationInjector({
        mailpoet: {
          Ajax: {
            post: mock
          }
        }
      });
      deferred.resolve({
        type: 'posts',
        posts: [1, 3]
      });
      module.getTransformedPosts({});
      module.getTransformedPosts({});

      mock.verify();
    });
  });
});
