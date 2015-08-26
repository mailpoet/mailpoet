define('test/newsletter_editor/components/config', [
    'newsletter_editor/App',
    'newsletter_editor/components/config'
  ], function(EditorApplication) {

  describe('Styles', function () {
      it('loads and stores globally available styles', function() {
          EditorApplication.module('components.styles').setGlobalStyles({
              testStyle: 'testValue',
          });
          var model = EditorApplication.module('components.styles').getGlobalStyles();
          expect(model.get('testStyle')).to.equal('testValue');
      });

      describe('model', function() {
          var model;
          beforeEach(function() {
              model = new (EditorApplication.module('components.styles').StylesModel)();
          });

          it('triggers autoSave when changed', function() {
              var mock = sinon.mock({ trigger: function(){}}).expects('trigger').once().withExactArgs('autoSave');
              EditorApplication.getChannel = function() {
                  return {
                      trigger: mock,
                  };
              };
              model.set('text.fontColor', '#123456');
              mock.verify();
          });
      });

      describe('view', function() {
          var model, view;
          beforeEach(function() {
              model = new (EditorApplication.module('components.styles').StylesModel)();
              view = new (EditorApplication.module('components.styles').StylesView)({ model: model });
          });

          it('renders', function() {
              expect(view.render).to.not.throw();
          });
      });
  });

});
