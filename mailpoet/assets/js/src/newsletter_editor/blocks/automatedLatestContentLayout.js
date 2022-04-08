/* eslint-disable func-names */
/**
 * Automated latest content block with image alignment.
 */
import App from 'newsletter_editor/App';
import BaseBlock from 'newsletter_editor/blocks/base';
import ButtonBlock from 'newsletter_editor/blocks/button';
import DividerBlock from 'newsletter_editor/blocks/divider';
import CommunicationComponent from 'newsletter_editor/components/communication';
import MailPoet from 'mailpoet';
import SuperModel from 'backbone.supermodel';
import _ from 'underscore';
import jQuery from 'jquery';

var Module = {};
var base = BaseBlock;

Module.ALCLayoutSupervisor = SuperModel.extend({
  initialize: function () {
    var DELAY_REFRESH_FOR_MS = 500;
    this.listenTo(
      App.getChannel(),
      'automatedLatestContentLayoutRefresh',
      _.debounce(this.refresh, DELAY_REFRESH_FOR_MS),
    );
  },
  refresh: function () {
    var blocks;
    var models =
      App.findModels(function (model) {
        return model.get('type') === 'automatedLatestContentLayout';
      }) || [];

    if (models.length === 0) return;
    blocks = _.map(models, function (model) {
      return model.toJSON();
    });

    CommunicationComponent.getBulkTransformedPosts({
      blocks: blocks,
    }).then(_.partial(this.refreshBlocks, models));
  },
  refreshBlocks: function (models, renderedBlocks) {
    _.each(_.zip(models, renderedBlocks), function (args) {
      var model = args[0];
      var contents = args[1];
      model.trigger('refreshPosts', contents);
    });
  },
});

Module.AutomatedLatestContentLayoutBlockModel = base.BlockModel.extend({
  stale: ['_container', '_displayOptionsHidden', '_featuredImagePosition'],
  defaults: function () {
    return this._getDefaults(
      {
        type: 'automatedLatestContentLayout',
        withLayout: true,
        amount: '5',
        contentType: 'post', // 'post'|'page'|'mailpoet_page'
        terms: [], // List of category and tag objects
        inclusionType: 'include', // 'include'|'exclude'
        displayType: 'excerpt', // 'excerpt'|'full'|'titleOnly'
        titleFormat: 'h1', // 'h1'|'h2'|'h3'|'ul'
        titleAlignment: 'left', // 'left'|'center'|'right'
        titleIsLink: false, // false|true
        imageFullWidth: false, // true|false
        titlePosition: 'abovePost', // 'abovePost'|'aboveExcerpt'
        featuredImagePosition: 'centered', // 'centered'|'left'|'right'|'alternate'|'none'
        fullPostFeaturedImagePosition: 'none', // 'centered'|'left'|'right'|'alternate'|'none'
        showAuthor: 'no', // 'no'|'aboveText'|'belowText'
        authorPrecededBy: 'Author:',
        showCategories: 'no', // 'no'|'aboveText'|'belowText'
        categoriesPrecededBy: 'Categories:',
        readMoreType: 'button', // 'link'|'button'
        readMoreText: 'Read more', // 'link'|'button'
        readMoreButton: {
          text: 'Read more',
          url: '[postLink]',
        },
        sortBy: 'newest', // 'newest'|'oldest',
        showDivider: true, // true|false
        divider: {},
        _container: new (App.getBlockTypeModel('container'))(),
        _displayOptionsHidden: true, // true|false
        _featuredImagePosition: 'none', // 'centered'|'left'|'right'|'alternate'|'none'
      },
      App.getConfig().get('blockDefaults.automatedLatestContentLayout'),
    );
  },
  relations: function () {
    return {
      readMoreButton: App.getBlockTypeModel('button'),
      divider: App.getBlockTypeModel('divider'),
      _container: App.getBlockTypeModel('container'),
    };
  },
  initialize: function (block) {
    // when added as new block, set default for full post featured image position to 'left'
    if (_.isEmpty(block)) {
      this.set('fullPostFeaturedImagePosition', 'left');
    }

    // For products with display type 'full' prefill 'fullPostFeaturedImagePosition' from existing
    // 'featuredImagePosition'. Products always supported images, even for 'full' display type.
    const isProductWithDisplayTypeFull =
      block && block.displayType === 'full' && block.contentType === 'product';
    if (
      isProductWithDisplayTypeFull &&
      !this.get('fullPostFeaturedImagePosition')
    ) {
      this.set(
        'fullPostFeaturedImagePosition',
        this.get('featuredImagePosition'),
      );
    }

    base.BlockView.prototype.initialize.apply(this, arguments);
    this.on(
      'change:amount change:contentType change:terms change:inclusionType change:displayType change:titleFormat change:featuredImagePosition change:fullPostFeaturedImagePosition change:titleAlignment change:titleIsLink change:imageFullWidth change:showAuthor change:authorPrecededBy change:showCategories change:categoriesPrecededBy change:readMoreType change:readMoreText change:sortBy change:showDivider change:titlePosition',
      this._handleChanges,
      this,
    );
    this.listenTo(this.get('readMoreButton'), 'change', this._handleChanges);
    this.listenTo(this.get('divider'), 'change', this._handleChanges);
    this.on('add remove update reset', this._handleChanges);
    this.on('refreshPosts', this.updatePosts, this);

    const field =
      this.get('displayType') === 'full'
        ? 'fullPostFeaturedImagePosition'
        : 'featuredImagePosition';
    this.set('_featuredImagePosition', this.get(field));
  },
  updatePosts: function (posts) {
    this.get('_container.blocks').reset(posts, { parse: true });
  },
  /**
   * Batch more changes during a specific time, instead of fetching
   * ALC posts on each model change
   */
  _handleChanges: function () {
    this._updateDefaults();
    App.getChannel().trigger('automatedLatestContentLayoutRefresh');
  },
});

