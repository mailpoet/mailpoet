import App from 'newsletter_editor/App';
import MailPoet from 'mailpoet';
import Marionette from 'backbone.marionette';
import Mousetrap from 'mousetrap';

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
    statesStack: [],
    currentStateIndex: 0,
  },

  getTemplate: function getTemplate() {
    return window.templates.history;
  },

  initialize: function initialize() {
    var that = this;
    App.getChannel().on('afterEditorSave', this.addState, this);
    Mousetrap.bind(['ctrl+z', 'command+z'], function keyboardUndo() {
      that.undo();
    });
    Mousetrap.bind(
      ['shift+ctrl+z', 'shift+command+z'],
      function keyboardRedo() {
        that.redo();
      },
    );
  },

  onAttach: function onRender() {
    this.elements.redo = document.getElementById('mailpoet-history-arrow-redo');
    this.elements.undo = document.getElementById('mailpoet-history-arrow-undo');
    this.addState(App.toJSON());
  },

  addState: function addState(json) {
    var stringifiedBody;
    if (!json || !json.body) {
      return;
    }
    stringifiedBody = JSON.stringify(json.body);
    if (
      this.model.statesStack[this.model.currentStateIndex] === stringifiedBody
    ) {
      return;
    }
    if (this.model.currentStateIndex > 0) {
      this.model.statesStack.splice(0, this.model.currentStateIndex);
    }
    this.model.statesStack.unshift(stringifiedBody);
    this.model.currentStateIndex = 0;
    this.model.statesStack.length = Math.min(
      this.model.statesStack.length,
      this.MAX_HISTORY_STATES,
    );
    this.updateArrowsUI();
  },

  canUndo: function canUndo() {
    return this.model.currentStateIndex < this.model.statesStack.length - 1;
  },

  canRedo: function canRedo() {
    return this.model.currentStateIndex > 0;
  },

  undo: function undo() {
    if (!this.canUndo()) {
      return;
    }
    this.model.currentStateIndex = Math.min(
      this.model.statesStack.length - 1,
      this.model.currentStateIndex + 1,
    );
    this.updateArrowsUI();
    this.applyState(this.model.currentStateIndex);
  },

  redo: function redo() {
    if (!this.canRedo()) {
      return;
    }
    this.model.currentStateIndex = Math.max(
      0,
      this.model.currentStateIndex - 1,
    );
    this.updateArrowsUI();
    this.applyState(this.model.currentStateIndex);
  },

  updateArrowsUI: function updateArrowsUI() {
    this.elements.undo.classList.toggle(
      'mailpoet_history_arrow_inactive',
      !this.canUndo(),
    );
    this.elements.redo.classList.toggle(
      'mailpoet_history_arrow_inactive',
      !this.canRedo(),
    );
    this.elements.undo.setAttribute(
      'title',
      MailPoet.I18n.t(this.canUndo() ? 'canUndo' : 'canNotUndo'),
    );
    this.elements.redo.setAttribute(
      'title',
      MailPoet.I18n.t(this.canRedo() ? 'canRedo' : 'canNotRedo'),
    );
  },

  applyState: function applyState(index) {
    const stateToApply = JSON.parse(this.model.statesStack[index]);
    App.getChannel().trigger('historyUpdate', stateToApply);
  },
});

App.on('start', function appStart(StartApp) {
  StartApp._appView.showChildView('historyRegion', new Module.HistoryView());
});

export default Module;
