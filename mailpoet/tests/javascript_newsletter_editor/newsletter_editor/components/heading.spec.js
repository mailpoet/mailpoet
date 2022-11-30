import { HeadingComponent } from 'newsletter_editor/components/heading';

const expect = global.expect;
const Backbone = global.Backbone;

describe('Heading', function () {
  describe('view', function () {
    var view;
    beforeEach(function () {
      var model = new Backbone.SuperModel({
        subject: 'a test subject',
      });
      model.isWoocommerceTransactional = function () {
        return false;
      };
      model.isAutomationEmail = function () {
        return false;
      };
      model.isConfirmationEmailTemplate = function () {
        return false;
      };
      view = new HeadingComponent.HeadingView({
        model: model,
      });
    });

    it('renders', function () {
      expect(view.render).to.not.throw();
    });

    describe('once rendered', function () {
      var model;
      beforeEach(function () {
        model = new Backbone.SuperModel({
          subject: 'a test subject',
          preheader: 'a test preheader',
        });
        model.isWoocommerceTransactional = function () {
          return false;
        };
        model.isAutomationEmail = function () {
          return false;
        };
        model.isConfirmationEmailTemplate = function () {
          return false;
        };
        view = new HeadingComponent.HeadingView({
          model: model,
        });
        view.render();
      });

      it('changes the model when subject field is changed', function () {
        view
          .$('.mailpoet_input_title')
          .val('a new testing subject')
          .trigger('change');
        expect(model.get('subject')).to.equal('a new testing subject');
      });

      it('changes the model when preheader field is changed', function () {
        view
          .$('.mailpoet_input_preheader')
          .val('a new testing preheader')
          .trigger('change');
        expect(model.get('preheader')).to.equal('a new testing preheader');
      });
    });
  });
});