Module.AutomatedLatestContentLayoutBlockView = base.BlockView.extend({
  className:
    'mailpoet_block mailpoet_automated_latest_content_block mailpoet_droppable_block',
  initialize: function () {
    function replaceButtonStylesHandler(data) {
      this.model.set({ readMoreButton: data });
    }
    App.getChannel().on(
      'replaceAllButtonStyles',
      replaceButtonStylesHandler.bind(this),
    );
  },
  getTemplate: function () {
    return window.templates.automatedLatestContentLayoutBlock;
  },
  regions: {
    toolsRegion: '.mailpoet_tools',
    postsRegion: '.mailpoet_automated_latest_content_block_posts',
  },
  modelEvents: _.extend(
    _.omit(base.BlockView.prototype.modelEvents, 'change'),
    {
      postsChanged: 'render',
    },
  ),
  events: {
    'click .mailpoet_automated_latest_content_block_overlay': 'showSettings',
  },
  onDragSubstituteBy: function () {
    return Module.AutomatedLatestContentLayoutWidgetView;
  },
  onRender: function () {
    var ContainerView = App.getBlockTypeView('container');
    var renderOptions = {
      disableTextEditor: true,
      disableDragAndDrop: true,
      emptyContainerMessage: MailPoet.I18n.t('noPostsToDisplay'),
    };
    this.toolsView = new Module.AutomatedLatestContentLayoutBlockToolsView({
      model: this.model,
    });
    this.showChildView('toolsRegion', this.toolsView);
    this.showChildView(
      'postsRegion',
      new ContainerView({
        model: this.model.get('_container'),
        renderOptions: renderOptions,
      }),
    );
  },
  duplicateBlock: function duplicateBlock() {
    var originalData = this.model.toJSON();
    var newModel = new Module.AutomatedLatestContentLayoutBlockModel(
      originalData,
    );
    this.model.collection.add(newModel, {
      at: this.model.collection.findIndex(this.model),
    });
  },
});

Module.AutomatedLatestContentLayoutBlockToolsView = base.BlockToolsView.extend({
  getSettingsView: function () {
    return Module.AutomatedLatestContentLayoutBlockSettingsView;
  },
});

