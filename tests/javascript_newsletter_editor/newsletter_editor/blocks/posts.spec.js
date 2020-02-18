import App from 'newsletter_editor/App';
import Communication from 'newsletter_editor/components/communication';
import PostsBlock from 'newsletter_editor/blocks/posts';
import ContainerBlock from 'newsletter_editor/blocks/container';

const expect = global.expect;
const sinon = global.sinon;
const Backbone = global.Backbone;
const jQuery = global.jQuery;

var EditorApplication = App;
var CommunicationComponent = Communication;

describe('Posts', function () {
  Backbone.Radio = {
    Requests: {
      request: function () {
      },
      reply: function () {
      },
    },
  };
  describe('model', function () {
    var model;
    var sandbox;

    before(function () {
      CommunicationComponent.getPosts = function () {
        var deferred = jQuery.Deferred();
        return deferred;
      };
    });

    beforeEach(function () {
      global.stubChannel(EditorApplication);
      global.stubConfig(EditorApplication);
      EditorApplication.getBlockTypeModel = sinon.stub().returns(Backbone.SuperModel);
      EditorApplication.getBlockTypeView = sinon.stub().returns(Backbone.View);
      model = new (PostsBlock.PostsBlockModel)();
      sandbox = sinon.createSandbox();
    });

    afterEach(function () {
      delete EditorApplication.getChannel;
      sandbox.restore();
    });

    it('has posts type', function () {
      expect(model.get('type')).to.equal('posts');
    });

    it('has post amount limit', function () {
      expect(model.get('amount')).to.match(/^\d+$/);
    });

    it('has post offset initialized', function () {
      expect(model.get('offset')).to.equal(0);
    });

    it('has post type filter', function () {
      expect(model.get('contentType')).to.match(/^(post|page|mailpoet_page)$/);
    });

    it('has terms filter', function () {
      expect(model.get('terms')).to.have.length(0);
    });

    it('has inclusion filter', function () {
      expect(model.get('inclusionType')).to.match(/^(include|exclude)$/);
    });

    it('has display type', function () {
      expect(model.get('displayType')).to.match(/^(excerpt|full|titleOnly)$/);
    });

    it('has title heading format', function () {
      expect(model.get('titleFormat')).to.match(/^(h1|h2|h3|ul)$/);
    });

    it('has title alignment', function () {
      expect(model.get('titleAlignment')).to.match(/^(left|center|right)$/);
    });

    it('optionally has title as link', function () {
      expect(model.get('titleIsLink')).to.be.a('boolean');
    });

    it('has image width', function () {
      expect(model.get('imageFullWidth')).to.be.a('boolean');
    });

    it('has featured image position', function () {
      expect(model.get('featuredImagePosition')).to.match(/^(centered|left|right|alternate|none)$/);
    });

    it('has an option to display author', function () {
      expect(model.get('showAuthor')).to.match(/^(no|aboveText|belowText)$/);
    });

    it('has text preceding author', function () {
      expect(model.get('authorPrecededBy')).to.be.a('string');
    });

    it('has an option to display categories', function () {
      expect(model.get('showCategories')).to.match(/^(no|aboveText|belowText)$/);
    });

    it('has text preceding categories', function () {
      expect(model.get('categoriesPrecededBy')).to.be.a('string');
    });

    it('has a link or a button type for read more', function () {
      expect(model.get('readMoreType')).to.match(/^(link|button)$/);
    });

    it('has read more text', function () {
      expect(model.get('readMoreText')).to.be.a('string');
    });

    it('has a read more button', function () {
      expect(model.get('readMoreButton')).to.be.instanceof(Backbone.Model);
    });

    it('has sorting', function () {
      expect(model.get('sortBy')).to.match(/^(newest|oldest)$/);
    });

    it('has an option to display divider', function () {
      expect(model.get('showDivider')).to.be.a('boolean');
    });

    it('has a divider', function () {
      expect(model.get('divider')).to.be.instanceof(Backbone.Model);
    });

    it('uses defaults from config when they are set', function () {
      var innerModel;
      global.stubConfig(EditorApplication, {
        blockDefaults: {
          posts: {
            amount: '17',
            contentType: 'mailpoet_page', // 'post'|'page'|'mailpoet_page'
            inclusionType: 'exclude', // 'include'|'exclude'
            displayType: 'full', // 'excerpt'|'full'|'titleOnly'
            titleFormat: 'h3', // 'h1'|'h2'|'h3'|'ul'
            titleAlignment: 'right', // 'left'|'center'|'right'
            titleIsLink: true, // false|true
            imageFullWidth: false, // true|false
            featuredImagePosition: 'aboveTitle',
            showAuthor: 'belowText', // 'no'|'aboveText'|'belowText'
            authorPrecededBy: 'Custom config author preceded by',
            showCategories: 'belowText', // 'no'|'aboveText'|'belowText'
            categoriesPrecededBy: 'Custom config categories preceded by',
            readMoreType: 'button', // 'link'|'button'
            readMoreText: 'Custom Config read more text',
            readMoreButton: {
              text: 'Custom config read more',
              url: '[postLink]',
              styles: {
                block: {
                  backgroundColor: '#123456',
                  borderColor: '#234567',
                },
                link: {
                  fontColor: '#345678',
                  fontFamily: 'Tahoma',
                  fontSize: '37px',
                },
              },
            },
            sortBy: 'oldest', // 'newest'|'oldest',
            showDivider: true, // true|false
            divider: {
              src: 'http://example.org/someConfigDividerImage.png',
              styles: {
                block: {
                  backgroundColor: '#456789',
                  padding: '38px',
                },
              },
            },
          },
        },
      });
      innerModel = new (PostsBlock.PostsBlockModel)();

      expect(innerModel.get('amount')).to.equal('17');
      expect(innerModel.get('contentType')).to.equal('mailpoet_page');
      expect(innerModel.get('inclusionType')).to.equal('exclude');
      expect(innerModel.get('displayType')).to.equal('full');
      expect(innerModel.get('titleFormat')).to.equal('h3');
      expect(innerModel.get('titleAlignment')).to.equal('right');
      expect(innerModel.get('titleIsLink')).to.equal(true);
      expect(innerModel.get('imageFullWidth')).to.equal(false);
      expect(innerModel.get('featuredImagePosition')).to.equal('aboveTitle');
      expect(innerModel.get('showAuthor')).to.equal('belowText');
      expect(innerModel.get('authorPrecededBy')).to.equal('Custom config author preceded by');
      expect(innerModel.get('showCategories')).to.equal('belowText');
      expect(innerModel.get('categoriesPrecededBy')).to.equal('Custom config categories preceded by');
      expect(innerModel.get('readMoreType')).to.equal('button');
      expect(innerModel.get('readMoreText')).to.equal('Custom Config read more text');
      expect(innerModel.get('readMoreButton.text')).to.equal('Custom config read more');
      expect(innerModel.get('readMoreButton.url')).to.equal('[postLink]');
      expect(innerModel.get('readMoreButton.styles.block.backgroundColor')).to.equal('#123456');
      expect(innerModel.get('readMoreButton.styles.block.borderColor')).to.equal('#234567');
      expect(innerModel.get('readMoreButton.styles.link.fontColor')).to.equal('#345678');
      expect(innerModel.get('readMoreButton.styles.link.fontFamily')).to.equal('Tahoma');
      expect(innerModel.get('readMoreButton.styles.link.fontSize')).to.equal('37px');
      expect(innerModel.get('sortBy')).to.equal('oldest');
      expect(innerModel.get('showDivider')).to.equal(true);
      expect(innerModel.get('divider.src')).to.equal('http://example.org/someConfigDividerImage.png');
      expect(innerModel.get('divider.styles.block.backgroundColor')).to.equal('#456789');
      expect(innerModel.get('divider.styles.block.padding')).to.equal('38px');
    });

    it('resets offset when fetching posts', function () {
      model.set('offset', 10);
      model.fetchAvailablePosts();
      expect(model.get('offset')).to.equal(0);
    });

    it('increases offset when loading more posts', function () {
      model.set({
        amount: 2,
        offset: 0,
      });
      model.set('_availablePosts', new Backbone.Collection([{}, {}])); // 2 posts
      model.trigger('loadMorePosts');
      expect(model.get('offset')).to.equal(2);
    });

    it('does not increase offset when there is no more posts to load', function () {
      model.set({
        amount: 10,
        offset: 0,
      });
      model.set('_availablePosts', new Backbone.Collection([{}, {}])); // 2 posts
      model.trigger('loadMorePosts');
      expect(model.get('offset')).to.equal(0);
    });

    it('triggers loading and loaded events for more posts', function () {
      var stub = sinon.stub(CommunicationComponent, 'getPosts').callsFake(function () {
        var deferred = jQuery.Deferred();
        deferred.resolve([{}]); // 1 post
        return deferred;
      });
      var spy = sinon.spy(model, 'trigger');

      model.set({
        amount: 2,
        offset: 0,
      });
      model.set('_availablePosts', new Backbone.Collection([{}, {}])); // 2 posts
      model._loadMorePosts();

      stub.restore();
      spy.restore();

      expect(spy.withArgs('loadingMorePosts').calledOnce).to.be.true;// eslint-disable-line no-unused-expressions
      expect(spy.withArgs('morePostsLoaded').calledOnce).to.be.true;// eslint-disable-line no-unused-expressions
      expect(model.get('_availablePosts').length).to.equal(3);
    });

    it('updates blockDefaults.posts when changed', function () {
      var stub = sandbox.stub(EditorApplication.getConfig(), 'set');
      model.trigger('change');
      expect(stub.callCount).to.equal(1);
      expect(stub.getCall(0).args[0]).to.equal('blockDefaults.posts');
      expect(stub.getCall(0).args[1]).to.deep.equal(model.toJSON());
    });
  });

  describe('block view', function () {
    var model;
    var view;

    beforeEach(function () {
      global.stubChannel(EditorApplication);
      global.stubConfig(EditorApplication);
      EditorApplication.getBlockTypeModel = sinon.stub().returns(Backbone.Model);
      model = new (PostsBlock.PostsBlockModel)();
      view = new (PostsBlock.PostsBlockView)({ model: model });

      // Disable auto-opening of settings view
      view.off('showSettings');
    });

    afterEach(function () {
      delete EditorApplication.getChannel;
    });

    it('renders', function () {
      expect(view.render).to.not.throw();
      expect(view.$('.mailpoet_posts_container')).to.have.length(1);
    });
  });

  describe('block settings view', function () {
    var model;
    var view;

    before(function () {
      CommunicationComponent.getPostTypes = function () {
        var deferred = jQuery.Deferred();
        deferred.resolve([
          {
            name: 'post',
            labels: {
              singular_name: 'Post',
            },
          },
          {
            name: 'page',
            labels: {
              singular_name: 'Page',
            },
          },
          {
            name: 'mailpoet_page',
            labels: {
              singular_name: 'Mailpoet page',
            },
          },
        ]);
        return deferred;
      };

      global.stubChannel(EditorApplication);
      global.stubConfig(EditorApplication, {
        blockDefaults: {},
      });
      EditorApplication.getBlockTypeModel = sinon.stub()
        .returns(ContainerBlock.ContainerBlockModel);
      model = new (PostsBlock.PostsBlockModel)();
      view = new (PostsBlock.PostsBlockSettingsView)({ model: model });
    });

    it('renders', function () {
      // Stub out block view requests
      model.request = sinon.stub().returns({ $el: {} });

      expect(view.render).to.not.throw();
    });

    describe('once rendered', function () {
      it('changes the model if post type changes', function () {
        var newValue = 'mailpoet_page';
        view.$('.mailpoet_settings_posts_content_type').val(newValue).change();
        expect(model.get('contentType')).to.equal(newValue);
      });

      it('changes the model if post status changes', function () {
        var newValue = 'pending';
        view.$('.mailpoet_posts_post_status').val(newValue).change();
        expect(model.get('postStatus')).to.equal(newValue);
      });

      it('changes the model if search term changes', function () {
        var newValue = 'some New search term';
        view.$('.mailpoet_posts_search_term').val(newValue).trigger('input');
        expect(model.get('search')).to.equal(newValue);
      });

      it('changes the model if display type changes', function () {
        var newValue = 'full';
        view.$('.mailpoet_posts_display_type').val(newValue).change();
        expect(model.get('displayType')).to.equal(newValue);
      });

      it('changes the model if title format changes', function () {
        var newValue = 'h3';
        view.$('.mailpoet_posts_title_format').val(newValue).change();
        expect(model.get('titleFormat')).to.equal(newValue);
      });

      it('changes the model if title alignment changes', function () {
        var newValue = 'right';
        view.$('.mailpoet_posts_title_alignment').val(newValue).change();
        expect(model.get('titleAlignment')).to.equal(newValue);
      });

      it('changes the model if title link changes', function () {
        var newValue = true;
        view.$('.mailpoet_posts_title_as_links').val(newValue).change();
        expect(model.get('titleIsLink')).to.equal(newValue);
      });

      it('changes the model if image alignment changes', function () {
        var newValue = false;
        view.$('.mailpoet_posts_image_full_width').val(newValue).change();
        expect(model.get('imageFullWidth')).to.equal(newValue);
      });

      it('changes the model if featured image position changes for excerpt display type', function () {
        var newValue = 'right';
        model.set('displayType', 'excerpt');
        view.$('.mailpoet_posts_featured_image_position').val(newValue).change();
        expect(model.get('featuredImagePosition')).to.equal(newValue);
        expect(model.get('_featuredImagePosition')).to.equal(newValue);
      });

      it('changes the model if featured image position changes for full post display type', function () {
        var newValue = 'alternate';
        model.set('displayType', 'full');
        view.$('.mailpoet_posts_featured_image_position').val(newValue).change();
        expect(model.get('fullPostFeaturedImagePosition')).to.equal(newValue);
        expect(model.get('_featuredImagePosition')).to.equal(newValue);
      });

      it('changes the model if show author changes', function () {
        var newValue = 'belowText';
        view.$('.mailpoet_posts_show_author').val(newValue).change();
        expect(model.get('showAuthor')).to.equal(newValue);
      });

      it('changes the model if author preceded by  changes', function () {
        var newValue = 'New author preceded by test';
        view.$('.mailpoet_posts_author_preceded_by').val(newValue).trigger('input');
        expect(model.get('authorPrecededBy')).to.equal(newValue);
      });

      it('changes the model if show categories changes', function () {
        var newValue = 'belowText';
        view.$('.mailpoet_posts_show_categories').val(newValue).change();
        expect(model.get('showCategories')).to.equal(newValue);
      });

      it('changes the model if categories preceded by changes', function () {
        var newValue = 'New categories preceded by test';
        view.$('.mailpoet_posts_categories').val(newValue).trigger('input');
        expect(model.get('categoriesPrecededBy')).to.equal(newValue);
      });

      it('changes the model if read more button type changes', function () {
        var newValue = 'link';
        view.$('.mailpoet_posts_read_more_type').val(newValue).change();
        expect(model.get('readMoreType')).to.equal(newValue);
      });

      it('changes the model if read more text changes', function () {
        var newValue = 'New read more text';
        view.$('.mailpoet_posts_read_more_text').val(newValue).trigger('input');
        expect(model.get('readMoreText')).to.equal(newValue);
      });

      describe('when "title only" display type is selected', function () {
        var innerModel;
        var innerView;
        beforeEach(function () {
          innerModel = new (PostsBlock.PostsBlockModel)();
          innerModel.request = sinon.stub().returns({ $el: {} });
          innerView = new (PostsBlock.PostsBlockSettingsView)({ model: innerModel });
          innerView.render();
          innerView.$('.mailpoet_posts_display_type').val('titleOnly').change();
        });

        it('shows "title as list" option', function () {
          expect(innerView.$('.mailpoet_posts_title_as_list')).to.not.have.$class('mailpoet_hidden');
        });

        describe('when "title as list" is selected', function () {
          beforeEach(function () {
            innerView.$('.mailpoet_posts_display_type').val('titleOnly').change();
            innerView.$('.mailpoet_posts_title_format').val('ul').change();
          });

          describe('"title is link" option', function () {
            it('is hidden', function () {
              expect(innerView.$('.mailpoet_posts_title_as_link')).to.have.$class('mailpoet_hidden');
            });

            it('is set to "yes"', function () {
              expect(innerModel.get('titleIsLink')).to.equal(true);
            });
          });
        });

        describe('when "title as list" is deselected', function () {
          before(function () {
            innerView.$('.mailpoet_posts_title_format').val('ul').change();
            innerView.$('.mailpoet_posts_title_format').val('h3').change();
          });

          describe('"title is link" option', function () {
            it('is visible', function () {
              expect(innerView.$('.mailpoet_posts_title_as_link')).to.not.have.$class('mailpoet_hidden');
            });
          });
        });
      });

      it('changes the model if show divider changes', function () {
        var newValue = true;
        view.$('.mailpoet_posts_show_divider').val(newValue).change();
        expect(model.get('showDivider')).to.equal(newValue);
      });
    });
  });
});
