import App from 'newsletter_editor/App';
import ContainerBlock from 'newsletter_editor/blocks/container';

const expect = global.expect;
const sinon = global.sinon;
const Backbone = global.Backbone;

var EditorApplication = App;

describe('Container', function () {
  var ModelClass = ContainerBlock.ContainerBlockModel;

  describe('model', function () {
    describe('by default', function () {
      var model;
      global.stubConfig(EditorApplication);
      model = new ModelClass();

      it('has container type', function () {
        expect(model.get('type')).to.equal('container');
      });

      it('has orientation', function () {
        expect(model.get('orientation')).to.equal('vertical');
      });

      it('has a background color', function () {
        expect(model.get('styles.block.backgroundColor')).to.match(
          /^(#[abcdef0-9]{6})|transparent$/,
        );
      });

      it('has a image display style', function () {
        expect(model.get('image.display')).to.equal('scale');
      });

      it('has a collection of blocks', function () {
        expect(model.get('blocks')).to.be.instanceof(Backbone.Collection);
      });

      it('uses defaults from config when they are set', function () {
        var innerModel;
        global.stubConfig(EditorApplication, {
          blockDefaults: {
            container: {
              styles: {
                block: {
                  backgroundColor: '#123456',
                },
              },
              image: {
                src: null,
                display: 'scale',
              },
            },
          },
        });
        innerModel = new ContainerBlock.ContainerBlockModel();

        expect(innerModel.get('styles.block.backgroundColor')).to.equal(
          '#123456',
        );
        expect(innerModel.get('image.display')).to.equal('scale');
      });

      it('do not update blockDefaults.container when changed', function () {
        var sandbox = sinon.createSandbox();
        var stub = sandbox.stub(EditorApplication.getConfig(), 'set');
        model.trigger('change');
        expect(stub.callCount).to.equal(0);
        sandbox.restore();
      });
    });

    describe('when creating with children', function () {
      var testModel = {
        type: 'sampleType',
        someField: 'Some Content',
      };
      var model;

      it('will recursively create children', function () {
        EditorApplication.getBlockTypeModel = sinon
          .stub()
          .returns(Backbone.Model);

        model = new ContainerBlock.ContainerBlockModel(
          {
            type: 'container',
            blocks: [testModel],
          },
          { parse: true },
        );

        expect(model.get('blocks')).to.have.length(1);
        expect(model.get('blocks').at(0).get('type')).to.equal(testModel.type);
        expect(model.get('blocks').at(0).get('someField')).to.equal(
          testModel.someField,
        );
      });

      it('will create nested containers and their children', function () {
        var stub = sinon.stub();
        stub.withArgs('container').returns(ModelClass);
        stub.withArgs('someType').returns(Backbone.Model);
        EditorApplication.getBlockTypeModel = stub;

        model = new ModelClass(
          {
            type: 'container',
            blocks: [
              {
                type: 'container',
                blocks: [
                  {
                    type: 'someType',
                    someField: 'some text',
                  },
                  {
                    type: 'someType',
                    someField: 'some text 2',
                  },
                ],
              },
            ],
          },
          { parse: true },
        );

        expect(model.get('blocks')).to.have.length(1);
        expect(model.get('blocks').at(0).get('blocks')).to.have.length(2);
        expect(
          model.get('blocks').at(0).get('blocks').at(1).get('someField'),
        ).to.equal('some text 2');
      });
    });
  });

  describe('block view', function () {
    global.stubChannel(EditorApplication);
    global.stubAvailableStyles(EditorApplication);

    it('renders', function () {
      var model;
      var view;
      model = new ContainerBlock.ContainerBlockModel();
      view = new ContainerBlock.ContainerBlockView({ model: model });
      expect(view.render).to.not.throw();
    });

    describe('once rendered', function () {
      describe('on root level', function () {
        var imageSrc = 'http://example.org/someNewImage.png';
        var model = new ContainerBlock.ContainerBlockModel({
          type: 'container',
          orientation: 'vertical',
          image: {
            src: imageSrc,
            display: 'scale',
            width: 123,
            height: 456,
          },
          styles: {
            block: {
              backgroundColor: 'transparent',
            },
          },
        });
        var view;

        beforeEach(function () {
          global.stubChannel(EditorApplication);
          global.stubAvailableStyles(EditorApplication);
          view = new ContainerBlock.ContainerBlockView({
            model: model,
            renderOptions: {
              depth: 0,
            },
          });
          view.render();
        });
        it('does not have a deletion tool', function () {
          expect(view.$('.mailpoet_delete_block')).to.have.length(0);
        });

        it('does not have a move tool', function () {
          expect(view.$('.mailpoet_move_block')).to.have.length(0);
        });

        it('does not have a settings tool', function () {
          expect(view.$('.mailpoet_edit_block')).to.have.length(0);
        });

        it('has a duplication tool', function () {
          expect(view.$('.mailpoet_duplicate_block')).to.have.length(1);
        });

        it('has a background image set', function () {
          var style = view.$('style').text();
          expect(style).contains('.mailpoet_editor_view_' + view.cid);
          expect(style).contains('background-color: #ffffff !important;');
          expect(style).contains(
            'background-image: url(http://example.org/someNewImage.png);',
          );
          expect(style).contains('background-position: center;');
          expect(style).contains('background-size: cover;');
        });
      });

      describe.skip('on non-root levels', function () {
        var model = new ContainerBlock.ContainerBlockModel();
        var view;

        beforeEach(function () {
          global.stubChannel(EditorApplication);
          global.stubAvailableStyles(EditorApplication);
          view = new ContainerBlock.ContainerBlockView({
            model: model,
            renderOptions: {
              depth: 1,
            },
          });
          view.render();
        });

        it('has a deletion tool', function () {
          expect(view.$('.mailpoet_delete_block')).to.have.length(1);
        });

        it('has a move tool', function () {
          expect(view.$('.mailpoet_move_block')).to.have.length(0);
        });

        it('has a settings tool', function () {
          expect(view.$('.mailpoet_edit_block')).to.have.length(1);
        });

        it('has a duplication tool', function () {
          expect(view.$('.mailpoet_duplicate_block')).to.have.length(1);
        });
      });
    });
  });

  describe('settings view', function () {
    global.stubChannel(EditorApplication);
    global.stubAvailableStyles(EditorApplication);

    it('renders', function () {
      var model;
      var view;
      model = new ContainerBlock.ContainerBlockModel();
      view = new ContainerBlock.ContainerBlockSettingsView({ model: model });
      expect(view.render).to.not.throw();
    });

    describe('once rendered', function () {
      var model;
      var view;
      var newSrc = 'http://example.org/someNewImage.png';
      beforeEach(function () {
        global.stubChannel(EditorApplication);
        global.stubAvailableStyles(EditorApplication);
        model = new ContainerBlock.ContainerBlockModel();
        view = new ContainerBlock.ContainerBlockSettingsView({ model: model });
        view.render();
      });

      it('updates the model when background color changes', function () {
        view
          .$('.mailpoet_field_container_background_color')
          .val('#123456')
          .trigger('change');
        expect(model.get('styles.block.backgroundColor')).to.equal('#123456');
      });

      it('updates the model background image display type changes', function () {
        view
          .$('.mailpoet_field_display_type:nth(2)')
          .attr('checked', true)
          .trigger('change');
        expect(model.get('image.display')).to.equal('tile');
      });

      it.skip('updates the model when background image src changes', function () {
        global.stubImage(123, 456);
        view.$('.mailpoet_field_image_address').val(newSrc).trigger('input');
        expect(model.get('image.src')).to.equal(newSrc);
      });

      it('updates the model when background image src is deleted', function () {
        global.stubImage(123, 456);
        view.$('.mailpoet_field_image_address').val('').trigger('input');
        expect(model.get('image.src')).to.equal(null);
      });

      it('displays/hides tools and highlight container block when settings active/inactive', function () {
        var settingsView;
        var blockView = new ContainerBlock.ContainerBlockView({ model: model });
        blockView.render();
        // Set proper depth since we want to highlight only top level containers
        blockView.renderOptions = {
          depth: 1,
        };
        expect(blockView.$el.hasClass('mailpoet_highlight')).to.equal(false);
        settingsView = new ContainerBlock.ContainerBlockSettingsView({
          model: model,
        });
        settingsView.render();
        expect(blockView.$el.hasClass('mailpoet_highlight')).to.equal(true);
        settingsView.destroy();
        expect(blockView.$el.hasClass('mailpoet_highlight')).to.equal(false);
      });

      it.skip('closes the sidepanel after "Done" is clicked', function () {
        var mock = sinon.mock().once();
        global.MailPoet.Modal.cancel = mock;
        view.$('.mailpoet_done_editing').trigger('click');
        mock.verify();
        delete global.MailPoet.Modal.cancel;
      });
    });
  });
});
