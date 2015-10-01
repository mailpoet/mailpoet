/**
 * Container content block.
 * This is a special kind of block, as it can contain content blocks, as well
 * as other containers.
 */
define([
    'backbone',
    'backbone.marionette',
    'underscore',
    'jquery',
    'newsletter_editor/App',
    'newsletter_editor/blocks/base'
  ], function(Backbone, Marionette, _, jQuery, App, BaseBlock) {

  "use strict";

  var Module = {},
      base = BaseBlock,
      BlockCollection;

  Module.ContainerBlockModel = base.BlockModel.extend({
    relations: {
      blocks: BlockCollection,
    },
    defaults: function() {
      return this._getDefaults({
        type: 'container',
        orientation: 'vertical',
        styles: {
          block: {
            backgroundColor: 'transparent',
          },
        },
        blocks: new BlockCollection(),
      }, App.getConfig().get('blockDefaults.container'));
    },
    validate: function() {
      // Recursively propagate validation checks to blocks in the tree
      var invalidBlock =  this.get('blocks').find(function(block) { return !block.isValid(); });
      if (invalidBlock) {
        return invalidBlock.validationError;
      }
    },
    parse: function(response) {
      // If container has any blocks - add them to a collection
      if (response.type === 'container' && _.has(response, 'blocks')) {
        response.blocks = new BlockCollection(response.blocks, {
          parse: true,
        });
      }
      return response;
    },
  });

  BlockCollection = Backbone.Collection.extend({
    model: base.BlockModel,
    initialize: function() {
      this.on('add change remove', function() { App.getChannel().trigger('autoSave'); });
    },
    parse: function(response) {
      var self = this;
      return _.map(response, function(block) {
        var Type = App.getBlockTypeModel(block.type);
        // TODO: If type has no registered model, use a backup one
        return new Type(block, {parse: true});
      });
    },
  });

  Module.ContainerBlockView = Marionette.CompositeView.extend({
    regionClass: Marionette.Region,
    className: 'mailpoet_block mailpoet_container_block mailpoet_droppable_block mailpoet_droppable_layout_block',
    getTemplate: function() { return templates.containerBlock; },
    childViewContainer: '> .mailpoet_container',
    getEmptyView: function() { return Module.ContainerBlockEmptyView; },
    emptyViewOptions: function() { return { renderOptions: this.renderOptions }; },
    modelEvents: {
      'change': 'render'
    },
    events: {
      "mouseenter": "showTools",
      "mouseleave": "hideTools",
      "click .mailpoet_newsletter_layer_selector": "toggleEditingLayer",
    },
    regions: {
      toolsRegion: '> .mailpoet_tools',
    },
    ui: {
      tools: '> .mailpoet_tools'
    },
    behaviors: {
      ContainerDropZoneBehavior: {},
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
        testAttachToInstance: function(model, view) {
          // Attach Draggable only to layout containers and disable it
          // for root and column containers.
          return view.renderOptions.depth === 1;
        },
      },
    },
    onDragSubstituteBy: function() {
      // For two and three column layouts display their respective widgets,
      // otherwise always default to one column layout widget
      if (this.renderOptions.depth === 1) {
        if (this.model.get('blocks').length === 3) return Module.ThreeColumnContainerWidgetView;
        if (this.model.get('blocks').length === 2) return Module.TwoColumnContainerWidgetView;
      }
      return Module.OneColumnContainerWidgetView;

    },
    constructor: function() {
      // Set the block collection to be handled by this view as well
      arguments[0].collection = arguments[0].model.get('blocks');
      Marionette.CompositeView.apply(this, arguments);
      this.$el.addClass('mailpoet_editor_view_' + this.cid);
    },
    initialize: function(options) {
      this.renderOptions = _.defaults(options.renderOptions || {}, {});
    },
    // Determines which view type should be used for a child
    getChildView: function(model) {
      // TODO: If type does not have a type registered, use a generic one
      return App.getBlockTypeView(model.get('type'));
    },
    childViewOptions: function() {
      var newRenderOptions = _.clone(this.renderOptions);
      if (newRenderOptions.depth !== undefined) {
        newRenderOptions.depth += 1;
      }
      return {
        renderOptions: newRenderOptions
      };
    },
    templateHelpers: function() {
      return {
        model: this.model.toJSON(),
        viewCid: this.cid,
      };
    },
    onRender: function() {
      this._rebuildRegions();
      this.toolsView = new Module.ContainerBlockToolsView({
        model: this.model,
        tools: {
          settings: this.renderOptions.depth > 1,
          delete: this.renderOptions.depth === 1,
          move: this.renderOptions.depth === 1,
          layerSelector: this.renderOptions.depth === 1,
        },
      });
      this.toolsRegion.show(this.toolsView);
    },
    onBeforeDestroy: function() {
      this.regionManager.destroy();
    },
    showTools: function() {
      if (this.renderOptions.depth === 1 && !this.$el.hasClass('mailpoet_container_layer_active')) {
        this.$(this.ui.tools).show();
        this.toolsView.triggerMethod('showTools');
      }
    },
    hideTools: function() {
      if (this.renderOptions.depth === 1 && !this.$el.hasClass('mailpoet_container_layer_active')) {
        this.$(this.ui.tools).hide();
        this.toolsView.triggerMethod('hideTools');
      }
    },
    toggleEditingLayer: function(event) {
      var that = this,
        $toggleButton = this.$('> .mailpoet_tools .mailpoet_newsletter_layer_selector'),
        $overlay = jQuery('.mailpoet_layer_overlay'),
        $container = this.$('> .mailpoet_container'),
        enableContainerLayer = function() {
          that.$el.addClass('mailpoet_container_layer_active');
          $toggleButton.addClass('mailpoet_container_layer_active');
          $container.addClass('mailpoet_layer_highlight');
          $overlay.click(disableContainerLayer);
          $overlay.show();
        },
        disableContainerLayer = function() {
          that.$el.removeClass('mailpoet_container_layer_active');
          $toggleButton.removeClass('mailpoet_container_layer_active');
          $container.removeClass('mailpoet_layer_highlight');
          $overlay.hide();
          $overlay.off('click');
        };
      if ($toggleButton.hasClass('mailpoet_container_layer_active')) {
        disableContainerLayer();
      } else {
        enableContainerLayer();
      }
      event.stopPropagation();
    },
    _buildRegions: function(regions) {
      var that = this;

      var defaults = {
        regionClass: this.getOption('regionClass'),
        parentEl: function() { return that.$el; }
      };

      return this.regionManager.addRegions(regions, defaults);
    },
    _rebuildRegions: function() {
      if (this.regionManager === undefined) {
        this.regionManager = new Marionette.RegionManager();
      }
      this.regionManager.destroy();
      _.extend(this, this._buildRegions(this.regions));
    },
    getDropFunc: function() {
      var that = this;
      return function() {
        var newModel = that.model.clone();
        that.model.destroy();
        return newModel;
      };
    },
  });

  Module.ContainerBlockEmptyView = Marionette.ItemView.extend({
    getTemplate: function() { return templates.containerEmpty; },
    initialize: function(options) {
      this.renderOptions = _.defaults(options.renderOptions || {}, {});
    },
    templateHelpers: function() {
      return {
        isRoot: this.renderOptions.depth === 0,
      };
    },
  });

  Module.ContainerBlockToolsView = base.BlockToolsView.extend({
    getSettingsView: function() { return Module.ContainerBlockSettingsView; },
  });

  Module.ContainerBlockSettingsView = base.BlockSettingsView.extend({
    getTemplate: function() { return templates.containerBlockSettings; },
    events: function() {
      return {
        "change .mailpoet_field_container_background_color": _.partial(this.changeColorField, "styles.block.backgroundColor"),
        "click .mailpoet_done_editing": "close",
      };
    },
    behaviors: {
      ColorPickerBehavior: {},
    },
  });

  Module.OneColumnContainerWidgetView = base.WidgetView.extend({
    className: base.WidgetView.prototype.className + ' mailpoet_droppable_layout_block',
    getTemplate: function() { return templates.oneColumnLayoutInsertion; },
    behaviors: {
      DraggableBehavior: {
        cloneOriginal: true,
        drop: function() {
          return new Module.ContainerBlockModel({
            orientation: 'horizontal',
            blocks: [
              new Module.ContainerBlockModel(),
            ]
          });
        }
      }
    },
  });

  Module.TwoColumnContainerWidgetView = base.WidgetView.extend({
    className: base.WidgetView.prototype.className + ' mailpoet_droppable_layout_block',
    getTemplate: function() { return templates.twoColumnLayoutInsertion; },
    behaviors: {
      DraggableBehavior: {
        cloneOriginal: true,
        drop: function() {
          return new Module.ContainerBlockModel({
            orientation: 'horizontal',
            blocks: [
              new Module.ContainerBlockModel(),
              new Module.ContainerBlockModel(),
            ]
          });
        }
      }
    },
  });

  Module.ThreeColumnContainerWidgetView = base.WidgetView.extend({
    className: base.WidgetView.prototype.className + ' mailpoet_droppable_layout_block',
    getTemplate: function() { return templates.threeColumnLayoutInsertion; },
    behaviors: {
      DraggableBehavior: {
        cloneOriginal: true,
        drop: function() {
          return new Module.ContainerBlockModel({
            orientation: 'horizontal',
            blocks: [
              new Module.ContainerBlockModel(),
              new Module.ContainerBlockModel(),
              new Module.ContainerBlockModel(),
            ]
          });
        }
      }
    },
  });

  App.on('before:start', function() {
    App.registerBlockType('container', {
      blockModel: Module.ContainerBlockModel,
      blockView: Module.ContainerBlockView,
    });

    App.registerLayoutWidget({
      name: 'oneColumnLayout',
      priority: 100,
      widgetView: Module.OneColumnContainerWidgetView,
    });

    App.registerLayoutWidget({
      name: 'twoColumnLayout',
      priority: 100,
      widgetView: Module.TwoColumnContainerWidgetView,
    });

    App.registerLayoutWidget({
      name: 'threeColumnLayout',
      priority: 100,
      widgetView: Module.ThreeColumnContainerWidgetView,
    });
  });

  return Module;
});
