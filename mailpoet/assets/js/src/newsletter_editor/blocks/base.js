/**
 * Defines base classes for actual content blocks to extend.
 * Extending content block modules need to at least extend
 * a BlockModel and a BlockView.
 * BlockToolsView, BlockSettingsView and BlockWidgetView are optional.
 */
import { App } from 'newsletter_editor/App';
import Marionette from 'backbone.marionette';
import SuperModel from 'backbone.supermodel';
import _ from 'underscore';
import jQuery from 'jquery';
import { MailPoet } from 'mailpoet';
import 'modal';
import { validateField } from '../utils';

var Module = {};
var AugmentedView = Marionette.View.extend({});

Module.BlockModel = SuperModel.extend({
  stale: [], // Attributes to be removed upon saving
  initialize: function initialize() {
    this.on('change', function onChange() {
      this._updateDefaults();
      App.getChannel().trigger('autoSave');
    });
  },
  _getDefaults: function getDefaults(blockDefaults, configDefaults) {
    var defaults;
    if (_.isObject(configDefaults) && _.isFunction(configDefaults.toJSON)) {
      defaults = configDefaults.toJSON();
    } else {
      defaults = configDefaults;
    }

    // Patch the resulting JSON object and fix it's constructors to be Object.
    // Otherwise SuperModel interprets it not as a simpleObject
    // and misbehaves
    // TODO: Investigate for a better solution
    return JSON.parse(
      JSON.stringify(jQuery.extend(blockDefaults, defaults || {})),
    );
  },
  _updateDefaults: function updateDefaults() {
    var context = this.get('context') || this.get('type');
    App.getConfig().set('blockDefaults.' + context, this.toJSON());
  },
  toJSON: function toJSON() {
    // Remove stale attributes from resulting JSON object
    return _.omit(SuperModel.prototype.toJSON.call(this), this.stale);
  },
  getChildren: function getChildren() {
    return [];
  },
});

Module.BlockView = AugmentedView.extend({
  regions: {
    toolsRegion: '> .mailpoet_tools',
  },
  modelEvents: {
    change: 'render',
    delete: 'deleteBlock',
    duplicate: 'duplicateBlock',
  },
  behaviors: {
    DraggableBehavior: {
      cloneOriginal: true,
      hideOriginal: true,
      onDrop: function onDrop(options) {
        // After a clone of model has been dropped, cleanup
        // and destroy self
        App.getChannel().trigger('hideSettings');
        options.dragBehavior.view.model.destroy();
      },
      onDragSubstituteBy: function onDragSubstituteBy(behavior) {
        var WidgetView;
        var node;
        // When block is being dragged, display the widget icon instead.
        // This will create an instance of block's widget view and
        // use it's rendered DOM element instead of the content block
        if (_.isFunction(behavior.view.onDragSubstituteBy)) {
          WidgetView = new (behavior.view.onDragSubstituteBy())();
          WidgetView.render();
          node = WidgetView.$el.get(0).cloneNode(true);
          WidgetView.destroy();
          return node;
        }
        return undefined;
      },
    },
    HighlightEditingBehavior: {},
  },
  templateContext: function templateContext() {
    return {
      model: this.model.toJSON(),
      viewCid: this.cid,
    };
  },
  constructor: function constructor() {
    AugmentedView.apply(this, arguments);
    this.$el.addClass('mailpoet_editor_view_' + this.cid);
  },
  initialize: function initialize() {
    this.on('showSettings', this.showSettings, this);
  },
  addHighlight: function addHighlight() {
    this.$el.addClass('mailpoet_highlight');
    if (!this.showingToolsDisabled) {
      this.$('> .mailpoet_tools').addClass('mailpoet_display_tools');
      this.toolsView.triggerMethod('showTools');
    }
  },
  removeHighlight: function removeHighlight() {
    this.$el.removeClass('mailpoet_highlight');
    this.hideTools();
  },
  hideTools: function hideTools() {
    this.$('> .mailpoet_tools').removeClass('mailpoet_display_tools');
    this.toolsView.triggerMethod('hideTools');
  },
  enableShowingTools: function enableShowingTools() {
    this.showingToolsDisabled = false;
  },
  disableShowingTools: function disableShowingTools() {
    this.showingToolsDisabled = true;
    this.hideTools();
  },
  showSettings: function showSettings(options) {
    this.toolsView.triggerMethod('showSettings', options);
  },
  /**
   * Defines drop behavior of BlockView instance
   */
  getDropFunc: function getDropFunc() {
    return function getDropFuncClone() {
      return this.model.clone();
    }.bind(this);
  },
  disableDragging: function disableDragging() {
    this.$el.addClass('mailpoet_ignore_drag');
  },
  enableDragging: function enableDragging() {
    this.$el.removeClass('mailpoet_ignore_drag');
  },
  deleteBlock: function deleteBlock() {
    this.transitionOut().then(
      function deleteBlockDestroy() {
        this.model.destroy();
      }.bind(this),
    );
  },
  duplicateBlock: function duplicateBlock() {
    this.model.collection.add(this.model.toJSON(), {
      at: this.model.collection.findIndex(this.model),
    });
  },
  transitionOut: function transitionOut() {
    return this._transition('slideUp', 'fadeOut', 'easeIn');
  },
  _transition: function transition(slideDirection, fadeDirection, easing) {
    var promise = jQuery.Deferred();

    this.$el
      .velocity(slideDirection, {
        duration: 250,
        easing: easing,
        complete: function complete() {
          promise.resolve();
        },
      })
      .velocity(fadeDirection, {
        duration: 250,
        easing: easing,
        queue: false, // Do not enqueue, trigger animation in parallel
      });

    return promise;
  },
});

