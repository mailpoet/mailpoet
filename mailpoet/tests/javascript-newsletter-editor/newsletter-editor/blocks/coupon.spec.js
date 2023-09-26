import Backbone from 'backbone';
import { App } from 'newsletter-editor/app';
import { ContentComponent } from 'newsletter-editor/components/content';
import { CouponBlock } from 'newsletter-editor/blocks/coupon';

const expect = global.expect;
const sinon = global.sinon;

var EditorApplication = App;
var sandbox;

describe('Coupon', function () {
  describe('model', function () {
    var model;

    beforeEach(function () {
      global.stubChannel(EditorApplication);
      global.stubConfig(EditorApplication, {
        blockDefaults: {},
      });
      EditorApplication.getNewsletter = function () {
        return new ContentComponent.NewsletterModel({
          type: 'standard',
        });
      };
      model = new CouponBlock.CouponBlockModel();
      model.isConfirmationEmailTemplate = function () {
        return false;
      };

      sandbox = sinon.createSandbox();
    });

    afterEach(function () {
      if (EditorApplication.getChannel) {
        delete EditorApplication.getChannel;
      }
      sandbox.restore();
    });

    it('has a coupon type', function () {
      expect(model.get('type')).to.equal('coupon');
    });

    it('has a discountType', function () {
      expect(model.get('discountType')).to.be.a('string');
    });

    it('has an amount', function () {
      expect(model.get('amount')).to.be.a('number');
    });

    it('has a max amount', function () {
      expect(model.get('amountMax')).to.equal(100);
    });

    it('has an expiry day', function () {
      expect(model.get('expiryDay')).to.be.a('number');
    });

    it('has a background color', function () {
      expect(model.get('styles.block.backgroundColor')).to.match(
        /^(#[abcdef0-9]{6})|transparent$/,
      );
    });

    it('has a block border color', function () {
      expect(model.get('styles.block.borderColor')).to.match(
        /^(#[abcdef0-9]{6})|transparent$/,
      );
    });

    it('has a block border width', function () {
      expect(model.get('styles.block.borderWidth')).to.match(/^\d+px$/);
    });

    it('has block border radius', function () {
      expect(model.get('styles.block.borderRadius')).to.match(/^\d+px$/);
    });

    it('has block border style', function () {
      expect(model.get('styles.block.borderStyle')).to.equal('solid');
    });

    it('has a block font color', function () {
      expect(model.get('styles.block.fontColor')).to.match(
        /^(#[abcdef0-9]{6})|transparent$/,
      );
    });

    it('has a block font family', function () {
      expect(model.get('styles.block.fontFamily')).to.equal('Verdan');
    });

    it('has a block font size', function () {
      expect(model.get('styles.block.fontSize')).to.match(/^\d+px$/);
    });

    it('has a block font weight', function () {
      expect(model.get('styles.block.fontWeight')).to.match(/^(bold|normal)$/);
    });

    it('has a block line height', function () {
      expect(model.get('styles.block.lineHeight')).to.match(/^\d+px$/);
    });

    it('has a block text align', function () {
      expect(model.get('styles.block.textAlign')).to.match(
        /^(left|center|right|justify)$/,
      );
    });

    it('has a block width', function () {
      expect(model.get('styles.block.width')).to.match(/^\d+px$/);
    });

    it('triggers autosave if any attribute changes', function () {
      var mock = sinon.mock().exactly(12).withArgs('autoSave');
      EditorApplication.getChannel = sinon.stub().returns({
        trigger: mock,
      });
      model.set('text', 'some other text');
      model.set('url', 'some url');
      model.set('styles.block.backgroundColor', '#123456');
      model.set('styles.block.borderColor', '#234567');
      model.set('styles.block.borderWidth', '3px');
      model.set('styles.block.borderRadius', '8px');
      model.set('styles.block.width', '400px');
      model.set('styles.block.lineHeight', '100px');
      model.set('styles.block.fontColor', '#345678');
      model.set('styles.block.fontFamily', 'Some other style');
      model.set('styles.block.fontSize', '10px');
      model.set('styles.block.fontWeight', 'bold');
      mock.verify();
    });

    it('updates blockDefaults.coupon when changed', function () {
      var stub = sandbox.stub(EditorApplication.getConfig(), 'set');
      model.trigger('change');
      expect(stub.callCount).to.equal(1);
      expect(stub.getCall(0).args[0]).to.equal('blockDefaults.coupon');
      expect(stub.getCall(0).args[1]).to.deep.equal(model.toJSON());
    });

    it('updates blockDefaults for usage context when changed', function () {
      var stub = sandbox.stub(EditorApplication.getConfig(), 'set');
      model.set('context', 'posts.readMoreButton');
      expect(stub.callCount).to.equal(1);
      expect(stub.getCall(0).args[0]).to.equal(
        'blockDefaults.posts.readMoreButton',
      );
      expect(stub.getCall(0).args[1]).to.deep.equal(model.toJSON());
    });

    it('uses defaults from config when they are set', function () {
      global.stubConfig(EditorApplication, {
        blockDefaults: {
          coupon: {
            type: 'coupon',
            amount: 7,
            amountMax: 100,
            discountType: 'fixed_cart',
            expiryDay: 5,
            styles: {
              block: {
                backgroundColor: '#fafafa',
                borderColor: '#ccc',
                borderRadius: '10px',
                borderStyle: 'solid',
                borderWidth: '2px',
                fontColor: '#888',
                fontFamily: 'Arial',
                fontSize: '12px',
                fontWeight: 'bold',
                lineHeight: '30px',
                textAlign: 'left',
                width: '150px',
              },
            },
          },
        },
      });
      model = new CouponBlock.CouponBlockModel();

      expect(model.get('amount')).to.equal(7);
      expect(model.get('amountMax')).to.equal(100);
      expect(model.get('discountType')).to.equal('fixed_cart');
      expect(model.get('expiryDay')).to.equal(5);
      expect(model.get('styles.block.backgroundColor')).to.equal('#fafafa');
      expect(model.get('styles.block.borderColor')).to.equal('#ccc');
      expect(model.get('styles.block.borderRadius')).to.equal('10px');
      expect(model.get('styles.block.borderStyle')).to.equal('solid');
      expect(model.get('styles.block.borderWidth')).to.equal('2px');
      expect(model.get('styles.block.fontColor')).to.equal('#888');
      expect(model.get('styles.block.fontFamily')).to.equal('Arial');
      expect(model.get('styles.block.fontSize')).to.equal('12px');
      expect(model.get('styles.block.fontWeight')).to.equal('bold');
      expect(model.get('styles.block.lineHeight')).to.equal('30px');
      expect(model.get('styles.block.textAlign')).to.equal('left');
      expect(model.get('styles.block.width')).to.equal('150px');
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
      model = new CouponBlock.CouponBlockModel();
      view = new CouponBlock.CouponBlockView({ model: model });
    });

    afterEach(function () {
      delete EditorApplication.getChannel;
    });

    it('renders coupon with overlay', function () {
      expect(view.render).to.not.throw();
      expect(view.$('.mailpoet_editor_coupon')).to.have.length(1);
      expect(view.$('.mailpoet_editor_coupon_overlay')).to.have.length(1);
    });
  });
});
