const expect = global.expect;
const sinon = global.sinon;
const Backbone = global.Backbone;

define([
  'newsletter_editor/App',
  'newsletter_editor/blocks/container'
], function (App, ContainerBlock) {
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
          expect(model.get('styles.block.backgroundColor')).to.match(/^(#[abcdef0-9]{6})|transparent$/);
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
                    backgroundColor: '#123456'
                  }
                }
              }
            }
          });
          innerModel = new (ContainerBlock.ContainerBlockModel)();

          expect(innerModel.get('styles.block.backgroundColor')).to.equal('#123456');
        });
      });

      describe('when creating with children', function () {
        var testModel = {
          type: 'sampleType',
          someField: 'Some Content'
        };
        var model;

        it('will recursively create children', function () {
          EditorApplication.getBlockTypeModel = sinon.stub().returns(Backbone.Model);

          model = new (ContainerBlock.ContainerBlockModel)({
            type: 'container',
            blocks: [testModel]
          }, { parse: true });

          expect(model.get('blocks')).to.have.length(1);
          expect(model.get('blocks').at(0).get('type')).to.equal(testModel.type);
          expect(model.get('blocks').at(0).get('someField')).to.equal(testModel.someField);
        });

        it('will create nested containers and their children', function () {
          var stub = sinon.stub();
          stub.withArgs('container').returns(ModelClass);
          stub.withArgs('someType').returns(Backbone.Model);
          EditorApplication.getBlockTypeModel = stub;

          model = new ModelClass({
            type: 'container',
            blocks: [
              {
                type: 'container',
                blocks: [
                  {
                    type: 'someType',
                    someField: 'some text'
                  },
                  {
                    type: 'someType',
                    someField: 'some text 2'
                  }
                ]
              }
            ]
          }, { parse: true });

          expect(model.get('blocks')).to.have.length(1);
          expect(model.get('blocks').at(0).get('blocks')).to.have.length(2);
          expect(
            model.get('blocks').at(0)
                 .get('blocks').at(1)
                 .get('someField')
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
        model = new (ContainerBlock.ContainerBlockModel)();
        view = new (ContainerBlock.ContainerBlockView)({ model: model });
        expect(view.render).to.not.throw();
      });

      describe('once rendered', function () {
        describe('on root level', function () {
          var model = new (ContainerBlock.ContainerBlockModel)();
          var view;

          beforeEach(function () {
            global.stubChannel(EditorApplication);
            global.stubAvailableStyles(EditorApplication);
            view = new (ContainerBlock.ContainerBlockView)({
              model: model,
              renderOptions: {
                depth: 0
              }
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
        });

        describe.skip('on non-root levels', function () {
          var model = new (ContainerBlock.ContainerBlockModel)();
          var view;

          beforeEach(function () {
            global.stubChannel(EditorApplication);
            global.stubAvailableStyles(EditorApplication);
            view = new (ContainerBlock.ContainerBlockView)({
              model: model,
              renderOptions: {
                depth: 1
              }
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
        model = new (ContainerBlock.ContainerBlockModel)();
        view = new (ContainerBlock.ContainerBlockSettingsView)({ model: model });
        expect(view.render).to.not.throw();
      });

      describe('once rendered', function () {
        var model;
        var view;
        beforeEach(function () {
          global.stubChannel(EditorApplication);
          global.stubAvailableStyles(EditorApplication);
          model = new (ContainerBlock.ContainerBlockModel)();
          view = new (ContainerBlock.ContainerBlockSettingsView)({ model: model });
        });

        it('updates the model when background color changes', function () {
          view.$('.mailpoet_field_container_background_color').val('#123456').change();
          expect(model.get('styles.block.backgroundColor')).to.equal('#123456');
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
});
