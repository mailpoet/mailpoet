/**
 * Posts block.
 *
 * This block works slightly differently compared to any other block.
 * The difference is that once the user changes settings of this block,
 * it will be removed and replaced with other blocks. So this block will
 * not appear in the final JSON structure, it serves only as a placeholder
 * for posts, that will be comprised of container, image, button and text blocks
 *
 * This block depends on blocks.button and blocks.divider for block model and
 * block settings view.
 */
import Backbone from 'backbone';
import Marionette from 'backbone.marionette';
import Radio from 'backbone.radio';
import _ from 'underscore';
import jQuery from 'jquery';
import MailPoet from 'mailpoet';
import App from 'newsletter_editor/App';
import CommunicationComponent from 'newsletter_editor/components/communication';
import BaseBlock from 'newsletter_editor/blocks/base';
import ButtonBlock from 'newsletter_editor/blocks/button';
import DividerBlock from 'newsletter_editor/blocks/divider';
import 'select2';

var Module = {};
var base = BaseBlock;
var PostsDisplayOptionsSettingsView;
var SinglePostSelectionSettingsView;
var EmptyPostSelectionSettingsView;
var PostSelectionSettingsView;
var PostsSelectionCollectionView;

Module.PostsBlockModel = base.BlockModel.extend({
  stale: ['_selectedPosts', '_availablePosts', '_transformedPosts'],
  defaults: function productsModelDefaults() {
    return this._getDefaults({
      type: 'posts',
      withLayout: true,
      amount: '10',
      offset: 0,
      contentType: 'post', // 'post'|'page'|'mailpoet_page'
      postStatus: 'publish', // 'draft'|'pending'|'private'|'publish'|'future'
      terms: [], // List of category and tag objects
      search: '', // Search keyword term
      inclusionType: 'include', // 'include'|'exclude'
      displayType: 'excerpt', // 'excerpt'|'full'|'titleOnly'
      titleFormat: 'h1', // 'h1'|'h2'|'h3'|'ul'
      titleAlignment: 'left', // 'left'|'center'|'right'
      titleIsLink: false, // false|true
      imageFullWidth: false, // true|false
      titlePosition: 'abovePost', // 'abovePost'|'aboveExcerpt'
      featuredImagePosition: 'centered', // 'centered'|'right'|'left'|'alternate'|'none'
      showAuthor: 'no', // 'no'|'aboveText'|'belowText'
      authorPrecededBy: 'Author:',
      showCategories: 'no', // 'no'|'aboveText'|'belowText'
      categoriesPrecededBy: 'Categories:',
      readMoreType: 'link', // 'link'|'button'
      readMoreText: 'Read more', // 'link'|'button'
      readMoreButton: {
        text: 'Read more',
        url: '[postLink]',
      },
      sortBy: 'newest', // 'newest'|'oldest',
      showDivider: true, // true|false
      divider: {},
      _selectedPosts: [],
      _availablePosts: [],
      _transformedPosts: new (App.getBlockTypeModel('container'))(),
    }, App.getConfig().get('blockDefaults.posts'));
  },
  relations: function relations() {
    return {
      readMoreButton: App.getBlockTypeModel('button'),
      divider: App.getBlockTypeModel('divider'),
      _selectedPosts: Backbone.Collection,
      _availablePosts: Backbone.Collection,
      _transformedPosts: App.getBlockTypeModel('container'),
    };
  },
  initialize: function initialize() {
    var POST_REFRESH_DELAY_MS = 500;
    var refreshAvailablePosts = _.debounce(
      this.fetchAvailablePosts.bind(this),
      POST_REFRESH_DELAY_MS
    );
    var refreshTransformedPosts = _.debounce(
      this._refreshTransformedPosts.bind(this),
      POST_REFRESH_DELAY_MS
    );

    // Attach Radio.Requests API primarily for highlighting
    _.extend(this, Radio.Requests);

    this.fetchAvailablePosts();
    this.on('change', this._updateDefaults, this);
    this.on('change:amount change:contentType change:terms change:inclusionType change:postStatus change:search change:sortBy', refreshAvailablePosts);
    this.on('loadMorePosts', this._loadMorePosts, this);

    this.listenTo(this.get('_selectedPosts'), 'add remove reset', refreshTransformedPosts);
    this.on('change:displayType change:titleFormat change:featuredImagePosition change:titleAlignment change:titleIsLink change:imageFullWidth change:showAuthor change:authorPrecededBy change:showCategories change:categoriesPrecededBy change:readMoreType change:readMoreText change:showDivider change:titlePosition', refreshTransformedPosts);
    this.listenTo(this.get('readMoreButton'), 'change', refreshTransformedPosts);
    this.listenTo(this.get('divider'), 'change', refreshTransformedPosts);

    this.on('insertSelectedPosts', this._insertSelectedPosts, this);
  },
  fetchAvailablePosts: function fetchAvailablePosts() {
    var that = this;
    this.set('offset', 0);
    CommunicationComponent.getPosts(this.toJSON()).done(function getPostsDone(posts) {
      that.get('_availablePosts').reset(posts);
      that.get('_selectedPosts').reset(); // Empty out the collection
      that.trigger('change:_availablePosts');
    }).fail(function getPostsFail() {
      MailPoet.Notice.error(MailPoet.I18n.t('failedToFetchAvailablePosts'));
    });
  },
  _loadMorePosts: function loadMorePosts() {
    var that = this;
    var postCount = this.get('_availablePosts').length;
    var nextOffset = this.get('offset') + Number(this.get('amount'));

    if (postCount === 0 || postCount < nextOffset) {
      // No more posts to load
      return false;
    }
    this.set('offset', nextOffset);
    this.trigger('loadingMorePosts');

    CommunicationComponent.getPosts(this.toJSON()).done(function getPostsDone(posts) {
      that.get('_availablePosts').add(posts);
      that.trigger('change:_availablePosts');
    }).fail(function getPostsFail() {
      MailPoet.Notice.error(MailPoet.I18n.t('failedToFetchAvailablePosts'));
    }).always(function getPostsAlways() {
      that.trigger('morePostsLoaded');
    });
    return true;
  },
  _refreshTransformedPosts: function refreshTransformedPosts() {
    var that = this;
    var data = this.toJSON();

    data.posts = this.get('_selectedPosts').pluck('ID');

    if (data.posts.length === 0) {
      this.get('_transformedPosts').get('blocks').reset();
      return;
    }

    CommunicationComponent.getTransformedPosts(data).done(function getTransformedPostsDone(posts) {
      that.get('_transformedPosts').get('blocks').reset(posts, { parse: true });
    }).fail(function getTransformedPostsFail() {
      MailPoet.Notice.error(MailPoet.I18n.t('failedToFetchRenderedPosts'));
    });
  },
  _insertSelectedPosts: function insertSelectedPosts() {
    var data = this.toJSON();
    var index = this.collection.indexOf(this);
    var collection = this.collection;

    data.posts = this.get('_selectedPosts').pluck('ID');

    if (data.posts.length === 0) return;

    CommunicationComponent.getTransformedPosts(data).done(function getTransformedPostsDone(posts) {
      collection.add(JSON.parse(JSON.stringify(posts)), { at: index });
    }).fail(function getTransformedPostsFail() {
      MailPoet.Notice.error(MailPoet.I18n.t('failedToFetchRenderedPosts'));
    });
  },
});

