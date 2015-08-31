define('test/newsletter_editor/components/heading', [
    'newsletter_editor/App',
    'newsletter_editor/components/heading'
  ], function(EditorApplication) {

  describe('Heading', function() {
    describe('view', function() {
      var view;
      beforeEach(function() {
        var model = new Backbone.SuperModel({
          newsletter_subject: 'a test subject',
        });
        view = new (EditorApplication.module("components.heading").HeadingView)({
          model: model,
        });
      });

      it('renders', function() {
        expect(view.render).to.not.throw();
      });

      describe('once rendered', function() {
        var view, model;
        beforeEach(function() {
          model = new Backbone.SuperModel({
            newsletter_subject: 'a test subject',
            newsletter_preheader: 'a test preheader',
          });
          view = new (EditorApplication.module("components.heading").HeadingView)({
            model: model,
          });
          view.render();
        });

        it('changes the model when subject field is changed', function() {
          view.$('.mailpoet_input_title').val('a new testing subject').keyup();
          expect(model.get('newsletter_subject')).to.equal('a new testing subject');
        });

        it('changes the model when preheader field is changed', function() {
          view.$('.mailpoet_input_preheader').val('a new testing preheader').keyup();
          expect(model.get('newsletter_preheader')).to.equal('a new testing preheader');
        });
      });
    });
  });
});