Module.BlockToolsView = AugmentedView.extend({
  getTemplate: function getTemplate() {
    return window.templates.genericBlockTools;
  },
  events: {
    'click .mailpoet_edit_block': 'toggleSettings',
    'click .mailpoet_delete_block_activate': 'showDeletionConfirmation',
    'click .mailpoet_delete_block_cancel': 'hideDeletionConfirmation',
    'click .mailpoet_delete_block_confirm': 'deleteBlock',
    'click .mailpoet_duplicate_block': 'duplicateBlock',
  },
  // Markers of whether these particular tools will be used for this instance
  tools: {
    settings: true,
    delete: true,
    duplicate: true,
    move: true,
  },
  getSettingsView: function getSettingsView() {
    return Module.BlockSettingsView;
  },
  initialize: function initialize(opts) {
    var options = opts || {};
    if (!_.isUndefined(options.tools)) {
      // Make a new block specific tool config object
      this.tools = jQuery.extend({}, this.tools, options.tools || {});
    }

    // Automatically cancel deletion
    this.on('hideTools', this.hideDeletionConfirmation, this);
    this.on('showSettings', this.changeSettings);
  },
  templateContext: function templateContext() {
    return {
      model: this.model.toJSON(),
      viewCid: this.cid,
      tools: this.tools,
    };
  },
  toggleSettings: function toggleSettings() {
    if (App.getDisplayedSettingsId() === this.model.cid) {
      App.getChannel().trigger('hideSettings');
      return;
    }
    this.changeSettings();
  },
  changeSettings: function changeSettings(options) {
    var ViewType = this.getSettingsView();
    var displayedSettingsId = App.getDisplayedSettingsId();
    if (displayedSettingsId) {
      if (displayedSettingsId === this.model.cid) {
        return;
      }
      App.getChannel().trigger('hideSettings');
      return;
    }
    document.activeElement.blur();
    App.getChannel().trigger('settingsDisplayed', this.model.cid);
    new ViewType(_.extend({ model: this.model }, options || {})).render();
  },
  showDeletionConfirmation: function showDeletionConfirmation() {
    this.$('.mailpoet_delete_block')
      .closest('.mailpoet_block')
      .find('> .mailpoet_block_highlight')
      .css({ background: '#E64047', opacity: 0.5 });

    this.$('.mailpoet_delete_block').addClass(
      'mailpoet_delete_block_activated',
    );
  },
  hideDeletionConfirmation: function hideDeletionConfirmation() {
    this.$('.mailpoet_delete_block')
      .closest('.mailpoet_block')
      .find('> .mailpoet_block_highlight')
      .css({ background: 'transparent', opacity: 1 });

    this.$('.mailpoet_delete_block').removeClass(
      'mailpoet_delete_block_activated',
    );
  },
  deleteBlock: function deleteBlock(event) {
    event.preventDefault();
    this.model.trigger('delete');
    App.getChannel().trigger('hideSettings');
    return false;
  },
  duplicateBlock: function duplicateBlock(event) {
    event.preventDefault();
    this.model.trigger('duplicate');
    App.getChannel().trigger('hideSettings');
    return false;
  },
});

Module.BlockSettingsView = Marionette.View.extend({
  className: 'mailpoet_editor_settings',
  behaviors: {
    ColorPickerBehavior: {},
  },
  initialize: function initialize(params) {
    var panelParams;
    this.model.trigger('startEditing');
    panelParams = {
      element: this.$el,
      template: '',
      position: 'right',
      overlayRender: false,
      width: App.getConfig().get('sidepanelWidth'),
      onCancel: function onCancel() {
        this.destroy();
      }.bind(this),
    };
    this.renderOptions = params.renderOptions || {};
    if (this.renderOptions.displayFormat === 'subpanel') {
      MailPoet.Modal.subpanel(panelParams);
    } else {
      MailPoet.Modal.panel(panelParams);
    }
    this.listenTo(App.getChannel(), 'hideSettings', this.close);
  },
  templateContext: function templateContext() {
    return {
      model: this.model.toJSON(),
    };
  },
  close: function close() {
    this.destroy();
  },
  changeField: function changeField(field, event) {
    if (!validateField(event.target)) {
      return;
    }
    this.model.set(field, jQuery(event.target).val());
  },
  changePixelField: function changePixelField(field, event) {
    this.changeFieldWithSuffix(field, event, 'px');
  },
  changeFieldWithSuffix: function changeFieldWithSuffix(field, event, suffix) {
    this.model.set(field, jQuery(event.target).val() + suffix);
  },
  changeBoolField: function changeBoolField(field, event) {
    this.model.set(field, jQuery(event.target).val() === 'true');
  },
  changeBoolCheckboxField: function changeBoolCheckboxField(field, event) {
    this.model.set(field, !!jQuery(event.target).prop('checked'));
  },
  changeColorField: function changeColorField(field, event) {
    var value = jQuery(event.target).val();
    if (value === '') {
      value = 'transparent';
    }
    this.model.set(field, value);
  },
  onBeforeDestroy: function onBeforeDestroy() {
    MailPoet.Modal.close();
    this.model.trigger('stopEditing');
    App.getChannel().trigger('settingsDisplayed', null);
  },
});

Module.WidgetView = Marionette.View.extend({
  className:
    'mailpoet_widget mailpoet_droppable_block mailpoet_droppable_widget',
  behaviors: {
    DraggableBehavior: {
      drop: function drop() {
        throw new Error('Unsupported operation');
      },
    },
  },
});

export { Module as BaseBlock };