Module.PostsBlockView = base.BlockView.extend({
  className: 'mailpoet_block mailpoet_posts_block mailpoet_droppable_block',
  getTemplate: function getTemplate() { return window.templates.productsBlock; },
  modelEvents: {}, // Forcefully disable all events
  regions: _.extend({
    postsRegion: '.mailpoet_posts_container',
  }, base.BlockView.prototype.regions),
  onDragSubstituteBy: function onDragSubstituteBy() { return Module.PostsWidgetView; },
  initialize: function initialize() {
    base.BlockView.prototype.initialize.apply(this, arguments);

    this.toolsView = new Module.PostsBlockToolsView({ model: this.model });
    this.model.reply('blockView', this.notifyAboutSelf, this);
  },
  onRender: function onRender() {
    var ContainerView;
    var renderOptions;
    if (!this.getRegion('toolsRegion').hasView()) {
      this.showChildView('toolsRegion', this.toolsView);
    }
    this.trigger('showSettings');

    ContainerView = App.getBlockTypeView('container');
    renderOptions = {
      disableTextEditor: true,
      disableDragAndDrop: true,
      emptyContainerMessage: MailPoet.I18n.t('noPostsToDisplay'),
    };
    this.showChildView('postsRegion', new ContainerView({ model: this.model.get('_transformedPosts'), renderOptions: renderOptions }));
  },
  notifyAboutSelf: function notifyAboutSelf() {
    return this;
  },
  onBeforeDestroy: function onBeforeDestroy() {
    this.model.stopReplying('blockView', this.notifyAboutSelf, this);
  },
});

