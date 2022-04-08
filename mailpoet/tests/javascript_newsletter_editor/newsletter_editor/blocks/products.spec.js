import App from 'newsletter_editor/App';
import Communication from 'newsletter_editor/components/communication';
import ProductsBlock from 'newsletter_editor/blocks/products';
import ContainerBlock from 'newsletter_editor/blocks/container';

const expect = global.expect;
const sinon = global.sinon;
const Backbone = global.Backbone;
const jQuery = global.jQuery;

var EditorApplication = App;
var CommunicationComponent = Communication;

describe('Products', function () {
  Backbone.Radio = {
    Requests: {
      request: function () {},
      reply: function () {},
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
      EditorApplication.getBlockTypeModel = sinon
        .stub()
        .returns(Backbone.SuperModel);
      EditorApplication.getBlockTypeView = sinon.stub().returns(Backbone.View);
      model = new ProductsBlock.ProductsBlockModel();
      sandbox = sinon.createSandbox();
    });

    afterEach(function () {
      delete EditorApplication.getChannel;
      sandbox.restore();
    });

    it('has products type', function () {
      expect(model.get('type')).to.equal('products');
    });

    it('has products amount limit', function () {
      expect(model.get('amount')).to.match(/^\d+$/);
    });

    it('has products offset initialized', function () {
      expect(model.get('offset')).to.equal(0);
    });

    it('has fixed content type', function () {
      expect(model.get('contentType')).to.equal('product');
    });

    it('has terms filter', function () {
      expect(model.get('terms')).to.have.length(0);
    });

    it('has inclusion filter', function () {
      expect(model.get('inclusionType')).to.equal('include');
    });

    it('has display type', function () {
      expect(model.get('displayType')).to.match(/^(excerpt|full|titleOnly)$/);
    });

    it('has title heading format', function () {
      expect(model.get('titleFormat')).to.match(/^(h1|h2|h3)$/);
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

    it('has image position', function () {
      expect(model.get('featuredImagePosition')).to.match(
        /^(centered|left|right|alternate|none)$/,
      );
    });

    it('has an option to display price', function () {
      expect(model.get('pricePosition')).to.match(/^(hidden|above|below)$/);
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
      expect(model.get('sortBy')).to.equal('newest');
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
          products: {
            type: 'products',
            withLayout: true,
            amount: '12',
            offset: 0,
            contentType: 'product',
            postStatus: 'publish',
            terms: [],
            search: '',
            inclusionType: 'include',
            displayType: 'full',
            titleFormat: 'h3',
            titleAlignment: 'right',
            titleIsLink: true,
            imageFullWidth: true,
            titlePosition: 'aboveExcerpt',
            featuredImagePosition: 'left',
            pricePosition: 'above',
            readMoreType: 'button',
            readMoreText: 'Go Shopping text',
            readMoreButton: {
              text: 'Go Shopping',
              url: '[productLink]',
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
            showDivider: true,
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
      innerModel = new ProductsBlock.ProductsBlockModel();

      expect(innerModel.get('amount')).to.equal('12');
      expect(innerModel.get('contentType')).to.equal('product');
      expect(innerModel.get('inclusionType')).to.equal('include');
      expect(innerModel.get('displayType')).to.equal('full');
      expect(innerModel.get('titleFormat')).to.equal('h3');
      expect(innerModel.get('titleAlignment')).to.equal('right');
      expect(innerModel.get('titleIsLink')).to.equal(true);
      expect(innerModel.get('imageFullWidth')).to.equal(true);
      expect(innerModel.get('featuredImagePosition')).to.equal('left');
      expect(innerModel.get('pricePosition')).to.equal('above');
      expect(innerModel.get('readMoreType')).to.equal('button');
      expect(innerModel.get('readMoreText')).to.equal('Go Shopping text');
      expect(innerModel.get('readMoreButton.text')).to.equal('Go Shopping');
      expect(innerModel.get('readMoreButton.url')).to.equal('[productLink]');
      expect(
        innerModel.get('readMoreButton.styles.block.backgroundColor'),
      ).to.equal('#123456');
      expect(
        innerModel.get('readMoreButton.styles.block.borderColor'),
      ).to.equal('#234567');
      expect(innerModel.get('readMoreButton.styles.link.fontColor')).to.equal(
        '#345678',
      );
      expect(innerModel.get('readMoreButton.styles.link.fontFamily')).to.equal(
        'Tahoma',
      );
      expect(innerModel.get('readMoreButton.styles.link.fontSize')).to.equal(
        '37px',
      );
      expect(innerModel.get('showDivider')).to.equal(true);
      expect(innerModel.get('divider.src')).to.equal(
        'http://example.org/someConfigDividerImage.png',
      );
      expect(innerModel.get('divider.styles.block.backgroundColor')).to.equal(
        '#456789',
      );
      expect(innerModel.get('divider.styles.block.padding')).to.equal('38px');
    });

    it('resets offset when fetching products', function () {
      model.set('offset', 10);
      model.fetchAvailableProducts();
      expect(model.get('offset')).to.equal(0);
    });

    it('increases offset when loading more products', function () {
      model.set({
        amount: 2,
        offset: 0,
      });
      model.set('_availableProducts', new Backbone.Collection([{}, {}])); // 2 products
      model.trigger('loadMoreProducts');
      expect(model.get('offset')).to.equal(2);
    });

    it('does not increase offset when there is no more products to load', function () {
      model.set({
        amount: 10,
        offset: 0,
      });
      model.set('_availableProducts', new Backbone.Collection([{}, {}])); // 2 products
      model.trigger('loadMoreProducts');
      expect(model.get('offset')).to.equal(0);
    });

    it('triggers loading and loaded events for more products', function () {
      var stub = sinon
        .stub(CommunicationComponent, 'getPosts')
        .callsFake(function () {
          var deferred = jQuery.Deferred();
          deferred.resolve([{}]); // 1 product
          return deferred;
        });
      var spy = sinon.spy(model, 'trigger');

      model.set({
        amount: 2,
        offset: 0,
      });
      model.set('_availableProducts', new Backbone.Collection([{}, {}])); // 2 products
      model._loadMoreProducts();

      stub.restore();
      spy.restore();

      expect(spy.withArgs('loadingMoreProducts').calledOnce).to.be.true; // eslint-disable-line no-unused-expressions
      expect(spy.withArgs('moreProductsLoaded').calledOnce).to.be.true; // eslint-disable-line no-unused-expressions
      expect(model.get('_availableProducts').length).to.equal(3);
    });

    it('updates blockDefaults.products when changed', function () {
      var stub = sandbox.stub(EditorApplication.getConfig(), 'set');
      model.trigger('change');
      expect(stub.callCount).to.equal(1);
      expect(stub.getCall(0).args[0]).to.equal('blockDefaults.products');
      expect(stub.getCall(0).args[1]).to.deep.equal(model.toJSON());
    });
  });

  describe('block view', function () {
    var model;
    var view;

    beforeEach(function () {
      global.stubChannel(EditorApplication);
      global.stubConfig(EditorApplication);
      EditorApplication.getBlockTypeModel = sinon
        .stub()
        .returns(Backbone.Model);
      model = new ProductsBlock.ProductsBlockModel();
      view = new ProductsBlock.ProductsBlockView({ model: model });

      // Disable auto-opening of settings view
      view.off('showSettings');
    });

    afterEach(function () {
      delete EditorApplication.getChannel;
    });

    it('renders', function () {
      expect(view.render).to.not.throw();
      expect(view.$('.mailpoet_products_container')).to.have.length(1);
    });
  });

  describe.skip('block settings view', function () {
    var model;
    var view;

    before(function () {
      global.stubChannel(EditorApplication);
      global.stubConfig(EditorApplication, {
        blockDefaults: {},
      });
      EditorApplication.getBlockTypeModel = sinon
        .stub()
        .returns(ContainerBlock.ContainerBlockModel);
      model = new ProductsBlock.ProductsBlockModel();
      view = new ProductsBlock.ProductsBlockSettingsView({ model: model });
    });

    it('renders', function () {
      // Stub out block view requests
      model.request = sinon.stub().returns({ $el: {} });

      expect(view.render).to.not.throw();
    });

    describe('once rendered', function () {
      it('changes the model if post status changes', function () {
        var newValue = 'pending';
        view
          .$('.mailpoet_products_post_status')
          .val(newValue)
          .trigger('change');
        expect(model.get('postStatus')).to.equal(newValue);
      });

      it('changes the model if search term changes', function () {
        var newValue = 'some New search term';
        view.$('.mailpoet_products_search_term').val(newValue).trigger('input');
        expect(model.get('search')).to.equal(newValue);
      });

      it('changes the model if display type changes', function () {
        var newValue = 'full';
        view
          .$('.mailpoet_products_display_type')
          .val(newValue)
          .trigger('change');
        expect(model.get('displayType')).to.equal(newValue);
      });

      it('changes the model if title format changes', function () {
        var newValue = 'h3';
        view
          .$('.mailpoet_products_title_format')
          .val(newValue)
          .trigger('change');
        expect(model.get('titleFormat')).to.equal(newValue);
      });

      it('changes the model if title alignment changes', function () {
        var newValue = 'right';
        view
          .$('.mailpoet_products_title_alignment')
          .val(newValue)
          .trigger('change');
        expect(model.get('titleAlignment')).to.equal(newValue);
      });

      it('changes the model if title link changes', function () {
        var newValue = true;
        view
          .$('.mailpoet_products_title_as_links')
          .val(newValue)
          .trigger('change');
        expect(model.get('titleIsLink')).to.equal(newValue);
      });

      it('changes the model if image alignment changes', function () {
        var newValue = false;
        view
          .$('.mailpoet_products_image_full_width')
          .val(newValue)
          .trigger('change');
        expect(model.get('imageFullWidth')).to.equal(newValue);
      });

      it('changes the model if image position changes', function () {
        var newValue = 'aboveTitle';
        view
          .$('.mailpoet_products_featured_image_position')
          .val(newValue)
          .trigger('change');
        expect(model.get('featuredImagePosition')).to.equal(newValue);
      });

      it('changes the model if price position changes', function () {
        var newValue = 'below';
        view
          .$('.mailpoet_products_price_position')
          .val(newValue)
          .trigger('change');
        expect(model.get('pricePosition')).to.equal(newValue);
      });

      it('changes the model if buy now button type changes', function () {
        var newValue = 'link';
        view
          .$('.mailpoet_products_read_more_type')
          .val(newValue)
          .trigger('change');
        expect(model.get('readMoreType')).to.equal(newValue);
      });

      it('changes the model if read more text changes', function () {
        var newValue = 'New buy now text';
        view
          .$('.mailpoet_products_read_more_text')
          .val(newValue)
          .trigger('input');
        expect(model.get('readMoreText')).to.equal(newValue);
      });

      describe('when "title only" display type is selected', function () {
        var innerModel;
        var innerView;
        beforeEach(function () {
          innerModel = new ProductsBlock.ProductsBlockModel();
          innerModel.request = sinon.stub().returns({ $el: {} });
          innerView = new ProductsBlock.ProductsBlockSettingsView({
            model: innerModel,
          });
          innerView.render();
          innerView
            .$('.mailpoet_products_display_type')
            .val('titleOnly')
            .trigger('change');
        });

        it('hides "title position" option', function () {
          expect(
            innerView.$('.mailpoet_products_title_position'),
          ).to.have.$class('mailpoet_hidden');
          expect(
            innerView.$('.mailpoet_products_title_position_separator'),
          ).to.have.$class('mailpoet_hidden');
        });
      });

      it('changes the model if show divider changes', function () {
        var newValue = true;
        view
          .$('.mailpoet_products_show_divider')
          .val(newValue)
          .trigger('change');
        expect(model.get('showDivider')).to.equal(newValue);
      });
    });
  });
});
