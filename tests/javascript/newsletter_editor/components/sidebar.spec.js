define([
    'newsletter_editor/App',
    'newsletter_editor/components/sidebar'
  ], function(EditorApplication, SidebarComponent) {

  describe('Sidebar', function() {
    describe('content view', function() {
      var view;
      beforeEach(function() {
        view = new (SidebarComponent.SidebarWidgetsView)({
          collection: new Backbone.Collection([])
        });
      });

      it('renders', function() {
        expect(view.render).to.not.throw();
      });
    });

    describe('layout view', function() {
      var view;
      beforeEach(function() {
        view = new (SidebarComponent.SidebarLayoutWidgetsView)({
          collection: new Backbone.Collection([])
        });
      });

      it('renders', function() {
        expect(view.render).to.not.throw();
      });
    });

    describe('styles view', function() {
      var view;
      beforeEach(function() {
        view = new (SidebarComponent.SidebarStylesView)({
          model: new Backbone.SuperModel({}),
          availableStyles: new Backbone.SuperModel({})
        });
      });

      it('renders', function() {
        expect(view.render).to.not.throw();
      });

      describe('once rendered', function() {
        var model, availableStyles, view;
        before(function() {
          model = new Backbone.SuperModel({
            text: {
              fontColor: '#000000',
              fontFamily: 'Arial'
            },
            h1: {
              fontColor: '#000001',
              fontFamily: 'Arial'
            },
            h2: {
              fontColor: '#000002',
              fontFamily: 'Arial'
            },
            h3: {
              fontColor: '#000003',
              fontFamily: 'Arial'
            },
            link: {
              fontColor: '#000005',
              textDecoration: 'none'
            },
            wrapper: {
              backgroundColor: '#090909'
            },
            body: {
              backgroundColor: '#020202'
            }
          });
          availableStyles = new Backbone.SuperModel({
            fonts: ['Arial', 'Times New Roman', 'Tahoma', 'Comic Sans', 'Lucida'],
            textSizes: [
              '9px', '10px'
            ],
            headingSizes: [
              '10px', '12px', '14px', '16px', '18px'
            ]
          });
          view = new (SidebarComponent.SidebarStylesView)({
            model: model,
            availableStyles: availableStyles
          });

          view.render();
        });

        it('changes model if text font color field changes', function() {
          view.$('#mailpoet_text_font_color').val('#123456').change();
          expect(model.get('text.fontColor')).to.equal('#123456');
        });

        it('changes model if h1 font color field changes', function() {
          view.$('#mailpoet_h1_font_color').val('#123457').change();
          expect(model.get('h1.fontColor')).to.equal('#123457');
        });

        it('changes model if h2 font color field changes', function() {
          view.$('#mailpoet_h2_font_color').val('#123458').change();
          expect(model.get('h2.fontColor')).to.equal('#123458');
        });

        it('changes model if h3 font color field changes', function() {
          view.$('#mailpoet_h3_font_color').val('#123426').change();
          expect(model.get('h3.fontColor')).to.equal('#123426');
        });

        it('changes model if link font color field changes', function() {
          view.$('#mailpoet_a_font_color').val('#323232').change();
          expect(model.get('link.fontColor')).to.equal('#323232');
        });

        it('changes model if newsletter background color field changes', function() {
          view.$('#mailpoet_newsletter_background_color').val('#636237').change();
          expect(model.get('wrapper.backgroundColor')).to.equal('#636237');
        });

        it('changes model if background color field changes', function() {
          view.$('#mailpoet_background_color').val('#878587').change();
          expect(model.get('body.backgroundColor')).to.equal('#878587');
        });

        it('changes model if text font family field changes', function() {
          view.$('#mailpoet_text_font_family').val('Times New Roman').change();
          expect(model.get('text.fontFamily')).to.equal('Times New Roman');
        });

        it('changes model if h1 font family field changes', function() {
          view.$('#mailpoet_h1_font_family').val('Comic Sans').change();
          expect(model.get('h1.fontFamily')).to.equal('Comic Sans');
        });

        it('changes model if h2 font family field changes', function() {
          view.$('#mailpoet_h2_font_family').val('Tahoma').change();
          expect(model.get('h2.fontFamily')).to.equal('Tahoma');
        });

        it('changes model if h3 font family field changes', function() {
          view.$('#mailpoet_h3_font_family').val('Lucida').change();
          expect(model.get('h3.fontFamily')).to.equal('Lucida');
        });

        it('changes model if text font size field changes', function() {
          view.$('#mailpoet_text_font_size').val('9px').change();
          expect(model.get('text.fontSize')).to.equal('9px');
        });

        it('changes model if h1 font size field changes', function() {
          view.$('#mailpoet_h1_font_size').val('12px').change();
          expect(model.get('h1.fontSize')).to.equal('12px');
        });

        it('changes model if h2 font size field changes', function() {
          view.$('#mailpoet_h2_font_size').val('14px').change();
          expect(model.get('h2.fontSize')).to.equal('14px');
        });

        it('changes model if h3 font size field changes', function() {
          view.$('#mailpoet_h3_font_size').val('16px').change();
          expect(model.get('h3.fontSize')).to.equal('16px');
        });

        it('changes model if link underline field changes', function() {
          view.$('#mailpoet_a_font_underline').prop('checked', true).change();
          expect(model.get('link.textDecoration')).to.equal('underline');
        });
      });
    });

    describe('preview view', function() {
      var view;
      beforeEach(function() {
        view = new (SidebarComponent.SidebarPreviewView)();
      });

      it('renders', function() {
        expect(view.render).to.not.throw();
      });
    });
  });
});
