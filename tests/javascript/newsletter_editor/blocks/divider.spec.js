define([
    'newsletter_editor/App',
    'newsletter_editor/blocks/divider'
  ], function(EditorApplication, DividerBlock) {

  describe("Divider", function () {
    describe("model", function () {
      var model;

      beforeEach(function () {
        global.stubChannel(EditorApplication);
        global.stubConfig(EditorApplication, {
          blockDefaults: {},
        });
        global.stubAvailableStyles(EditorApplication);
        model = new (DividerBlock.DividerBlockModel)();
      });

      afterEach(function () {
        delete EditorApplication.getChannel;
      });

      it("has a divider type", function () {
        expect(model.get('type')).to.equal('divider');
      });

      it("has a background color", function () {
        expect(model.get('styles.block.backgroundColor')).to.match(/^(#[abcdef0-9]{6})|transparent$/);
      });

      it("has padding", function () {
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

      it("changes attributes with set", function () {
        var newValue = 'outset';
        model.set('styles.block.borderStyle', newValue);
        expect(model.get('styles.block.borderStyle')).to.equal(newValue);
      });

      it("triggers autosave if any attribute changes", function () {
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

      it("uses defaults from config when they are set", function () {
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
        var model = new (DividerBlock.DividerBlockModel)();

        expect(model.get('styles.block.backgroundColor')).to.equal('#123456');
        expect(model.get('styles.block.padding')).to.equal('37px');
        expect(model.get('styles.block.borderStyle')).to.equal('inset');
        expect(model.get('styles.block.borderWidth')).to.equal('7px');
        expect(model.get('styles.block.borderColor')).to.equal('#345678');
      });
    });

    describe('block view', function () {
      global.stubChannel(EditorApplication);
      global.stubConfig(EditorApplication);
      var model = new (DividerBlock.DividerBlockModel)(),
        view;

      beforeEach(function () {
        global.stubChannel(EditorApplication);
        view = new (DividerBlock.DividerBlockView)({model: model});
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
    });

    describe('settings view', function () {
      global.stubChannel(EditorApplication);
      global.stubAvailableStyles(EditorApplication, {
        dividers: ['solid', 'inset'],
      });
      var model = new (DividerBlock.DividerBlockModel)(),
        view = new (DividerBlock.DividerBlockSettingsView)({model: model});

      it('renders', function () {
        expect(view.render).to.not.throw();
        expect(view.$('.mailpoet_divider_selector')).to.have.length(1);
      });

      describe('once rendered', function () {
        var model, view;

        before(function() {
          global.stubChannel(EditorApplication);
          global.stubAvailableStyles(EditorApplication, {
            dividers: ['solid', 'inset'],
          });
        });

        beforeEach(function () {
          model = new (DividerBlock.DividerBlockModel)();
          view = new (DividerBlock.DividerBlockSettingsView)({model: model});
          view.render();
        });

        it('updates the model when divider style changes', function () {
          view.$('.mailpoet_field_divider_style').last().click();
          expect(model.get('styles.block.borderStyle')).to.equal('inset');
        });

        it('updates the model when divider width changes', function () {
          view.$('.mailpoet_field_divider_border_width').val('17').change();
          expect(model.get('styles.block.borderWidth')).to.equal('17px');
        });

        it('updates the range slider when divider width input changes', function () {
          view.$('.mailpoet_field_divider_border_width_input').val('19').keyup();
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

        it ('changes color of available divider styles when actual divider color changes', function() {
          var newColor = '#889912';
          view.$('.mailpoet_field_divider_border_color').val(newColor).change();
          expect(view.$('.mailpoet_field_divider_style div')).to.have.$css('border-top-color', newColor);
        });

        it('does not display "Apply to all" option when `hideApplyToAll` option is active', function() {
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
          delete(global.MailPoet.Modal.cancel);
        });
      });
    });
  });
});
