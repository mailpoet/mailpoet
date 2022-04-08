import App from 'newsletter_editor/App';
import WCContentBlock from 'newsletter_editor/blocks/woocommerceContent';

const expect = global.expect;
const sinon = global.sinon;

var EditorApplication = App;

describe('WoocommerceContent', function () {
  describe('model', function () {
    var model;
    var sandbox;

    beforeEach(function () {
      global.stubChannel(EditorApplication);
      global.stubConfig(EditorApplication, {
        blockDefaults: {},
      });
      global.stubAvailableStyles(EditorApplication);
      model = new WCContentBlock.BlockModel();
      sandbox = sinon.createSandbox();
    });

    afterEach(function () {
      delete EditorApplication.getChannel;
      sandbox.restore();
    });

    it('has woocommerceContent type', function () {
      expect(model.get('type')).to.equal('woocommerceContent');
    });

    it('has titleColor', function () {
      expect(model.get('styles.titleColor')).to.match(
        /^(#[abcdef0-9]{6})|transparent$/,
      );
    });

    it('uses defaults from config when they are set', function () {
      global.stubConfig(EditorApplication, {
        blockDefaults: {
          woocommerceContent: {
            styles: {
              titleColor: '#567890',
            },
          },
        },
      });
      model = new WCContentBlock.BlockModel();

      expect(model.get('styles.titleColor')).to.equal('#567890');
    });
  });

  describe('block view', function () {
    var model;
    var view;

    beforeEach(function () {
      global.stubChannel(EditorApplication);
      global.stubConfig(EditorApplication);
      global.stubAvailableStyles(EditorApplication);
      model = new WCContentBlock.BlockModel();
      view = new WCContentBlock.BlockView({ model: model });
    });

    it('renders', function () {
      expect(view.render).to.not.throw();
      expect(view.$('.mailpoet_woocommerce_content')).to.have.length(1);
    });
  });
});
