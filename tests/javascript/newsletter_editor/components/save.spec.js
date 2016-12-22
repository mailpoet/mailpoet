define([
    'newsletter_editor/App',
    'newsletter_editor/components/save',
    'amd-inject-loader!newsletter_editor/components/save',
    'jquery'
  ], function(EditorApplication, SaveComponent, SaveInjector, jQuery) {

  describe('Save', function() {
    describe('save method', function() {
      var module;
      before(function() {
        module = SaveInjector({
          'newsletter_editor/components/communication': {
            saveNewsletter: function() {
              return jQuery.Deferred();
            }
          }
        });
      });

      it('triggers beforeEditorSave event', function() {
        var spy = sinon.spy();
        global.stubChannel(EditorApplication, {
          trigger: spy,
        });
        EditorApplication.toJSON = sinon.stub().returns({
          body: {
            type: 'container',
          },
        });
        module.save();
        expect(spy.withArgs('beforeEditorSave').calledOnce).to.be.true;
      });

      it('triggers afterEditorSave event', function() {
        var spy = sinon.spy(),
          promise = jQuery.Deferred();
        global.stubChannel(EditorApplication, {
          trigger: spy,
        });
        EditorApplication.toJSON = sinon.stub().returns({
          body: {
            type: 'container',
          },
        });
        var module = SaveInjector({
          'newsletter_editor/components/communication': {
            saveNewsletter: sinon.stub().returns(promise),
          }
        });
        promise.resolve({ success: true });
        module.save();
        expect(spy.withArgs('afterEditorSave').calledOnce).to.be.true;
      });

      it('sends newsletter json to server for saving', function() {
        var mock = sinon.mock().once().returns(jQuery.Deferred());
        var module = SaveInjector({
          'newsletter_editor/components/communication': {
            saveNewsletter: mock,
          }
        });
        global.stubChannel(EditorApplication);

        EditorApplication.toJSON = sinon.stub().returns({});
        module.save();

        mock.verify();
      });

      it('encodes newsletter body in JSON format', function() {
        var body = {type: 'testType'},
          mock = sinon.mock()
            .once()
            .withArgs({
              body: JSON.stringify(body),
            })
            .returns(jQuery.Deferred());
        global.stubChannel(EditorApplication);

        EditorApplication.toJSON = sinon.stub().returns({
          body: body,
        });
        var module = SaveInjector({
          'newsletter_editor/components/communication': {
            saveNewsletter: mock,
          }
        });
        module.save();

        mock.verify();
      });

      it('provides a promise if a result container is passed to save event', function() {
        var spy = sinon.spy(module, 'save'),
          saveResult = {promise: null};
        module.saveAndProvidePromise(saveResult);
        spy.restore();
        expect(spy.calledOnce).to.be.true;
        expect(saveResult.promise).to.be.an('object');
        expect(saveResult.promise.then).to.be.a('function');
      });
    });

    describe('view', function() {
      var view;
      before(function() {
        EditorApplication._contentContainer = { isValid: sinon.stub().returns(true) };
        global.stubConfig(EditorApplication);
        view = new (SaveComponent.SaveView)();
      });

      it('renders', function() {
        expect(view.render).to.not.throw();
      });

      describe('once rendered', function() {
        var view;
        beforeEach(function() {
          EditorApplication._contentContainer = { isValid: sinon.stub().returns(true) };
          view = new (SaveComponent.SaveView)();
          view.render();
        });

        it('triggers newsletter saving when clicked on save button', function() {
          var mock = sinon.mock({ trigger: function() {} }).expects('trigger').once().withArgs('save');
          global.stubChannel(EditorApplication, {
            trigger: mock,
          });
          view.$('.mailpoet_save_button').click();

          mock.verify();
        });

        it('displays saving options when clicked on save options button', function() {
          view.$('.mailpoet_save_show_options').click();
          expect(view.$('.mailpoet_save_options')).to.not.have.$class('mailpoet_hidden');
        });

        it('triggers template saving when clicked on "save as template" button', function() {
          var mock = sinon.mock({ post: function() {} }).expects('post').once().returns(jQuery.Deferred()),
            html2canvasMock = jQuery.Deferred();

          html2canvasMock.resolve({
            toDataURL: function() { return 'somedataurl'; },
          });

          EditorApplication.getBody = sinon.stub();
          var module = SaveInjector({
            'mailpoet': {
              Ajax: {
                post: mock,
              },
              I18n: {
                t: function() { return ''; },
              },
              Notice: {
                success: function() {},
                error: function() {},
              },
            },
            'newsletter_editor/App': EditorApplication,
            'html2canvas': function() {
              return {
                then: function() { return html2canvasMock; }
              };
            },
          });
          var view = new (module.SaveView)();
          view.render();

          view.$('.mailpoet_save_as_template_name').val('A sample template');
          view.$('.mailpoet_save_as_template_description').val('Sample template description');
          view.$('.mailpoet_save_as_template').click();

          mock.verify();
        });
      });
    });
  });
});
