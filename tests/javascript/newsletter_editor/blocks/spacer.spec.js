define([
    'newsletter_editor/App',
    'newsletter_editor/blocks/spacer'
  ], function(EditorApplication, SpacerBlock) {

  describe('Spacer', function () {
    describe('model', function () {
      var model;

      beforeEach(function () {
        global.stubChannel(EditorApplication);
        global.stubConfig(EditorApplication, {
          blockDefaults: {}
        });
        global.stubAvailableStyles(EditorApplication);
        model = new (SpacerBlock.SpacerBlockModel)();
      });

      afterEach(function () {
        delete EditorApplication.getChannel;
      });

      it('has spacer type', function () {
        expect(model.get('type')).to.equal('spacer');
      });

      it('has height', function () {
        expect(model.get('styles.block.height')).to.match(/\d+px/);
      });

      it('has a background color', function () {
        expect(model.get('styles.block.backgroundColor')).to.match(/^(#[abcdef0-9]{6})|transparent$/);
      });

      it("changes attributes with set", function () {
        var newValue = '33px';
        model.set('styles.block.height', newValue);
        expect(model.get('styles.block.height')).to.equal(newValue);
      });

      it("triggers autosave if any attribute changes", function () {
        var mock = sinon.mock().exactly(2).withArgs('autoSave');
        EditorApplication.getChannel = sinon.stub().returns({
          trigger: mock
        });

        model.set('styles.block.backgroundColor', '#000000');
        model.set('styles.block.height', '77px');

        mock.verify();
      });

      it("uses defaults from config when they are set", function () {
        global.stubConfig(EditorApplication, {
          blockDefaults: {
            spacer: {
              styles: {
                block: {
                  backgroundColor: '#567890',
                  height: '19px'
                }
              }
            }
          }
        });
        var model = new (SpacerBlock.SpacerBlockModel)();

        expect(model.get('styles.block.backgroundColor')).to.equal('#567890');
        expect(model.get('styles.block.height')).to.equal('19px');
      });
    });

    describe('block view', function () {
      var model;
      var view;

      beforeEach(function () {
        global.stubChannel(EditorApplication);
        global.stubConfig(EditorApplication);
        global.stubAvailableStyles(EditorApplication);
        model = new (SpacerBlock.SpacerBlockModel)();
        view = new (SpacerBlock.SpacerBlockView)({model: model});
      });

      it('renders', function () {
        expect(view.render).to.not.throw();
        expect(view.$('.mailpoet_spacer')).to.have.length(1);
        expect(view.$('.mailpoet_spacer').css('background-color')).to.equal(model.get('styles.block.backgroundColor'));
        expect(view.$('.mailpoet_spacer').css('height')).to.equal(model.get('styles.block.height'));
      });

      it('rerenders if model attributes change', function () {
        view.render();

        model.set('styles.block.height', '71px');

        expect(view.$('.mailpoet_spacer').css('height')).to.equal('71px');
      });

      it('opens settings if clicked', function () {
        var mock = sinon.mock().once();
        model.on('startEditing', mock);
        view.render();
        view.$('.mailpoet_spacer').click();
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

      var model = new (SpacerBlock.SpacerBlockModel)(),
        view;

      beforeEach(function () {
        global.stubChannel(EditorApplication);
        view = new (SpacerBlock.SpacerBlockSettingsView)({model: model});
      });

      it('renders', function () {
        expect(view.render).to.not.throw();
      });

      describe('once rendered', function () {
        var view, model;
        beforeEach(function() {
        global.stubChannel(EditorApplication);
        global.stubConfig(EditorApplication);
        model = new (SpacerBlock.SpacerBlockModel)();
        view = new (SpacerBlock.SpacerBlockSettingsView)({model: model});
        view.render();
        });

        it('updates the model when background color changes', function () {
          view.$('.mailpoet_field_spacer_background_color').val('#123456').change();
          expect(model.get('styles.block.backgroundColor')).to.equal('#123456');
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
