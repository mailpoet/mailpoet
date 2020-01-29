import App from 'newsletter_editor/App';
import DividerBlock from 'newsletter_editor/blocks/divider';

const expect = global.expect;
const sinon = global.sinon;

var EditorApplication = App;
var sandbox;

describe('Divider', function () {
  describe('model', function () {
    var model;

    beforeEach(function () {
      global.stubChannel(EditorApplication);
      global.stubConfig(EditorApplication, {
        blockDefaults: {},
      });
      global.stubAvailableStyles(EditorApplication);
      model = new (DividerBlock.DividerBlockModel)();
      sandbox = sinon.createSandbox();
    });

    afterEach(function () {
      delete EditorApplication.getChannel;
      sandbox.restore();
    });

    it('has a divider type', function () {
      expect(model.get('type')).to.equal('divider');
    });

    it('has a background color', function () {
      expect(model.get('styles.block.backgroundColor')).to.match(/^(#[abcdef0-9]{6})|transparent$/);
    });

    it('has padding', function () {
      expect(model.get('styles.block.padding')).to.match(/^\d+px$/);
    });

    it('has border style', function () {
      expect(model.get('styles.block.borderStyle')).to.match(/^(none|dotted|dashed|solid|double|groove|ridge|inset|outset)$/);
    });

    it('has border width', function () {
      expect(model.get('styles.block.borderWidth')).to.match(/^\d+px$/);
    });

    it('has border color', function () {
      expect(model.get('styles.block.borderColor')).to.match(/^(#[abcdef0-9]{6})|transparent$/);
    });

    it('changes attributes with set', function () {
      var newValue = 'outset';
      model.set('styles.block.borderStyle', newValue);
      expect(model.get('styles.block.borderStyle')).to.equal(newValue);
    });

    it('triggers autosave if any attribute changes', function () {
      var mock = sinon.mock().exactly(5).withArgs('autoSave');
      EditorApplication.getChannel = sinon.stub().returns({
        trigger: mock,
      });

      model.set('styles.block.backgroundColor', '#000000');
      model.set('styles.block.padding', '19px');
      model.set('styles.block.borderStyle', 'double');
      model.set('styles.block.borderWidth', '17px');
      model.set('styles.block.borderColor', '#123456');

      mock.verify();
    });

    it('uses defaults from config when they are set', function () {
      var innerModel;
      global.stubConfig(EditorApplication, {
        blockDefaults: {
          divider: {
            styles: {
              block: {
                backgroundColor: '#123456',
                padding: '37px',
                borderStyle: 'inset',
                borderWidth: '7px',
                borderColor: '#345678',
              },
            },
          },
        },
      });
      innerModel = new (DividerBlock.DividerBlockModel)();

      expect(innerModel.get('styles.block.backgroundColor')).to.equal('#123456');
      expect(innerModel.get('styles.block.padding')).to.equal('37px');
      expect(innerModel.get('styles.block.borderStyle')).to.equal('inset');
      expect(innerModel.get('styles.block.borderWidth')).to.equal('7px');
      expect(innerModel.get('styles.block.borderColor')).to.equal('#345678');
    });

    it('updates blockDefaults.divider when changed', function () {
      var stub = sandbox.stub(EditorApplication.getConfig(), 'set');
      model.trigger('change');
      expect(stub.callCount).to.equal(1);
      expect(stub.getCall(0).args[0]).to.equal('blockDefaults.divider');
      expect(stub.getCall(0).args[1]).to.deep.equal(model.toJSON());
    });

    it('updates blockDefaults for usage context when changed', function () {
      var stub = sandbox.stub(EditorApplication.getConfig(), 'set');
      model.set('context', 'posts.divider');
      expect(stub.callCount).to.equal(1);
      expect(stub.getCall(0).args[0]).to.equal('blockDefaults.posts.divider');
      expect(stub.getCall(0).args[1]).to.deep.equal(model.toJSON());
    });
  });

  describe('block view', function () {
    var model;
    var view;

    beforeEach(function () {
      global.stubChannel(EditorApplication);
      global.stubConfig(EditorApplication);
      model = new (DividerBlock.DividerBlockModel)();
      view = new (DividerBlock.DividerBlockView)({ model: model });
    });

    it('renders', function () {
      expect(view.render).to.not.throw();
      expect(view.$('.mailpoet_divider')).to.have.length(1);
    });

    it('rerenders if model attributes change', function () {
      view.render();

      model.set('styles.block.borderStyle', 'inset');

      expect(view.$('.mailpoet_divider').css('border-top-style')).to.equal('inset');
    });

    it('opens settings if clicked', function () {
      var mock = sinon.mock().once();
      model.on('startEditing', mock);
      view.render();
      view.$('.mailpoet_divider').click();
      mock.verify();
    });

    it('does not open settings if clicked on the resize handle', function () {
      var mock = sinon.mock().never();
      model.on('startEditing', mock);
      view.render();
      view.$('.mailpoet_resize_handle').click();
      mock.verify();
    });
  });

  describe('settings view', function () {
    global.stubChannel(EditorApplication);
    global.stubConfig(EditorApplication);
    global.stubAvailableStyles(EditorApplication, {
      dividers: ['solid', 'inset'],
    });

    it('renders', function () {
      var model;
      var view;
      model = new (DividerBlock.DividerBlockModel)();
      view = new (DividerBlock.DividerBlockSettingsView)({ model: model });
      expect(view.render).to.not.throw();
      expect(view.$('.mailpoet_divider_selector')).to.have.length(1);
    });

    describe('once rendered', function () {
      var model;
      var view;

      before(function () {
        global.stubChannel(EditorApplication);
        global.stubAvailableStyles(EditorApplication, {
          dividers: ['solid', 'inset'],
        });
      });

      beforeEach(function () {
        model = new (DividerBlock.DividerBlockModel)();
        view = new (DividerBlock.DividerBlockSettingsView)({ model: model });
        view.render();
      });

      it('updates the model when divider style changes', function () {
        view.$('.mailpoet_field_divider_style').last().click();
        expect(model.get('styles.block.borderStyle')).to.equal('inset');
      });

      it('updates the model when divider width slider changes', function () {
        view.$('.mailpoet_field_divider_border_width').val('17').change();
        expect(model.get('styles.block.borderWidth')).to.equal('17px');
      });

      it('updates the range slider when divider width input changes', function () {
        view.$('.mailpoet_field_divider_border_width_input').val('19').trigger('input');
        expect(view.$('.mailpoet_field_divider_border_width').val()).to.equal('19');
      });

      it('updates the input when divider width range slider changes', function () {
        view.$('.mailpoet_field_divider_border_width').val('19').change();
        expect(view.$('.mailpoet_field_divider_border_width_input').val()).to.equal('19');
      });

      it('updates the model when divider color changes', function () {
        view.$('.mailpoet_field_divider_border_color').val('#123457').change();
        expect(model.get('styles.block.borderColor')).to.equal('#123457');
      });

      it('updates the model when divider background color changes', function () {
        view.$('.mailpoet_field_divider_background_color').val('#cccccc').change();
        expect(model.get('styles.block.backgroundColor')).to.equal('#cccccc');
      });

      it('changes color of available divider styles when actual divider color changes', function () {
        var newColor = '#889912';
        view.$('.mailpoet_field_divider_border_color').val(newColor).change();
        expect(view.$('.mailpoet_field_divider_style div')).to.have.$css('border-top-color', newColor);
      });

      it('does not display "Apply to all" option when `hideApplyToAll` option is active', function () {
        view = new (DividerBlock.DividerBlockSettingsView)({
          model: model,
          renderOptions: {
            hideApplyToAll: true,
          },
        });
        view.render();
        expect(view.$('.mailpoet_button_divider_apply_to_all').length).to.equal(0);
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
