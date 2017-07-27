define([
    'newsletter_editor/App',
    'newsletter_editor/blocks/social',
    'backbone'
  ], function(EditorApplication, SocialBlock, Backbone) {

  describe('Social', function () {
    describe('block model', function () {
      var model;
      beforeEach(function () {
        global.stubChannel(EditorApplication);
        global.stubConfig(EditorApplication);
        model = new (SocialBlock.SocialBlockModel)();
      });

      it('has a social type', function () {
        expect(model.get('type')).to.equal('social');
      });

      it('has an icon set it uses', function () {
        expect(model.get('iconSet')).to.be.a('string');
      });

      it('has icons', function () {
        expect(model.get('icons')).to.be.an.instanceof(Backbone.Collection);
      });

      it("uses defaults from config when they are set", function () {
        global.stubConfig(EditorApplication, {
          blockDefaults: {
            social: {
              iconSet: 'customConfigIconSet'
            }
          }
        });
        var model = new (SocialBlock.SocialBlockModel)();

        expect(model.get('iconSet')).to.equal('customConfigIconSet');
      });
    });

    describe('icon model', function () {
      var model;
      before(function () {
        global.stubChannel(EditorApplication);
        global.stubAvailableStyles(EditorApplication, {
          'socialIconSets.default.custom': 'someimage.jpg'
        });
        global.stubConfig(EditorApplication, {
          socialIcons: {
            custom: {
              defaultLink: 'http://example.org',
              title: 'sometitle'
            }
          }
        });
        model = new (SocialBlock.SocialIconModel)();
      });

      it('has a socialIcon type', function () {
        expect(model.get('type')).to.equal('socialIcon');
      });

      it('has a link', function () {
        expect(model.get('link')).to.be.a('string');
        expect(model.get('link')).to.equal('http://example.org');
      });

      it('has an image', function () {
        expect(model.get('image')).to.equal('someimage.jpg');
      });

      it('has height', function () {
        expect(model.get('height')).to.equal('32px');
      });

      it('has width', function () {
        expect(model.get('width')).to.equal('32px');
      });

      it('has text', function () {
        expect(model.get('text')).to.equal('sometitle');
      });
    });

    describe('block view', function () {
      var model;

      beforeEach(function () {
        global.stubChannel(EditorApplication);
        global.stubAvailableStyles(EditorApplication, {
          socialIconSets: {
            'default': {
              'custom': 'http://www.sott.net/images/icons/big_x.png'
            },
            'light': {
              'custom': 'http://content.indiainfoline.com/wc/news/ImageGallery/css/close_32x32.png'
            }
          },
          socialIcons: {
            'custom': {
              title: 'Custom',
              linkFieldName: 'Page URL',
              defaultLink: 'http://example.org'
            }
          }
        });
        model = new (SocialBlock.SocialBlockModel)({
          type: 'social',
          iconSet: 'default',
          icons: [
            {
              type: 'socialIcon',
              iconType: 'custom',
              link: 'somelink.htm',
              image: 'someimage.png',
              text: 'some text'
            }
          ]
        });
      });

      it('renders', function () {
        var view = new (SocialBlock.SocialBlockView)({model: model});
        expect(view.render).to.not.throw();
        expect(view.$('.mailpoet_social')).to.have.length(1);
      });

      describe('once rendered', function () {
        var model, view;

        before(function () {
          global.stubChannel(EditorApplication);
          model = new (SocialBlock.SocialBlockModel)({
            type: 'social',
            iconSet: 'default',
            icons: [
              {
                type: 'socialIcon',
                iconType: 'custom',
                link: 'http://example.org/',
                image: 'http://example.org/someimage.png',
                text: 'some text'
              },
              {
                type: 'socialIcon',
                iconType: 'facebook',
                link: 'http://facebook.com/',
                image: 'http://facebook.com/icon.png',
                text: 'Facebook icon'
              }
            ]
          });
          view = new (SocialBlock.SocialBlockView)({model: model});
          view.render();
        });

        it('shows multiple social icons', function () {
          expect(view.$('.mailpoet_social a').eq(0).prop('href')).to.equal('http://example.org/');
          expect(view.$('.mailpoet_social img').eq(0).prop('src')).to.equal('http://example.org/someimage.png');
          expect(view.$('.mailpoet_social img').eq(0).prop('alt')).to.equal('some text');

          expect(view.$('.mailpoet_social a').eq(1).prop('href')).to.equal('http://facebook.com/');
          expect(view.$('.mailpoet_social img').eq(1).prop('src')).to.equal('http://facebook.com/icon.png');
          expect(view.$('.mailpoet_social img').eq(1).prop('alt')).to.equal('Facebook icon');
        });
      });
    });

    describe('block settings view', function () {
      var model;

      beforeEach(function () {
        global.stubChannel(EditorApplication);
        global.stubAvailableStyles(EditorApplication, {
          socialIconSets: {
            'default': {
              'custom': 'someimage.png'
            },
            'light': {
              'custom': 'http://content.indiainfoline.com/wc/news/ImageGallery/css/close_32x32.png'
            }
          },
          socialIcons: {
            'custom': {
              title: 'Custom',
              linkFieldName: 'Page URL',
              defaultLink: 'http://example.org'
            }
          }
        });
        model = new (SocialBlock.SocialBlockModel)({
          type: 'social',
          iconSet: 'default',
          icons: [
            {
              type: 'socialIcon',
              iconType: 'custom',
              link: 'somelink.htm',
              image: 'someimage.png',
              height: '32px',
              width: '32px',
              text: 'some text'
            }
          ]
        });
      });

      it('renders', function () {
        var view = new (SocialBlock.SocialBlockSettingsView)({model: model});
        expect(view.render).to.not.throw();
      });

      describe('once rendered', function () {
        var model, view;
        beforeEach(function () {
          global.stubChannel(EditorApplication);
          global.stubAvailableStyles(EditorApplication, {
            socialIconSets: {
              'default': {
                'custom': 'http://www.sott.net/images/icons/big_x.png'
              },
              'light': {
                'custom': 'http://content.indiainfoline.com/wc/news/ImageGallery/css/close_32x32.png'
              }
            },
            socialIcons: {
              'custom': {
                title: 'Custom',
                linkFieldName: 'Page URL',
                defaultLink: 'http://example.org'
              }
            }
          });
          model = new (SocialBlock.SocialBlockModel)({
            type: 'social',
            iconSet: 'default',
            icons: [
              {
                type: 'socialIcon',
                iconType: 'custom',
                link: 'somelink.htm',
                image: 'someimage.png',
                height: '32px',
                width: '32px',
                text: 'some text'
              }
            ]
          });
          view = new (SocialBlock.SocialBlockSettingsView)({model: model});
          view.render();
        });

        it('updates icons in settings if iconset changes', function() {
          view.$('.mailpoet_social_icon_set').last().click();
          expect(view.$('.mailpoet_social_icon_field_image').val()).to.equal(EditorApplication.getAvailableStyles().get('socialIconSets.light.custom'));
        });

        it('removes the icon when "remove" is clicked', function() {
          view.$('.mailpoet_delete_block').click();
          expect(model.get('icons').length).to.equal(0);
          expect(view.$('.mailpoet_social_icon_settings').length).to.equal(0);
        });

        it('adds another icon when "Add another social network" is pressed', function() {
          view.$('.mailpoet_add_social_icon').click();
          expect(model.get('icons').length).to.equal(2);
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
