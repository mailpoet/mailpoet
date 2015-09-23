define([
    'newsletter_editor/App',
    'newsletter_editor/components/heading'
  ], function(EditorApplication, HeadingComponent) {

  describe('Heading', function() {
    describe('view', function() {
      var view;
      beforeEach(function() {
        var model = new Backbone.SuperModel({
          subject: 'a test subject',
        });
        view = new (HeadingComponent.HeadingView)({
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
            subject: 'a test subject',
            preheader: 'a test preheader',
          });
          view = new (HeadingComponent.HeadingView)({
            model: model,
          });
          view.render();
        });

        it('changes the model when subject field is changed', function() {
          view.$('.mailpoet_input_title').val('a new testing subject').keyup();
          expect(model.get('subject')).to.equal('a new testing subject');
        });

        it('changes the model when preheader field is changed', function() {
          view.$('.mailpoet_input_preheader').val('a new testing preheader').keyup();
          expect(model.get('preheader')).to.equal('a new testing preheader');
        });
      });
    });
  });
});
