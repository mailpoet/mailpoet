import App from 'newsletter_editor/App';
import ImageBlock from 'newsletter_editor/blocks/image';

const expect = global.expect;
const sinon = global.sinon;

var EditorApplication = App;

describe('Image', function () {
  describe('model', function () {
    var model;
    var sandbox;
    beforeEach(function () {
      global.stubChannel(EditorApplication);
      global.stubConfig(EditorApplication, {
        blockDefaults: {},
      });
      model = new (ImageBlock.ImageBlockModel)();
      sandbox = sinon.createSandbox();
    });
    afterEach(function () {
      sandbox.restore();
    });

    it('has an image type', function () {
      expect(model.get('type')).to.equal('image');
    });

    it('has a link', function () {
      expect(model.get('link')).to.be.a('string');
    });

    it('has an image src', function () {
      expect(model.get('src')).to.be.a('string');
    });

    it('has alt text', function () {
      expect(model.get('alt')).to.be.a('string');
    });

    it('can be full width', function () {
      expect(model.get('fullWidth')).to.be.a('boolean');
    });

    it('has a width', function () {
      expect(model.get('width')).to.match(/^\d+px$/);
    });

    it('has a height', function () {
      expect(model.get('height')).to.match(/^\d+px$/);
    });

    it('has alignment', function () {
      expect(model.get('styles.block.textAlign')).to.match(/^(left|center|right)$/);
    });

    it('changes attributes with set', function () {
      var newValue = 'someImage.png';
      model.set('src', newValue);
      expect(model.get('src')).to.equal(newValue);
    });

    it('triggers autosave when any of the attributes change', function () {
      var mock = sinon.mock().exactly(7).withArgs('autoSave');
      EditorApplication.getChannel = sinon.stub().returns({
        trigger: mock,
      });

      model.set('link', 'http://example.net');
      model.set('src', 'someNewImage.png');
      model.set('alt', 'Some alt text');
      model.set('fullWidth', false);
      model.set('width', '63px');
      model.set('height', '61px');
      model.set('styles.block.textAlign', 'right');

      mock.verify();
    });

    it('uses defaults from config when they are set', function () {
      var innerModel;
      global.stubConfig(EditorApplication, {
        blockDefaults: {
          image: {
            link: 'http://example.org/customConfigPage',
            src: 'http://example.org/someCustomConfigImage.png',
            alt: 'Custom config alt',
            fullWidth: false,
            width: '1234px',
            height: '2345px',
            styles: {
              block: {
                textAlign: 'right',
              },
            },
          },
        },
      });
      innerModel = new (ImageBlock.ImageBlockModel)();

      expect(innerModel.get('link')).to.equal('http://example.org/customConfigPage');
      expect(innerModel.get('src')).to.equal('http://example.org/someCustomConfigImage.png');
      expect(innerModel.get('alt')).to.equal('Custom config alt');
      expect(innerModel.get('fullWidth')).to.equal(false);
      expect(innerModel.get('width')).to.equal('1234px');
      expect(innerModel.get('height')).to.equal('2345px');
      expect(innerModel.get('styles.block.textAlign')).to.equal('right');
    });

    it('do not update blockDefaults.image when changed', function () {
      var stub = sandbox.stub(EditorApplication.getConfig(), 'set');
      model.trigger('change');
      expect(stub.callCount).to.equal(0);
    });
  });

  describe('block view', function () {
    global.stubChannel(EditorApplication);
    global.stubConfig(EditorApplication);
    global.stubAvailableStyles(EditorApplication);

    it('renders', function () {
      var view;
      var model;
      model = new (ImageBlock.ImageBlockModel)();
      view = new (ImageBlock.ImageBlockView)({ model: model });
      expect(view.render).to.not.throw();
      expect(view.$('.mailpoet_content')).to.have.length(1);
    });

    describe('render', function () {
      it('sets sizes to model from rendered image when they are null', function () {
        const model = new (ImageBlock.ImageBlockModel)({ width: null, height: null });
        const view = new (ImageBlock.ImageBlockView)({ model: model });
        view.render();
        view.$('.mailpoet_content img').get(0).width = 100;
        view.$('.mailpoet_content img').get(0).height = 200;
        view.$('.mailpoet_content img').trigger('load');
        expect(view.model.get('width')).to.equal(100);
        expect(view.model.get('height')).to.equal(200);
      });

      it('sets sizes to model from rendered image when they are set to auto', function () {
        const model = new (ImageBlock.ImageBlockModel)({ width: 'auto', height: 'auto' });
        const view = new (ImageBlock.ImageBlockView)({ model: model });
        view.render();
        view.$('.mailpoet_content img').get(0).width = 100;
        view.$('.mailpoet_content img').get(0).height = 200;
        view.$('.mailpoet_content img').trigger('load');
        expect(view.model.get('width')).to.equal(100);
        expect(view.model.get('height')).to.equal(200);
      });

      it('keeps sizes when they are already set', function () {
        const model = new (ImageBlock.ImageBlockModel)({ width: 300, height: 400 });
        const view = new (ImageBlock.ImageBlockView)({ model: model });
        view.render();
        view.$('.mailpoet_content img').get(0).width = 100;
        view.$('.mailpoet_content img').get(0).height = 200;
        view.$('.mailpoet_content img').trigger('load');
        expect(view.model.get('width')).to.equal(300);
        expect(view.model.get('height')).to.equal(400);
      });
    });

    describe('once rendered', function () {
      var model;
      var view;

      beforeEach(function () {
        global.stubChannel(EditorApplication);
        global.stubAvailableStyles(EditorApplication);
        model = new (ImageBlock.ImageBlockModel)({
          link: 'http://example.org/somepath',
          src: 'http://example.org/someimage.png',
          alt: 'some alt',
        });
        view = new (ImageBlock.ImageBlockView)({ model: model });
        view.render();
      });

      it('displays the image', function () {
        expect(view.$('.mailpoet_content a').attr('href')).to.equal('http://example.org/somepath');
        expect(view.$('.mailpoet_content img').attr('src')).to.equal('http://example.org/someimage.png');
        expect(view.$('.mailpoet_content img').attr('alt')).to.equal('some alt');
      });

      it('rerenders if attribute changes', function () {
        var newValue = 'http://example.org/someNEWimage.png';
        expect(view.$('.mailpoet_content img').attr('src')).to.not.equal(newValue);
        model.set('src', newValue);
        expect(view.$('.mailpoet_content img').attr('src')).to.equal(newValue);
      });

      it('opens settings if clicked on the image', function () {
        var mock = sinon.mock().once();
        model.on('startEditing', mock);
        view.$('img').click();
        mock.verify();
      });
    });
  });

  describe('block settings view', function () {
    var model;
    var view;
    var newWidth = 123;
    var newHeight = 456;
    var newLink = 'http://example.org/someNewLink';
    var newSrc = 'http://example.org/someNewImage.png';

    before(function () {
      global.stubChannel(EditorApplication);
      global.stubConfig(EditorApplication, {
        blockDefaults: {},
      });
      global.stubImage(newWidth, newHeight);
      model = new (ImageBlock.ImageBlockModel)();
      view = new (ImageBlock.ImageBlockSettingsView)({ model: model });
    });

    it('renders', function () {
      expect(view.render).to.not.throw();
    });

    describe('once rendered', function () {
      it('updates the model when link changes', function () {
        view.$('.mailpoet_field_image_link').val(newLink).trigger('input');
        expect(model.get('link')).to.equal(newLink);
      });

      it('updates the model when src changes', function () {
        view.$('.mailpoet_field_image_address').val(newSrc).trigger('input');
        expect(model.get('src')).to.equal(newSrc);
      });

      it('updates the width when src changes', function () {
        view.$('.mailpoet_field_image_address').val(newSrc).trigger('input');
        expect(model.get('width')).to.equal(newWidth + 'px');
      });

      it('updates the height when src changes', function () {
        view.$('.mailpoet_field_image_address').val(newSrc).trigger('input');
        expect(model.get('height')).to.equal(newHeight + 'px');
      });

      it('updates the model when alt changes', function () {
        var newValue = 'Some new alt text';
        view.$('.mailpoet_field_image_alt_text').val(newValue).trigger('input');
        expect(model.get('alt')).to.equal(newValue);
      });

      it('updates the model when padding changes', function () {
        view.$('.mailpoet_field_image_full_width').prop('checked', false).change();
        expect(model.get('fullWidth')).to.equal(false);
      });

      it('updates the model when alignment changes', function () {
        view.$('.mailpoet_field_image_alignment').first().prop('checked', true).change();
        expect(model.get('styles.block.textAlign')).to.equal('left');
      });

      it.skip('closes the sidepanel after "Done" is clicked', function () {
        var mock = sinon.mock().once();
        global.MailPoet.Modal.cancel = mock;
        view.$('.mailpoet_done_editing').click();
        mock.verify();
        delete (global.MailPoet.Modal.cancel);
      });
    });
  });
});