Module.PostsBlockToolsView = base.BlockToolsView.extend({
  getSettingsView: function getSettingsView() { return Module.PostsBlockSettingsView; },
});

Module.PostsBlockSettingsView = base.BlockSettingsView.extend({
  getTemplate: function getTemplate() { return window.templates.productsBlockSettings; },
  regions: {
    selectionRegion: '.mailpoet_settings_posts_selection',
    displayOptionsRegion: '.mailpoet_settings_posts_display_options',
  },
  events: {
    'click .mailpoet_settings_posts_show_display_options': 'switchToDisplayOptions',
    'click .mailpoet_settings_posts_show_post_selection': 'switchToPostSelection',
    'click .mailpoet_settings_posts_insert_selected': 'insertPosts',
  },
  templateContext: function templateContext() {
    return {
      model: this.model.toJSON(),
    };
  },
  initialize: function initialize() {
    this.model.trigger('startEditing');
    this.selectionView = new PostSelectionSettingsView({ model: this.model });
    this.displayOptionsView = new PostsDisplayOptionsSettingsView({ model: this.model });
  },
  onRender: function onRender() {
    var that = this;
    this.model.request('blockView');

    this.showChildView('selectionRegion', this.selectionView);
    this.showChildView('displayOptionsRegion', this.displayOptionsView);

    MailPoet.Modal.panel({
      element: this.$el,
      template: '',
      position: 'right',
      width: App.getConfig().get('sidepanelWidth'),
      onCancel: function onCancel() {
        // Self destroy the block if the user closes settings modal
        that.model.destroy();
      },
    });

    // Inform child views that they have been attached to document
    this.selectionView.triggerMethod('attach');
    this.displayOptionsView.triggerMethod('attach');
  },
  switchToDisplayOptions: function switchToDisplayOptions() {
    // Switch content view
    this.$('.mailpoet_settings_posts_selection').addClass('mailpoet_closed');
    this.$('.mailpoet_settings_posts_display_options').removeClass('mailpoet_closed');

    // Switch controls
    this.$('.mailpoet_settings_posts_show_display_options').addClass('mailpoet_hidden');
    this.$('.mailpoet_settings_posts_show_post_selection').removeClass('mailpoet_hidden');
  },
  switchToPostSelection: function switchToPostSelection() {
    // Switch content view
    this.$('.mailpoet_settings_posts_display_options').addClass('mailpoet_closed');
    this.$('.mailpoet_settings_posts_selection').removeClass('mailpoet_closed');

    // Switch controls
    this.$('.mailpoet_settings_posts_show_post_selection').addClass('mailpoet_hidden');
    this.$('.mailpoet_settings_posts_show_display_options').removeClass('mailpoet_hidden');
  },
  insertPosts: function insertPosts() {
    this.model.trigger('insertSelectedPosts');
    this.model.destroy();
    this.close();
  },
});