// Sidebar view container
Module.AutomatedLatestContentLayoutBlockSettingsView =
  base.BlockSettingsView.extend({
    getTemplate: function () {
      return window.templates.automatedLatestContentLayoutBlockSettings;
    },
    events: function () {
      return {
        'click .mailpoet_automated_latest_content_hide_display_options':
          'toggleDisplayOptions',
        'click .mailpoet_automated_latest_content_show_display_options':
          'toggleDisplayOptions',
        'click .mailpoet_automated_latest_content_select_button':
          'showButtonSettings',
        'click .mailpoet_automated_latest_content_select_divider':
          'showDividerSettings',
        'change .mailpoet_automated_latest_content_read_more_type':
          'changeReadMoreType',
        'change .mailpoet_automated_latest_content_display_type':
          'changeDisplayType',
        'change .mailpoet_automated_latest_content_title_format':
          'changeTitleFormat',
        'change .mailpoet_automated_latest_content_title_as_links': _.partial(
          this.changeBoolField,
          'titleIsLink',
        ),
        'change .mailpoet_automated_latest_content_show_divider': _.partial(
          this.changeBoolField,
          'showDivider',
        ),
        'input .mailpoet_automated_latest_content_show_amount': _.partial(
          this.changeField,
          'amount',
        ),
        'change .mailpoet_automated_latest_content_content_type': _.partial(
          this.changeField,
          'contentType',
        ),
        'change .mailpoet_automated_latest_content_include_or_exclude':
          _.partial(this.changeField, 'inclusionType'),
        'change .mailpoet_automated_latest_content_title_alignment': _.partial(
          this.changeField,
          'titleAlignment',
        ),
        'change .mailpoet_automated_latest_content_image_full_width': _.partial(
          this.changeBoolField,
          'imageFullWidth',
        ),
        'change .mailpoet_automated_latest_content_featured_image_position':
          'changeFeaturedImagePosition',
        'change .mailpoet_automated_latest_content_show_author': _.partial(
          this.changeField,
          'showAuthor',
        ),
        'input .mailpoet_automated_latest_content_author_preceded_by':
          _.partial(this.changeField, 'authorPrecededBy'),
        'change .mailpoet_automated_latest_content_show_categories': _.partial(
          this.changeField,
          'showCategories',
        ),
        'input .mailpoet_automated_latest_content_categories': _.partial(
          this.changeField,
          'categoriesPrecededBy',
        ),
        'input .mailpoet_automated_latest_content_read_more_text': _.partial(
          this.changeField,
          'readMoreText',
        ),
        'change .mailpoet_automated_latest_content_sort_by': _.partial(
          this.changeField,
          'sortBy',
        ),
        'change .mailpoet_automated_latest_content_title_position': _.partial(
          this.changeField,
          'titlePosition',
        ),
        'click .mailpoet_done_editing': 'close',
      };
    },
    onRender: function () {
      var that = this;

      // Dynamically update available post types
      CommunicationComponent.getPostTypes().done(
        _.bind(this._updateContentTypes, this),
      );

      this.$('.mailpoet_automated_latest_content_categories_and_tags')
        .select2({
          multiple: true,
          allowClear: true,
          placeholder: MailPoet.I18n.t('categoriesAndTags'),
          ajax: {
            data: function (params) {
              return {
                term: params.term,
                page: params.page || 1,
              };
            },
            transport: function (options, success, failure) {
              var taxonomies;
              var termsPromise;
              var promise = CommunicationComponent.getTaxonomies(
                that.model.get('contentType'),
              ).then(function (tax) {
                taxonomies = tax;
                // Fetch available terms based on the list of taxonomies already fetched
                termsPromise = CommunicationComponent.getTerms({
                  search: options.data.term,
                  page: options.data.page,
                  taxonomies: _.keys(taxonomies),
                }).then(function (terms) {
                  return {
                    taxonomies: taxonomies,
                    terms: terms,
                  };
                });
                return termsPromise;
              });

              promise.then(success);
              promise.fail(failure);
              return promise;
            },
            processResults: function (data) {
              // Transform taxonomies and terms into select2 compatible format
              return {
                results: _.map(data.terms, function (item) {
                  return _.defaults(
                    {
                      text:
                        data.taxonomies[item.taxonomy].labels.singular_name +
                        ': ' +
                        item.name,
                      id: item.term_id,
                    },
                    item,
                  );
                }),
                pagination: {
                  more: data.terms.length === 100,
                },
              };
            },
          },
        })
        .on({
          'select2:select': function (event) {
            var terms = that.model.get('terms');
            terms.add(event.params.data);
            // Reset whole model in order for change events to propagate properly
            that.model.set('terms', terms.toJSON());
          },
          'select2:unselect': function (event) {
            var terms = that.model.get('terms');
            terms.remove(event.params.data);
            // Reset whole model in order for change events to propagate properly
            that.model.set('terms', terms.toJSON());
          },
        })
        .trigger('change');
    },
    toggleDisplayOptions: function () {
      this.model.set(
        '_displayOptionsHidden',
        !this.model.get('_displayOptionsHidden'),
      );
      this.render();
    },
    showButtonSettings: function () {
      var buttonModule = ButtonBlock;
      new buttonModule.ButtonBlockSettingsView({
        model: this.model.get('readMoreButton'),
        renderOptions: {
          displayFormat: 'subpanel',
          hideLink: true,
          hideApplyToAll: true,
        },
      }).render();
    },
    showDividerSettings: function () {
      var dividerModule = DividerBlock;
      new dividerModule.DividerBlockSettingsView({
        model: this.model.get('divider'),
        renderOptions: {
          displayFormat: 'subpanel',
          hideApplyToAll: true,
        },
      }).render();
    },
    changeReadMoreType: function (event) {
      var value = jQuery(event.target).val();
      if (value === 'link') {
        this.$('.mailpoet_automated_latest_content_read_more_text').removeClass(
          'mailpoet_hidden',
        );
        this.$('.mailpoet_automated_latest_content_select_button').addClass(
          'mailpoet_hidden',
        );
      } else if (value === 'button') {
        this.$('.mailpoet_automated_latest_content_read_more_text').addClass(
          'mailpoet_hidden',
        );
        this.$('.mailpoet_automated_latest_content_select_button').removeClass(
          'mailpoet_hidden',
        );
      }
      this.changeField('readMoreType', event);
    },
    changeDisplayType: function (event) {
      var value = jQuery(event.target).val();

      // Reset titleFormat if it was set to List when switching away from displayType=titleOnly
      if (value !== 'titleOnly' && this.model.get('titleFormat') === 'ul') {
        this.model.set('titleFormat', 'h1');
        this.$('.mailpoet_automated_latest_content_title_format').val(['h1']);
        this.$('.mailpoet_automated_latest_content_title_as_link').removeClass(
          'mailpoet_hidden',
        );
      }
      this.changeField('displayType', event);

      const field =
        this.model.get('displayType') === 'full'
          ? 'fullPostFeaturedImagePosition'
          : 'featuredImagePosition';
      this.model.set('_featuredImagePosition', this.model.get(field));
      this.render();
    },
    changeTitleFormat: function (event) {
      var value = jQuery(event.target).val();
      if (value === 'ul') {
        this.$(
          '.mailpoet_automated_latest_content_non_title_list_options',
        ).addClass('mailpoet_hidden');

        this.model.set('titleIsLink', true);
        this.$('.mailpoet_automated_latest_content_title_as_link').addClass(
          'mailpoet_hidden',
        );
        this.$('.mailpoet_automated_latest_content_title_as_links').val([
          'true',
        ]);
      } else {
        this.$(
          '.mailpoet_automated_latest_content_non_title_list_options',
        ).removeClass('mailpoet_hidden');
        this.$('.mailpoet_automated_latest_content_title_as_link').removeClass(
          'mailpoet_hidden',
        );
      }
      this.changeField('titleFormat', event);
    },
    changeFeaturedImagePosition: function (event) {
      const field =
        this.model.get('displayType') === 'full'
          ? 'fullPostFeaturedImagePosition'
          : 'featuredImagePosition';
      this.changeField(field, event);
      this.changeField('_featuredImagePosition', event);
    },
    _updateContentTypes: function (postTypes) {
      var select = this.$('.mailpoet_automated_latest_content_content_type');
      var selectedValue = this.model.get('contentType');

      select.find('option').remove();
      _.each(postTypes, function (type) {
        select.append(
          jQuery('<option>', {
            value: type.name,
            text: type.label,
          }),
        );
      });
      select.val(selectedValue);
    },
  });

