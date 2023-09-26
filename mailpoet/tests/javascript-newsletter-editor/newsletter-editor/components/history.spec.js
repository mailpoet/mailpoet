import { HistoryComponent } from 'newsletter-editor/components/history';

const expect = global.expect;

describe('History', function () {
  describe('view', function () {
    var view;
    beforeEach(function () {
      view = new HistoryComponent.HistoryView();
    });

    it('renders', function () {
      expect(view.render).to.not.throw();
    });
  });
});
