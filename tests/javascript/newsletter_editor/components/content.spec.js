import App from 'newsletter_editor/App';
import ContentComponent from 'newsletter_editor/components/content';

const expect = global.expect;
const sinon = global.sinon;
const Backbone = global.Backbone;
const _ = global._;

var EditorApplication = App;

describe('Content', function () {
  describe('newsletter model', function () {
    var model;

    beforeEach(function () {
      model = new (ContentComponent.NewsletterModel)({
        body: {
          globalStyles: {
            style1: 'style1Value',
            style2: 'style2Value',
          },
          content: {
            data1: 'data1Value',
            data2: 'data2Value',
          },
        },
        subject: 'my test subject',
      });
    });

    it('triggers autosave on change', function () {
      var mock = sinon.mock({ trigger: function () {} }).expects('trigger').once().withArgs('autoSave');
      global.stubChannel(EditorApplication, {
        trigger: mock,
      });
      model.set('subject', 'another test subject');
      mock.verify();
    });

    it('does not include styles and content properties in its JSON', function () {
      var json = model.toJSON();
      expect(json).to.deep.equal({ subject: 'my test subject' });
    });

    describe('toJSON()', function () {
      it('will only contain properties modifiable by the editor', function () {
        var json;
        model = new (ContentComponent.NewsletterModel)({
          id: 19,
          subject: 'some subject',
          preheader: 'some preheader',
          segments: [1, 2, 3],
          modified_at: '2000-01-01 12:01:02',
          someField: 'someValue',
        });

        json = model.toJSON();
        expect(json.id).to.equal(19);
        expect(json.subject).to.equal('some subject');
        expect(json.preheader).to.equal('some preheader');
        expect(json).to.not.include.keys('segments', 'modified_at', 'someField');
      });
    });
  });

  describe('block types', function () {
    it('registers a block type view and model', function () {
      var blockModel = new Backbone.SuperModel();
      var blockView = new Backbone.View();
      ContentComponent.registerBlockType('testType', {
        blockModel: blockModel,
        blockView: blockView,
      });
      expect(ContentComponent.getBlockTypeModel('testType')).to.deep.equal(blockModel);
      expect(ContentComponent.getBlockTypeView('testType')).to.deep.equal(blockView);
    });
  });

  describe('transformation to json', function () {
    it('includes content, globalStyles and initial newsletter fields', function () {
      var json;
      var dataField = {
        containerModelField: 'containerModelValue',
      };
      var stylesField = {
        globalStylesField: 'globalStylesValue',
      };
      var newsletterFields = {
        subject: 'test newsletter subject',
      };
      var blockDefaults = {
        button: {},
      };
      EditorApplication._contentContainer = {
        toJSON: function () {
          return dataField;
        },
      };
      EditorApplication.getGlobalStyles = function () {
        return {
          toJSON: function () {
            return stylesField;
          },
        };
      };
      EditorApplication.getNewsletter = function () {
        return {
          toJSON: function () {
            return newsletterFields;
          },
        };
      };
      EditorApplication.getConfig().set('blockDefaults', blockDefaults);
      json = ContentComponent.toJSON();
      expect(json).to.deep.equal(_.extend({
        body: {
          content: dataField,
          globalStyles: stylesField,
          blockDefaults: blockDefaults,
        },
      }, newsletterFields));
    });
  });
});
