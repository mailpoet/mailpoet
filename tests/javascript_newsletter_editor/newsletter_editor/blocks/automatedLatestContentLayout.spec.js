import App from 'newsletter_editor/App';
import AutomatedLatestContentBlock from 'newsletter_editor/blocks/automatedLatestContentLayout';
import ContainerBlock from 'newsletter_editor/blocks/container';
import Communication from 'newsletter_editor/components/communication';

/*  (ES6 -> CommonJS transform needed for inject-loader) */
/* eslint-disable-next-line max-len  */
import AutomatedLatestContentInjector from 'inject-loader!babel-loader?plugins[]=@babel/plugin-transform-modules-commonjs!newsletter_editor/blocks/automatedLatestContentLayout';

const expect = global.expect;
const sinon = global.sinon;
const Backbone = global.Backbone;
const jQuery = global.jQuery;

var EditorApplication = App;
var CommunicationComponent = Communication;

describe('Automated Latest Content Layout Supervisor', function () {
  var model;
  var mock;
  var module;
  beforeEach(function () {
    model = new AutomatedLatestContentBlock.ALCLayoutSupervisor();
  });

  it('fetches posts in bulk from the server', function () {
    global.stubChannel(EditorApplication);
    EditorApplication.findModels = sinon.stub().returns([new Backbone.SuperModel()]);

    mock = sinon.mock({ getBulkTransformedPosts: function () {} })
      .expects('getBulkTransformedPosts').once().returns(jQuery.Deferred());

    module = AutomatedLatestContentInjector({
      'newsletter_editor/components/communication': {
        getBulkTransformedPosts: mock,
      },
    }).default;

    model = new module.ALCLayoutSupervisor();
    model.refresh();

    mock.verify();
  });

  it('refreshes posts for given blocks', function () {
    var block1 = new Backbone.SuperModel();
    var block2 = new Backbone.SuperModel();
    var postsSet1 = [
      { type: 'customTypeOne' },
    ];
    var postsSet2 = [
      { type: 'customTypeTwo' },
      { type: 'customTypeTwo' },
    ];
    var mock1 = sinon.mock(block1);
    var mock2 = sinon.mock(block2);

    mock1.expects('trigger').once().withArgs('refreshPosts', postsSet1);
    mock2.expects('trigger').once().withArgs('refreshPosts', postsSet2);

    model.refreshBlocks([block1, block2], [postsSet1, postsSet2]);

    mock1.verify();
    mock2.verify();
  });
});

