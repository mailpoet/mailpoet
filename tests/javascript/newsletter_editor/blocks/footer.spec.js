define([
    'newsletter_editor/App',
    'newsletter_editor/blocks/footer'
  ], function(EditorApplication, FooterBlock) {

  describe('Footer', function () {
    describe('model', function () {
      var model;
      beforeEach(function () {
        global.stubChannel(EditorApplication);
        global.stubConfig(EditorApplication, {
          blockDefaults: {}
        });
        model = new (FooterBlock.FooterBlockModel)();
      });

      it('has a footer type', function () {
        expect(model.get('type')).to.equal('footer');
      });

      it('has text', function () {
        expect(model.get('text')).to.be.a('string');
      });

      it('has a background color', function () {
        expect(model.get('styles.block.backgroundColor')).to.match(/^(#[abcdef0-9]{6})|transparent$/);
      });

      it('has a text color', function () {
        expect(model.get('styles.text.fontColor')).to.match(/^(#[abcdef0-9]{6})|transparent$/);
      });

      it('has a font family', function () {
        expect(model.get('styles.text.fontFamily')).to.equal('Arial');
      });

      it('has a font size', function () {
        expect(model.get('styles.text.fontSize')).to.match(/^\d+px$/);
      });

      it('has text alignment', function () {
        expect(model.get('styles.text.textAlign')).to.match(/^(left|center|right|justify)$/);
      });

      it('has a link color', function () {
        expect(model.get('styles.link.fontColor')).to.match(/^(#[abcdef0-9]{6})|transparent$/);
      });

      it('has link decoration', function () {
        expect(model.get('styles.link.textDecoration')).to.match(/^(underline|none)$/);
      });

      it('changes attributes with set', function () {
        var newValue = 'Some New Text';
        model.set('text', newValue);
        expect(model.get('text')).to.equal(newValue);
      });

      it('triggers autosave when any of the attributes change', function () {
        var mock = sinon.mock().exactly(8).withArgs('autoSave');
        EditorApplication.getChannel = sinon.stub().returns({
          trigger: mock
        });

        model.set('text', 'Some new text');
        model.set('styles.block.backgroundColor', '#123456');
        model.set('styles.text.fontColor', '#123456');
        model.set('styles.text.fontFamily', 'SomeFontCT');
        model.set('styles.text.fontSize', '23px');
        model.set('styles.text.textAlign', 'justify');
        model.set('styles.link.fontColor', '#123456');
        model.set('styles.link.textDecoration', 'underline');

        mock.verify();
      });

      it("uses defaults from config when they are set", function () {
        global.stubConfig(EditorApplication, {
          blockDefaults: {
            footer: {
              text: 'some custom config text',
              styles: {
                block: {
                  backgroundColor: '#123456'
                },
                text: {
                  fontColor: '#234567',
                  fontFamily: 'Tahoma',
                  fontSize: '37px',
                  textAlign: 'right'
                },
                link: {
                  fontColor: '#345678',
                  textDecoration: 'underline'
                }
              }
            }
          }
        });
        var model = new (FooterBlock.FooterBlockModel)();

        expect(model.get('text')).to.equal('some custom config text');
        expect(model.get('styles.block.backgroundColor')).to.equal('#123456');
        expect(model.get('styles.text.fontColor')).to.equal('#234567');
        expect(model.get('styles.text.fontFamily')).to.equal('Tahoma');
        expect(model.get('styles.text.fontSize')).to.equal('37px');
        expect(model.get('styles.text.textAlign')).to.equal('right');
        expect(model.get('styles.link.fontColor')).to.equal('#345678');
        expect(model.get('styles.link.textDecoration')).to.equal('underline');
      });

    });

    describe('block view', function () {
      global.stubChannel(EditorApplication);
      global.stubConfig(EditorApplication);
      global.stubAvailableStyles(EditorApplication);
      var model = new (FooterBlock.FooterBlockModel)(),
        view;

      beforeEach(function () {
        global.stubChannel(EditorApplication);
        view = new (FooterBlock.FooterBlockView)({model: model});
      });

      it('renders', function () {
        expect(view.render).to.not.throw();
        expect(view.$('.mailpoet_content')).to.have.length(1);
      });
    });

    describe('settings view', function () {
      global.stubChannel(EditorApplication);
      global.stubAvailableStyles(EditorApplication, {
        fonts: ['Arial', 'Tahoma'],
        textSizes: ['16px', '20px']
      });
      var model = new (FooterBlock.FooterBlockModel)(),
        view = new (FooterBlock.FooterBlockSettingsView)({model: model});

      it('renders', function () {
        expect(view.render).to.not.throw();
        expect(view.$('.mailpoet_field_footer_text_color')).to.have.length(1);
      });

      describe('once rendered', function () {
      var model, view;

      beforeEach(function() {
        global.stubChannel(EditorApplication);
        global.stubAvailableStyles(EditorApplication, {
          fonts: ['Arial', 'Tahoma'],
          textSizes: ['16px', '20px']
        });
        model = new (FooterBlock.FooterBlockModel)({});
        view = new (FooterBlock.FooterBlockSettingsView)({model: model});
        view.render();
      });

        it('updates the model when text font color changes', function () {
          view.$('.mailpoet_field_footer_text_color').val('#123456').change();
          expect(model.get('styles.text.fontColor')).to.equal('#123456');
        });

        it('updates the model when text font family changes', function () {
          var value = 'Tahoma';
          view.$('.mailpoet_field_footer_text_font_family').val(value).change();
          expect(model.get('styles.text.fontFamily')).to.equal(value);
        });

        it('updates the model when text font size changes', function () {
          var value = '20px';
          view.$('.mailpoet_field_footer_text_size').val(value).change();
          expect(model.get('styles.text.fontSize')).to.equal(value);
        });

        it('updates the model when link font color changes', function () {
          view.$('#mailpoet_field_footer_link_color').val('#123456').change();
          expect(model.get('styles.link.fontColor')).to.equal('#123456');
        });

        it('updates the model when link text decoration changes', function () {
          view.$('#mailpoet_field_footer_link_underline').prop('checked', true).change();
          expect(model.get('styles.link.textDecoration')).to.equal('underline');
        });

        it('updates the model when background color changes', function () {
          view.$('.mailpoet_field_footer_alignment').last().prop('checked', true).change();
          expect(model.get('styles.text.textAlign')).to.equal('right');
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
