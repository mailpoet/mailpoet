import { App } from 'newsletter-editor/app';
import { SaveComponent } from 'newsletter-editor/components/save';
import { CommunicationComponent } from 'newsletter-editor/components/communication';
import { ContainerBlock } from 'newsletter-editor/blocks/container';
import { FooterBlock } from 'newsletter-editor/blocks/footer';
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
      var errorCountFor;
      var optOutFooter =
        '<a href="[link:subscription_tracking_opt_out_url]">x</a>';
      var stubEmptyContentContainer;
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

      // The no-footer branch builds real block models, so the spec hands it the
      // real constructors rather than stubs -- the shape they produce is the
      // thing under test.
      stubEmptyContentContainer = function (collector) {
        // Building the models fires 'add'/'change', which both reach for the
        // channel.
        global.stubChannel(App);
        App.getBlockTypeModel = sinon.stub();
        App.getBlockTypeModel
          .withArgs('footer')
          .returns(FooterBlock.FooterBlockModel);
        App.getBlockTypeModel
          .withArgs('container')
          .returns(ContainerBlock.ContainerBlockModel);
        App._contentContainer = {
          isValid: sinon.stub().returns(true),
          get: sinon
            .stub()
            .withArgs('blocks')
            .returns({
              add: function (block) {
                collector.push(block);
              },
            }),
        };
      };

      errorCountFor = function (settingOn, footerText) {
        var stub;
        global.stubConfig(App, {
          validation: { validateTrackingOptOutLinkPresent: settingOn },
        });
        App.getNewsletter = sinon.stub().returns({
          get: sinon.stub().withArgs('type').returns('standard'),
        });
        stub = sinon.stub(view, 'showValidationError');
        view.validateNewsletter({
          body: { content: { blocks: [{ type: 'footer', text: footerText }] } },
        });
        return stub.callCount;
      };

      it('shows an error when the tracking opt-out link is missing', function () {
        expect(errorCountFor(true, 'bye')).to.be.equal(1);
      });

      it('accepts the tracking opt-out shortcode in the body', function () {
        expect(errorCountFor(true, optOutFooter)).to.be.equal(0);
      });

      it('does not ask for the opt-out link when the setting is off', function () {
        expect(errorCountFor(false, 'bye')).to.be.equal(0);
      });

      it('adds the opt-out link to an existing footer block', function () {
        var footer;
        // A real footer is a Backbone model, so the stub needs trigger() as well
        // as get/set — the view asks the model to repaint after setting the text.
        footer = {
          get: sinon.stub().returns('bye'),
          set: sinon.spy(),
          trigger: sinon.spy(),
        };
        App.findModels = sinon.stub().returns([footer]);
        sinon.stub(view, 'validateNewsletter');
        view.addTrackingOptOutLink();
        expect(footer.set.calledOnce).to.be.equal(true);
        expect(footer.set.firstCall.args[0]).to.be.equal('text');
        expect(footer.set.firstCall.args[1]).to.contain(
          '[link:subscription_tracking_opt_out_url]',
        );
      });

      it('adds the footer as a whole new row when the email has none', function () {
        // The root content container only holds rows: a horizontal container
        // per row, a vertical container per column inside it, leaf blocks
        // inside those. A footer added straight to the root draws on the
        // canvas and passes validation, but Columns\Renderer skips any
        // top-level block with no `blocks` of its own, so the sent email would
        // carry neither the opt-out link nor the unsubscribe link.
        var added = [];
        var row;
        var column;
        var footer;
        stubEmptyContentContainer(added);
        App.findModels = sinon.stub().returns([]);
        sinon.stub(view, 'validateNewsletter');

        view.addTrackingOptOutLink();

        expect(added.length).to.be.equal(1);
        row = added[0];
        expect(row.get('type')).to.be.equal('container');
        expect(row.get('orientation')).to.be.equal('horizontal');

        column = row.get('blocks').at(0);
        expect(column.get('type')).to.be.equal('container');
        expect(column.get('orientation')).to.be.equal('vertical');

        footer = column.get('blocks').at(0);
        expect(footer.get('type')).to.be.equal('footer');
        expect(footer.get('text')).to.contain(
          '[link:subscription_tracking_opt_out_url]',
        );
      });

      it('keeps the unsubscribe links the new footer comes with', function () {
        // The footer defaults carry them, and on a footerless email this block
        // is the only place a subscriber can unsubscribe from.
        var added = [];
        var footerText;
        stubEmptyContentContainer(added);
        App.findModels = sinon.stub().returns([]);
        sinon.stub(view, 'validateNewsletter');

        view.addTrackingOptOutLink();

        footerText = added[0]
          .get('blocks')
          .at(0)
          .get('blocks')
          .at(0)
          .get('text');
        expect(footerText).to.contain('[link:subscription_unsubscribe_url]');
        expect(footerText).to.contain('[link:subscription_manage_url]');
      });

      it('asks the footer to repaint, since change:text does not re-render it', function () {
        // Without this the model updates but the canvas keeps showing the old DOM
        // until a page reload. FooterBlockView drops the base 'change' -> render
        // binding so TinyMCE is not torn down mid-keystroke, so a programmatic
        // set('text') has to ask for the repaint explicitly.
        var footer;
        footer = {
          get: sinon.stub().returns('bye'),
          set: sinon.spy(),
          trigger: sinon.spy(),
        };
        App.findModels = sinon.stub().returns([footer]);
        sinon.stub(view, 'validateNewsletter');
        view.addTrackingOptOutLink();
        expect(footer.trigger.calledOnce).to.be.equal(true);
        expect(footer.trigger.firstCall.args[0]).to.be.equal('redraw');
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
