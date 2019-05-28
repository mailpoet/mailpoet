import App from 'newsletter_editor/App';
import Marionette from 'backbone.marionette';

var Module = {};

Module.HistoryView = Marionette.View.extend({
  getTemplate: function getTemplate() {
    return window.templates.history;
  },
});

App.on('start', function appStart(StartApp) {
  StartApp._appView.showChildView('historyRegion', new Module.HistoryView());
});

export default Module;
