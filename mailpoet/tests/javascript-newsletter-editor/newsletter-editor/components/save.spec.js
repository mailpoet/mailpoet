import { App } from 'newsletter-editor/app';
import { SaveComponent } from 'newsletter-editor/components/save';
import { CommunicationComponent } from 'newsletter-editor/components/communication';
import { MailPoet } from 'mailpoet';
import jQuery from 'jquery';

const expect = global.expect;
const sinon = global.sinon;
const Backbone = global.Backbone;

describe('Save', function () {
  describe('save method', function () {
    var saveNewsletterStub;

    before(function () {
      saveNewsletterStub = sinon
        .stub(CommunicationComponent, 'saveNewsletter')
        .returns(jQuery.Deferred());
    });

    after(function () {
      saveNewsletterStub.restore();
    });

    it('triggers beforeEditorSave event', function () {
      var spy = sinon.spy();
      global.stubChannel(App, {
        trigger: spy,
      });
      App.toJSON = sinon.stub().returns({
        body: {
          type: 'container',
        },
      });
      SaveComponent.save();
      expect(spy).to.have.callCount(1);
      expect(spy).to.have.been.calledWith('beforeEditorSave');
    });

    it('triggers afterEditorSave event', function () {
      var spy = sinon.spy();
      var promise = jQuery.Deferred();
      global.stubChannel(App, {
        trigger: spy,
      });
      App.toJSON = sinon.stub().returns({
        body: {
          type: 'container',
        },
      });
      saveNewsletterStub.returns(promise);
      promise.resolve({ success: true });
      SaveComponent.save();
      expect(spy.withArgs('afterEditorSave').calledOnce).to.be.true; // eslint-disable-line no-unused-expressions
    });

    it('sends newsletter json to server for saving', function () {
      var mock;
      saveNewsletterStub.restore();
      mock = sinon.mock(CommunicationComponent);
      mock.expects('saveNewsletter').once().returns(jQuery.Deferred());
      global.stubChannel(App);

      App.toJSON = sinon.stub().returns({});
      SaveComponent.save();

      mock.verify();
      mock.restore();
      saveNewsletterStub = sinon
        .stub(CommunicationComponent, 'saveNewsletter')
        .returns(jQuery.Deferred());
    });

    it('encodes newsletter body in JSON format', function () {
      var body = { type: 'testType' };
      var mock;
      saveNewsletterStub.restore();
      mock = sinon.mock(CommunicationComponent);
      mock
        .expects('saveNewsletter')
        .once()
        .withArgs({
          body: JSON.stringify(body),
        })
        .returns(jQuery.Deferred());
      global.stubChannel(App);

      App.toJSON = sinon.stub().returns({
        body: body,
      });
      SaveComponent.save();

      mock.verify();
      mock.restore();
      saveNewsletterStub = sinon
        .stub(CommunicationComponent, 'saveNewsletter')
        .returns(jQuery.Deferred());
    });
  });

  describe('view', function () {
    var validNewsletter = {
      body: {
        content: {
          blocks: [{ type: 'footer' }],
        },
      },
    };
    before(function () {
      var newsletter = {
        get: sinon.stub().withArgs('type').returns('newsletter'),
      };
      App._contentContainer = {
        isValid: sinon.stub().returns(true),
      };
      global.stubConfig(App);
      App.getNewsletter = sinon.stub().returns(newsletter);
    });

    it('renders', function () {
      var view;
      var model = new Backbone.SuperModel({});
      model.isWoocommerceTransactional = function () {
        return false;
      };
      model.isAutomationEmail = function () {
        return false;
      };
      model.isConfirmationEmailTemplate = function () {
        return false;
      };
      view = new SaveComponent.SaveView({ model: model });
      expect(view.render).to.not.throw();
    });

    describe('validateNewsletter', function () {
      var hideValidationErrorStub;
      var view;
      var model;
      beforeEach(function () {
        model = new Backbone.SuperModel({});
        model.isWoocommerceTransactional = function () {
          return false;
        };
        model.isAutomationEmail = function () {
          return false;
        };
        model.isConfirmationEmailTemplate = function () {
          return false;
        };
        view = new SaveComponent.SaveView({ model: model });
        hideValidationErrorStub = sinon.stub(view, 'hideValidationError');
      });

      it('hides errors for valid newsletter', function () {
        view.validateNewsletter(validNewsletter);
        expect(hideValidationErrorStub.callCount).to.be.equal(1);
      });

      it('hides errors for valid post notification', function () {
        var newsletter = {
          get: sinon.stub().withArgs('type').returns('notification'),
        };
        App.getNewsletter = sinon.stub().returns(newsletter);
        view.validateNewsletter({
          body: {
            content: {
              blocks: [{ type: 'automatedLatestContent' }],
            },
          },
        });
        expect(hideValidationErrorStub.callCount).to.be.equal(1);
      });

      it('shows error for notification email type when ALC content is not present', function () {
        var newsletter = {
          get: sinon.stub().withArgs('type').returns('notification'),
        };
        var showValidationErrorStub = sinon.stub(view, 'showValidationError');
        App.getNewsletter = sinon.stub().returns(newsletter);
        view.validateNewsletter(validNewsletter);
        expect(showValidationErrorStub.callCount).to.be.equal(1);
      });
    });

    describe('once rendered', function () {
      var view;
      var model;
      beforeEach(function () {
        App._contentContainer = {
          isValid: sinon.stub().returns(true),
        };
        model = new Backbone.SuperModel({});
        model.isWoocommerceTransactional = function () {
          return false;
        };
        model.isAutomationEmail = function () {
          return false;
        };
        model.isConfirmationEmailTemplate = function () {
          return false;
        };
        view = new SaveComponent.SaveView({ model: model });
        view.render();
      });

      it('triggers newsletter saving when clicked on save button', function () {
        var mock = sinon
          .mock({ request: function () {} })
          .expects('request')
          .once()
          .withArgs('save');
        global.stubChannel(App, {
          request: mock,
        });
        view.$('.mailpoet_save_button').trigger('click');

        mock.verify();
      });

      it('displays saving options when clicked on save options button', function () {
        view.$('.mailpoet_save_show_options').trigger('click');
        expect(view.$('.mailpoet_save_options')).to.not.have.$class(
          'mailpoet_hidden',
        );
      });

      it('triggers template saving when clicked on "save as template" button', function () {
        var ajaxPostStub;
        var promiseMock = {};
        var originalI18n = MailPoet.I18n;
        var originalNotice = MailPoet.Notice;
        var originalTrackEvent = MailPoet.trackEvent;

        promiseMock.then = function (cb) {
          cb();
          return promiseMock;
        };
        promiseMock.catch = promiseMock.then;

        App.getBody = sinon.stub();
        App.getNewsletter = function () {
          return {
            get: function () {
              return 'standard';
            },
          };
        };

        ajaxPostStub = sinon
          .stub(MailPoet.Ajax, 'post')
          .returns(jQuery.Deferred());
        MailPoet.I18n = {
          t: function () {
            return '';
          },
        };
        MailPoet.Notice = { success: function () {}, error: function () {} };
        MailPoet.trackEvent = function () {};

        model = new Backbone.SuperModel({});
        model.isWoocommerceTransactional = function () {
          return false;
        };
        model.isAutomationEmail = function () {
          return false;
        };
        model.isConfirmationEmailTemplate = function () {
          return false;
        };
        view = new SaveComponent.SaveView({ model: model });
        view.render();

        view.$('.mailpoet_save_as_template_name').val('A sample template');
        view
          .$('.mailpoet_save_as_template_description')
          .val('Sample template description');
        view.$('.mailpoet_save_as_template').trigger('click');

        expect(ajaxPostStub.calledOnce).to.be.true; // eslint-disable-line no-unused-expressions

        ajaxPostStub.restore();
        MailPoet.I18n = originalI18n;
        MailPoet.Notice = originalNotice;
        MailPoet.trackEvent = originalTrackEvent;
      });

      it('saves newsletter when clicked on "next" button', function () {
        var spy = sinon.spy();
        var saveNewsletterStub = sinon
          .stub(CommunicationComponent, 'saveNewsletter')
          .returns(jQuery.Deferred());
        global.stubChannel(App, {
          trigger: spy,
        });
        model = new Backbone.SuperModel({});
        model.isWoocommerceTransactional = function () {
          return false;
        };
        model.isAutomationEmail = function () {
          return false;
        };
        model.isConfirmationEmailTemplate = function () {
          return false;
        };
        view = new SaveComponent.SaveView({ model: model });
        view.render();

        view.$('.mailpoet_save_next').trigger('click');
        expect(spy).to.have.callCount(1);
        expect(spy).to.have.been.calledWith('beforeEditorSave');
        saveNewsletterStub.restore();
      });
    });
  });

  describe('preview view', function () {
    var view;
    beforeEach(function () {
      view = new SaveComponent.NewsletterPreviewView();
    });

    it.skip('renders', function () {
      expect(view.render).to.not.throw();
    });
  });
});
