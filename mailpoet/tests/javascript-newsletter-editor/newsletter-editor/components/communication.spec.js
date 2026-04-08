import { CommunicationComponent } from 'newsletter-editor/components/communication';
import { MailPoet } from 'mailpoet';

const expect = global.expect;
const jQuery = global.jQuery;
const sinon = global.sinon;

describe('getPostTypes', function () {
  var originalPost;

  beforeEach(function () {
    CommunicationComponent._cachedQuery.cache = {};
  });

  it('fetches post types from the server', function () {
    originalPost = MailPoet.Ajax.post;
    MailPoet.Ajax.post = function () {
      var deferred = jQuery.Deferred();
      deferred.resolve({
        data: {
          post: 'val1',
          page: 'val2',
        },
      });
      return deferred;
    };
    CommunicationComponent.getPostTypes().done(function (types) {
      expect(types).to.eql(['val1', 'val2']);
    });
    MailPoet.Ajax.post = originalPost;
  });

  it('caches results', function () {
    var deferred = jQuery.Deferred();
    var mock = sinon
      .mock({ post: function () {} })
      .expects('post')
      .once()
      .returns(deferred);
    originalPost = MailPoet.Ajax.post;
    MailPoet.Ajax.post = mock;
    deferred.resolve({
      post: 'val1',
      page: 'val2',
    });
    CommunicationComponent.getPostTypes();
    CommunicationComponent.getPostTypes();

    mock.verify();
    MailPoet.Ajax.post = originalPost;
  });
});

describe('getTaxonomies', function () {
  var originalPost;

  beforeEach(function () {
    CommunicationComponent._cachedQuery.cache = {};
  });

  it('sends post type to endpoint', function () {
    var spy;
    var post = function () {
      var deferred = jQuery.Deferred();
      deferred.resolve({
        category: 'val1',
        post_tag: 'val2',
      });
      return deferred;
    };
    spy = sinon.spy(post);
    originalPost = MailPoet.Ajax.post;
    MailPoet.Ajax.post = spy;

    CommunicationComponent.getTaxonomies('post');
    expect(spy.args[0][0].data.postType).to.equal('post');
    MailPoet.Ajax.post = originalPost;
  });

  it('fetches taxonomies from the server', function () {
    originalPost = MailPoet.Ajax.post;
    MailPoet.Ajax.post = function () {
      var deferred = jQuery.Deferred();
      deferred.resolve({
        data: {
          category: 'val1',
        },
      });
      return deferred;
    };
    CommunicationComponent.getTaxonomies('page').done(function (types) {
      expect(types).to.eql({ category: 'val1' });
    });
    MailPoet.Ajax.post = originalPost;
  });

  it('caches results', function () {
    var deferred = jQuery.Deferred();
    var mock = sinon
      .mock({ post: function () {} })
      .expects('post')
      .once()
      .returns(deferred);
    originalPost = MailPoet.Ajax.post;
    MailPoet.Ajax.post = mock;
    deferred.resolve({ category: 'val1' });
    CommunicationComponent.getTaxonomies('page');
    CommunicationComponent.getTaxonomies('page');

    mock.verify();
    MailPoet.Ajax.post = originalPost;
  });
});

describe('getTerms', function () {
  var originalPost;

  beforeEach(function () {
    CommunicationComponent._cachedQuery.cache = {};
  });

  it('sends terms to endpoint', function () {
    var spy;
    var post = function () {
      var deferred = jQuery.Deferred();
      deferred.resolve({});
      return deferred;
    };
    spy = sinon.spy(post);
    originalPost = MailPoet.Ajax.post;
    MailPoet.Ajax.post = spy;

    CommunicationComponent.getTerms({
      taxonomies: ['category', 'post_tag'],
    });
    expect(spy.args[0][0].data.taxonomies).to.eql(['category', 'post_tag']);
    MailPoet.Ajax.post = originalPost;
  });

  it('fetches terms from the server', function () {
    originalPost = MailPoet.Ajax.post;
    MailPoet.Ajax.post = function () {
      var deferred = jQuery.Deferred();
      deferred.resolve({
        data: {
          term1: 'term1val1',
          term2: 'term2val2',
        },
      });
      return deferred;
    };
    CommunicationComponent.getTerms({ taxonomies: ['category'] }).done(
      function (types) {
        expect(types).to.eql({ term1: 'term1val1', term2: 'term2val2' });
      },
    );
    MailPoet.Ajax.post = originalPost;
  });

  it('caches results', function () {
    var deferred = jQuery.Deferred();
    var mock = sinon
      .mock({ post: function () {} })
      .expects('post')
      .once()
      .returns(deferred);
    originalPost = MailPoet.Ajax.post;
    MailPoet.Ajax.post = mock;
    deferred.resolve({ term1: 'term1val1', term2: 'term2val2' });
    CommunicationComponent.getTerms({ taxonomies: ['category'] });
    CommunicationComponent.getTerms({ taxonomies: ['category'] });

    mock.verify();
    MailPoet.Ajax.post = originalPost;
  });
});

