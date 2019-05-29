import App from 'newsletter_editor/App';
import Marionette from 'backbone.marionette';

var Module = {};

Module.HistoryView = Marionette.View.extend({
  MAX_HISTORY_STATES: 25,

  model: {
    states: [], // from newest
    currentStateIndex: 0,
  },

  getTemplate: function getTemplate() {
    return window.templates.history;
  },

  initialize: function initialize() {
    App.getChannel().on('afterEditorSave', this.setCurrentState, this);
  },

  setCurrentState: function setCurrentState(json) {
    if (!json || !json.body) {
      return;
    }
    if (this.model.currentStateIndex > 0) {
      this.model.states.splice(0, this.model.currentStateIndex);
    }
    this.model.states.unshift(json.body);
    this.model.currentStateIndex = 0;
    this.model.states.length = Math.min(this.model.states.length, this.MAX_HISTORY_STATES);
  },
});

App.on('start', function appStart(StartApp) {
  StartApp._appView.showChildView('historyRegion', new Module.HistoryView());
});

export default Module;
