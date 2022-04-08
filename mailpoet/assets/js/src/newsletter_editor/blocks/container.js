/* eslint-disable func-names */
/**
 * Container content block.
 * This is a special kind of block, as it can contain content blocks, as well
 * as other containers.
 */
import Backbone from 'backbone';
import Marionette from 'backbone.marionette';
import _ from 'underscore';
import jQuery from 'jquery';
import App from 'newsletter_editor/App';
import BaseBlock from 'newsletter_editor/blocks/base';

var Module = {};
var base = BaseBlock;
var BlockCollection;

BlockCollection = Backbone.Collection.extend({
  model: base.BlockModel,
  initialize: function () {
    this.on('add change remove', function () {
      App.getChannel().trigger('autoSave');
    });
  },
  parse: function (response) {
    return _.map(response, function (block) {
      var Type = App.getBlockTypeModel(block.type);
      // TODO: If type has no registered model, use a backup one
      return new Type(block, { parse: true });
    });
  },
});

Module.ContainerBlockModel = base.BlockModel.extend({
  relations: {
    blocks: BlockCollection,
  },
  defaults: function () {
    return this._getDefaults(
      {
        type: 'container',
        columnLayout: false,
        orientation: 'vertical',
        image: {
          src: null,
          display: 'scale',
        },
        styles: {
          block: {
            backgroundColor: 'transparent',
          },
        },
        blocks: new BlockCollection(),
      },
      App.getConfig().get('blockDefaults.container'),
    );
  },
  _updateDefaults: function updateDefaults() {},
  validate: function () {
    // Recursively propagate validation checks to blocks in the tree
    var invalidBlock = this.get('blocks').find(function (block) {
      return !block.isValid();
    });
    if (invalidBlock) {
      return invalidBlock.validationError;
    }
    return undefined;
  },
  parse: function (response) {
    // If container has any blocks - add them to a collection
    if (
      response.type === 'container' &&
      _.has(response, 'blocks') &&
      response.blocks.constructor === Array
    ) {
      response.blocks = new BlockCollection(response.blocks, {
        parse: true,
      });
    }
    return response;
  },
  getChildren: function () {
    var models = this.get('blocks').map(function (model) {
      return [model, model.getChildren()];
    });

    return _.flatten(models);
  },
});

Module.ContainerBlocksView = Marionette.CollectionView.extend({
  className: 'mailpoet_container',
  events: {
    click: 'removeFocusFromAnyActiveElement',
  },
  childView: function (model) {
    return App.getBlockTypeView(model.get('type'));
  },
  childViewOptions: function () {
    var newRenderOptions = _.clone(this.renderOptions);
    if (newRenderOptions.depth !== undefined) {
      newRenderOptions.depth += 1;
    }
    return {
      renderOptions: newRenderOptions,
    };
  },
  emptyView: function () {
    return Module.ContainerBlockEmptyView;
  },
  emptyViewOptions: function () {
    return { renderOptions: this.renderOptions };
  },
  initialize: function (options) {
    this.renderOptions = options.renderOptions;
  },
  onChildviewResizeStart: function onChildviewResizeStart() {
    this.triggerMethod('resizeStart');
  },
  onChildviewResizeStop: function onChildviewResizeStart(event) {
    this.triggerMethod('resizeStop', event);
  },
  removeFocusFromAnyActiveElement: function removeFocusFromAnyActiveElement(
    event,
  ) {
    var elementClass;
    if (!event || !event.target) {
      return;
    }
    elementClass = event.target.getAttribute('class');
    if (
      !elementClass ||
      elementClass.indexOf('mailpoet_container_horizontal') === -1
    ) {
      return;
    }
    document.activeElement.blur();
  },
});

