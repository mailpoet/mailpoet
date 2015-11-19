define([
    'newsletter_editor/App',
    'newsletter_editor/components/wordpress',
    'newsletter_editor/blocks/posts'
  ], function(EditorApplication, WordpressComponent, PostsBlock) {

  describe('Posts', function () {
    Backbone.Radio = {
      Requests: {
        request: function () {
        }, reply: function () {
        },
      },
    };
    describe('model', function () {
      var model;

      before(function() {
        WordpressComponent.getPosts = function() {
          var deferred = jQuery.Deferred();
          return deferred;
        };
      });

      beforeEach(function () {
        global.stubChannel(EditorApplication);
        global.stubConfig(EditorApplication);
        EditorApplication.getBlockTypeModel = sinon.stub().returns(Backbone.SuperModel);
        model = new (PostsBlock.PostsBlockModel)();
      });

      afterEach(function () {
        delete EditorApplication.getChannel;
      });

      it('has posts type', function () {
        expect(model.get('type')).to.equal('posts');
      });

      it('has post amount limit', function () {
        expect(model.get('amount')).to.match(/^\d+$/);
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

      it('has title position', function () {
        expect(model.get('titlePosition')).to.match(/^(inTextBlock|aboveBlock)$/);
      });

      it('has title alignment', function () {
        expect(model.get('titleAlignment')).to.match(/^(left|center|right)$/);
      });

      it('optionally has title as link', function () {
        expect(model.get('titleIsLink')).to.be.a('boolean');
      });

      it('has image specific alignment', function () {
        expect(model.get('imagePadded')).to.be.a('boolean');
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

      it("uses defaults from config when they are set", function () {
        global.stubConfig(EditorApplication, {
          blockDefaults: {
            posts: {
              amount: '17',
              contentType: 'mailpoet_page', // 'post'|'page'|'mailpoet_page'
              inclusionType: 'exclude', // 'include'|'exclude'
              displayType: 'full', // 'excerpt'|'full'|'titleOnly'
              titleFormat: 'h3', // 'h1'|'h2'|'h3'|'ul'
              titlePosition: 'aboveBlock', // 'inTextBlock'|'aboveBlock',
              titleAlignment: 'right', // 'left'|'center'|'right'
              titleIsLink: true, // false|true
              imagePadded: false, // true|false
              //imageAlignment: 'right', // 'centerFull'|'centerPadded'|'left'|'right'|'alternate'|'none'
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
        var model = new (PostsBlock.PostsBlockModel)();

        expect(model.get('amount')).to.equal('17');
        expect(model.get('contentType')).to.equal('mailpoet_page');
        expect(model.get('inclusionType')).to.equal('exclude');
        expect(model.get('displayType')).to.equal('full');
        expect(model.get('titleFormat')).to.equal('h3');
        expect(model.get('titlePosition')).to.equal('aboveBlock');
        expect(model.get('titleAlignment')).to.equal('right');
        expect(model.get('titleIsLink')).to.equal(true);
        expect(model.get('imagePadded')).to.equal(false);
        expect(model.get('showAuthor')).to.equal('belowText');
        expect(model.get('authorPrecededBy')).to.equal('Custom config author preceded by');
        expect(model.get('showCategories')).to.equal('belowText');
        expect(model.get('categoriesPrecededBy')).to.equal('Custom config categories preceded by');
        expect(model.get('readMoreType')).to.equal('button');
        expect(model.get('readMoreText')).to.equal('Custom Config read more text');
        expect(model.get('readMoreButton.text')).to.equal('Custom config read more');
        expect(model.get('readMoreButton.url')).to.equal('[postLink]');
        expect(model.get('readMoreButton.styles.block.backgroundColor')).to.equal('#123456');
        expect(model.get('readMoreButton.styles.block.borderColor')).to.equal('#234567');
        expect(model.get('readMoreButton.styles.link.fontColor')).to.equal('#345678');
        expect(model.get('readMoreButton.styles.link.fontFamily')).to.equal('Tahoma');
        expect(model.get('readMoreButton.styles.link.fontSize')).to.equal('37px');
        expect(model.get('sortBy')).to.equal('oldest');
        expect(model.get('showDivider')).to.equal(true);
        expect(model.get('divider.src')).to.equal('http://example.org/someConfigDividerImage.png');
        expect(model.get('divider.styles.block.backgroundColor')).to.equal('#456789');
        expect(model.get('divider.styles.block.padding')).to.equal('38px');
      });
    });

    describe('block view', function () {
      var model, view;

      beforeEach(function () {
        global.stubChannel(EditorApplication);
        global.stubConfig(EditorApplication);
        EditorApplication.getBlockTypeModel = sinon.stub().returns(Backbone.Model);
        model = new (PostsBlock.PostsBlockModel)();
        view = new (PostsBlock.PostsBlockView)({model: model});

        // Disable auto-opening of settings view
        view.off('showSettings');
      });

      afterEach(function () {
        delete EditorApplication.getChannel;
      });

      it('renders', function () {
        expect(view.render).to.not.throw();
        expect(view.$('.mailpoet_content')).to.have.length(1);
      });
    });

    describe('block settings view', function () {
      var model, view;

      before(function () {
        WordpressComponent.getPostTypes = function() {
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
        EditorApplication.getBlockTypeModel = sinon.stub().returns(Backbone.Model);
        model = new (PostsBlock.PostsBlockModel)();
        view = new (PostsBlock.PostsBlockSettingsView)({model: model});
      });

      it('renders', function () {
        // Stub out block view requests
        model.request = sinon.stub().returns({$el: {}});

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
          view.$('.mailpoet_posts_search_term').val(newValue).keyup();
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

        it('changes the model if title position changes', function () {
          var newValue = 'aboveBlock';
          view.$('.mailpoet_posts_title_position').val(newValue).change();
          expect(model.get('titlePosition')).to.equal(newValue);
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
          view.$('.mailpoet_posts_image_padded').val(newValue).change();
          expect(model.get('imagePadded')).to.equal(newValue);
        });

        it('changes the model if show author changes', function () {
          var newValue = 'belowText';
          view.$('.mailpoet_posts_show_author').val(newValue).change();
          expect(model.get('showAuthor')).to.equal(newValue);
        });

        it('changes the model if author preceded by  changes', function () {
          var newValue = 'New author preceded by test';
          view.$('.mailpoet_posts_author_preceded_by').val(newValue).keyup();
          expect(model.get('authorPrecededBy')).to.equal(newValue);
        });

        it('changes the model if show categories changes', function () {
          var newValue = 'belowText';
          view.$('.mailpoet_posts_show_categories').val(newValue).change();
          expect(model.get('showCategories')).to.equal(newValue);
        });

        it('changes the model if categories preceded by changes', function () {
          var newValue = 'New categories preceded by test';
          view.$('.mailpoet_posts_categories').val(newValue).keyup();
          expect(model.get('categoriesPrecededBy')).to.equal(newValue);
        });

        it('changes the model if read more button type changes', function () {
          var newValue = 'link';
          view.$('.mailpoet_posts_read_more_type').val(newValue).change();
          expect(model.get('readMoreType')).to.equal(newValue);
        });

        it('changes the model if read more text changes', function () {
          var newValue = 'New read more text';
          view.$('.mailpoet_posts_read_more_text').val(newValue).keyup();
          expect(model.get('readMoreText')).to.equal(newValue);
        });

        describe('when "title only" display type is selected', function() {
          var model, view;
          beforeEach(function() {
            model = new (PostsBlock.PostsBlockModel)();
            model.request = sinon.stub().returns({$el: {}});
            view = new (PostsBlock.PostsBlockSettingsView)({model: model});
            view.render();
            view.$('.mailpoet_posts_display_type').val('titleOnly').change();
          });

          it('shows "title as list" option', function () {
            expect(view.$('.mailpoet_posts_title_as_list')).to.not.have.$class('mailpoet_hidden');
          });

          describe('when "title as list" is selected', function() {
            beforeEach(function() {
              view.$('.mailpoet_posts_display_type').val('titleOnly').change();
              view.$('.mailpoet_posts_title_format').val('ul').change();
            });

            describe('"title is link" option', function () {
              it('is hidden', function () {
                expect(view.$('.mailpoet_posts_title_as_link')).to.have.$class('mailpoet_hidden');
              });

              it('is set to "yes"', function() {
                expect(model.get('titleIsLink')).to.equal(true);
              });
            });
          });

          describe('when "title as list" is deselected', function() {
            before(function() {
              view.$('.mailpoet_posts_title_format').val('ul').change();
              view.$('.mailpoet_posts_title_format').val('h3').change();
            });

            describe('"title is link" option', function () {
              it('is visible', function () {
                expect(view.$('.mailpoet_posts_title_as_link')).to.not.have.$class('mailpoet_hidden');
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
});
