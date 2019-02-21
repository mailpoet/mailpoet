/* eslint-disable func-names */
/**
 * Automated latest content block.
 * Only query parameters can be modified by the user. Posts pulled by this
 * block will change as more posts get published.
 *
 * This block depends on blocks.button and blocks.divider for block model and
 * block settings view.
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

Module.ALCSupervisor = SuperModel.extend({
  initialize: function () {
    var DELAY_REFRESH_FOR_MS = 500;
    this.listenTo(
      App.getChannel(),
      'automatedLatestContentRefresh',
      _.debounce(this.refresh, DELAY_REFRESH_FOR_MS)
    );
  },
  refresh: function () {
    var blocks;
    var models = App.findModels(function (model) {
      return model.get('type') === 'automatedLatestContent';
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
    _.each(
      _.zip(models, renderedBlocks),
      function (args) {
        var model = args[0];
        var contents = args[1];
        model.trigger('refreshPosts', contents);
      }
    );
  },
});

Module.AutomatedLatestContentBlockModel = base.BlockModel.extend({
  stale: ['_container'],
  defaults: function () {
    return this._getDefaults({
      type: 'automatedLatestContent',
      withLayout: false,
      amount: '5',
      contentType: 'post', // 'post'|'page'|'mailpoet_page'
      terms: [], // List of category and tag objects
      inclusionType: 'include', // 'include'|'exclude'
      displayType: 'excerpt', // 'excerpt'|'full'|'titleOnly'
      titleFormat: 'h1', // 'h1'|'h2'|'h3'|'ul'
      titleAlignment: 'left', // 'left'|'center'|'right'
      titleIsLink: false, // false|true
      imageFullWidth: false, // true|false
      featuredImagePosition: 'belowTitle', // 'aboveTitle'|'belowTitle'|'none'
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
    }, App.getConfig().get('blockDefaults.automatedLatestContent'));
  },
  relations: function () {
    return {
      readMoreButton: App.getBlockTypeModel('button'),
      divider: App.getBlockTypeModel('divider'),
      _container: App.getBlockTypeModel('container'),
    };
  },
  initialize: function () {
    base.BlockView.prototype.initialize.apply(this, arguments);
    this.on('change:amount change:contentType change:terms change:inclusionType change:displayType change:titleFormat change:featuredImagePosition change:titleAlignment change:titleIsLink change:imageFullWidth change:showAuthor change:authorPrecededBy change:showCategories change:categoriesPrecededBy change:readMoreType change:readMoreText change:sortBy change:showDivider', this._handleChanges, this);
    this.listenTo(this.get('readMoreButton'), 'change', this._handleChanges);
    this.listenTo(this.get('divider'), 'change', this._handleChanges);
    this.on('add remove update reset', this._handleChanges);
    this.on('refreshPosts', this.updatePosts, this);
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
    App.getChannel().trigger('automatedLatestContentRefresh');
  },
});

Module.AutomatedLatestContentBlockView = base.BlockView.extend({
  className: 'mailpoet_block mailpoet_automated_latest_content_block mailpoet_droppable_block',
  initialize: function () {
    function replaceButtonStylesHandler(data) {
      this.model.set({ readMoreButton: data });
    }
    App.getChannel().on('replaceAllButtonStyles', replaceButtonStylesHandler.bind(this));
  },
  getTemplate: function () { return window.templates.automatedLatestContentBlock; },
  regions: {
    toolsRegion: '.mailpoet_tools',
    postsRegion: '.mailpoet_automated_latest_content_block_posts',
  },
  modelEvents: _.extend(
    _.omit(base.BlockView.prototype.modelEvents, 'change'),
    {
      postsChanged: 'render',
    }
  ),
  events: _.extend(base.BlockView.prototype.events, {
    'click .mailpoet_automated_latest_content_block_overlay': 'showSettings',
  }),
  onDragSubstituteBy: function () { return Module.AutomatedLatestContentWidgetView; },
  onRender: function () {
    var ContainerView = App.getBlockTypeView('container');
    var renderOptions = {
      disableTextEditor: true,
      disableDragAndDrop: true,
      emptyContainerMessage: MailPoet.I18n.t('noPostsToDisplay'),
    };
    this.toolsView = new Module.AutomatedLatestContentBlockToolsView({ model: this.model });
    this.showChildView('toolsRegion', this.toolsView);
    this.showChildView('postsRegion', new ContainerView({ model: this.model.get('_container'), renderOptions: renderOptions }));
  },
});

Module.AutomatedLatestContentBlockToolsView = base.BlockToolsView.extend({
  getSettingsView: function () { return Module.AutomatedLatestContentBlockSettingsView; },
});

// Sidebar view container
Module.AutomatedLatestContentBlockSettingsView = base.BlockSettingsView.extend({
  getTemplate: function () { return window.templates.automatedLatestContentBlockSettings; },
  events: function () {
    return {
      'click .mailpoet_automated_latest_content_hide_display_options': 'toggleDisplayOptions',
      'click .mailpoet_automated_latest_content_show_display_options': 'toggleDisplayOptions',
      'click .mailpoet_automated_latest_content_select_button': 'showButtonSettings',
      'click .mailpoet_automated_latest_content_select_divider': 'showDividerSettings',
      'change .mailpoet_automated_latest_content_read_more_type': 'changeReadMoreType',
      'change .mailpoet_automated_latest_content_display_type': 'changeDisplayType',
      'change .mailpoet_automated_latest_content_title_format': 'changeTitleFormat',
      'change .mailpoet_automated_latest_content_title_as_links': _.partial(this.changeBoolField, 'titleIsLink'),
      'change .mailpoet_automated_latest_content_show_divider': _.partial(this.changeBoolField, 'showDivider'),
      'input .mailpoet_automated_latest_content_show_amount': _.partial(this.changeField, 'amount'),
      'change .mailpoet_automated_latest_content_content_type': _.partial(this.changeField, 'contentType'),
      'change .mailpoet_automated_latest_content_include_or_exclude': _.partial(this.changeField, 'inclusionType'),
      'change .mailpoet_automated_latest_content_title_alignment': _.partial(this.changeField, 'titleAlignment'),
      'change .mailpoet_automated_latest_content_image_full_width': _.partial(this.changeBoolField, 'imageFullWidth'),
      'change .mailpoet_automated_latest_content_featured_image_position': _.partial(this.changeField, 'featuredImagePosition'),
      'change .mailpoet_automated_latest_content_show_author': _.partial(this.changeField, 'showAuthor'),
      'input .mailpoet_automated_latest_content_author_preceded_by': _.partial(this.changeField, 'authorPrecededBy'),
      'change .mailpoet_automated_latest_content_show_categories': _.partial(this.changeField, 'showCategories'),
      'input .mailpoet_automated_latest_content_categories': _.partial(this.changeField, 'categoriesPrecededBy'),
      'input .mailpoet_automated_latest_content_read_more_text': _.partial(this.changeField, 'readMoreText'),
      'change .mailpoet_automated_latest_content_sort_by': _.partial(this.changeField, 'sortBy'),
      'click .mailpoet_done_editing': 'close',
    };
  },
  onRender: function () {
    var that = this;

    // Dynamically update available post types
    CommunicationComponent.getPostTypes().done(_.bind(this._updateContentTypes, this));

    this.$('.mailpoet_automated_latest_content_categories_and_tags').select2({
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
            that.model.get('contentType')
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
            results: _.map(
              data.terms,
              function (item) {
                return _.defaults({
                  text: data.taxonomies[item.taxonomy].labels.singular_name + ': ' + item.name,
                  id: item.term_id,
                }, item);
              }
            ),
            pagination: {
              more: data.terms.length === 100,
            },
          };
        },
      },
    }).on({
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
    }).trigger('change');
  },
  toggleDisplayOptions: function () {
    var el = this.$('.mailpoet_automated_latest_content_display_options');
    var showControl = this.$('.mailpoet_automated_latest_content_show_display_options');
    if (el.hasClass('mailpoet_closed')) {
      el.removeClass('mailpoet_closed');
      showControl.addClass('mailpoet_hidden');
    } else {
      el.addClass('mailpoet_closed');
      showControl.removeClass('mailpoet_hidden');
    }
  },
  showButtonSettings: function () {
    var buttonModule = ButtonBlock;
    (new buttonModule.ButtonBlockSettingsView({
      model: this.model.get('readMoreButton'),
      renderOptions: {
        displayFormat: 'subpanel',
        hideLink: true,
        hideApplyToAll: true,
      },
    })).render();
  },
  showDividerSettings: function () {
    var dividerModule = DividerBlock;
    (new dividerModule.DividerBlockSettingsView({
      model: this.model.get('divider'),
      renderOptions: {
        displayFormat: 'subpanel',
        hideApplyToAll: true,
      },
    })).render();
  },
  changeReadMoreType: function (event) {
    var value = jQuery(event.target).val();
    if (value === 'link') {
      this.$('.mailpoet_automated_latest_content_read_more_text').removeClass('mailpoet_hidden');
      this.$('.mailpoet_automated_latest_content_select_button').addClass('mailpoet_hidden');
    } else if (value === 'button') {
      this.$('.mailpoet_automated_latest_content_read_more_text').addClass('mailpoet_hidden');
      this.$('.mailpoet_automated_latest_content_select_button').removeClass('mailpoet_hidden');
    }
    this.changeField('readMoreType', event);
  },
  changeDisplayType: function (event) {
    var value = jQuery(event.target).val();

    if (value === 'titleOnly') {
      this.$('.mailpoet_automated_latest_content_title_as_list').removeClass('mailpoet_hidden');
      this.$('.mailpoet_automated_latest_content_image_full_width_option').addClass('mailpoet_hidden');
      this.$('.mailpoet_automated_latest_content_image_separator').addClass('mailpoet_hidden');
    } else {
      this.$('.mailpoet_automated_latest_content_title_as_list').addClass('mailpoet_hidden');
      this.$('.mailpoet_automated_latest_content_image_full_width_option').removeClass('mailpoet_hidden');
      this.$('.mailpoet_automated_latest_content_image_separator').removeClass('mailpoet_hidden');

      // Reset titleFormat if it was set to List when switching away from displayType=titleOnly
      if (this.model.get('titleFormat') === 'ul') {
        this.model.set('titleFormat', 'h1');
        this.$('.mailpoet_automated_latest_content_title_format').val(['h1']);
        this.$('.mailpoet_automated_latest_content_title_as_link').removeClass('mailpoet_hidden');
      }
    }

    if (value === 'excerpt') {
      this.$('.mailpoet_automated_latest_content_featured_image_position_container').removeClass('mailpoet_hidden');
    } else {
      this.$('.mailpoet_automated_latest_content_featured_image_position_container').addClass('mailpoet_hidden');
    }
    this.changeField('displayType', event);
  },
  changeTitleFormat: function (event) {
    var value = jQuery(event.target).val();
    if (value === 'ul') {
      this.$('.mailpoet_automated_latest_content_non_title_list_options').addClass('mailpoet_hidden');

      this.model.set('titleIsLink', true);
      this.$('.mailpoet_automated_latest_content_title_as_link').addClass('mailpoet_hidden');
      this.$('.mailpoet_automated_latest_content_title_as_links').val(['true']);
    } else {
      this.$('.mailpoet_automated_latest_content_non_title_list_options').removeClass('mailpoet_hidden');
      this.$('.mailpoet_automated_latest_content_title_as_link').removeClass('mailpoet_hidden');
    }
    this.changeField('titleFormat', event);
  },
  _updateContentTypes: function (postTypes) {
    var select = this.$('.mailpoet_automated_latest_content_content_type');
    var selectedValue = this.model.get('contentType');

    select.find('option').remove();
    _.each(postTypes, function (type) {
      select.append(jQuery('<option>', {
        value: type.name,
        text: type.label,
      }));
    });
    select.val(selectedValue);
  },
});

Module.AutomatedLatestContentWidgetView = base.WidgetView.extend({
  getTemplate: function () { return window.templates.automatedLatestContentInsertion; },
  behaviors: {
    DraggableBehavior: {
      cloneOriginal: true,
      drop: function () {
        return new Module.AutomatedLatestContentBlockModel({}, { parse: true });
      },
      onDrop: function (options) {
        options.droppedView.triggerMethod('showSettings');
      },
    },
  },
});

App.on('before:start', function (BeforeStartApp) {
  BeforeStartApp.registerBlockType('automatedLatestContent', {
    blockModel: Module.AutomatedLatestContentBlockModel,
    blockView: Module.AutomatedLatestContentBlockView,
  });
});

App.on('start', function (StartApp) {
  var Application = StartApp;
  Application._ALCSupervisor = new Module.ALCSupervisor();
  Application._ALCSupervisor.refresh();
});

export default Module;