describe('Automated latest content layout', function () {
  describe('model', function () {
    var model;
    var module;
    var sandbox;

    before(function () {
      module = AutomatedLatestContentBlock;
    });

    beforeEach(function () {
      global.stubChannel(EditorApplication);
      global.stubConfig(EditorApplication);
      EditorApplication.getBlockTypeModel = sinon.stub().returns(Backbone.SuperModel);
      model = new (module.AutomatedLatestContentLayoutBlockModel)();
      sandbox = sinon.createSandbox();
    });

    afterEach(function () {
      delete EditorApplication.getChannel;
      delete EditorApplication.getBlockTypeModel;
      sandbox.restore();
    });

    it('has automatedLatestContentLayout type', function () {
      expect(model.get('type')).to.equal('automatedLatestContentLayout');
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

    it('has title position', function () {
      expect(model.get('titlePosition')).to.match(/^(abovePost|aboveExcerpt)$/);
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
      global.stubConfig(EditorApplication, {
        blockDefaults: {
          automatedLatestContentLayout: {
            amount: '17',
            contentType: 'mailpoet_page', // 'post'|'page'|'mailpoet_page'
            inclusionType: 'exclude', // 'include'|'exclude'
            displayType: 'full', // 'excerpt'|'full'|'titleOnly'
            titleFormat: 'h3', // 'h1'|'h2'|'h3'|'ul'
            titleAlignment: 'right', // 'left'|'center'|'right'
            titleIsLink: true, // false|true
            imageFullWidth: false, // true|false
            featuredImagePosition: 'aboveTitle',
            titlePosition: 'aboveExcerpt',
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
      model = new (module.AutomatedLatestContentLayoutBlockModel)();

      expect(model.get('amount')).to.equal('17');
      expect(model.get('contentType')).to.equal('mailpoet_page');
      expect(model.get('inclusionType')).to.equal('exclude');
      expect(model.get('displayType')).to.equal('full');
      expect(model.get('titleFormat')).to.equal('h3');
      expect(model.get('titleAlignment')).to.equal('right');
      expect(model.get('titleIsLink')).to.equal(true);
      expect(model.get('imageFullWidth')).to.equal(false);
      expect(model.get('featuredImagePosition')).to.equal('aboveTitle');
      expect(model.get('titlePosition')).to.equal('aboveExcerpt');
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

    it('accepts displayable posts', function () {
      EditorApplication.getBlockTypeModel = sinon.stub()
        .returns(ContainerBlock.ContainerBlockModel);
      model = new (module.AutomatedLatestContentLayoutBlockModel)();

      model.updatePosts([{
        type: 'someCustomType',
      }]);

      expect(model.get('_container.blocks').size()).to.equal(1);
      expect(model.get('_container.blocks').first().get('type')).to.equal('someCustomType');
    });

    it('updates blockDefaults.automatedLatestContentLayout when handling changes', function () {
      var stub = sandbox.stub(EditorApplication.getConfig(), 'set');
      model.set('amount', '17');
      model.set('contentType', 'mailpoet_page');
      model.set('terms', []);
      model.set('inclusionType', 'exclude');
      model.set('displayType', 'full');
      model.set('titleFormat', 'h3');
      model.set('titleAlignment', 'right');
      model.set('titleIsLink', true);
      model.set('imageFullWidth', true);
      model.set('featuredImagePosition', 'aboveTitle');
      model.set('titlePosition', 'aboveExcerpt');
      model.set('showAuthor', 'belowText');
      model.set('authorPrecededBy', 'Custom config author preceded by');
      model.set('showCategories', 'belowText');
      model.set('categoriesPrecededBy', 'Custom config categories preceded by');
      model.set('sortBy', 'oldest');
      model.set('showDivider', false);
      expect(stub.callCount).to.equal(17);
      expect(stub.getCall(16).args[0]).to.equal('blockDefaults.automatedLatestContentLayout');
      expect(stub.getCall(16).args[1]).to.deep.equal(model.toJSON());
    });
  });

  describe('block view', function () {
    var model;
    var view;
    var module;

    before(function () {
      module = AutomatedLatestContentBlock;
    });

    beforeEach(function () {
      global.stubChannel(EditorApplication);
      global.stubConfig(EditorApplication);
      EditorApplication.getBlockTypeModel = sinon.stub().returns(Backbone.Model);
      EditorApplication.getBlockTypeView = sinon.stub().returns(Backbone.View);
      model = new (module.AutomatedLatestContentLayoutBlockModel)();
      view = new (module.AutomatedLatestContentLayoutBlockView)({ model: model });
    });

    afterEach(function () {
      delete EditorApplication.getChannel;
    });

    it('renders', function () {
      expect(view.render).to.not.throw();
      expect(view.$('.mailpoet_content')).to.have.length(1);
    });
  });

  describe('replaceAllButtonStyles', function () {
    var onStub;
    var view;

    beforeEach(function () {
      onStub = sinon.stub();
      global.stubChannel(EditorApplication, { on: onStub });
      global.stubConfig(EditorApplication);
      EditorApplication.getBlockTypeModel = sinon.stub().returns(Backbone.Model);
      EditorApplication.getBlockTypeView = sinon.stub().returns(Backbone.View);
      view = new (AutomatedLatestContentBlock.AutomatedLatestContentLayoutBlockView)({
        model: { set: sinon.stub() },
      });
    });

    it('listens to the event', function () {
      expect(onStub).to.have.been.callCount(1);
      expect(onStub).to.have.been.calledWith('replaceAllButtonStyles', sinon.match.func);
    });

    it('updates the model', function () {
      const callback = onStub.getCall(0).args[1];
      const data = {
        styles: {
          block: {
            borderRadius: '14px',
          },
        },
      };
      callback(data);
      expect(view.model.set).to.have.been.callCount(1);
      expect(view.model.set).to.have.been.calledWithMatch(sinon.match.has('readMoreButton', data));
    });
  });

  describe('block settings view', function () {
    var model;
    var view;
    var module;

    before(function () {
      module = AutomatedLatestContentInjector({
        'newsletter_editor/components/communication': {
          getPostTypes: function () {
            return jQuery.Deferred();
          },
        },
      }).default;
    });

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
      EditorApplication.getBlockTypeModel = sinon.stub().returns(Backbone.Model);
      EditorApplication.getBlockTypeView = sinon.stub().returns(Backbone.View);
    });

    beforeEach(function () {
      model = new (module.AutomatedLatestContentLayoutBlockModel)();
      view = new (module.AutomatedLatestContentLayoutBlockSettingsView)({ model: model });
    });

    after(function () {
      delete EditorApplication.getChannel;
    });

    it('renders', function () {
      expect(view.render).to.not.throw();
    });

    describe('once rendered', function () {
      beforeEach(function () {
        model = new (module.AutomatedLatestContentLayoutBlockModel)();
        view = new (module.AutomatedLatestContentLayoutBlockSettingsView)({ model: model });
        view.render();
      });

      it('changes the model if post amount changes', function () {
        var newValue = '11';
        view.$('.mailpoet_automated_latest_content_show_amount').val(newValue).trigger('input');
        expect(model.get('amount')).to.equal(newValue);
      });

      it('changes the model if post type changes', function () {
        var newValue = 'mailpoet_page';
        view.$('.mailpoet_automated_latest_content_content_type').val(newValue).change();
        expect(model.get('contentType')).to.equal(newValue);
      });

      it('changes the model if inclusion type changes', function () {
        var newValue = 'exclude';
        view.$('.mailpoet_automated_latest_content_include_or_exclude').val(newValue).change();
        expect(model.get('inclusionType')).to.equal(newValue);
      });

      it('changes the model if display type changes', function () {
        var newValue = 'full';
        view.$('.mailpoet_automated_latest_content_display_type').val(newValue).change();
        expect(model.get('displayType')).to.equal(newValue);
      });

      it('changes the model if title format changes', function () {
        var newValue = 'h3';
        view.$('.mailpoet_automated_latest_content_title_format').val(newValue).change();
        expect(model.get('titleFormat')).to.equal(newValue);
      });

      it('changes the model if title alignment changes', function () {
        var newValue = 'right';
        view.$('.mailpoet_automated_latest_content_title_alignment').val(newValue).change();
        expect(model.get('titleAlignment')).to.equal(newValue);
      });

      it('changes the model if title link changes', function () {
        var newValue = true;
        view.$('.mailpoet_automated_latest_content_title_as_links').val(newValue).change();
        expect(model.get('titleIsLink')).to.equal(newValue);
      });

      it('changes the model if image alignment changes', function () {
        var newValue = false;
        view.$('.mailpoet_automated_latest_content_image_full_width').val(newValue).change();
        expect(model.get('imageFullWidth')).to.equal(newValue);
      });

      it('changes the model if featured image position changes for excerpt display type', function () {
        var newValue = 'right';
        model.set('displayType', 'excerpt');
        view.$('.mailpoet_automated_latest_content_featured_image_position').val(newValue).change();
        expect(model.get('featuredImagePosition')).to.equal(newValue);
        expect(model.get('_featuredImagePosition')).to.equal(newValue);
      });

      it('changes the model if featured image position changes for full post display type', function () {
        var newValue = 'alternate';
        model.set('displayType', 'full');
        view.$('.mailpoet_automated_latest_content_featured_image_position').val(newValue).change();
        expect(model.get('fullPostFeaturedImagePosition')).to.equal(newValue);
        expect(model.get('_featuredImagePosition')).to.equal(newValue);
      });

      it('changes the model if featured image position changes', function () {
        var newValue = 'aboveExcerpt';
        view.$('.mailpoet_automated_latest_content_title_position').val(newValue).change();
        expect(model.get('titlePosition')).to.equal(newValue);
      });

      it('changes the model if show author changes', function () {
        var newValue = 'belowText';
        view.$('.mailpoet_automated_latest_content_show_author').val(newValue).change();
        expect(model.get('showAuthor')).to.equal(newValue);
      });

      it('changes the model if author preceded by  changes', function () {
        var newValue = 'New author preceded by test';
        view.$('.mailpoet_automated_latest_content_author_preceded_by').val(newValue).trigger('input');
        expect(model.get('authorPrecededBy')).to.equal(newValue);
      });

      it('changes the model if show categories changes', function () {
        var newValue = 'belowText';
        view.$('.mailpoet_automated_latest_content_show_categories').val(newValue).change();
        expect(model.get('showCategories')).to.equal(newValue);
      });

      it('changes the model if categories preceded by changes', function () {
        var newValue = 'New categories preceded by test';
        view.$('.mailpoet_automated_latest_content_categories').val(newValue).trigger('input');
        expect(model.get('categoriesPrecededBy')).to.equal(newValue);
      });

      it('changes the model if read more button type changes', function () {
        var newValue = 'link';
        view.$('.mailpoet_automated_latest_content_read_more_type').val(newValue).change();
        expect(model.get('readMoreType')).to.equal(newValue);
      });

      it('changes the model if read more text changes', function () {
        var newValue = 'New read more text';
        view.$('.mailpoet_automated_latest_content_read_more_text').val(newValue).trigger('input');
        expect(model.get('readMoreText')).to.equal(newValue);
      });

      it('changes the model if sort by changes', function () {
        var newValue = 'oldest';
        view.$('.mailpoet_automated_latest_content_sort_by').val(newValue).change();
        expect(model.get('sortBy')).to.equal(newValue);
      });

      it('changes the model if show divider changes', function () {
        var newValue = true;
        view.$('.mailpoet_automated_latest_content_show_divider').val(newValue).change();
        expect(model.get('showDivider')).to.equal(newValue);
      });

      describe('when "title only" display type is selected', function () {
        beforeEach(function () {
          model = new (module.AutomatedLatestContentLayoutBlockModel)();
          view = new (module.AutomatedLatestContentLayoutBlockSettingsView)({ model: model });
          view.render();
          view.$('.mailpoet_automated_latest_content_display_type').val('titleOnly').change();
        });

        it('shows "title as list" option', function () {
          expect(view.$('.mailpoet_automated_latest_content_title_as_list')).to.not.have.$class('mailpoet_hidden');
        });

        describe('when "title as list" is selected', function () {
          beforeEach(function () {
            model = new (module.AutomatedLatestContentLayoutBlockModel)();
            view = new (module.AutomatedLatestContentLayoutBlockSettingsView)({ model: model });
            view.render();
            view.$('.mailpoet_automated_latest_content_display_type').val('titleOnly').change();
            view.$('.mailpoet_automated_latest_content_title_format').val('ul').change();
          });

          describe('"title is link" option', function () {
            it('is hidden', function () {
              expect(view.$('.mailpoet_automated_latest_content_title_as_link')).to.have.$class('mailpoet_hidden');
            });

            it('is set to "yes"', function () {
              expect(model.get('titleIsLink')).to.equal(true);
            });
          });
        });

        describe('when "title as list" is deselected', function () {
          before(function () {
            view.$('.mailpoet_automated_latest_content_title_format').val('ul').change();
            view.$('.mailpoet_automated_latest_content_title_format').val('h3').change();
          });

          describe('"title is link" option', function () {
            it('is visible', function () {
              expect(view.$('.mailpoet_automated_latest_content_title_as_link')).to.not.have.$class('mailpoet_hidden');
            });
          });
        });
      });
    });
  });
});