Module.ContainerBlockView = base.BlockView.extend({
  regions: _.extend({}, base.BlockView.prototype.regions, {
    blocks: {
      el: '> .mailpoet_container',
      replaceElement: true,
    },
  }),
  className:
    'mailpoet_block mailpoet_container_block mailpoet_droppable_block mailpoet_droppable_layout_block',
  getTemplate: function () {
    return window.templates.containerBlock;
  },
  events: _.extend({}, base.BlockView.prototype.events, {
    'click .mailpoet_newsletter_layer_selector': 'toggleEditingLayer',
  }),
  ui: {
    tools: '> .mailpoet_tools',
  },
  behaviors: _.extend({}, base.BlockView.prototype.behaviors, {
    ContainerDropZoneBehavior: {},
    DraggableBehavior: {
      cloneOriginal: true,
      hideOriginal: true,
      onDrop: function (options) {
        // After a clone of model has been dropped, cleanup
        // and destroy self
        options.dragBehavior.view.model.destroy();
      },
      onDragSubstituteBy: function (behavior) {
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
      testAttachToInstance: function (model, view) {
        // Attach Draggable only to layout containers and disable it
        // for root and column containers.
        return view.renderOptions.depth === 1;
      },
    },
    HighlightEditingBehavior: {},
  }),
  onDragSubstituteBy: function () {
    // For two and three column layouts display their respective widgets,
    // otherwise always default to one column layout widget
    if (this.renderOptions.depth === 1) {
      if (this.model.get('blocks').length === 3)
        return Module.ThreeColumnContainerWidgetView;
      if (this.model.get('blocks').length === 2)
        return Module.TwoColumnContainerWidgetView;
    }
    return Module.OneColumnContainerWidgetView;
  },
  initialize: function (options) {
    base.BlockView.prototype.initialize.apply(this, arguments);

    this.renderOptions = _.defaults(options.renderOptions || {}, {});
  },
  onRender: function () {
    var classIrregular = '';
    var columnLayout;
    this.toolsView = new Module.ContainerBlockToolsView({
      model: this.model,
      tools: {
        settings: this.renderOptions.depth === 1,
        delete: this.renderOptions.depth === 1,
        duplicate: true,
        move: this.renderOptions.depth === 1,
        layerSelector: false,
      },
    });
    this.showChildView('toolsRegion', this.toolsView);
    this.showChildView(
      'blocks',
      new Module.ContainerBlocksView({
        collection: this.model.get('blocks'),
        renderOptions: this.renderOptions,
      }),
    );

    // TODO: Look for a better way to do this than here
    // Sets child container orientation HTML class here,
    // as child CollectionView won't have access to model
    // and will overwrite existing region element instead
    columnLayout = this.model.get('columnLayout');
    if (typeof columnLayout === 'string') {
      classIrregular =
        'mailpoet_irregular_width_contents_container column_layout_' +
        columnLayout;
    }
    this.$('> .mailpoet_container').attr(
      'class',
      'mailpoet_container mailpoet_container_' +
        this.model.get('orientation') +
        ' ' +
        classIrregular,
    );
  },
  addHighlight: function addHighlight() {
    if (
      this.renderOptions.depth !== 1 ||
      this.$el.hasClass('mailpoet_container_layer_active')
    ) {
      return;
    }
    this.$(this.ui.tools).addClass('mailpoet_display_tools');
    this.$el.addClass('mailpoet_highlight');
    this.toolsView.triggerMethod('showTools');
  },
  removeHighlight: function removeHighlight() {
    if (
      this.renderOptions.depth !== 1 ||
      this.$el.hasClass('mailpoet_container_layer_active')
    ) {
      return;
    }
    this.$(this.ui.tools).removeClass('mailpoet_display_tools');
    this.$el.removeClass('mailpoet_highlight');
    this.toolsView.triggerMethod('hideTools');
  },
  toggleEditingLayer: function (event) {
    var that = this;
    var $toggleButton = this.$(
      '> .mailpoet_tools .mailpoet_newsletter_layer_selector',
    );
    var $overlay = jQuery('.mailpoet_layer_overlay');
    var $container = this.$('> .mailpoet_container');
    var disableContainerLayer = function () {
      that.$el.removeClass('mailpoet_container_layer_active');
      $toggleButton.removeClass('mailpoet_container_layer_active');
      $container.removeClass('mailpoet_layer_highlight');
      $overlay.hide();
      $overlay.off('click');
    };
    var enableContainerLayer = function () {
      that.$el.addClass('mailpoet_container_layer_active');
      $toggleButton.addClass('mailpoet_container_layer_active');
      $container.addClass('mailpoet_layer_highlight');
      $overlay.on('click', disableContainerLayer);
      $overlay.show();
    };
    if ($toggleButton.hasClass('mailpoet_container_layer_active')) {
      disableContainerLayer();
    } else {
      enableContainerLayer();
    }
    event.stopPropagation();
  },
});

Module.ContainerBlockEmptyView = Marionette.View.extend({
  getTemplate: function () {
    return window.templates.containerEmpty;
  },
  initialize: function (options) {
    this.renderOptions = _.defaults(options.renderOptions || {}, {});
  },
  templateContext: function () {
    return {
      isRoot: this.renderOptions.depth === 0,
      emptyContainerMessage: this.renderOptions.emptyContainerMessage || '',
    };
  },
});

Module.ContainerBlockToolsView = base.BlockToolsView.extend({
  getSettingsView: function () {
    return Module.ContainerBlockSettingsView;
  },
});

Module.ContainerBlockSettingsView = base.BlockSettingsView.extend({
  behaviors: _.extend({}, base.BlockSettingsView.prototype.behaviors, {
    MediaManagerBehavior: {
      onSelect: 'onImageSelect',
    },
  }),
  getTemplate: function () {
    return window.templates.containerBlockSettings;
  },
  events: function () {
    return {
      'change .mailpoet_field_container_background_color': _.partial(
        this.changeColorField,
        'styles.block.backgroundColor',
      ),
      'click .mailpoet_done_editing': 'close',
      'change .mailpoet_field_display_type': 'changeDisplayType',
    };
  },
  initialize: function () {
    base.BlockSettingsView.prototype.initialize.apply(this, arguments);
    this.model.trigger('startEditing');
    this._columnsSettingsView = new Module.ContainerBlockColumnsSettingsView({
      collection: this.model.get('blocks'),
    });
  },
  changeDisplayType: function (event) {
    this.model.get('image').set('display', event.target.value);
    this.model.trigger('change');
  },
  onImageSelect: function (image) {
    this.model.set('image.src', image.src);
    this.model.trigger('change');
    this.render();
  },
});

Module.ContainerBlockColumnsSettingsView = Marionette.CollectionView.extend({
  childView: function () {
    return Module.ContainerBlockColumnSettingsView;
  },
  childViewOptions: function (model, index) {
    return {
      columnIndex: index,
    };
  },
});

Module.ContainerBlockColumnSettingsView = Marionette.View.extend({
  getTemplate: function () {
    return window.templates.containerBlockColumnSettings;
  },
  initialize: function (options) {
    this.columnNumber = (options.columnIndex || 0) + 1;
  },
  templateContext: function () {
    return {
      model: this.model.toJSON(),
      columnNumber: this.columnNumber,
    };
  },
});

Module.OneColumnContainerWidgetView = base.WidgetView.extend({
  className:
    base.WidgetView.prototype.className + ' mailpoet_droppable_layout_block',
  getTemplate: function () {
    return window.templates.oneColumnLayoutInsertion;
  },
  behaviors: {
    DraggableBehavior: {
      cloneOriginal: true,
      drop: function () {
        return new Module.ContainerBlockModel({
          orientation: 'horizontal',
          blocks: [new Module.ContainerBlockModel()],
        });
      },
    },
  },
});

Module.TwoColumnContainerWidgetView = base.WidgetView.extend({
  className:
    base.WidgetView.prototype.className + ' mailpoet_droppable_layout_block',
  getTemplate: function () {
    return window.templates.twoColumnLayoutInsertion;
  },
  behaviors: {
    DraggableBehavior: {
      cloneOriginal: true,
      drop: function () {
        return new Module.ContainerBlockModel({
          orientation: 'horizontal',
          blocks: [
            new Module.ContainerBlockModel(),
            new Module.ContainerBlockModel(),
          ],
        });
      },
    },
  },
});

Module.ThreeColumnContainerWidgetView = base.WidgetView.extend({
  className:
    base.WidgetView.prototype.className + ' mailpoet_droppable_layout_block',
  getTemplate: function () {
    return window.templates.threeColumnLayoutInsertion;
  },
  behaviors: {
    DraggableBehavior: {
      cloneOriginal: true,
      drop: function () {
        return new Module.ContainerBlockModel({
          orientation: 'horizontal',
          blocks: [
            new Module.ContainerBlockModel(),
            new Module.ContainerBlockModel(),
            new Module.ContainerBlockModel(),
          ],
        });
      },
    },
  },
});

Module.TwoColumn12ContainerWidgetView = base.WidgetView.extend({
  className:
    base.WidgetView.prototype.className + ' mailpoet_droppable_layout_block',
  getTemplate: function () {
    return window.templates.twoColumn12LayoutInsertion;
  },
  behaviors: {
    DraggableBehavior: {
      cloneOriginal: true,
      drop: function () {
        var block = new Module.ContainerBlockModel({
          orientation: 'horizontal',
          blocks: [
            new Module.ContainerBlockModel(),
            new Module.ContainerBlockModel(),
          ],
        });
        block.set('columnLayout', '1_2');
        return block;
      },
    },
  },
});

Module.TwoColumn21ContainerWidgetView = base.WidgetView.extend({
  className:
    base.WidgetView.prototype.className + ' mailpoet_droppable_layout_block',
  getTemplate: function () {
    return window.templates.twoColumn21LayoutInsertion;
  },
  behaviors: {
    DraggableBehavior: {
      cloneOriginal: true,
      drop: function () {
        var block = new Module.ContainerBlockModel({
          orientation: 'horizontal',
          blocks: [
            new Module.ContainerBlockModel(),
            new Module.ContainerBlockModel(),
          ],
        });
        block.set('columnLayout', '2_1');
        return block;
      },
    },
  },
});

App.on('before:start', function (BeforeStartApp) {
  BeforeStartApp.registerBlockType('container', {
    blockModel: Module.ContainerBlockModel,
    blockView: Module.ContainerBlockView,
  });

  BeforeStartApp.registerLayoutWidget({
    name: 'oneColumnLayout',
    priority: 100,
    widgetView: Module.OneColumnContainerWidgetView,
  });

  BeforeStartApp.registerLayoutWidget({
    name: 'twoColumnLayout',
    priority: 100,
    widgetView: Module.TwoColumnContainerWidgetView,
  });

  BeforeStartApp.registerLayoutWidget({
    name: 'threeColumnLayout',
    priority: 100,
    widgetView: Module.ThreeColumnContainerWidgetView,
  });

  BeforeStartApp.registerLayoutWidget({
    name: 'twoColumn12Layout',
    priority: 100,
    widgetView: Module.TwoColumn12ContainerWidgetView,
  });

  BeforeStartApp.registerLayoutWidget({
    name: 'twoColumn21Layout',
    priority: 100,
    widgetView: Module.TwoColumn21ContainerWidgetView,
  });
});

export default Module;
