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
define([
    'backbone',
    'backbone.marionette',
    'backbone.radio',
    'underscore',
    'jquery',
    'mailpoet',
    'newsletter_editor/App',
    'newsletter_editor/components/communication',
    'newsletter_editor/blocks/base',
    'newsletter_editor/blocks/button',
    'newsletter_editor/blocks/divider',
    'select2'
  ], function(
    Backbone,
    Marionette,
    Radio,
    _,
    jQuery,
    MailPoet,
    App,
    CommunicationComponent,
    BaseBlock,
    ButtonBlock,
    DividerBlock
  ) {

  "use strict";

  var Module = {},
      base = BaseBlock;

  Module.PostsBlockModel = base.BlockModel.extend({
    stale: ['_selectedPosts', '_availablePosts', '_transformedPosts'],
    defaults: function() {
      return this._getDefaults({
        type: 'posts',
        amount: '10',
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
        featuredImagePosition: 'belowTitle', // 'aboveTitle'|'belowTitle'|'none'
        //imageAlignment: 'centerPadded', // 'centerFull'|'centerPadded'|'left'|'right'|'alternate'|'none'
        showAuthor: 'no', // 'no'|'aboveText'|'belowText'
        authorPrecededBy: 'Author:',
        showCategories: 'no', // 'no'|'aboveText'|'belowText'
        categoriesPrecededBy: 'Categories:',
        readMoreType: 'link', // 'link'|'button'
        readMoreText: 'Read more', // 'link'|'button'
        readMoreButton: {
          text: 'Read more',
          url: '[postLink]'
        },
        sortBy: 'newest', // 'newest'|'oldest',
        showDivider: true, // true|false
        divider: {},
        _selectedPosts: [],
        _availablePosts: [],
        _transformedPosts: new (App.getBlockTypeModel('container'))(),
      }, App.getConfig().get('blockDefaults.posts'));
    },
    relations: function() {
      return {
        readMoreButton: App.getBlockTypeModel('button'),
        divider: App.getBlockTypeModel('divider'),
        _selectedPosts: Backbone.Collection,
        _availablePosts: Backbone.Collection,
        _transformedPosts: App.getBlockTypeModel('container'),
      };
    },
    initialize: function() {
      var that = this,
        POST_REFRESH_DELAY_MS = 500,
        refreshAvailablePosts = _.debounce(this.fetchAvailablePosts.bind(this), POST_REFRESH_DELAY_MS),
        refreshTransformedPosts = _.debounce(this._refreshTransformedPosts.bind(this), POST_REFRESH_DELAY_MS);

      // Attach Radio.Requests API primarily for highlighting
      _.extend(this, Radio.Requests);

      this.fetchAvailablePosts();
      this.on('change:amount change:contentType change:terms change:inclusionType change:postStatus change:search change:sortBy', refreshAvailablePosts);

      this.listenTo(this.get('_selectedPosts'), 'add remove reset', refreshTransformedPosts);
      this.on('change:displayType change:titleFormat change:featuredImagePosition change:titleAlignment change:titleIsLink change:imageFullWidth change:showAuthor change:authorPrecededBy change:showCategories change:categoriesPrecededBy change:readMoreType change:readMoreText change:showDivider', refreshTransformedPosts);
      this.listenTo(this.get('readMoreButton'), 'change', refreshTransformedPosts);
      this.listenTo(this.get('divider'), 'change', refreshTransformedPosts);

      this.on('insertSelectedPosts', this._insertSelectedPosts, this);
    },
    fetchAvailablePosts: function() {
      var that = this;
      CommunicationComponent.getPosts(this.toJSON()).done(function(posts) {
        that.get('_availablePosts').reset(posts);
        that.get('_selectedPosts').reset(); // Empty out the collection
        that.trigger('change:_availablePosts');
      }).fail(function() {
        MailPoet.Notice.error(MailPoet.I18n.t('failedToFetchAvailablePosts'));
      });
    },
    _refreshTransformedPosts: function() {
      var that = this,
        data = this.toJSON();

      data.posts = this.get('_selectedPosts').pluck('ID');

      if (data.posts.length === 0) {
        this.get('_transformedPosts').get('blocks').reset();
        return;
      }

      CommunicationComponent.getTransformedPosts(data).done(function(posts) {
        that.get('_transformedPosts').get('blocks').reset(posts, {parse: true});
      }).fail(function() {
        MailPoet.Notice.error(MailPoet.I18n.t('failedToFetchRenderedPosts'));
      });
    },
    _insertSelectedPosts: function() {
      var that = this,
        data = this.toJSON(),
        index = this.collection.indexOf(this),
        collection = this.collection;

      data.posts = this.get('_selectedPosts').pluck('ID');

      if (data.posts.length === 0) return;

      CommunicationComponent.getTransformedPosts(data).done(function(posts) {
        collection.add(posts, { at: index });
      }).fail(function() {
        MailPoet.Notice.error(MailPoet.I18n.t('failedToFetchRenderedPosts'));
      });
    },
  });

  Module.PostsBlockView = base.BlockView.extend({
    className: "mailpoet_block mailpoet_posts_block mailpoet_droppable_block",
    getTemplate: function() { return templates.postsBlock; },
    modelEvents: {}, // Forcefully disable all events
    regions: _.extend({
      postsRegion: '.mailpoet_posts_block_posts',
    }, base.BlockView.prototype.regions),
    onDragSubstituteBy: function() { return Module.PostsWidgetView; },
    initialize: function() {
      base.BlockView.prototype.initialize.apply(this, arguments);

      this.toolsView = new Module.PostsBlockToolsView({ model: this.model });
      this.model.reply('blockView', this.notifyAboutSelf, this);
    },
    onRender: function() {
      if (!this.toolsRegion.hasView()) {
        this.toolsRegion.show(this.toolsView);
      }
      this.trigger('showSettings');

      var ContainerView = App.getBlockTypeView('container'),
        renderOptions = {
          disableTextEditor: true,
          disableDragAndDrop: true,
        };
      this.postsRegion.show(new ContainerView({ model: this.model.get('_transformedPosts'), renderOptions: renderOptions }));
    },
    notifyAboutSelf: function() {
      return this;
    },
    onBeforeDestroy: function() {
      this.model.stopReplying('blockView', this.notifyAboutSelf, this);
    },
  });

  Module.PostsBlockToolsView = base.BlockToolsView.extend({
    getSettingsView: function() { return Module.PostsBlockSettingsView; },
  });

  Module.PostsBlockSettingsView = base.BlockSettingsView.extend({
    getTemplate: function() { return templates.postsBlockSettings; },
    regions: {
      selectionRegion: '.mailpoet_settings_posts_selection',
      displayOptionsRegion: '.mailpoet_settings_posts_display_options',
    },
    events: {
      'click .mailpoet_settings_posts_show_display_options': 'switchToDisplayOptions',
      'click .mailpoet_settings_posts_show_post_selection': 'switchToPostSelection',
      'click .mailpoet_settings_posts_insert_selected': 'insertPosts',
    },
    templateHelpers: function() {
      return {
        model: this.model.toJSON(),
      };
    },
    initialize: function() {
      this.model.trigger('startEditing');
      this.selectionView = new PostSelectionSettingsView({ model: this.model });
      this.displayOptionsView = new PostsDisplayOptionsSettingsView({ model: this.model });
    },
    onRender: function() {
      var that = this,
        blockView = this.model.request('blockView');

      this.selectionRegion.show(this.selectionView);
      this.displayOptionsRegion.show(this.displayOptionsView);

      MailPoet.Modal.panel({
        element: this.$el,
        template: '',
        position: 'right',
        width: App.getConfig().get('sidepanelWidth'),
        onCancel: function() {
          // Self destroy the block if the user closes settings modal
          that.model.destroy();
        },
      });
    },
    switchToDisplayOptions: function() {
      // Switch content view
      this.$('.mailpoet_settings_posts_selection').addClass('mailpoet_closed');
      this.$('.mailpoet_settings_posts_display_options').removeClass('mailpoet_closed');

      // Switch controls
      this.$('.mailpoet_settings_posts_show_display_options').addClass('mailpoet_hidden');
      this.$('.mailpoet_settings_posts_show_post_selection').removeClass('mailpoet_hidden');
    },
    switchToPostSelection: function() {
      // Switch content view
      this.$('.mailpoet_settings_posts_display_options').addClass('mailpoet_closed');
      this.$('.mailpoet_settings_posts_selection').removeClass('mailpoet_closed');

      // Switch controls
      this.$('.mailpoet_settings_posts_show_post_selection').addClass('mailpoet_hidden');
      this.$('.mailpoet_settings_posts_show_display_options').removeClass('mailpoet_hidden');
    },
    insertPosts: function() {
      this.model.trigger('insertSelectedPosts');
      this.model.destroy();
      this.close();
    },
  });

  var PostSelectionSettingsView = Marionette.CompositeView.extend({
    getTemplate: function() { return templates.postSelectionPostsBlockSettings; },
    getChildView: function() { return SinglePostSelectionSettingsView; },
    childViewContainer: '.mailpoet_post_selection_container',
    getEmptyView: function() { return EmptyPostSelectionSettingsView; },
    childViewOptions: function() {
      return {
        blockModel: this.model,
      };
    },
    events: function() {
      return {
        'change .mailpoet_settings_posts_content_type': _.partial(this.changeField, 'contentType'),
        'change .mailpoet_posts_post_status': _.partial(this.changeField, 'postStatus'),
        'keyup .mailpoet_posts_search_term': _.partial(this.changeField, 'search'),
      };
    },
    constructor: function() {
      // Set the block collection to be handled by this view as well
      arguments[0].collection = arguments[0].model.get('_availablePosts');
      Marionette.CompositeView.apply(this, arguments);
    },
    onRender: function() {
      var that = this;

      // Dynamically update available post types
      CommunicationComponent.getPostTypes().done(_.bind(this._updateContentTypes, this));

      this.$('.mailpoet_posts_categories_and_tags').select2({
        multiple: true,
        allowClear: true,
        placeholder: MailPoet.I18n.t('categoriesAndTags'),
        ajax: {
          data: function (params) {
            return {
              term: params.term
            };
          },
          transport: function(options, success, failure) {
            var taxonomies,
                promise = CommunicationComponent.getTaxonomies(that.model.get('contentType')).then(function(tax) {
              taxonomies = tax;
              // Fetch available terms based on the list of taxonomies already fetched
              var promise = CommunicationComponent.getTerms({
                search: options.data.term,
                taxonomies: _.keys(taxonomies)
              }).then(function(terms) {
                return {
                  taxonomies: taxonomies,
                  terms: terms,
                };
              });
              return promise;
            });

            promise.then(success);
            promise.fail(failure);
            return promise;
          },
          processResults: function(data) {
            // Transform taxonomies and terms into select2 compatible format
            return {
              results: _.map(
                data.terms,
                function(item) {
                  return _.defaults({
                    text: data.taxonomies[item.taxonomy].labels.singular_name + ': ' + item.name,
                    id: item.term_id
                  }, item);
                }
              )
            };
          },
        },
      }).on({
        'select2:select': function(event) {
          var terms = that.model.get('terms');
          terms.add(event.params.data);
          // Reset whole model in order for change events to propagate properly
          that.model.set('terms', terms.toJSON());
        },
        'select2:unselect': function(event) {
          var terms = that.model.get('terms');
          terms.remove(event.params.data);
          // Reset whole model in order for change events to propagate properly
          that.model.set('terms', terms.toJSON());
        },
      }).trigger( 'change' );
    },
    changeField: function(field, event) {
      this.model.set(field, jQuery(event.target).val());
    },
    _updateContentTypes: function(postTypes) {
      var select = this.$('.mailpoet_settings_posts_content_type'),
          selectedValue = this.model.get('contentType');

      select.find('option').remove();
      _.each(postTypes, function(type) {
        select.append(jQuery('<option>', {
          value: type.name,
          text: type.label,
        }));
      });
      select.val(selectedValue);
    },
  });

  var EmptyPostSelectionSettingsView = Marionette.ItemView.extend({
    getTemplate: function() { return templates.emptyPostPostsBlockSettings; },
  });

  var SinglePostSelectionSettingsView = Marionette.ItemView.extend({
    getTemplate: function() { return templates.singlePostPostsBlockSettings; },
    events: function() {
      return {
        'change .mailpoet_select_post_checkbox': 'postSelectionChange',
      };
    },
    templateHelpers: function() {
      return {
        model: this.model.toJSON(),
        index: this._index,
      };
    },
    initialize: function(options) {
      this.blockModel = options.blockModel;
    },
    postSelectionChange: function(event) {
      var checkBox = jQuery(event.target),
        selectedPostsCollection = this.blockModel.get('_selectedPosts');
      if (checkBox.prop('checked')) {
        selectedPostsCollection.add(this.model);
      } else {
        selectedPostsCollection.remove(this.model);
      }
    },
  });

  var PostsDisplayOptionsSettingsView = base.BlockSettingsView.extend({
    getTemplate: function() { return templates.displayOptionsPostsBlockSettings; },
    events: function() {
      return {
        "click .mailpoet_posts_select_button": 'showButtonSettings',
        "click .mailpoet_posts_select_divider": 'showDividerSettings',
        "change .mailpoet_posts_read_more_type": 'changeReadMoreType',
        "change .mailpoet_posts_display_type": 'changeDisplayType',
        "change .mailpoet_posts_title_format": 'changeTitleFormat',
        "change .mailpoet_posts_title_as_links": _.partial(this.changeBoolField, 'titleIsLink'),
        "change .mailpoet_posts_show_divider": _.partial(this.changeBoolField, 'showDivider'),
        "keyup .mailpoet_posts_show_amount": _.partial(this.changeField, "amount"),
        "change .mailpoet_posts_content_type": _.partial(this.changeField, "contentType"),
        "change .mailpoet_posts_include_or_exclude": _.partial(this.changeField, "inclusionType"),
        "change .mailpoet_posts_title_alignment": _.partial(this.changeField, "titleAlignment"),
        "change .mailpoet_posts_image_full_width": _.partial(this.changeBoolField, "imageFullWidth"),
        "change .mailpoet_posts_featured_image_position": _.partial(this.changeField, "featuredImagePosition"),
        "change .mailpoet_posts_show_author": _.partial(this.changeField, "showAuthor"),
        "keyup .mailpoet_posts_author_preceded_by": _.partial(this.changeField, "authorPrecededBy"),
        "change .mailpoet_posts_show_categories": _.partial(this.changeField, "showCategories"),
        "keyup .mailpoet_posts_categories": _.partial(this.changeField, "categoriesPrecededBy"),
        "keyup .mailpoet_posts_read_more_text": _.partial(this.changeField, "readMoreText"),
        "change .mailpoet_posts_sort_by": _.partial(this.changeField, "sortBy"),
      };
    },
    templateHelpers: function() {
      return {
        model: this.model.toJSON(),
      };
    },
    showButtonSettings: function(event) {
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
    showDividerSettings: function(event) {
      var dividerModule = DividerBlock;
      (new dividerModule.DividerBlockSettingsView({
        model: this.model.get('divider'),
        renderOptions: {
          displayFormat: 'subpanel',
          hideApplyToAll: true,
        },
      })).render();
    },
    changeReadMoreType: function(event) {
      var value = jQuery(event.target).val();
      if (value == 'link') {
        this.$('.mailpoet_posts_read_more_text').removeClass('mailpoet_hidden');
        this.$('.mailpoet_posts_select_button').addClass('mailpoet_hidden');
      } else if (value == 'button') {
        this.$('.mailpoet_posts_read_more_text').addClass('mailpoet_hidden');
        this.$('.mailpoet_posts_select_button').removeClass('mailpoet_hidden');
      }
      this.changeField('readMoreType', event);
    },
    changeDisplayType: function(event) {
      var value = jQuery(event.target).val();
      if (value == 'titleOnly') {
        this.$('.mailpoet_posts_title_as_list').removeClass('mailpoet_hidden');
        this.$('.mailpoet_posts_image_full_width_option').addClass('mailpoet_hidden');
      } else {
        this.$('.mailpoet_posts_title_as_list').addClass('mailpoet_hidden');
        this.$('.mailpoet_posts_image_full_width_option').removeClass('mailpoet_hidden');

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
    changeTitleFormat: function(event) {
      var value = jQuery(event.target).val();
      if (value == 'ul') {
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
    getTemplate: function() { return templates.postsInsertion; },
    behaviors: {
      DraggableBehavior: {
        cloneOriginal: true,
        drop: function() {
          return new Module.PostsBlockModel({}, { parse: true });
        }
      }
    },
  });

  App.on('before:start', function() {
    App.registerBlockType('posts', {
      blockModel: Module.PostsBlockModel,
      blockView: Module.PostsBlockView,
    });

    App.registerWidget({
      name: 'posts',
      widgetView: Module.PostsWidgetView,
      priority: 96,
    });
  });

  return Module;
});
