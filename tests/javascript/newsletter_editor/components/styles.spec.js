define([
    'newsletter_editor/App',
    'newsletter_editor/components/styles'
  ], function(EditorApplication, StylesComponent) {

  describe('Styles', function () {
    it('loads and stores globally available styles', function() {
      StylesComponent.setGlobalStyles({
        testStyle: 'testValue'
      });
      var model = StylesComponent.getGlobalStyles();
      expect(model.get('testStyle')).to.equal('testValue');
    });

    describe('model', function() {
      var model;
      beforeEach(function() {
        model = new (StylesComponent.StylesModel)();
      });

      it('triggers autoSave when changed', function() {
        var mock = sinon.mock({ trigger: function(){}}).expects('trigger').once().withExactArgs('autoSave');
        EditorApplication.getChannel = function() {
          return {
            trigger: mock
          };
        };
        model.set('text.fontColor', '#123456');
        mock.verify();
      });
    });

    describe('view', function() {
      var model, view;
      beforeEach(function() {
        model = new (StylesComponent.StylesModel)();
        view = new (StylesComponent.StylesView)({ model: model });
      });

      it('renders', function() {
        expect(view.render).to.not.throw();
      });
    });
  });
});
