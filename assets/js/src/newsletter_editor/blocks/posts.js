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
    'newsletter_editor/components/wordpress',
    'newsletter_editor/blocks/base',
    'newsletter_editor/blocks/button',
    'newsletter_editor/blocks/divider',
  ], function(Backbone, Marionette, Radio, _, jQuery, MailPoet, App, WordpressComponent, BaseBlock, ButtonBlock, DividerBlock) {

  "use strict";

  var Module = {},
      base = BaseBlock;

  Module.PostsBlockModel = base.BlockModel.extend({
    stale: ['_selectedPosts', '_availablePosts'],
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
        titlePosition: 'inTextBlock', // 'inTextBlock'|'aboveBlock',
        titleAlignment: 'left', // 'left'|'center'|'right'
        titleIsLink: false, // false|true
        imagePadded: true, // true|false
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
      }, App.getConfig().get('blockDefaults.posts'));
    },
    relations: function() {
      return {
        readMoreButton: App.getBlockTypeModel('button'),
        divider: App.getBlockTypeModel('divider'),
        _selectedPosts: Backbone.Collection,
        _availablePosts: Backbone.Collection,
      };
    },
    initialize: function() {
      var that = this;
      // Attach Radio.Requests API primarily for highlighting
      _.extend(this, Radio.Requests);

      this.fetchAvailablePosts();
      this.on('change:amount change:contentType change:terms change:inclusionType change:postStatus change:search change:sortBy', this._scheduleFetchAvailablePosts, this);
      this.on('insertSelectedPosts', this._insertSelectedPosts, this);
    },
    fetchAvailablePosts: function() {
      var that = this;
      WordpressComponent.getPosts(this.toJSON()).done(function(posts) {
        console.log('Posts fetched', arguments);
        that.get('_availablePosts').reset(posts);
        that.get('_selectedPosts').reset(); // Empty out the collection
        that.trigger('change:_availablePosts');
      }).fail(function() {
        console.log('Posts fetchPosts error', arguments);
      });
    },
    /**
     * Batch more changes during a specific time, instead of fetching
     * ALC posts on each model change
     */
    _scheduleFetchAvailablePosts: function() {
      var timeout = 500,
        that = this;
      if (this._fetchPostsTimer !== undefined) {
        clearTimeout(this._fetchPostsTimer);
      }
      this._fetchPostsTimer = setTimeout(function() {
        that.fetchAvailablePosts();
        that._fetchPostsTimer = undefined;
      }, timeout);
    },
    _insertSelectedPosts: function() {
      var that = this,
        data = this.toJSON(),
        index = this.collection.indexOf(this),
        collection = this.collection;

      data.posts = this.get('_selectedPosts').pluck('ID');

      if (data.posts.length === 0) return;

      WordpressComponent.getTransformedPosts(data).done(function(posts) {
        console.log('Available posts fetched', arguments);
        collection.add(posts, { at: index });
      }).fail(function() {
        console.log('Posts fetchPosts error', arguments);
      });
      // TODO: Move query logic to new AJAX format
      //mailpoet_post_wpi('automated_latest_content.php', data, function(response) {
        //console.log('Available posts fetched', arguments);
        //collection.add(response, { at: index });
      //}, function() {
        //console.log('Posts fetchPosts error', arguments);
      //});
    },
  });

  Module.PostsBlockView = base.BlockView.extend({
    className: "mailpoet_block mailpoet_posts_block mailpoet_droppable_block",
    getTemplate: function() { return templates.postsBlock; },
    modelEvents: {},
    onDragSubstituteBy: function() { return Module.PostsWidgetView; },
    initialize: function() {
      this.toolsView = new Module.PostsBlockToolsView({ model: this.model });
      this.on('showSettings', this.showSettings);
      this.model.reply('blockView', this.notifyAboutSelf, this);
    },
    onRender: function() {
      if (!this.toolsRegion.hasView()) {
        this.toolsRegion.show(this.toolsView);
      }
      this.trigger('showSettings');
    },
    showSettings: function(options) {
      this.toolsView.triggerMethod('showSettings', options);
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
    initialize: function() {
      base.BlockToolsView.prototype.initialize.apply(this, arguments);
      this.on('showSettings', this.changeSettings);
      this.settingsView = new Module.PostsBlockSettingsView({ model: this.model });
    },
    changeSettings: function() {
      this.settingsView.render();
    },
    onBeforeDestroy: function() {
      this.settingsView.destroy();
      this.off('showSettings');
      MailPoet.Modal.close();
    },
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
        overlay: true,
        highlight: blockView.$el,
        width: App.getConfig().get('sidepanelWidth'),
        onCancel: function() {
          // Self destroy the block if the user closes settings modal
          that.model.destroy();
        },
      });
    },
    switchToDisplayOptions: function() {
      // Switch content view
      this.$('.mailpoet_settings_posts_selection').addClass('mailpoet_hidden');
      this.$('.mailpoet_settings_posts_display_options').removeClass('mailpoet_hidden');

      // Switch controls
      this.$('.mailpoet_settings_posts_show_display_options').addClass('mailpoet_hidden');
      this.$('.mailpoet_settings_posts_show_post_selection').removeClass('mailpoet_hidden');
    },
    switchToPostSelection: function() {
      // Switch content view
      this.$('.mailpoet_settings_posts_display_options').addClass('mailpoet_hidden');
      this.$('.mailpoet_settings_posts_selection').removeClass('mailpoet_hidden');

      // Switch controls
      this.$('.mailpoet_settings_posts_show_post_selection').addClass('mailpoet_hidden');
      this.$('.mailpoet_settings_posts_show_display_options').removeClass('mailpoet_hidden');
    },
    insertPosts: function() {
      this.model.trigger('insertSelectedPosts');
      this.model.destroy();
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
      WordpressComponent.getPostTypes().done(_.bind(this._updateContentTypes, this));

      this.$('.mailpoet_posts_categories_and_tags').select2({
        multiple: true,
        allowClear: true,
        query: function(options) {
          var taxonomies = [];
          // Delegate data loading to our own endpoints
          WordpressComponent.getTaxonomies(that.model.get('contentType')).then(function(tax) {
            taxonomies = tax;
            // Fetch available terms based on the list of taxonomies already fetched
            var promise = WordpressComponent.getTerms({
              search: options.term,
              taxonomies: _.keys(taxonomies)
            }).then(function(terms) {
              return {
                taxonomies: taxonomies,
                terms: terms,
              };
            });
            return promise;
          }).done(function(args) {
            // Transform taxonomies and terms into select2 compatible format
            options.callback({
              results: _.map(
                args.terms,
                function(item) {
                  return _.defaults({
                    text: args.taxonomies[item.taxonomy].labels.singular_name + ': ' + item.name,
                    id: item.term_id
                  }, item);
                }
              )
            });
          });
        },
        //ajax: {
          //url: App.getConfig().get('urls.termSearch'),
          //type: 'POST',
          //dataType: 'json',
          //delay: 250,
          //data: function(searchParameter, page) {
            //return JSON.stringify({
              //postType: that.model.get('contentType'),
              //search: searchParameter,
              //limit: 10, // TODO: Move this hardcoded limit to Config
              //page: page,
            //});
          //},
          /**
           * Parse results for select2.
           * Returns object, where `results` key holds a list of
           * select item objects
           */
          //results: function (data, page) {
            //return {
              //results: _.map(
                //data.results,
                //function(item) {
                  //return _.defaults({
                    //text: data.taxonomies[item.taxonomy].labels.singular_name + ': ' + item.name,
                    //id: item.term_id
                  //}, item);
                //}
              //)
            //};
          //}
        //},
      }).trigger( 'change' ).on({
        'change': function(e){
          var data = [];

          if (typeof data === 'string') {
            if (data === '') {
              data = [];
            } else {
              data = JSON.parse(data);
            }
          }

          if ( e.added ){
            data.push(e.added);
          }

          // Update ALC model
          that.model.set('terms', data);

          jQuery(this).data('selected', JSON.stringify(data));
        }
      });
    },
    onBeforeDestroy: function() {
      // Force close select2 if it hasn't closed yet
      this.$('.mailpoet_posts_categories_and_tags').select2('close');
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
          text: type.labels.singular_name,
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
        "change .mailpoet_posts_title_position": _.partial(this.changeField, "titlePosition"),
        "change .mailpoet_posts_title_alignment": _.partial(this.changeField, "titleAlignment"),
        "change .mailpoet_posts_image_padded": _.partial(this.changeBoolField, "imagePadded"),
        "change .mailpoet_posts_show_author": _.partial(this.changeField, "showAuthor"),
        "keyup .mailpoet_posts_author_preceded_by": _.partial(this.changeField, "authorPrecededBy"),
        "change .mailpoet_posts_show_categories": _.partial(this.changeField, "showCategories"),
        "keyup .mailpoet_posts_categories": _.partial(this.changeField, "categoriesPrecededBy"),
        "keyup .mailpoet_posts_read_more_text": _.partial(this.changeField, "readMoreText"),
        "change .mailpoet_posts_sort_by": _.partial(this.changeField, "sortBy"),
      };
    },
    behaviors: {
      ColorPickerBehavior: {},
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
        this.$('.mailpoet_posts_title_position_container').addClass('mailpoet_hidden');
        this.$('.mailpoet_posts_title_as_list').removeClass('mailpoet_hidden');
      } else {
        this.$('.mailpoet_posts_title_position_container').removeClass('mailpoet_hidden');
        this.$('.mailpoet_posts_title_as_list').addClass('mailpoet_hidden');

        // Reset titleFormat if it was set to List when switching away from displayType=titleOnly
        if (this.model.get('titleFormat') === 'ul') {
          this.model.set('titleFormat', 'h1');
          this.$('.mailpoet_posts_title_format').val(['h1']);
          this.$('.mailpoet_posts_title_as_link').removeClass('mailpoet_hidden');
        }
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
