const expect = global.expect;
const sinon = global.sinon;

define([
  'newsletter_editor/App',
  'newsletter_editor/blocks/button'
], function(App, ButtonBlock) {
  var EditorApplication = App;

  describe('Button', function () {
    describe('model', function () {
      var model;

      beforeEach(function () {
        global.stubChannel(EditorApplication);
        global.stubConfig(EditorApplication, {
          blockDefaults: {}
        });
        model = new (ButtonBlock.ButtonBlockModel)();
      });

      afterEach(function () {
        if(EditorApplication.getChannel) {
          delete EditorApplication.getChannel;
        }
      });

      it('has a button type', function () {
        expect(model.get('type')).to.equal('button');
      });

      it('has a label', function () {
        expect(model.get('text')).to.be.a('string');
      });

      it('has a url', function () {
        expect(model.get('url')).to.be.a('string');
      });

      it('has a block background color', function () {
        expect(model.get('styles.block.backgroundColor')).to.match(/^(#[abcdef0-9]{6})|transparent$/);
      });

      it('has a block border color', function () {
        expect(model.get('styles.block.borderColor')).to.match(/^(#[abcdef0-9]{6})|transparent$/);
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

      it('has a text color', function () {
        expect(model.get('styles.block.fontColor')).to.match(/^(#[abcdef0-9]{6})|transparent$/);
      });

      it('has a text font family', function () {
        expect(model.get('styles.block.fontFamily')).to.be.a('string');
      });

      it('has a text size', function () {
        expect(model.get('styles.block.fontSize')).to.match(/^\d+px$/);
      });

      it('has a text font weight', function () {
        expect(model.get('styles.block.fontWeight')).to.match(/^(bold|normal)$/);
      });

      it('has width', function () {
        expect(model.get('styles.block.width')).to.match(/^\d+px$/);
      });

      it('has line height', function () {
        expect(model.get('styles.block.lineHeight')).to.match(/^\d+px$/);
      });

      it('changes attributes with set', function () {
        var newText = 'Some new text';
        model.set('text', newText);
        expect(model.get('text')).to.equal(newText);
      });

      it('triggers autosave if any attribute changes', function () {
        var mock = sinon.mock().exactly(12).withArgs('autoSave');
        EditorApplication.getChannel = sinon.stub().returns({
          trigger: mock
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

      it('uses defaults from config when they are set', function () {
        global.stubConfig(EditorApplication, {
          blockDefaults: {
            button: {
              text: 'Some new text',
              url: 'http://somenewurl.com',
              styles: {
                block: {
                  backgroundColor: '#123456',
                  borderColor: '#234567',
                  borderWidth: '11px',
                  borderRadius: '13px',
                  borderStyle: 'solid',
                  width: '371px',
                  lineHeight: '107px',
                  fontColor: '#345678',
                  fontFamily: 'Tahoma',
                  fontSize: '30px',
                  fontWeight: 'bold'
                }
              }
            }
          }
        });
        var model = new (ButtonBlock.ButtonBlockModel)();

        expect(model.get('text')).to.equal('Some new text');
        expect(model.get('url')).to.equal('http://somenewurl.com');
        expect(model.get('styles.block.backgroundColor')).to.equal('#123456');
        expect(model.get('styles.block.borderColor')).to.equal('#234567');
        expect(model.get('styles.block.borderWidth')).to.equal('11px');
        expect(model.get('styles.block.borderRadius')).to.equal('13px');
        expect(model.get('styles.block.borderStyle')).to.equal('solid');
        expect(model.get('styles.block.width')).to.equal('371px');
        expect(model.get('styles.block.lineHeight')).to.equal('107px');
        expect(model.get('styles.block.fontColor')).to.equal('#345678');
        expect(model.get('styles.block.fontFamily')).to.equal('Tahoma');
        expect(model.get('styles.block.fontSize')).to.equal('30px');
        expect(model.get('styles.block.fontWeight')).to.equal('bold');
      });
    });

    describe('block view', function () {
      var model;

      beforeEach(function () {
        global.stubChannel(EditorApplication);
        model = new (ButtonBlock.ButtonBlockModel)();
      });

      it('renders', function () {
        var view = new (ButtonBlock.ButtonBlockView)({model: model});
        expect(view.render).to.not.throw();
        expect(view.$('.mailpoet_editor_button')).to.have.length(1);
      });

      it('rerenders when attributes change', function () {
        var view = new (ButtonBlock.ButtonBlockView)({model: model});
        view.render();

        model.set('text', 'Some new text');

        expect(view.$('.mailpoet_editor_button').text()).to.equal('Some new text');
      });

      describe('once rendered', function () {
        var model;
        var view;

        before(function () {
          global.stubChannel(EditorApplication);
          model = new (ButtonBlock.ButtonBlockModel)({
            text: 'Some button',
            url: 'http://example.org',
            styles: {
              block: {
                backgroundColor: '#123456',
                borderColor: '#234567',
                borderWidth: '7px',
                borderRadius: '8px',
                borderStyle: 'solid',
                width: '123px',
                lineHeight: '45px',
                fontColor: '#345678',
                fontFamily: 'Arial',
                fontSize: '12px',
                fontWeight: 'bold'
              }
            }
          });
          view = new (ButtonBlock.ButtonBlockView)({model: model});
          view.render();
        });

        it('has a specified text', function () {
          expect(view.$('.mailpoet_editor_button').text()).to.equal(model.get('text'));
        });

        it('has a specified button url', function () {
          expect(view.$('.mailpoet_editor_button').attr('href')).to.equal(model.get('url'));
        });

        it('has a specified background color', function () {
          // jQuery colors appear in rgb format, not hex6
          expect(view.$('.mailpoet_editor_button').css('background-color')).to.equal('rgb(18, 52, 86)');
        });

        it('has a specified border color', function () {
          expect(view.$('.mailpoet_editor_button').css('border-color')).to.equal(model.get('styles.block.borderColor'));
        });

        it('has a specified border width', function () {
          expect(view.$('.mailpoet_editor_button').css('border-width')).to.equal(model.get('styles.block.borderWidth'));
        });

        it('has a specified border radius', function () {
          expect(view.$('.mailpoet_editor_button').css('border-radius')).to.equal(model.get('styles.block.borderRadius'));
        });

        it('has a specified border style', function () {
          expect(view.$('.mailpoet_editor_button').css('border-style')).to.equal(model.get('styles.block.borderStyle'));
        });

        it('has a specified width', function () {
          expect(view.$('.mailpoet_editor_button').css('width')).to.equal(model.get('styles.block.width'));
        });

        it('has a specified line height', function () {
          expect(view.$('.mailpoet_editor_button').css('lineHeight')).to.equal(model.get('styles.block.lineHeight'));
        });

        it('has a specified font color', function () {
          // jQuery colors appear in rgb format, not hex6
          expect(view.$('.mailpoet_editor_button').css('color')).to.equal('rgb(52, 86, 120)');
        });

        it('has a specified font family', function () {
          expect(view.$('.mailpoet_editor_button').css('font-family')).to.contain(model.get('styles.block.fontFamily'));
        });

        it('has a specified font size', function () {
          expect(view.$('.mailpoet_editor_button').css('font-size')).to.equal(model.get('styles.block.fontSize'));
        });

        it('has a specified font weight', function () {
          expect(view.$('.mailpoet_editor_button').css('font-weight')).to.equal(model.get('styles.block.fontWeight'));
        });

        it('opens settings if clicked', function () {
          var mock = sinon.mock().once();
          model.on('startEditing', mock);
          view.$('.mailpoet_editor_button').click();
          mock.verify();
        });
      });
    });

    describe('replaceAllButtonStyles', function () {
      var model;
      var view;
      var onStub;

      beforeEach(function () {
        onStub = sinon.stub();
        global.stubChannel(EditorApplication, {on: onStub});
        model = {set: sinon.stub(), toJSON: sinon.stub()};
        view = new (ButtonBlock.ButtonBlockView)({model: model});
        view.render();
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
              borderRadius: '14px'
            }
          }
        };
        callback(data);
        expect(model.set).to.have.been.callCount(1);
        expect(model.set).to.have.been.calledWithExactly(data);
      });
    });

    describe('block settings view', function () {
      var model;

      beforeEach(function () {
        global.stubChannel(EditorApplication);
        global.stubAvailableStyles(EditorApplication, {
          fonts: ['Arial', 'Tahoma'],
          headingSizes: ['16px', '20px']
        });

        model = new (ButtonBlock.ButtonBlockModel)({
          type: 'button',
          text: 'Some random text'
        });
      });

      it('renders', function () {
        var view = new (ButtonBlock.ButtonBlockSettingsView)({model: model});
        expect(view.render).to.not.throw();
      });

      describe('once rendered', function () {
        var model;
        var view;
        before(function() {
          global.stubChannel(EditorApplication);
          global.stubConfig(EditorApplication);
          global.stubAvailableStyles(EditorApplication, {
            fonts: ['Arial', 'Tahoma'],
            headingSizes: ['16px', '20px']
          });
        });

        beforeEach(function() {
          model = new (ButtonBlock.ButtonBlockModel)({
            type: 'button',
            text: 'Some random text'
          });
          view = new (ButtonBlock.ButtonBlockSettingsView)({model: model});

          view.render();
        });

        it('updates the model when text is changed', function () {
          var newValue = 'something else';

          view.$('.mailpoet_field_button_text').val(newValue).trigger('input');

          expect(model.get('text')).to.equal(newValue);
        });

        it('updates the model when link is changed', function () {
          var newValue = 'http://google.com/?q=123456';

          view.$('.mailpoet_field_button_url').val(newValue).trigger('input');

          expect(model.get('url')).to.equal(newValue);
        });

        it('updates the model when font color changes', function () {
          var newValue = '#cccccc';

          view.$('.mailpoet_field_button_font_color').val(newValue).change();

          expect(model.get('styles.block.fontColor')).to.equal(newValue);
        });

        it('updates the model when font family changes', function () {
          var newValue = 'Tahoma';

          view.$('.mailpoet_field_button_font_family').val(newValue).change();

          expect(model.get('styles.block.fontFamily')).to.equal(newValue);
        });

        it('updates the model when font size changes', function () {
          var newValue = '20px';
          view.$('.mailpoet_field_button_font_size').val(newValue).change();
          expect(model.get('styles.block.fontSize')).to.equal(newValue);
        });

        it('updates the model when font weight changes', function () {
          var newValue = 'bold';
          view.$('.mailpoet_field_button_font_weight').prop('checked', true).change();
          expect(model.get('styles.block.fontWeight')).to.equal(newValue);
        });

        it('updates the model when background color changes', function () {
          var newValue = '#cccccc';

          view.$('.mailpoet_field_button_background_color').val(newValue).change();

          expect(model.get('styles.block.backgroundColor')).to.equal(newValue);
        });

        it('updates the model when border color changes', function () {
          var newValue = '#cccccc';

          view.$('.mailpoet_field_button_border_color').val(newValue).change();

          expect(model.get('styles.block.borderColor')).to.equal(newValue);
        });

        it('updates the model when border width changes', function () {
          view.$('.mailpoet_field_button_border_width').val('3').change();
          expect(model.get('styles.block.borderWidth')).to.equal('3px');
        });
        it('updates the range slider when border width input changes', function () {
          view.$('.mailpoet_field_button_border_width_input').val('5').trigger('input');
          expect(view.$('.mailpoet_field_button_border_width').val()).to.equal('5');
        });
        it('updates the input when border width range slider changes', function () {
          view.$('.mailpoet_field_button_border_width').val('4').change();
          expect(view.$('.mailpoet_field_button_border_width_input').val()).to.equal('4');
        });

        it('updates the model when border radius changes', function () {
          view.$('.mailpoet_field_button_border_radius').val('7').change();
          expect(model.get('styles.block.borderRadius')).to.equal('7px');
        });
        it('updates the range slider when border radius input changes', function () {
          view.$('.mailpoet_field_button_border_radius_input').val('7').trigger('input');
          expect(view.$('.mailpoet_field_button_border_radius').val()).to.equal('7');
        });
        it('updates the input when border radius range slider changes', function () {
          view.$('.mailpoet_field_button_border_radius').val('7').change();
          expect(view.$('.mailpoet_field_button_border_radius_input').val()).to.equal('7');
        });

        it('updates the model when width changes', function () {
          view.$('.mailpoet_field_button_width').val('127').change();
          expect(model.get('styles.block.width')).to.equal('127px');
        });
        it('updates the range slider when width input changes', function () {
          view.$('.mailpoet_field_button_width_input').val('127').trigger('input');
          expect(view.$('.mailpoet_field_button_width').val()).to.equal('127');
        });
        it('updates the input when width range slider changes', function () {
          view.$('.mailpoet_field_button_width').val('127').change();
          expect(view.$('.mailpoet_field_button_width_input').val()).to.equal('127');
        });

        it('updates the model when line height changes', function () {
          view.$('.mailpoet_field_button_line_height').val('37').change();
          expect(model.get('styles.block.lineHeight')).to.equal('37px');
        });
        it('updates the range slider when line height input changes', function () {
          view.$('.mailpoet_field_button_line_height_input').val('37').trigger('input');
          expect(view.$('.mailpoet_field_button_line_height').val()).to.equal('37');
        });
        it('updates the input when line height range slider changes', function () {
          view.$('.mailpoet_field_button_line_height').val('37').change();
          expect(view.$('.mailpoet_field_button_line_height_input').val()).to.equal('37');
        });

        it('does not display link option when `hideLink` option is active', function() {
          view = new (ButtonBlock.ButtonBlockSettingsView)({
            model: model,
            renderOptions: {
              hideLink: true
            }
          });
          view.render();
          expect(view.$('.mailpoet_field_button_url').length).to.equal(0);
        });

        it('does not display "Apply to all" option when `hideApplyToAll` option is active', function() {
          view = new (ButtonBlock.ButtonBlockSettingsView)({
            model: model,
            renderOptions: {
              hideApplyToAll: true
            }
          });
          view.render();
          expect(view.$('.mailpoet_field_button_replace_all_styles').length).to.equal(0);
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
