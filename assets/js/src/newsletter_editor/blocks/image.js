/**
 * Image content block
 */
define([
    'newsletter_editor/App',
    'newsletter_editor/blocks/base',
    'underscore'
  ], function(App, BaseBlock, _) {

  "use strict";

  var Module = {},
      base = BaseBlock,
      ImageWidgetView;

  Module.ImageBlockModel = base.BlockModel.extend({
    defaults: function() {
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
    className: "mailpoet_block mailpoet_image_block mailpoet_droppable_block",
    getTemplate: function() { return templates.imageBlock; },
    onDragSubstituteBy: function() { return Module.ImageWidgetView; },
    templateContext: function() {
      return _.extend({
        imageMissingSrc: App.getConfig().get('urls.imageMissing')
      }, base.BlockView.prototype.templateContext.apply(this));
    },
    behaviors: _.extend({}, base.BlockView.prototype.behaviors, {
      ShowSettingsBehavior: {}
    }),
    onRender: function() {
      this.toolsView = new Module.ImageBlockToolsView({ model: this.model });
      this.showChildView('toolsRegion', this.toolsView);

      if (this.model.get('fullWidth')) {
        this.$el.addClass('mailpoet_full_image');
      } else {
        this.$el.removeClass('mailpoet_full_image');
      }
    }
  });

  Module.ImageBlockToolsView = base.BlockToolsView.extend({
    getSettingsView: function() { return Module.ImageBlockSettingsView; }
  });

  Module.ImageBlockSettingsView = base.BlockSettingsView.extend({
    onRender: function() {
      MailPoet.helpTooltip.show(document.getElementById('tooltip-designer-full-width'), {
        tooltipId: 'tooltip-editor-full-width',
        tooltip: MailPoet.I18n.t('helpTooltipDesignerFullWidth')
      });
      MailPoet.helpTooltip.show(document.getElementById('tooltip-designer-ideal-width'), {
        tooltipId: 'tooltip-editor-ideal-width',
        tooltip: MailPoet.I18n.t('helpTooltipDesignerIdealWidth')
      });
    },
    getTemplate: function() { return templates.imageBlockSettings; },
    events: function() {
      return {
        "input .mailpoet_field_image_link": _.partial(this.changeField, "link"),
        "input .mailpoet_field_image_address": 'changeAddress',
        "input .mailpoet_field_image_alt_text": _.partial(this.changeField, "alt"),
        "change .mailpoet_field_image_full_width": _.partial(this.changeBoolCheckboxField, "fullWidth"),
        "change .mailpoet_field_image_alignment": _.partial(this.changeField, "styles.block.textAlign"),
        "click .mailpoet_field_image_select_another_image": "showMediaManager",
        "click .mailpoet_done_editing": "close"
      };
    },
    initialize: function(options) {
      base.BlockSettingsView.prototype.initialize.apply(this, arguments);

      if (options.showImageManager) {
        this.showMediaManager();
      }
    },
    showMediaManager: function() {
      if (this._mediaManager) {
        this._mediaManager.resetSelections();
        this._mediaManager.open();
        return;
      }

      var MediaManager = wp.media.view.MediaFrame.Select.extend({

        initialize: function() {
          wp.media.view.MediaFrame.prototype.initialize.apply(this, arguments);

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

        resetSelections: function() {
          this.state().get('selection').reset();
        },

        createQuery: function(options) {
          var query = wp.media.query(options);
          return query;
        },

        createStates: function() {
          var options = this.options;

          // Add the default states.
          this.states.add([
            // Main states.
            new wp.media.controller.Library({
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

          if(wp.media.view.settings.post.featuredImageId) {
            this.states.add(new wp.media.controller.FeaturedImage());
          }
        },

        bindHandlers: function() {
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

          var handlers = {
              content: {
                'embed': 'embedContent',
                'edit-selection': 'editSelectionContent'
              },
              toolbar: {
                'main-insert': 'mainInsertToolbar'
              }
            };

          _.each(handlers, function(regionHandlers, region) {
            _.each(regionHandlers, function(callback, handler) {
              this.on(region + ':render:' + handler, this[callback], this);
            }, this);
          }, this);
        },

        uploadContent: function() {
          wp.media.view.MediaFrame.Select.prototype.uploadContent.apply(this, arguments);
          this.$el.addClass('hide-toolbar');
        },

        // Content
        embedContent: function() {
          var view = new wp.media.view.Embed({
            controller: this,
            model: this.state()
          }).render();

          this.content.set(view);
          view.url.focus();
        },

        editSelectionContent: function() {
          var state = this.state(),
            selection = state.get('selection'),
            view;

          view = new wp.media.view.AttachmentsBrowser({
            controller: this,
            collection: selection,
            selection: selection,
            model: state,
            sortable: true,
            search: false,
            dragInfo: true,

            AttachmentView: wp.media.view.Attachment.EditSelection
          }).render();

          view.toolbar.set('backToLibrary', {
            text: 'Return to library',
            priority: -100,

            click: function() {
              this.controller.content.mode('browse');
            }
          });

          // Browse our library of attachments.
          this.content.set(view);
        },

        // Toolbars
        selectionStatusToolbar: function(view) {
          var editable = this.state().get('editable');

          view.set('selection', new wp.media.view.Selection({
            controller: this,
            collection: this.state().get('selection'),
            priority: -40,

            // If the selection is editable, pass the callback to
            // switch the content mode.
            editable: editable && function() {
              this.controller.content.mode('edit-selection');
            }
          }).render() );
        },

        mainInsertToolbar: function(view) {
          var controller = this;

          this.selectionStatusToolbar(view);

          view.set('insert', {
            style: 'primary',
            priority: 80,
            text: 'Select Image',
            requires: { selection: true },

            click: function() {
              var state = controller.state(),
                selection = state.get('selection');

              controller.close();
              state.trigger('insert', selection).reset();
            }
          });
        },

        mainEmbedToolbar: function(toolbar) {
          toolbar.view = new wp.media.view.Toolbar.Embed({
            controller: this,
            text: 'Add images'
          });
        }

      });

      var theFrame = this._mediaManager = new MediaManager({
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
        }),
        that = this;

      this._mediaManager.on('insert', function() {
        // Append media manager image selections to Images tab
        var selection = theFrame.state().get('selection');
        selection.each(function(attachment) {
          var sizes = attachment.get('sizes'),
            // Following advice from Becs, the target width should
            // be a double of one column width to render well on
            // retina screen devices
            targetImageWidth = 1320,

            // For main image use the size, that's closest to being 660px in width
            sizeKeys = _.keys(sizes),

            // Pick the width that is closest to target width
            increasingByWidthDifference = _.sortBy(
              _.keys(sizes),
              function(size) { return Math.abs(targetImageWidth - sizes[size].width); }
            ),
            bestWidth = sizes[_.first(increasingByWidthDifference)].width,
            imagesOfBestWidth = _.filter(_.values(sizes), function(size) { return size.width === bestWidth; }),

            // Maximize the height if there are multiple images with same width
            mainSize = _.max(imagesOfBestWidth, function(size) { return size.height; });

          that.model.set({
            height: mainSize.height + 'px',
            width: mainSize.width + 'px',
            src: mainSize.url,
            alt: (attachment.get('alt') !== "" && attachment.get('alt') !== undefined) ? attachment.get('alt') : attachment.get('title')
          });
          // Rerender settings view due to changes from outside of settings view
          that.render();
        });
      });

      this._mediaManager.open();
    },
    changeAddress: function(event) {
      var src = jQuery(event.target).val();
      var image = new Image();

      image.onload = function() {
        this.model.set({
          src: src,
          width: image.naturalWidth + 'px',
          height: image.naturalHeight + 'px'
        });
      }.bind(this);

      image.src = src;
    },
    onBeforeDestroy: function() {
      base.BlockSettingsView.prototype.onBeforeDestroy.apply(this, arguments);
      if (typeof this._mediaManager === 'object') {
        this._mediaManager.remove();
      }
    }
  });

  ImageWidgetView = base.WidgetView.extend({
    getTemplate: function() { return templates.imageInsertion; },
    behaviors: {
      DraggableBehavior: {
        cloneOriginal: true,
        drop: function() {
          return new Module.ImageBlockModel();
        },
        onDrop: function(options) {
          options.droppedView.triggerMethod('showSettings', { showImageManager: true });
        }
      }
    }
  });
  Module.ImageWidgetView = ImageWidgetView;

  App.on('before:start', function(App, options) {
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
