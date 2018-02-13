const expect = global.expect;
const sinon = global.sinon;

define([
  'newsletter_editor/App',
  'newsletter_editor/components/save',
  'amd-inject-loader!newsletter_editor/components/save',
  'jquery'
], function (App, SaveComponent, SaveInjector, jQuery) {
  var EditorApplication = App;

  describe('Save', function () {
    describe('save method', function () {
      var module;
      before(function () {
        module = SaveInjector({
          'newsletter_editor/components/communication': {
            saveNewsletter: function () {
              return jQuery.Deferred();
            }
          }
        });
      });

      it('triggers beforeEditorSave event', function () {
        var spy = sinon.spy();
        global.stubChannel(EditorApplication, {
          trigger: spy
        });
        EditorApplication.toJSON = sinon.stub().returns({
          body: {
            type: 'container'
          }
        });
        module.save();
        expect(spy).to.have.callCount(1);
        expect(spy).to.have.been.calledWith('beforeEditorSave');
      });

      it('triggers afterEditorSave event', function () {
        var innerModule;
        var spy = sinon.spy();
        var promise = jQuery.Deferred();
        global.stubChannel(EditorApplication, {
          trigger: spy
        });
        EditorApplication.toJSON = sinon.stub().returns({
          body: {
            type: 'container'
          }
        });
        innerModule = SaveInjector({
          'newsletter_editor/components/communication': {
            saveNewsletter: sinon.stub().returns(promise)
          }
        });
        promise.resolve({ success: true });
        innerModule.save();
        expect(spy.withArgs('afterEditorSave').calledOnce).to.be.true;// eslint-disable-line no-unused-expressions
      });

      it('sends newsletter json to server for saving', function () {
        var mock = sinon.mock().once().returns(jQuery.Deferred());
        var innerModule = SaveInjector({
          'newsletter_editor/components/communication': {
            saveNewsletter: mock
          }
        });
        global.stubChannel(EditorApplication);

        EditorApplication.toJSON = sinon.stub().returns({});
        innerModule.save();

        mock.verify();
      });

      it('encodes newsletter body in JSON format', function () {
        var innerModule;
        var body = { type: 'testType' };
        var mock = sinon.mock()
          .once()
          .withArgs({
            body: JSON.stringify(body)
          })
          .returns(jQuery.Deferred());
        global.stubChannel(EditorApplication);

        EditorApplication.toJSON = sinon.stub().returns({
          body: body
        });
        innerModule = SaveInjector({
          'newsletter_editor/components/communication': {
            saveNewsletter: mock
          }
        });
        innerModule.save();

        mock.verify();
      });
    });

    describe('view', function () {
      before(function () {
        var newsletter = { get: sinon.stub().withArgs('type').returns('newsletter') };
        EditorApplication._contentContainer = { isValid: sinon.stub().returns(true) };
        global.stubConfig(EditorApplication);
        EditorApplication.getNewsletter = sinon.stub().returns(newsletter);
      });

      it('renders', function () {
        var view = new (SaveComponent.SaveView)();
        expect(view.render).to.not.throw();
      });

      describe('validateNewsletter', function () {
        var hideValidationErrorStub;
        var view;
        beforeEach(function () {
          view = new (SaveComponent.SaveView)();
          hideValidationErrorStub = sinon.stub(view, 'hideValidationError');
        });

        it('hides errors for valid newsletter', function () {
          view.validateNewsletter({});
          expect(hideValidationErrorStub.callCount).to.be.equal(1);
        });

        it('hides errors for valid post notification', function () {
          var newsletter = { get: sinon.stub().withArgs('type').returns('notification') };
          EditorApplication.getNewsletter = sinon.stub().returns(newsletter);
          view.validateNewsletter({
            content: {
              blocks: [
                { type: 'automatedLatestContent' }
              ]
            }
          });
          expect(hideValidationErrorStub.callCount).to.be.equal(1);
        });

        it('shows error for notification email type when ALC content is not present', function () {
          var newsletter = { get: sinon.stub().withArgs('type').returns('notification') };
          var showValidationErrorStub = sinon.stub(view, 'showValidationError');
          EditorApplication.getNewsletter = sinon.stub().returns(newsletter);
          view.validateNewsletter({});
          expect(showValidationErrorStub.callCount).to.be.equal(1);
        });
      });

      describe('once rendered', function () {
        var view;
        beforeEach(function () {
          EditorApplication._contentContainer = { isValid: sinon.stub().returns(true) };
          view = new (SaveComponent.SaveView)();
          view.render();
        });

        it('triggers newsletter saving when clicked on save button', function () {
          var mock = sinon.mock({ request: function () {} }).expects('request').once().withArgs('save');
          global.stubChannel(EditorApplication, {
            request: mock
          });
          view.$('.mailpoet_save_button').click();

          mock.verify();
        });

        it('displays saving options when clicked on save options button', function () {
          view.$('.mailpoet_save_show_options').click();
          expect(view.$('.mailpoet_save_options')).to.not.have.$class('mailpoet_hidden');
        });

        it('triggers template saving when clicked on "save as template" button', function () {
          var mock = sinon.mock({ post: function () {} }).expects('post').once().returns(jQuery.Deferred());
          var html2canvasMock = jQuery.Deferred();
          var module;

          html2canvasMock.resolve({
            toDataURL: function () { return 'somedataurl'; }
          });

          EditorApplication.getBody = sinon.stub();
          EditorApplication.getNewsletter = function () {
            return {
              get: function () {
                return 'standard';
              }
            };
          };
          module = SaveInjector({
            mailpoet: {
              Ajax: {
                post: mock
              },
              I18n: {
                t: function () { return ''; }
              },
              Notice: {
                success: function () {},
                error: function () {}
              },
              trackEvent: function () {}
            },
            'newsletter_editor/App': EditorApplication,
            html2canvas: function () {
              return {
                then: function () { return html2canvasMock; }
              };
            }
          });
          view = new (module.SaveView)();
          view.render();

          view.$('.mailpoet_save_as_template_name').val('A sample template');
          view.$('.mailpoet_save_as_template_description').val('Sample template description');
          view.$('.mailpoet_save_as_template').click();

          mock.verify();
        });

        it('saves newsletter when clicked on "next" button', function () {
          var spy = sinon.spy();
          var module = SaveInjector({
            'newsletter_editor/components/communication': {
              saveNewsletter: function () {
                return jQuery.Deferred();
              }
            }
          });
          global.stubChannel(EditorApplication, {
            trigger: spy
          });
          view = new (module.SaveView)();
          view.render();

          view.$('.mailpoet_save_next').click();
          expect(spy).to.have.callCount(1);
          expect(spy).to.have.been.calledWith('beforeEditorSave');
        });
      });
    });
  });
});
