import { App } from 'newsletter-editor/app';
import Marionette from 'backbone.marionette';
import Mousetrap from 'mousetrap';
import { _x, __ } from '@wordpress/i18n';
import { cloneDeep } from 'lodash';

var Module = {};

Module.HistoryView = Marionette.View.extend({
  MAX_HISTORY_STATES: 25,

  elements: {
    redo: null,
    undo: null,
    resetTemplate: null,
  },

  events: {
    'click #mailpoet-history-arrow-undo': 'undo',
    'click #mailpoet-history-arrow-redo': 'redo',
    'click #mailpoet-history-reset-template': 'resetTemplate',
  },

  model: {
    statesStack: [],
    currentStateIndex: 0,
  },

  getTemplate: function getTemplate() {
    return window.templates.history;
  },

  getOriginalTemplate: function getOriginalTemplate() {
    return cloneDeep(window.mailpoet_original_template_body);
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
    this.elements.resetTemplate = document.getElementById(
      'mailpoet-history-reset-template',
    );
    // Show reset template button only if there is an original template
    if (this.getOriginalTemplate()) {
      this.elements.resetTemplate.classList.remove('mailpoet_hidden');
    }
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

  resetTemplate: function resetTemplate() {
    const templateBody = this.getOriginalTemplate();
    // Usage of confirm is intentional and appropriate for this case
    // eslint-disable-next-line no-alert
    const confirmed = window.confirm(
      __(
        'Reset template will restore the original template as distributed with the plugin. You will loose all your edits.',
      ),
    );
    if (!confirmed) {
      return;
    }
    App.getChannel().trigger('historyUpdate', templateBody);
    App.getChannel().request('save');
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
      this.canUndo()
        ? _x(
            'Undo',
            'A button title when user can undo the change in editor',
            'mailpoet',
          )
        : _x(
            'No actions available to undo.',
            "A button title when user can't undo the change in editor",
            'mailpoet',
          ),
    );
    this.elements.redo.setAttribute(
      'title',
      this.canRedo()
        ? _x(
            'Redo',
            'A button title when user can redo the change in editor',
            'mailpoet',
          )
        : _x(
            'No actions available to redo.',
            "A button title when user can't redo the change in editor",
            'mailpoet',
          ),
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

export { Module as HistoryComponent };
