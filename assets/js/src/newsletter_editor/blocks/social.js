/**
 * Social icons content block
 */
define('newsletter_editor/blocks/social', [
    'newsletter_editor/App',
    'backbone',
    'backbone.supermodel',
    'backbone.marionette',
    'mailpoet',
  ], function(EditorApplication, Backbone, SuperModel, Marionette, MailPoet) {

  EditorApplication.module("blocks.social", function(Module, App, Backbone, Marionette, $, _) {
      "use strict";

      var base = App.module('blocks.base'),
          SocialBlockSettingsIconSelectorView, SocialBlockSettingsIconView, SocialBlockSettingsStylesView;

      Module.SocialIconModel = SuperModel.extend({
          defaults: function() {
              var defaultValues = App.getConfig().get('socialIcons.custom');
              return {
                  type: 'socialIcon',
                  iconType: 'custom',
                  link: defaultValues.get('defaultLink'),
                  image: App.getAvailableStyles().get('socialIconSets.default.custom'),
                  height: '32px',
                  width: '32px',
                  text: defaultValues.get('title'),
              };
          },
          initialize: function(options) {
              var that = this;
              // Make model swap to default values for that type when iconType changes
              this.on('change:iconType', function() {
                  var defaultValues = App.getConfig().get('socialIcons').get(that.get('iconType')),
                      iconSet = that.collection.iconBlockModel.getIconSet();
                  this.set({
                      link: defaultValues.get('defaultLink'),
                      image: iconSet.get(that.get('iconType')),
                      text: defaultValues.get('title'),
                  });
              }, this);
              this.on('change', function() { App.getChannel().trigger('autoSave'); });
          },
      });

      Module.SocialIconCollectionModel = Backbone.Collection.extend({
          model: Module.SocialIconModel
      });

      Module.SocialBlockModel = base.BlockModel.extend({
          name: 'iconBlockModel',
          defaults: function() {
              return this._getDefaults({
                  type: 'social',
                  iconSet: 'default',
                  icons: new Module.SocialIconCollectionModel(),
              }, EditorApplication.getConfig().get('blockDefaults.social'));
          },
          relations: {
              icons: Module.SocialIconCollectionModel,
          },
          initialize: function() {
              this.get('icons').on('add remove change', this._iconsChanged, this);
              this.on('change:iconSet', this.changeIconSet, this);
          },
          getIconSet: function() {
              return App.getAvailableStyles().get('socialIconSets').get(this.get('iconSet'));
          },
          changeIconSet: function() {
              var iconSet = this.getIconSet();
              _.each(this.get('icons').models, function(model) {
                  model.set('image', iconSet.get(model.get('iconType')));
              });
          },
          _iconsChanged: function() {
              App.getChannel().trigger('autoSave'); 
          },
      });

      var SocialIconView = Marionette.ItemView.extend({
          tagName: 'span',
          getTemplate: function() { return templates.socialIconBlock; },
          modelEvents: {
              'change': 'render',
          },
          templateHelpers: function() {
              var allIconSets = App.getAvailableStyles().get('socialIconSets');
              return {
                  model: this.model.toJSON(),
                  allIconSets: allIconSets.toJSON(),
              };
          },
      });

      Module.SocialBlockView = Marionette.CompositeView.extend({
          regionClass: Marionette.Region,
          className: 'mailpoet_block mailpoet_social_block mailpoet_droppable_block',
          getTemplate: function() { return templates.socialBlock; },
          childViewContainer: '.mailpoet_social',
          modelEvents: {
              'change': 'render'
          },
          events: {
              "mouseover": "showTools",
              "mouseout": "hideTools",
          },
          regions: {
              toolsRegion: '> .mailpoet_tools',
          },
          ui: {
              tools: '> .mailpoet_tools'
          },
          behaviors: {
              DraggableBehavior: {
                  cloneOriginal: true,
                  hideOriginal: true,
                  onDrop: function(options) {
                      // After a clone of model has been dropped, cleanup
                      // and destroy self
                      options.dragBehavior.view.model.destroy();
                  },
                  onDragSubstituteBy: function(behavior) {
                      var WidgetView, node;
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
                  },
              },
          },
          onDragSubstituteBy: function() { return Module.SocialWidgetView; },
          constructor: function() {
              // Set the block collection to be handled by this view as well
              arguments[0].collection = arguments[0].model.get('icons');
              Marionette.CompositeView.apply(this, arguments);
          },
          // Determines which view type should be used for a child
          childView: SocialIconView,
          templateHelpers: function() {
              return {
                  model: this.model.toJSON(),
                  viewCid: this.cid,
              };
          },
          onRender: function() {
              this._rebuildRegions();
              this.toolsView = new Module.SocialBlockToolsView({ model: this.model });
              this.toolsRegion.show(this.toolsView);
          },
          onBeforeDestroy: function() {
              this.regionManager.destroy();
          },
          showTools: function(_event) {
              this.$(this.ui.tools).show();
              _event.stopPropagation();
          },
          hideTools: function(_event) {
              this.$(this.ui.tools).hide();
              _event.stopPropagation();
          },
          getDropFunc: function() {
              var that = this;
              return function() {
                  var newModel = that.model.clone();
                  //that.model.destroy();
                  return newModel;
              };
          },
          _buildRegions: function(regions) {
              var that = this;

              var defaults = {
                  regionClass: this.getOption('regionClass'),
                  parentEl: function() { return that.$el; }
              };

              return this.regionManager.addRegions(regions, defaults);
          },
          _rebuildRegions: function() {
              if (this.regionManager === undefined) {
                  this.regionManager = new Backbone.Marionette.RegionManager();
              }
              this.regionManager.destroy();
              _.extend(this, this._buildRegions(this.regions));
          },
      });

      Module.SocialBlockToolsView = base.BlockToolsView.extend({
          getSettingsView: function() { return Module.SocialBlockSettingsView; },
      });

      // Sidebar view container
      Module.SocialBlockSettingsView = base.BlockSettingsView.extend({
          getTemplate: function() { return templates.socialBlockSettings; },
          regions: {
              iconRegion: '#mailpoet_social_icons_selection',
              stylesRegion: '#mailpoet_social_icons_styles',
          },
          events: function() {
              return {
                  "click .mailpoet_done_editing": "close",
              };
          },
          initialize: function() {
              base.BlockSettingsView.prototype.initialize.apply(this, arguments);

              this._iconSelectorView = new SocialBlockSettingsIconSelectorView({ model: this.model });
              this._stylesView = new SocialBlockSettingsStylesView({ model: this.model });
          },
          onRender: function() {
              this.iconRegion.show(this._iconSelectorView);
              this.stylesRegion.show(this._stylesView);
          }
      });

      // Single icon settings view, used by the selector view
      SocialBlockSettingsIconView = Marionette.ItemView.extend({
          getTemplate: function() { return templates.socialSettingsIcon; },
          events: function() {
              return {
                  "click .mailpoet_delete_block": "deleteIcon",
                  "change .mailpoet_social_icon_field_type": _.partial(this.changeField, "iconType"),
                  "keyup .mailpoet_social_icon_field_image": _.partial(this.changeField, "image"),
                  "keyup .mailpoet_social_icon_field_link": this.changeLink,
                  "keyup .mailpoet_social_icon_field_text": _.partial(this.changeField, "text"),
              };
          },
          modelEvents: {
              'change:iconType': 'render',
              'change:image': function() {
                  this.$('.mailpoet_social_icon_image').attr('src', this.model.get('image'));
              },
              'change:text': function() {
                  this.$('.mailpoet_social_icon_image').attr('alt', this.model.get('text'));
              },
          },
          templateHelpers: function() {
              var icons = App.getConfig().get('socialIcons'),
                  // Construct icon type list of format [{iconType: 'type', title: 'Title'}, ...]
                  availableIconTypes = _.map(_.keys(icons.attributes), function(key) { return { iconType: key, title: icons.get(key).get('title') }; }),
                  allIconSets = App.getAvailableStyles().get('socialIconSets');
              return {
                  model: this.model.toJSON(),
                  iconTypes: availableIconTypes,
                  currentType: icons.get(this.model.get('iconType')).toJSON(),
                  allIconSets: allIconSets.toJSON(),
              };
          },
          deleteIcon: function() {
              this.model.destroy();
          },
          changeLink: function(event) {
              if (this.model.get('iconType') === 'email') {
                  this.model.set('link', 'mailto:' + jQuery(event.target).val());
              } else {
                  return this.changeField('link', event);
              }
          },
          changeField: function(field, event) {
              this.model.set(field, jQuery(event.target).val());
          },
      });

      // Select icons section container view
      SocialBlockSettingsIconSelectorView = Marionette.CompositeView.extend({
          getTemplate: function() { return templates.socialSettingsIconSelector; },
          childView: SocialBlockSettingsIconView,
          childViewContainer: '#mailpoet_social_icon_selector_contents',
          events: {
              'click .mailpoet_add_social_icon': 'addSocialIcon',
          },
          modelEvents: {
              'change:iconSet': 'render',
          },
          behaviors: {
              SortableBehavior: {
                  items: '#mailpoet_social_icon_selector_contents > div',
              },
          },
          constructor: function() {
              // Set the icon collection to be handled by this view as well
              arguments[0].collection = arguments[0].model.get('icons');
              Marionette.CompositeView.apply(this, arguments);
          },
          addSocialIcon: function() {
              // Add a social icon with default values
              this.collection.add({});
          }
      });

      SocialBlockSettingsStylesView = Marionette.ItemView.extend({
          getTemplate: function() { return templates.socialSettingsStyles; },
          modelEvents: {
              'change': 'render',
          },
          events: {
              'click .mailpoet_social_icon_set': 'changeSocialIconSet',
          },
          initialize: function() {
              this.listenTo(this.model.get('icons'), 'add remove change', this.render);
          },
          templateHelpers: function() {
              var allIconSets = App.getAvailableStyles().get('socialIconSets');
              return {
                  activeSet: this.model.get('iconSet'),
                  socialIconSets: allIconSets.toJSON(),
                  availableSets: _.keys(allIconSets.toJSON()),
                  availableSocialIcons: this.model.get('icons').pluck('iconType'),
              };
          },
          changeSocialIconSet: function(event) {
              this.model.set('iconSet', jQuery(event.currentTarget).data('setname'));
          },
          onBeforeDestroy: function() {
              this.model.get('icons').off('add remove', this.render, this);
          },
      });

      Module.SocialWidgetView = base.WidgetView.extend({
          getTemplate: function() { return templates.socialInsertion; },
          behaviors: {
              DraggableBehavior: {
                  cloneOriginal: true,
                  drop: function() {
                      return new Module.SocialBlockModel({
                          type: 'social',
                          iconSet: 'default',
                          icons: [
                              {
                                  type: 'socialIcon',
                                  iconType: 'facebook',
                                  link: 'http://example.com',
                                  image: App.getAvailableStyles().get('socialIconSets.default.facebook'),
                                  height: '32px',
                                  width: '32px',
                                  text: 'Facebook',
                              },
                              {
                                  type: 'socialIcon',
                                  iconType: 'twitter',
                                  link: 'http://example.com',
                                  image: App.getAvailableStyles().get('socialIconSets.default.twitter'),
                                  height: '32px',
                                  width: '32px',
                                  text: 'Twitter',
                              },
                          ],
                      }, { parse: true });
                  }
              }
          },
      });

      App.on('before:start', function() {
          App.registerBlockType('social', {
              blockModel: Module.SocialBlockModel,
              blockView: Module.SocialBlockView,
          });

          App.registerWidget({
              name: 'social',
              widgetView: Module.SocialWidgetView,
              priority: 95,
          });
      });
  });

});
