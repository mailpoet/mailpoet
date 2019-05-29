import App from 'newsletter_editor/App';
import jQuery from 'jquery';
import Marionette from 'backbone.marionette';

var Module = {};

Module.HistoryView = Marionette.View.extend({
  MAX_HISTORY_STATES: 25,

  elements: {
    redo: null,
    undo: null,
  },

  events: {
    'click #mailpoet-history-arrow-undo': 'undo',
    'click #mailpoet-history-arrow-redo': 'redo',
  },

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

  onAttach: function onRender() {
    this.elements.redo = jQuery('#mailpoet-history-arrow-redo');
    this.elements.undo = jQuery('#mailpoet-history-arrow-undo');
    this.updateArrowsUI();
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
    this.updateArrowsUI();
  },

  canUndo: function canUndo() {
    return this.model.currentStateIndex < (this.model.states.length - 1)
  },

  canRedo: function canRedo() {
    return this.model.currentStateIndex !== 0;
  },

  undo: function undo() {
    if (!this.canUndo()) {
      return;
    }
  },

  redo: function redo() {
    if (!this.canRedo()) {
      return;
    }
  },

  updateArrowsUI: function updateArrowsUI() {
    this.elements.undo.addClass('mailpoet_history_arrow_inactive');
    this.elements.redo.addClass('mailpoet_history_arrow_inactive');
    if (this.canUndo()) {
      this.elements.undo.removeClass('mailpoet_history_arrow_inactive');
    };
    if (this.canRedo()) {
      this.elements.redo.removeClass('mailpoet_history_arrow_inactive');
    };
  },
});

App.on('start', function appStart(StartApp) {
  StartApp._appView.showChildView('historyRegion', new Module.HistoryView());
});

export default Module;