Module.AutomatedLatestContentLayoutWidgetView = base.WidgetView.extend({
  className:
    base.WidgetView.prototype.className + ' mailpoet_droppable_layout_block',
  getTemplate: function () {
    return window.templates.automatedLatestContentLayoutInsertion;
  },
  behaviors: {
    DraggableBehavior: {
      cloneOriginal: true,
      drop: function () {
        return new Module.AutomatedLatestContentLayoutBlockModel(
          {},
          { parse: true },
        );
      },
      onDrop: function (options) {
        options.droppedView.triggerMethod('showSettings');
      },
    },
  },
});

App.on('before:start', function (BeforeStartApp) {
  BeforeStartApp.registerBlockType('automatedLatestContentLayout', {
    blockModel: Module.AutomatedLatestContentLayoutBlockModel,
    blockView: Module.AutomatedLatestContentLayoutBlockView,
  });

  BeforeStartApp.registerWidget({
    name: 'automatedLatestContentLayout',
    widgetView: Module.AutomatedLatestContentLayoutWidgetView,
    priority: 97,
  });
});

App.on('start', function (StartApp) {
  var Application = StartApp;
  Application._ALCLayoutSupervisor = new Module.ALCLayoutSupervisor();
  Application._ALCLayoutSupervisor.refresh();
});

export default Module;
