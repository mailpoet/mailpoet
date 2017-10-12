/**
 * Image content block
 */
define([
  'newsletter_editor/App',
  'newsletter_editor/blocks/base',
  'underscore',
  'mailpoet',
  'jquery'
], function (App, BaseBlock, _, MailPoet, jQuery) {

  'use strict';

  var Module = {};
  var base = BaseBlock;
  var ImageWidgetView;

  Module.ImageBlockModel = base.BlockModel.extend({
    defaults: function () {
      return this._getDefaults({
        type: 'image',
        link: '',
        src: '',
        alt: 'An image of...',
        fullWidth: true, // true | false
        width: '64px',
        height: '64px',
        styles: {
          block: {
            textAlign: 'center'
          }
        }
      }, App.getConfig().get('blockDefaults.image'));
    }
  });

  Module.ImageBlockView = base.BlockView.extend({
    className: 'mailpoet_block mailpoet_image_block mailpoet_droppable_block',
    getTemplate: function () { return window.templates.imageBlock; },
    onDragSubstituteBy: function () { return Module.ImageWidgetView; },
    templateContext: function () {
      return _.extend({
        imageMissingSrc: App.getConfig().get('urls.imageMissing')
      }, base.BlockView.prototype.templateContext.apply(this));
    },
    behaviors: _.extend({}, base.BlockView.prototype.behaviors, {
      ResizableBehavior: {
        elementSelector: '.mailpoet_image',
        resizeHandleSelector: '.mailpoet_image_resize_handle',
        onResize: function (event) {
          var corner = this.$('.mailpoet_image').offset();
          var width = event.pageX - corner.left;
          this.view.model.set('width', width + 'px');
        }
      },
      ShowSettingsBehavior: {
        ignoreFrom: '.mailpoet_image_resize_handle'
      }
    }),
    normalizeSrc: function () {
      var src = this.model.get('src');
      if (src.startsWith('/')) { // if the link is relative
        src = window.location.href
          .split('/')
          .slice(0, 3)
          .join('/') + src;
        this.model.set('src', src);
      }
    },
    onRender: function () {
      this.normalizeSrc();
      this.toolsView = new Module.ImageBlockToolsView({ model: this.model });
      this.showChildView('toolsRegion', this.toolsView);
      if (this.model.get('fullWidth')) {
        this.$el.addClass('mailpoet_full_image');
      } else {
        this.$el.removeClass('mailpoet_full_image');
      }
      this.$('.mailpoet_content').css('width', this.model.get('width'));
    }
  });

  Module.ImageBlockToolsView = base.BlockToolsView.extend({
    getSettingsView: function () { return Module.ImageBlockSettingsView; }
  });

  Module.ImageBlockSettingsView = base.BlockSettingsView.extend({
    onRender: function () {
      MailPoet.helpTooltip.show(document.getElementById('tooltip-designer-full-width'), {
        tooltipId: 'tooltip-editor-full-width',
        tooltip: MailPoet.I18n.t('helpTooltipDesignerFullWidth')
      });
      MailPoet.helpTooltip.show(document.getElementById('tooltip-designer-ideal-width'), {
        tooltipId: 'tooltip-editor-ideal-width',
        tooltip: MailPoet.I18n.t('helpTooltipDesignerIdealWidth')
      });
    },
    getTemplate: function () { return window.templates.imageBlockSettings; },
    events: function () {
      return {
        'input .mailpoet_field_image_link': _.partial(this.changeField, 'link'),
        'input .mailpoet_field_image_address': 'changeAddress',
        'input .mailpoet_field_image_alt_text': _.partial(this.changeField, 'alt'),
        'change .mailpoet_field_image_full_width': _.partial(this.changeBoolCheckboxField, 'fullWidth'),
        'change .mailpoet_field_image_alignment': _.partial(this.changeField, 'styles.block.textAlign'),
        'click .mailpoet_field_image_select_another_image': 'showMediaManager',
        'click .mailpoet_done_editing': 'close',
        'input .mailpoet_field_image_width': _.partial(this.updateValueAndCall, '.mailpoet_field_image_width_input', _.partial(this.changePixelField, 'width').bind(this)),
        'change .mailpoet_field_image_width': _.partial(this.updateValueAndCall, '.mailpoet_field_image_width_input', _.partial(this.changePixelField, 'width').bind(this)),
        'input .mailpoet_field_image_width_input': _.partial(this.updateValueAndCall, '.mailpoet_field_image_width', _.partial(this.changePixelField, 'width').bind(this))
      };
    },
    modelEvents: function () {
      return {
        'change:maxWidth': 'updateMaxWidth',
        'change:width': 'updateWidth'
      };
    },
    updateValueAndCall: function (fieldToUpdate, callable, event) {
      this.$(fieldToUpdate).val(jQuery(event.target).val());
      callable(event);
    },
    updateMaxWidth: function () {
      var maxWidth = parseInt(this.model.get('maxWidth'));
      this.$('.mailpoet_field_image_width').attr('max', maxWidth);
      this.$('.mailpoet_field_image_width_input').attr('max', maxWidth);
    },
    updateWidth: function () {
      var width = parseInt(this.model.get('width'));
      this.$('.mailpoet_field_image_width').val(width);
      this.$('.mailpoet_field_image_width_input').val(width);
    },
    initialize: function (options) {
      base.BlockSettingsView.prototype.initialize.apply(this, arguments);

      if (options.showImageManager) {
        this.showMediaManager();
      }
    },
    showMediaManager: function () {
      var that = this;
      var MediaManager;
      var theFrame;
      if (this._mediaManager) {
        this._mediaManager.resetSelections();
        this._mediaManager.open();
        return;
      }

      MediaManager = window.wp.media.view.MediaFrame.Select.extend({

        initialize: function () {
          window.wp.media.view.MediaFrame.prototype.initialize.apply(this, arguments);

          _.defaults(this.options, {
            multiple: true,
            editing: false,
            state: 'insert'
          });

          this.createSelection();
          this.createStates();
          this.bindHandlers();
          this.createIframeStates();

          // Hide title
          this.$el.addClass('hide-title');
        },

        resetSelections: function () {
          this.state().get('selection').reset();
        },

        createQuery: function (options) {
          var query = window.wp.media.query(options);
          return query;
        },

        createStates: function () {
          var options = this.options;

          // Add the default states.
          this.states.add([
            // Main states.
            new window.wp.media.controller.Library({
              id: 'insert',
              title: 'Add images',
              priority: 20,
              toolbar: 'main-insert',
              filterable: 'image',
              library: this.createQuery(options.library),
              multiple: options.multiple ? 'reset' : false,
              editable: false,

              // If the user isn't allowed to edit fields,
              // can they still edit it locally?
              allowLocalEdits: false,

              // Show the attachment display settings.
              displaySettings: false,
              // Update user settings when users adjust the
              // attachment display settings.
              displayUserSettings: false
            })
          ]);

          if (window.wp.media.view.settings.post.featuredImageId) {
            this.states.add(new window.wp.media.controller.FeaturedImage());
          }
        },

        bindHandlers: function () {
          var handlers;
          // from Select
          this.on('router:create:browse', this.createRouter, this);
          this.on('router:render:browse', this.browseRouter, this);
          this.on('content:create:browse', this.browseContent, this);
          this.on('content:render:upload', this.uploadContent, this);
          this.on('toolbar:create:select', this.createSelectToolbar, this);

          this.on('menu:create:gallery', this.createMenu, this);
          this.on('toolbar:create:main-insert', this.createToolbar, this);
          this.on('toolbar:create:main-gallery', this.createToolbar, this);
          this.on('toolbar:create:main-embed', this.mainEmbedToolbar, this);

          this.on('updateExcluded', this.browseContent, this);

          handlers = {
            content: {
              embed: 'embedContent',
              'edit-selection': 'editSelectionContent'
            },
            toolbar: {
              'main-insert': 'mainInsertToolbar'
            }
          };

          _.each(handlers, function (regionHandlers, region) {
            _.each(regionHandlers, function (callback, handler) {
              this.on(region + ':render:' + handler, this[callback], this);
            }, this);
          }, this);
        },

        uploadContent: function () {
          window.wp.media.view.MediaFrame.Select.prototype.uploadContent.apply(this, arguments);
          this.$el.addClass('hide-toolbar');
        },

        // Content
        embedContent: function () {
          var view = new window.wp.media.view.Embed({
            controller: this,
            model: this.state()
          }).render();

          this.content.set(view);
          view.url.focus();
        },

        editSelectionContent: function () {
          var state = this.state();
          var selection = state.get('selection');
          var view;

          view = new window.wp.media.view.AttachmentsBrowser({
            controller: this,
            collection: selection,
            selection: selection,
            model: state,
            sortable: true,
            search: false,
            dragInfo: true,

            AttachmentView: window.wp.media.view.Attachment.EditSelection
          }).render();

          view.toolbar.set('backToLibrary', {
            text: 'Return to library',
            priority: -100,

            click: function () {
              this.controller.content.mode('browse');
            }
          });

          // Browse our library of attachments.
          this.content.set(view);
        },

        // Toolbars
        selectionStatusToolbar: function (view) {
          var editable = this.state().get('editable');

          view.set('selection', new window.wp.media.view.Selection({
            controller: this,
            collection: this.state().get('selection'),
            priority: -40,

            // If the selection is editable, pass the callback to
            // switch the content mode.
            editable: editable && function () {
              this.controller.content.mode('edit-selection');
            }
          }).render());
        },

        mainInsertToolbar: function (view) {
          var controller = this;

          this.selectionStatusToolbar(view);

          view.set('insert', {
            style: 'primary',
            priority: 80,
            text: 'Select Image',
            requires: { selection: true },

            click: function () {
              var state = controller.state();
              var selection = state.get('selection');

              controller.close();
              state.trigger('insert', selection).reset();
            }
          });
        },

        mainEmbedToolbar: function (toolbar) {
          var tbar = toolbar;
          tbar.view = new window.wp.media.view.Toolbar.Embed({
            controller: this,
            text: 'Add images'
          });
        }

      });

      theFrame = new MediaManager({
        id: 'mailpoet-media-manager',
        frame: 'select',
        title: 'Select image',
        editing: false,
        multiple: false,
        library: {
          type: 'image'
        },
        displaySettings: false,
        button: {
          text: 'Select'
        }
      });
      this._mediaManager = theFrame;

      this._mediaManager.on('insert', function () {
        // Append media manager image selections to Images tab
        var selection = theFrame.state().get('selection');
        selection.each(function (attachment) {
          var sizes = attachment.get('sizes');
          // Following advice from Becs, the target width should
          // be a double of one column width to render well on
          // retina screen devices
          var targetImageWidth = 1320;

          // Pick the width that is closest to target width
          var increasingByWidthDifference = _.sortBy(
            _.keys(sizes),
            function (size) { return Math.abs(targetImageWidth - sizes[size].width); }
          );
          var bestWidth = sizes[_.first(increasingByWidthDifference)].width;
          var imagesOfBestWidth = _.filter(_.values(sizes), function (size) { return size.width === bestWidth; });

          // Maximize the height if there are multiple images with same width
          var mainSize = _.max(imagesOfBestWidth, function (size) { return size.height; });

          that.model.set({
            height: mainSize.height + 'px',
            width: mainSize.width + 'px',
            src: mainSize.url,
            alt: (attachment.get('alt') !== '' && attachment.get('alt') !== undefined) ? attachment.get('alt') : attachment.get('title')
          });
          // Rerender settings view due to changes from outside of settings view
          that.render();
        });
      });

      this._mediaManager.open();
    },
    changeAddress: function (event) {
      var src = jQuery(event.target).val();
      var image = new Image();

      image.onload = function () {
        this.model.set({
          src: src,
          width: image.naturalWidth + 'px',
          height: image.naturalHeight + 'px'
        });
      }.bind(this);

      image.src = src;
    },
    onBeforeDestroy: function () {
      base.BlockSettingsView.prototype.onBeforeDestroy.apply(this, arguments);
      if (typeof this._mediaManager === 'object') {
        this._mediaManager.remove();
      }
    }
  });

  ImageWidgetView = base.WidgetView.extend({
    getTemplate: function () { return window.templates.imageInsertion; },
    behaviors: {
      DraggableBehavior: {
        cloneOriginal: true,
        drop: function () {
          return new Module.ImageBlockModel();
        },
        onDrop: function (options) {
          options.droppedView.triggerMethod('showSettings', { showImageManager: true });
        }
      }
    }
  });
  Module.ImageWidgetView = ImageWidgetView;

  App.on('before:start', function (App) {
    App.registerBlockType('image', {
      blockModel: Module.ImageBlockModel,
      blockView: Module.ImageBlockView
    });

    App.registerWidget({
      name: 'image',
      widgetView: Module.ImageWidgetView,
      priority: 91
    });
  });

  return Module;
});