describe('getPosts', function () {
  var originalPost;

  beforeEach(function () {
    CommunicationComponent._cachedQuery.cache = {};
  });

  it('sends options to endpoint', function () {
    var spy;
    var post = function () {
      var deferred = jQuery.Deferred();
      deferred.resolve({});
      return deferred;
    };
    spy = sinon.spy(post);
    originalPost = MailPoet.Ajax.post;
    MailPoet.Ajax.post = spy;

    CommunicationComponent.getPosts({
      type: 'posts',
      search: 'some search term',
    });
    expect(spy.args[0][0].data).to.eql({
      type: 'posts',
      search: 'some search term',
    });
    MailPoet.Ajax.post = originalPost;
  });

  it('fetches posts from the server', function () {
    originalPost = MailPoet.Ajax.post;
    MailPoet.Ajax.post = function () {
      var deferred = jQuery.Deferred();
      deferred.resolve({
        data: [{ post_title: 'title 1' }, { post_title: 'post title 2' }],
      });
      return deferred;
    };
    CommunicationComponent.getPosts().done(function (posts) {
      expect(posts).to.eql([
        { post_title: 'title 1' },
        { post_title: 'post title 2' },
      ]);
    });
    MailPoet.Ajax.post = originalPost;
  });

  it('caches results', function () {
    var deferred = jQuery.Deferred();
    var mock = sinon
      .mock({ post: function () {} })
      .expects('post')
      .once()
      .returns(deferred);
    originalPost = MailPoet.Ajax.post;
    MailPoet.Ajax.post = mock;
    deferred.resolve({
      type: 'posts',
      search: 'some search term',
    });
    CommunicationComponent.getPosts({});
    CommunicationComponent.getPosts({});

    mock.verify();
    MailPoet.Ajax.post = originalPost;
  });
});

describe('getTransformedPosts', function () {
  var originalPost;

  beforeEach(function () {
    CommunicationComponent._cachedQuery.cache = {};
  });

  it('sends options to endpoint', function () {
    var spy;
    var post = function () {
      var deferred = jQuery.Deferred();
      deferred.resolve({});
      return deferred;
    };
    spy = sinon.spy(post);
    originalPost = MailPoet.Ajax.post;
    MailPoet.Ajax.post = spy;

    CommunicationComponent.getTransformedPosts({
      type: 'posts',
      posts: [1, 2],
    });
    expect(spy.args[0][0].data).to.eql({
      type: 'posts',
      posts: [1, 2],
    });
    MailPoet.Ajax.post = originalPost;
  });

  it('fetches transformed posts from the server', function () {
    originalPost = MailPoet.Ajax.post;
    MailPoet.Ajax.post = function () {
      var deferred = jQuery.Deferred();
      deferred.resolve({
        data: [
          { type: 'text', text: 'something' },
          { type: 'text', text: 'something else' },
        ],
      });
      return deferred;
    };
    CommunicationComponent.getTransformedPosts().done(function (posts) {
      expect(posts).to.eql([
        { type: 'text', text: 'something' },
        { type: 'text', text: 'something else' },
      ]);
    });
    MailPoet.Ajax.post = originalPost;
  });

  it('caches results', function () {
    var deferred = jQuery.Deferred();
    var mock = sinon
      .mock({ post: function () {} })
      .expects('post')
      .once()
      .returns(deferred);
    originalPost = MailPoet.Ajax.post;
    MailPoet.Ajax.post = mock;
    deferred.resolve({
      type: 'posts',
      posts: [1, 3],
    });
    CommunicationComponent.getTransformedPosts({});
    CommunicationComponent.getTransformedPosts({});

    mock.verify();
    MailPoet.Ajax.post = originalPost;
  });
});
