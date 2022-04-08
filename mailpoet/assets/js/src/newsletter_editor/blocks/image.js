/* eslint-disable func-names */
/**
 * Image content block
 */
import App from 'newsletter_editor/App';
import BaseBlock from 'newsletter_editor/blocks/base';
import _ from 'underscore';
import MailPoet from 'mailpoet';
import jQuery from 'jquery';

var Module = {};
var base = BaseBlock;
var ImageWidgetView;

Module.ImageBlockModel = base.BlockModel.extend({
  defaults: function () {
    return this._getDefaults(
      {
        type: 'image',
        link: '',
        src: '',
        alt: 'An image of...',
        fullWidth: true, // true | false
        width: '64px',
        height: '64px',
        styles: {
          block: {
            textAlign: 'center',
          },
        },
      },
      App.getConfig().get('blockDefaults.image'),
    );
  },
  _updateDefaults: function () {},
});

Module.ImageBlockView = base.BlockView.extend({
  className: 'mailpoet_block mailpoet_image_block mailpoet_droppable_block',
  getTemplate: function () {
    return window.templates.imageBlock;
  },
  onDragSubstituteBy: function () {
    return Module.ImageWidgetView;
  },
  templateContext: function () {
    return _.extend(
      {
        imageMissingSrc: App.getConfig().get('urls.imageMissing'),
      },
      base.BlockView.prototype.templateContext.apply(this),
    );
  },
  behaviors: _.extend({}, base.BlockView.prototype.behaviors, {
    ResizableBehavior: {
      elementSelector: '.mailpoet_image',
      resizeHandleSelector: '.mailpoet_image_resize_handle',
      onResize: function (event) {
        var alignment = this.view.model.get('styles.block.textAlign');
        var corner = this.$('.mailpoet_image').offset();
        var currentWidth = this.$('.mailpoet_image').width();
        var newWidth = event.pageX - corner.left;
        if (alignment === 'right') {
          newWidth = currentWidth + corner.left - event.pageX;
        }
        this.view.model.set('width', newWidth + 'px');
      },
    },
    ShowSettingsBehavior: {
      ignoreFrom: '.mailpoet_image_resize_handle',
    },
  }),
  onRender: function () {
    var that = this;
    this.toolsView = new Module.ImageBlockToolsView({ model: this.model });
    this.showChildView('toolsRegion', this.toolsView);
    if (this.model.get('fullWidth')) {
      this.$el.addClass('mailpoet_full_image');
    } else {
      this.$el.removeClass('mailpoet_full_image');
    }
    // Ensure size values for images unknown size data (e.g. added via Gutenberg edited post/page)
    if (!this.model.get('width') || this.model.get('width') === 'auto') {
      this.$el.find('img').on('load', function () {
        that.model.set('width', this.width);
        that.model.set('height', this.height);
      });
    }
    this.$('.mailpoet_content').css('width', this.model.get('width'));
  },
});

Module.ImageBlockToolsView = base.BlockToolsView.extend({
  getSettingsView: function () {
    return Module.ImageBlockSettingsView;
  },
});

Module.ImageBlockSettingsView = base.BlockSettingsView.extend({
  behaviors: _.extend({}, base.BlockSettingsView.prototype.behaviors, {
    MediaManagerBehavior: {
      onSelect: 'onImageSelect',
    },
  }),
  onRender: function () {
    MailPoet.helpTooltip.show(
      document.getElementById('tooltip-designer-full-width'),
      {
        tooltipId: 'tooltip-editor-full-width',
        tooltip: MailPoet.I18n.t('helpTooltipDesignerFullWidth'),
        place: 'top',
      },
    );
    MailPoet.helpTooltip.show(
      document.getElementById('tooltip-designer-ideal-width'),
      {
        tooltipId: 'tooltip-editor-ideal-width',
        tooltip: MailPoet.I18n.t('helpTooltipDesignerIdealWidth'),
        place: 'bottom',
      },
    );
  },
  getTemplate: function () {
    return window.templates.imageBlockSettings;
  },
  events: function () {
    return {
      'input .mailpoet_field_image_link': _.partial(this.changeField, 'link'),
      'input .mailpoet_field_image_alt_text': _.partial(
        this.changeField,
        'alt',
      ),
      'change .mailpoet_field_image_full_width': _.partial(
        this.changeBoolCheckboxField,
        'fullWidth',
      ),
      'change .mailpoet_field_image_alignment': _.partial(
        this.changeField,
        'styles.block.textAlign',
      ),
      'click .mailpoet_done_editing': 'close',
      'input .mailpoet_field_image_width': _.partial(
        this.updateValueAndCall,
        '.mailpoet_field_image_width_input',
        _.partial(this.changePixelField, 'width').bind(this),
      ),
      'change .mailpoet_field_image_width': _.partial(
        this.updateValueAndCall,
        '.mailpoet_field_image_width_input',
        _.partial(this.changePixelField, 'width').bind(this),
      ),
      'input .mailpoet_field_image_width_input': _.partial(
        this.updateValueAndCall,
        '.mailpoet_field_image_width',
        _.partial(this.changePixelField, 'width').bind(this),
      ),
    };
  },
  modelEvents: function () {
    return {
      'change:maxWidth': 'updateMaxWidth',
      'change:width': 'updateWidth',
    };
  },
  updateValueAndCall: function (fieldToUpdate, callable, event) {
    this.$(fieldToUpdate).val(jQuery(event.target).val());
    callable(event);
  },
  updateMaxWidth: function () {
    var maxWidth = parseInt(this.model.get('maxWidth'), 10);
    this.$('.mailpoet_field_image_width').attr('max', maxWidth);
    this.$('.mailpoet_field_image_width_input').attr('max', maxWidth);
  },
  updateWidth: function () {
    var width = parseInt(this.model.get('width'), 10);
    this.$('.mailpoet_field_image_width').val(width);
    this.$('.mailpoet_field_image_width_input').val(width);
  },
  onImageSelect: function (image) {
    if (image.src === null) {
      this.model.set({ src: '' });
    } else {
      this.model.set(image);
    }
    // Rerender settings view due to changes from outside of settings view
    this.render();
  },
});

ImageWidgetView = base.WidgetView.extend({
  id: 'automation_editor_block_image',
  getTemplate: function () {
    return window.templates.imageInsertion;
  },
  behaviors: {
    DraggableBehavior: {
      cloneOriginal: true,
      drop: function () {
        return new Module.ImageBlockModel();
      },
      onDrop: function (options) {
        options.droppedView.triggerMethod('showSettings', {
          showImageManager: true,
        });
      },
    },
  },
});
Module.ImageWidgetView = ImageWidgetView;

App.on('before:start', function (BeforeStartApp) {
  BeforeStartApp.registerBlockType('image', {
    blockModel: Module.ImageBlockModel,
    blockView: Module.ImageBlockView,
  });

  BeforeStartApp.registerWidget({
    name: 'image',
    widgetView: Module.ImageWidgetView,
    priority: 91,
  });
});

export default Module;
