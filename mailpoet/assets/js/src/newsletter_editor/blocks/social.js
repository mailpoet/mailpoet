/* eslint-disable func-names */
/**
 * Social icons content block
 */
import { App } from 'newsletter_editor/App';
import { BaseBlock } from 'newsletter_editor/blocks/base';
import Backbone from 'backbone';
import Marionette from 'backbone.marionette';
import SuperModel from 'backbone.supermodel';
import _ from 'underscore';
import jQuery from 'jquery';
import { validateField } from '../utils';

var Module = {};
var base = BaseBlock;
var SocialBlockSettingsIconSelectorView;
var SocialBlockSettingsIconView;
var SocialBlockSettingsIconCollectionView;
var SocialBlockSettingsStylesView;
var SocialIconView;

Module.SocialIconModel = SuperModel.extend({
  defaults: function () {
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
  initialize: function () {
    var that = this;
    // Make model swap to default values for that type when iconType changes
    this.on(
      'change:iconType',
      function () {
        var defaultValues = App.getConfig()
          .get('socialIcons')
          .get(that.get('iconType'));
        var iconSet = that.collection.iconBlockModel.getIconSet();
        this.set({
          link: defaultValues.get('defaultLink'),
          image: iconSet.get(that.get('iconType')),
          text: defaultValues.get('title'),
        });
      },
      this,
    );
    this.on('change', function () {
      App.getChannel().trigger('autoSave');
    });
  },
});

Module.SocialIconCollectionModel = Backbone.Collection.extend({
  model: Module.SocialIconModel,
});

Module.SocialBlockModel = base.BlockModel.extend({
  name: 'iconBlockModel',
  defaults: function () {
    return this._getDefaults(
      {
        type: 'social',
        iconSet: 'default',
        styles: {
          block: {
            textAlign: 'center',
          },
        },
        icons: new Module.SocialIconCollectionModel(),
      },
      App.getConfig().get('blockDefaults.social'),
    );
  },
  relations: {
    icons: Module.SocialIconCollectionModel,
  },
  initialize: function () {
    this.get('icons').on('add remove change', this._iconsChanged, this);
    this.on('change:iconSet', this.changeIconSet, this);
    this.on('change', this._updateDefaults, this);
  },
  getIconSet: function () {
    return App.getAvailableStyles()
      .get('socialIconSets')
      .get(this.get('iconSet'));
  },
  changeIconSet: function () {
    var iconSet = this.getIconSet();
    _.each(this.get('icons').models, function (model) {
      model.set('image', iconSet.get(model.get('iconType')));
    });
  },
  _iconsChanged: function () {
    this._updateDefaults();
    App.getChannel().trigger('autoSave');
  },
});

SocialIconView = Marionette.View.extend({
  tagName: 'span',
  getTemplate: function () {
    return window.templates.socialIconBlock;
  },
  modelEvents: {
    change: 'render',
  },
  templateContext: function () {
    var allIconSets = App.getAvailableStyles().get('socialIconSets');
    return {
      model: this.model.toJSON(),
      allIconSets: allIconSets.toJSON(),
      imageMissingSrc: App.getConfig().get('urls.imageMissing'),
    };
  },
});

Module.SocialIconCollectionView = Marionette.CollectionView.extend({
  childView: SocialIconView,
});

Module.SocialBlockView = base.BlockView.extend({
  className: 'mailpoet_block mailpoet_social_block mailpoet_droppable_block',
  getTemplate: function () {
    return window.templates.socialBlock;
  },
  regions: _.extend({}, base.BlockView.prototype.regions, {
    icons: '.mailpoet_social',
  }),
  ui: {
    tools: '> .mailpoet_tools',
  },
  behaviors: _.extend({}, base.BlockView.prototype.behaviors, {
    ShowSettingsBehavior: {},
  }),
  onDragSubstituteBy: function () {
    return Module.SocialWidgetView;
  },
  onRender: function () {
    this.toolsView = new Module.SocialBlockToolsView({ model: this.model });
    this.showChildView('toolsRegion', this.toolsView);
    this.showChildView(
      'icons',
      new Module.SocialIconCollectionView({
        collection: this.model.get('icons'),
      }),
    );
  },
});

Module.SocialBlockToolsView = base.BlockToolsView.extend({
  getSettingsView: function () {
    return Module.SocialBlockSettingsView;
  },
});

// Sidebar view container
Module.SocialBlockSettingsView = base.BlockSettingsView.extend({
  getTemplate: function () {
    return window.templates.socialBlockSettings;
  },
  regions: {
    iconRegion: '#mailpoet_social_icons_selection',
    stylesRegion: '#mailpoet_social_icons_styles',
  },
  events: function () {
    return {
      'click .mailpoet_done_editing': 'close',
      'change .mailpoet_social_block_alignment': _.partial(
        this.changeField,
        'styles.block.textAlign',
      ),
    };
  },
  initialize: function () {
    base.BlockSettingsView.prototype.initialize.apply(this, arguments);

    this._iconSelectorView = new SocialBlockSettingsIconSelectorView({
      model: this.model,
    });
    this._stylesView = new SocialBlockSettingsStylesView({ model: this.model });
  },
  onRender: function () {
    this.showChildView('iconRegion', this._iconSelectorView);
    this.showChildView('stylesRegion', this._stylesView);
  },
});

// Single icon settings view, used by the selector view
SocialBlockSettingsIconView = Marionette.View.extend({
  getTemplate: function () {
    return window.templates.socialSettingsIcon;
  },
  events: function () {
    return {
      'click .mailpoet_delete_block': 'deleteIcon',
      'change .mailpoet_social_icon_field_type': _.partial(
        this.changeField,
        'iconType',
      ),
      'input .mailpoet_social_icon_field_image': _.partial(
        this.changeField,
        'image',
      ),
      'input .mailpoet_social_icon_field_link': this.changeLink,
      'input .mailpoet_social_icon_field_text': _.partial(
        this.changeField,
        'text',
      ),
    };
  },
  modelEvents: {
    'change:iconType': 'render',
    'change:image': function () {
      this.$('.mailpoet_social_icon_image').attr(
        'src',
        this.model.get('image'),
      );
    },
    'change:text': function () {
      this.$('.mailpoet_social_icon_image').attr('alt', this.model.get('text'));
    },
  },
  templateContext: function () {
    var icons = App.getConfig().get('socialIcons');
    // Construct icon type list of format [{iconType: 'type', title: 'Title'}, ...]
    var availableIconTypes = _.map(_.keys(icons.attributes), function (key) {
      return { iconType: key, title: icons.get(key).get('title') };
    });
    var allIconSets = App.getAvailableStyles().get('socialIconSets');
    return _.extend(
      {},
      base.BlockView.prototype.templateContext.apply(this, arguments),
      {
        iconTypes: availableIconTypes,
        currentType: icons.get(this.model.get('iconType')).toJSON(),
        allIconSets: allIconSets.toJSON(),
      },
    );
  },
  deleteIcon: function () {
    this.model.destroy();
  },
  changeLink: function (event) {
    if (!validateField(event.target)) {
      return;
    }
    if (this.model.get('iconType') === 'email') {
      this.model.set('link', 'mailto:' + jQuery(event.target).val());
    } else {
      this.changeField('link', event);
    }
  },
  changeField: function (field, event) {
    if (!validateField(event.target)) {
      return;
    }
    this.model.set(field, jQuery(event.target).val());
  },
});

SocialBlockSettingsIconCollectionView = Marionette.CollectionView.extend({
  behaviors: {
    SortableBehavior: {
      items: '> div',
    },
  },
  childViewContainer: '#mailpoet_social_icon_selector_contents',
  childView: SocialBlockSettingsIconView,
});

// Select icons section container view
SocialBlockSettingsIconSelectorView = Marionette.View.extend({
  getTemplate: function () {
    return window.templates.socialSettingsIconSelector;
  },
  regions: {
    icons: '#mailpoet_social_icon_selector_contents',
  },
  events: {
    'click .mailpoet_add_social_icon': 'addSocialIcon',
  },
  modelEvents: {
    'change:iconSet': 'render',
  },
  addSocialIcon: function () {
    // Add a social icon with default values
    this.model.get('icons').add({});
  },
  onRender: function () {
    this.showChildView(
      'icons',
      new SocialBlockSettingsIconCollectionView({
        collection: this.model.get('icons'),
      }),
    );
  },
});

SocialBlockSettingsStylesView = Marionette.View.extend({
  getTemplate: function () {
    return window.templates.socialSettingsStyles;
  },
  modelEvents: {
    change: 'render',
  },
  events: {
    'click .mailpoet_social_icon_set': 'changeSocialIconSet',
  },
  initialize: function () {
    this.listenTo(this.model.get('icons'), 'add remove change', this.render);
  },
  templateContext: function () {
    var allIconSets = App.getAvailableStyles().get('socialIconSets');
    return {
      activeSet: this.model.get('iconSet'),
      socialIconSets: allIconSets.toJSON(),
      availableSets: _.keys(allIconSets.toJSON()),
      availableSocialIcons: this.model.get('icons').pluck('iconType'),
    };
  },
  changeSocialIconSet: function (event) {
    this.model.set('iconSet', jQuery(event.currentTarget).data('setname'));
  },
  onBeforeDestroy: function () {
    this.model.get('icons').off('add remove', this.render, this);
  },
});

Module.SocialWidgetView = base.WidgetView.extend({
  id: 'automation_editor_block_social',
  getTemplate: function () {
    return window.templates.socialInsertion;
  },
  behaviors: {
    DraggableBehavior: {
      cloneOriginal: true,
      drop: function () {
        return new Module.SocialBlockModel();
      },
    },
  },
});

App.on('before:start', function (BeforeStartApp) {
  BeforeStartApp.registerBlockType('social', {
    blockModel: Module.SocialBlockModel,
    blockView: Module.SocialBlockView,
  });

  BeforeStartApp.registerWidget({
    name: 'social',
    widgetView: Module.SocialWidgetView,
    priority: 95,
  });
});

export { Module as SocialBlock };
