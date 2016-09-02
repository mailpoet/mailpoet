/**
 * Defines base classes for actual content blocks to extend.
 * Extending content block modules need to at least extend
 * a BlockModel and a BlockView.
 * BlockToolsView, BlockSettingsView and BlockWidgetView are optional.
 */
define([
    'newsletter_editor/App',
    'backbone.marionette',
    'backbone.supermodel',
    'underscore',
    'jquery',
    'mailpoet',
    'modal'
  ], function(App, Marionette, SuperModel, _, jQuery, MailPoet, Modal) {

  "use strict";

  var Module = {},
      AugmentedView = Marionette.LayoutView.extend({});

  Module.BlockModel = SuperModel.extend({
    stale: [], // Attributes to be removed upon saving
    initialize: function() {
      var that = this;
      this.on('change', function() {
        App.getChannel().trigger('autoSave');
      });
    },
    _getDefaults: function(blockDefaults, configDefaults) {
      var defaults = (_.isObject(configDefaults) && _.isFunction(configDefaults.toJSON)) ? configDefaults.toJSON() : configDefaults;

      // Patch the resulting JSON object and fix it's constructors to be Object.
      // Otherwise SuperModel interprets it not as a simpleObject
      // and misbehaves
      // TODO: Investigate for a better solution
      return JSON.parse(JSON.stringify(jQuery.extend(blockDefaults, defaults || {})));
    },
    toJSON: function() {
      // Remove stale attributes from resulting JSON object
      return _.omit(SuperModel.prototype.toJSON.call(this), this.stale);
    },
    getChildren: function() {
      return [];
    },
  });

  Module.BlockView = AugmentedView.extend({
    regions: {
      toolsRegion: '> .mailpoet_tools',
    },
    modelEvents: {
      'change': 'render',
      'delete': 'deleteBlock',
    },
    events: {
      "mouseenter": "showTools",
      "mouseleave": "hideTools",
    },
    behaviors: {
      DraggableBehavior: {
        cloneOriginal: true,
        hideOriginal: true,
        onDrop: function(options) {
          // After a clone of model has been dropped, cleanup
          // and destroy self
          options.dragBehavior.view.model.destroy();
        },
        onDragSubstituteBy: function(behavior) {
          var WidgetView, node;
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
        },
      },
      HighlightEditingBehavior: {},
    },
    templateHelpers: function() {
      return {
        model: this.model.toJSON(),
        viewCid: this.cid,
      };
    },
    constructor: function() {
      AugmentedView.apply(this, arguments);
      this.$el.addClass('mailpoet_editor_view_' + this.cid);
    },
    initialize: function() {
      this.on('showSettings', this.showSettings, this);
      this.on('dom:refresh', this.showBlock, this);
      this._isFirstRender = true;
    },
    showTools: function(_event) {
      if (!this.showingToolsDisabled) {
        this.$('> .mailpoet_tools').addClass('mailpoet_display_tools');
        this.toolsView.triggerMethod('showTools');
      }
    },
    hideTools: function(e) {
      this.$('> .mailpoet_tools').removeClass('mailpoet_display_tools');
      this.toolsView.triggerMethod('hideTools');
    },
    enableShowingTools: function() {
      this.showingToolsDisabled = false;
    },
    disableShowingTools: function() {
      this.showingToolsDisabled = true;
      this.hideTools();
    },
    showSettings: function(options) {
      this.toolsView.triggerMethod('showSettings', options);
    },
    /**
     * Defines drop behavior of BlockView instance
     */
    getDropFunc: function() {
      return function() {
        return this.model.clone();
      }.bind(this);
    },
    showBlock: function() {
      if (this._isFirstRender) {
        this.transitionIn();
        this._isFirstRender = false;
      }
    },
    deleteBlock: function() {
      this.transitionOut().then(function() {
        this.model.destroy();
      }.bind(this));
    },
    transitionIn: function() {
      return this._transition('slideDown', 'fadeIn', 'easeOut');
    },
    transitionOut: function() {
      return this._transition('slideUp', 'fadeOut', 'easeIn');
    },
    _transition: function(slideDirection, fadeDirection, easing) {
      var promise = jQuery.Deferred();

      this.$el.velocity(
        slideDirection,
        {
          duration: 250,
          easing: easing,
          complete: function() {
            promise.resolve();
          }.bind(this),
        }
      ).velocity(
        fadeDirection,
        {
          duration: 250,
          easing: easing,
          queue: false, // Do not enqueue, trigger animation in parallel
        }
      );

      return promise;
    },
  });

  Module.BlockToolsView = AugmentedView.extend({
    getTemplate: function() { return templates.genericBlockTools; },
    events: {
      "click .mailpoet_edit_block": "changeSettings",
      "click .mailpoet_delete_block_activate": "showDeletionConfirmation",
      "click .mailpoet_delete_block_cancel": "hideDeletionConfirmation",
      "click .mailpoet_delete_block_confirm": "deleteBlock",
    },
    // Markers of whether these particular tools will be used for this instance
    tools: {
      settings: true,
      delete: true,
      move: true,
    },
    getSettingsView: function() { return Module.BlockSettingsView; },
    initialize: function(options) {
      options = options || {};
      if (!_.isUndefined(options.tools)) {
        // Make a new block specific tool config object
        this.tools = jQuery.extend({}, this.tools, options.tools || {});
      }

      // Automatically cancel deletion
      this.on('hideTools', this.hideDeletionConfirmation, this);
      this.on('showSettings', this.changeSettings);
    },
    templateHelpers: function() {
      return {
        model: this.model.toJSON(),
        viewCid: this.cid,
        tools: this.tools,
      };
    },
    changeSettings: function(options) {
      var ViewType = this.getSettingsView();
      (new ViewType(_.extend({ model: this.model }, options || {}))).render();
    },
    showDeletionConfirmation: function() {
      this.$('.mailpoet_delete_block').addClass('mailpoet_delete_block_activated');
    },
    hideDeletionConfirmation: function() {
      this.$('.mailpoet_delete_block').removeClass('mailpoet_delete_block_activated');
    },
    deleteBlock: function(event) {
      event.preventDefault();
      this.model.trigger('delete');
      return false;
    },
  });

  Module.BlockSettingsView = Marionette.LayoutView.extend({
    className: 'mailpoet_editor_settings',
    behaviors: {
      ColorPickerBehavior: {},
    },
    initialize: function(params) {
      this.model.trigger('startEditing');
      var panelParams = {
        element: this.$el,
        template: '',
        position: 'right',
        width: App.getConfig().get('sidepanelWidth'),
        onCancel: function() {
          this.destroy();
        }.bind(this),
      };
      this.renderOptions = params.renderOptions || {};
      if (this.renderOptions.displayFormat === 'subpanel') {
        MailPoet.Modal.subpanel(panelParams);
      } else {
        MailPoet.Modal.panel(panelParams);
      }
    },
    close: function(event) {
      this.destroy();
    },
    changeField: function(field, event) {
      this.model.set(field, jQuery(event.target).val());
    },
    changePixelField: function(field, event) {
      this.changeFieldWithSuffix(field, event, 'px');
    },
    changeFieldWithSuffix: function(field, event, suffix) {
      this.model.set(field, jQuery(event.target).val() + suffix);
    },
    changeBoolField: function(field, event) {
      this.model.set(field, (jQuery(event.target).val() === 'true') ? true : false);
    },
    changeBoolCheckboxField: function(field, event) {
      this.model.set(field, (!!jQuery(event.target).prop('checked')));
    },
    changeColorField: function(field, event) {
      var value = jQuery(event.target).val();
      if (value === '') {
        value = 'transparent';
      }
      this.model.set(field, value);
    },
    onBeforeDestroy: function() {
      MailPoet.Modal.close();
      this.model.trigger('stopEditing');
    },
  });

  Module.WidgetView = Marionette.ItemView.extend({
    className: 'mailpoet_widget mailpoet_droppable_block mailpoet_droppable_widget',
    behaviors: {
      DraggableBehavior: {
        drop: function() {
          throw "Unsupported operation";
        }
      }
    },
  });

  return Module;
});
