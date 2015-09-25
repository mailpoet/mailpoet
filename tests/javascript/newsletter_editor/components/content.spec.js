define([
    'newsletter_editor/App',
    'newsletter_editor/components/content'
  ], function(EditorApplication, ContentComponent) {

  describe('Content', function() {
    describe('newsletter model', function() {
      var model;

      beforeEach(function() {
        model = new (ContentComponent.NewsletterModel)({
          data: {
            globalStyles: {
              style1: 'style1Value',
              style2: 'style2Value',
            },
            content: {
              data1: 'data1Value',
              data2: 'data2Value',
            },
          },
          someField: 'someValue'
        });
      });

      it('triggers autosave on change', function() {
        var mock = sinon.mock({ trigger: function() {} }).expects('trigger').once().withArgs('autoSave');
        global.stubChannel(EditorApplication, {
          trigger: mock,
        });
        model.set('someField', 'anotherValue');
        mock.verify();
      });

      it('does not include styles and content attributes in its JSON', function() {
        var json = model.toJSON();
        expect(json).to.deep.equal({someField: 'someValue'});
      });
    });

    describe('block types', function() {
      it('registers a block type view and model', function() {
        var blockModel = new Backbone.SuperModel(),
          blockView = new Backbone.View();
        ContentComponent.registerBlockType('testType', {
          blockModel: blockModel,
          blockView: blockView,
        });
        expect(ContentComponent.getBlockTypeModel('testType')).to.deep.equal(blockModel);
        expect(ContentComponent.getBlockTypeView('testType')).to.deep.equal(blockView);
      });
    });

    describe('transformation to json', function() {
      it('includes content, globalStyles and initial newsletter fields', function() {
        var dataField = {
          containerModelField: 'containerModelValue',
        }, stylesField = {
          globalStylesField: 'globalStylesValue',
        }, newsletterFields = {
          subject: 'test newsletter subject',
        };
        EditorApplication._contentContainer = {
          toJSON: function() {
            return dataField;
          }
        };
        EditorApplication.getGlobalStyles = function() {
          return {
            toJSON: function() {
              return stylesField;
            },
          };
        };
        EditorApplication.getNewsletter = function() {
          return {
            toJSON: function() {
              return newsletterFields;
            },
          };
        };
        var json = ContentComponent.toJSON();
        expect(json).to.deep.equal(_.extend({
          data: {
            content: dataField,
            globalStyles: stylesField
          },
        }, newsletterFields));
      });
    });
  });
});