PostsSelectionCollectionView = Marionette.CollectionView.extend({
  className: 'mailpoet_post_scroll_container',
  childView: function childView() { return SinglePostSelectionSettingsView; },
  emptyView: function emptyView() { return EmptyPostSelectionSettingsView; },
  childViewOptions: function childViewOptions() {
    return {
      blockModel: this.blockModel,
    };
  },
  initialize: function initialize(options) {
    this.blockModel = options.blockModel;
  },
  events: {
    scroll: 'onPostsScroll',
  },
  onPostsScroll: function onPostsScroll(event) {
    var $postsBox = jQuery(event.target);
    if ($postsBox.scrollTop() + $postsBox.innerHeight() >= $postsBox[0].scrollHeight) {
      // Load more posts if scrolled to bottom
      this.blockModel.trigger('loadMorePosts');
    }
  },
});

PostSelectionSettingsView = Marionette.View.extend({
  getTemplate: function getTemplate() {
    return window.templates.postSelectionProductsBlockSettings;
  },
  regions: {
    posts: '.mailpoet_post_selection_container',
  },
  events: function events() {
    return {
      'change .mailpoet_settings_posts_content_type': _.partial(this.changeField, 'contentType'),
      'change .mailpoet_posts_post_status': _.partial(this.changeField, 'postStatus'),
      'input .mailpoet_posts_search_term': _.partial(this.changeField, 'search'),
    };
  },
  modelEvents: {
    'change:offset': function changeOffset(model, value) {
      // Scroll posts view to top if settings are changed
      if (value === 0) {
        this.$('.mailpoet_post_scroll_container').scrollTop(0);
      }
    },
    loadingMorePosts: function loadingMorePosts() {
      this.$('.mailpoet_post_selection_loading').css('visibility', 'visible');
    },
    morePostsLoaded: function morePostsLoaded() {
      this.$('.mailpoet_post_selection_loading').css('visibility', 'hidden');
    },
  },
  templateContext: function templateContext() {
    return {
      model: this.model.toJSON(),
    };
  },
  onRender: function onRender() {
    var postsView;
    // Dynamically update available post types
    CommunicationComponent.getPostTypes().done(_.bind(this._updateContentTypes, this));
    postsView = new PostsSelectionCollectionView({
      collection: this.model.get('_availablePosts'),
      blockModel: this.model,
    });

    this.showChildView('posts', postsView);
  },
  onAttach: function onAttach() {
    var that = this;

    this.$('.mailpoet_posts_categories_and_tags').select2({
      multiple: true,
      allowClear: true,
      placeholder: MailPoet.I18n.t('categoriesAndTags'),
      ajax: {
        data: function data(params) {
          return {
            term: params.term,
            page: params.page || 1,
          };
        },
        transport: function transport(options, success, failure) {
          var taxonomies;
          var termsPromise;
          var promise = CommunicationComponent.getTaxonomies(
            that.model.get('contentType')
          ).then(function getTerms(tax) {
            taxonomies = tax;
            // Fetch available terms based on the list of taxonomies already fetched
            termsPromise = CommunicationComponent.getTerms({
              search: options.data.term,
              page: options.data.page,
              taxonomies: _.keys(taxonomies),
            }).then(function mapTerms(terms) {
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
        processResults: function processResults(data) {
          // Transform taxonomies and terms into select2 compatible format
          return {
            results: _.map(
              data.terms,
              function results(item) {
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
      'select2:select': function select2Select(event) {
        var terms = that.model.get('terms');
        terms.add(event.params.data);
        // Reset whole model in order for change events to propagate properly
        that.model.set('terms', terms.toJSON());
      },
      'select2:unselect': function select2Unselect(event) {
        var terms = that.model.get('terms');
        terms.remove(event.params.data);
        // Reset whole model in order for change events to propagate properly
        that.model.set('terms', terms.toJSON());
      },
    }).trigger('change');
  },
  changeField: function changeField(field, event) {
    this.model.set(field, jQuery(event.target).val());
  },
  _updateContentTypes: function updateContentTypes(postTypes) {
    var select = this.$('.mailpoet_settings_posts_content_type');
    var selectedValue = this.model.get('contentType');

    select.find('option').remove();
    _.each(postTypes, function postTypesMap(type) {
      select.append(jQuery('<option>', {
        value: type.name,
        text: type.label,
      }));
    });
    select.val(selectedValue);
  },
});

EmptyPostSelectionSettingsView = Marionette.View.extend({
  getTemplate: function getTemplate() { return window.templates.emptyPostProductsBlockSettings; },
});

SinglePostSelectionSettingsView = Marionette.View.extend({
  getTemplate: function getTemplate() { return window.templates.singlePostProductsBlockSettings; },
  events: function events() {
    return {
      'change .mailpoet_select_post_checkbox': 'postSelectionChange',
    };
  },
  templateContext: function templateContext() {
    return {
      model: this.model.toJSON(),
      index: this._index,
    };
  },
  initialize: function initialize(options) {
    this.blockModel = options.blockModel;
  },
  postSelectionChange: function postSelectionChange(event) {
    var checkBox = jQuery(event.target);
    var selectedPostsCollection = this.blockModel.get('_selectedPosts');
    if (checkBox.prop('checked')) {
      selectedPostsCollection.add(this.model);
    } else {
      selectedPostsCollection.remove(this.model);
    }
  },
});

PostsDisplayOptionsSettingsView = base.BlockSettingsView.extend({
  getTemplate: function getTemplate() {
    return window.templates.displayOptionsProductsBlockSettings;
  },
  events: function events() {
    return {
      'click .mailpoet_posts_select_button': 'showButtonSettings',
      'click .mailpoet_posts_select_divider': 'showDividerSettings',
      'change .mailpoet_posts_read_more_type': 'changeReadMoreType',
      'change .mailpoet_posts_display_type': 'changeDisplayType',
      'change .mailpoet_posts_title_format': 'changeTitleFormat',
      'change .mailpoet_posts_title_as_links': _.partial(this.changeBoolField, 'titleIsLink'),
      'change .mailpoet_posts_show_divider': _.partial(this.changeBoolField, 'showDivider'),
      'input .mailpoet_posts_show_amount': _.partial(this.changeField, 'amount'),
      'change .mailpoet_posts_content_type': _.partial(this.changeField, 'contentType'),
      'change .mailpoet_posts_include_or_exclude': _.partial(this.changeField, 'inclusionType'),
      'change .mailpoet_posts_title_alignment': _.partial(this.changeField, 'titleAlignment'),
      'change .mailpoet_posts_image_full_width': _.partial(this.changeBoolField, 'imageFullWidth'),
      'change .mailpoet_posts_featured_image_position': _.partial(this.changeField, 'featuredImagePosition'),
      'change .mailpoet_posts_show_author': _.partial(this.changeField, 'showAuthor'),
      'input .mailpoet_posts_author_preceded_by': _.partial(this.changeField, 'authorPrecededBy'),
      'change .mailpoet_posts_show_categories': _.partial(this.changeField, 'showCategories'),
      'input .mailpoet_posts_categories': _.partial(this.changeField, 'categoriesPrecededBy'),
      'input .mailpoet_posts_read_more_text': _.partial(this.changeField, 'readMoreText'),
      'change .mailpoet_posts_sort_by': _.partial(this.changeField, 'sortBy'),
      'change .mailpoet_automated_latest_content_title_position': _.partial(this.changeField, 'titlePosition'),
    };
  },
  templateContext: function templateContext() {
    return {
      model: this.model.toJSON(),
    };
  },
  showButtonSettings: function showButtonSettings() {
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
  showDividerSettings: function showDividerSettings() {
    var dividerModule = DividerBlock;
    (new dividerModule.DividerBlockSettingsView({
      model: this.model.get('divider'),
      renderOptions: {
        displayFormat: 'subpanel',
        hideApplyToAll: true,
      },
    })).render();
  },
  changeReadMoreType: function changeReadMoreType(event) {
    var value = jQuery(event.target).val();
    if (value === 'link') {
      this.$('.mailpoet_posts_read_more_text').removeClass('mailpoet_hidden');
      this.$('.mailpoet_posts_select_button').addClass('mailpoet_hidden');
    } else if (value === 'button') {
      this.$('.mailpoet_posts_read_more_text').addClass('mailpoet_hidden');
      this.$('.mailpoet_posts_select_button').removeClass('mailpoet_hidden');
    }
    this.changeField('readMoreType', event);
  },
  changeDisplayType: function changeDisplayType(event) {
    var value = jQuery(event.target).val();
    if (value === 'titleOnly') {
      this.$('.mailpoet_posts_title_as_list').removeClass('mailpoet_hidden');
      this.$('.mailpoet_posts_image_full_width_option').addClass('mailpoet_hidden');
      this.$('.mailpoet_posts_image_separator').addClass('mailpoet_hidden');
    } else {
      this.$('.mailpoet_posts_title_as_list').addClass('mailpoet_hidden');
      this.$('.mailpoet_posts_image_full_width_option').removeClass('mailpoet_hidden');
      this.$('.mailpoet_posts_image_separator').removeClass('mailpoet_hidden');

      // Reset titleFormat if it was set to List when switching away from displayType=titleOnly
      if (this.model.get('titleFormat') === 'ul') {
        this.model.set('titleFormat', 'h1');
        this.$('.mailpoet_posts_title_format').val(['h1']);
        this.$('.mailpoet_posts_title_as_link').removeClass('mailpoet_hidden');
      }
    }

    if (value === 'excerpt') {
      this.$('.mailpoet_posts_featured_image_position_container').removeClass('mailpoet_hidden');
    } else {
      this.$('.mailpoet_posts_featured_image_position_container').addClass('mailpoet_hidden');
    }

    this.changeField('displayType', event);
  },
  changeTitleFormat: function changeTitleFormat(event) {
    var value = jQuery(event.target).val();
    if (value === 'ul') {
      this.$('.mailpoet_posts_non_title_list_options').addClass('mailpoet_hidden');

      this.model.set('titleIsLink', true);
      this.$('.mailpoet_posts_title_as_link').addClass('mailpoet_hidden');
      this.$('.mailpoet_posts_title_as_links').val(['true']);
    } else {
      this.$('.mailpoet_posts_non_title_list_options').removeClass('mailpoet_hidden');
      this.$('.mailpoet_posts_title_as_link').removeClass('mailpoet_hidden');
    }
    this.changeField('titleFormat', event);
  },
});

Module.PostsWidgetView = base.WidgetView.extend({
  className: base.WidgetView.prototype.className + ' mailpoet_droppable_layout_block',
  getTemplate: function getTemplate() { return window.templates.productsInsertion; },
  behaviors: {
    DraggableBehavior: {
      cloneOriginal: true,
      drop: function drop() {
        return new Module.PostsBlockModel({}, { parse: true });
      },
    },
  },
});

App.on('before:start', function beforeStartApp(BeforeStartApp) {
  if (_.isEmpty(window.config.displayWcProductsWidget)) {
    return;
  }
  BeforeStartApp.registerBlockType('products', {
    blockModel: Module.PostsBlockModel,
    blockView: Module.PostsBlockView,
  });

  BeforeStartApp.registerWidget({
    name: 'products',
    widgetView: Module.PostsWidgetView,
    priority: 98,
  });
});

export default Module;
