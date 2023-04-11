/* eslint-disable func-names */
/**
 * Media manager behaviour
 *
 * Adds a media manager integration with the view
 */
import Marionette from 'backbone.marionette';
import _ from 'underscore';
import jQuery from 'jquery';
import { BehaviorsLookup } from 'newsletter_editor/behaviors/BehaviorsLookup';

var BL = BehaviorsLookup;
var DELAY_REFRESH_FOR_MS = 500;

BL.MediaManagerBehavior = Marionette.Behavior.extend({
  ui: {
    'select-image': '.mailpoet_field_image_select_image',
    'address-input': '.mailpoet_field_image_address',
  },
  events: {
    'click @ui.select-image': 'showMediaManager',
    'input @ui.address-input': 'changeAddress',
  },
  initialize: function () {
    if (this.view.options.showImageManager) {
      this.showMediaManager();
    }
  },
  changeAddress: _.debounce(function (event) {
    var src = jQuery(event.target).val();
    var image = new Image();

    if (!src && this.options.onSelect) {
      this.view[this.options.onSelect]({
        src: null,
        width: null,
        height: null,
      });
      return;
    }

    image.onload = function () {
      if (this.options.onSelect) {
        this.view[this.options.onSelect]({
          src: src,
          width: image.naturalWidth + 'px',
          height: image.naturalHeight + 'px',
        });
      }
    }.bind(this);

    image.src = src;
  }, DELAY_REFRESH_FOR_MS),
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
        window.wp.media.view.MediaFrame.prototype.initialize.apply(
          this,
          arguments,
        );

        _.defaults(this.options, {
          multiple: true,
          editing: false,
          state: 'insert',
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
            displayUserSettings: false,
          }),
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
            'edit-selection': 'editSelectionContent',
          },
          toolbar: {
            'main-insert': 'mainInsertToolbar',
          },
        };

        _.each(
          handlers,
          function (regionHandlers, region) {
            _.each(
              regionHandlers,
              function (callback, handler) {
                this.on(region + ':render:' + handler, this[callback], this);
              },
              this,
            );
          },
          this,
        );
      },

      uploadContent: function () {
        window.wp.media.view.MediaFrame.Select.prototype.uploadContent.apply(
          this,
          arguments,
        );
        this.$el.addClass('hide-toolbar');
      },

      // Content
      embedContent: function () {
        var view = new window.wp.media.view.Embed({
          controller: this,
          model: this.state(),
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

          AttachmentView: window.wp.media.view.Attachment.EditSelection,
        }).render();

        view.toolbar.set('backToLibrary', {
          text: 'Return to library',
          priority: -100,

          click: function () {
            this.controller.content.mode('browse');
          },
        });

        // Browse our library of attachments.
        this.content.set(view);
      },

      // Toolbars
      selectionStatusToolbar: function (view) {
        var editable = this.state().get('editable');

        view.set(
          'selection',
          new window.wp.media.view.Selection({
            controller: this,
            collection: this.state().get('selection'),
            priority: -40,

            // If the selection is editable, pass the callback to
            // switch the content mode.
            editable:
              editable &&
              function () {
                this.controller.content.mode('edit-selection');
              },
          }).render(),
        );
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
          },
        });
      },

      mainEmbedToolbar: function (toolbar) {
        var tbar = toolbar;
        tbar.view = new window.wp.media.view.Toolbar.Embed({
          controller: this,
          text: 'Add images',
        });
      },
    });

    theFrame = new MediaManager({
      id: 'mailpoet-media-manager',
      frame: 'select',
      title: 'Select image',
      editing: false,
      multiple: false,
      library: {
        type: 'image',
      },
      displaySettings: false,
      button: {
        text: 'Select',
      },
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
          function (size) {
            return Math.abs(targetImageWidth - sizes[size].width);
          },
        );
        var bestWidth = sizes[_.first(increasingByWidthDifference)].width;
        var imagesOfBestWidth = _.filter(_.values(sizes), function (size) {
          return size.width === bestWidth;
        });

        // Maximize the height if there are multiple images with same width
        var mainSize = _.max(imagesOfBestWidth, function (size) {
          return size.height;
        });

        if (that.options.onSelect) {
          that.view[that.options.onSelect]({
            height: mainSize.height + 'px',
            width: mainSize.width + 'px',
            src: mainSize.url,
            alt:
              attachment.get('alt') !== undefined ? attachment.get('alt') : '',
          });
        }
      });
    });
    this._mediaManager.open();
  },
  onBeforeDestroy: function () {
    if (typeof this._mediaManager === 'object') {
      this._mediaManager.remove();
    }
  },
});
