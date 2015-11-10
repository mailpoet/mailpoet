define([
    'newsletter_editor/App',
    'newsletter_editor/blocks/image'
  ], function(EditorApplication, ImageBlock) {

  describe('Image', function () {
    describe('model', function () {
      var model;
      beforeEach(function () {
        global.stubChannel(EditorApplication);
        global.stubConfig(EditorApplication, {
          blockDefaults: {},
        });
        model = new (ImageBlock.ImageBlockModel)();
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

      it('can be padded', function () {
        expect(model.get('padded')).to.be.a('boolean');
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
        model.set('padded', false);
        model.set('width', '63px');
        model.set('height', '61px');
        model.set('styles.block.textAlign', 'right');

        mock.verify();
      });

      it("uses defaults from config when they are set", function () {
        global.stubConfig(EditorApplication, {
          blockDefaults: {
            image: {
              link: 'http://example.org/customConfigPage',
              src: 'http://example.org/someCustomConfigImage.png',
              alt: 'Custom config alt',
              padded: false,
              width: '1234px',
              height: '2345px',
              styles: {
                block: {
                  textAlign: 'right',
                },
              },
            }
          },
        });
        var model = new (ImageBlock.ImageBlockModel)();

        expect(model.get('link')).to.equal('http://example.org/customConfigPage');
        expect(model.get('src')).to.equal('http://example.org/someCustomConfigImage.png');
        expect(model.get('alt')).to.equal('Custom config alt');
        expect(model.get('padded')).to.equal(false);
        expect(model.get('width')).to.equal('1234px');
        expect(model.get('height')).to.equal('2345px');
        expect(model.get('styles.block.textAlign')).to.equal('right');
      });
    });

    describe('block view', function () {
      global.stubChannel(EditorApplication);
      global.stubConfig(EditorApplication);
      global.stubAvailableStyles(EditorApplication);
      var model = new (ImageBlock.ImageBlockModel)(),
        view;

      beforeEach(function () {
        view = new (ImageBlock.ImageBlockView)({model: model});
      });

      it('renders', function () {
        expect(view.render).to.not.throw();
        expect(view.$('.mailpoet_content')).to.have.length(1);
      });

      describe('once rendered', function () {
        var model = new (ImageBlock.ImageBlockModel)({
            link: 'http://example.org/somepath',
            src: 'http://example.org/someimage.png',
            alt: 'some alt',
          }),
          view;

        beforeEach(function () {
          global.stubChannel(EditorApplication);
          global.stubAvailableStyles(EditorApplication);
          view = new (ImageBlock.ImageBlockView)({model: model});
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
      });
    });

    describe('block settings view', function () {
      var model, view;

      before(function () {
        global.stubChannel(EditorApplication);
        global.stubConfig(EditorApplication, {
          blockDefaults: {},
        });
        model = new (ImageBlock.ImageBlockModel)();
        view = new (ImageBlock.ImageBlockSettingsView)({model: model});
      });

      it('renders', function () {
        expect(view.render).to.not.throw();
      });

      describe('once rendered', function () {
        it('updates the model when link changes', function () {
          var newValue = 'http://example.org/someNewLink';
          view.$('.mailpoet_field_image_link').val(newValue).keyup();
          expect(model.get('link')).to.equal(newValue);
        });

        it('updates the model when src changes', function () {
          var newValue = 'http://example.org/someNewImage.png';
          view.$('.mailpoet_field_image_address').val(newValue).keyup();
          expect(model.get('src')).to.equal(newValue);
        });

        it('updates the model when alt changes', function () {
          var newValue = 'Some new alt text';
          view.$('.mailpoet_field_image_alt_text').val(newValue).keyup();
          expect(model.get('alt')).to.equal(newValue);
        });

        it('updates the model when padding changes', function () {
          var newValue = 'false';
          view.$('.mailpoet_field_image_padded').prop('checked', false).change();
          expect(model.get('padded')).to.equal(false);
        });

        it.skip('closes the sidepanel after "Done" is clicked', function() {
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
