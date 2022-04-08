/* eslint-disable func-names */
import App from 'newsletter_editor/App';
import Backbone from 'backbone';
import Marionette from 'backbone.marionette';
import SuperModel from 'backbone.supermodel';
import _ from 'underscore';
import jQuery from 'jquery';

var Module = {};
var SidebarView;
// Widget handlers for use to create new content blocks via drag&drop
Module._contentWidgets = new (Backbone.Collection.extend({
  model: SuperModel.extend({
    defaults: {
      name: '',
      priority: 100,
      widgetView: undefined,
    },
  }),
  comparator: 'priority',
}))();
Module.registerWidget = function (widget) {
  var hiddenWidgets = App.getConfig().get('hiddenWidgets');
  if (hiddenWidgets && hiddenWidgets.includes(widget.name)) {
    return false;
  }
  return Module._contentWidgets.add(widget);
};
Module.getWidgets = function () {
  return Module._contentWidgets;
};

// Layout widget handlers for use to create new layout blocks via drag&drop
Module._layoutWidgets = new (Backbone.Collection.extend({
  model: SuperModel.extend({
    defaults: {
      name: '',
      priority: 100,
      widgetView: undefined,
    },
  }),
  comparator: 'priority',
}))();
Module.registerLayoutWidget = function (widget) {
  return Module._layoutWidgets.add(widget);
};
Module.getLayoutWidgets = function () {
  return Module._layoutWidgets;
};

SidebarView = Marionette.View.extend({
  getTemplate: function () {
    return window.templates.sidebar;
  },
  regions: {
    contentRegion: '.mailpoet_content_region',
    layoutRegion: '.mailpoet_layout_region',
    stylesRegion: '.mailpoet_styles_region',
    previewRegion: '.mailpoet_preview_region',
  },
  events: {
    'click .mailpoet_sidebar_region h3, .mailpoet_sidebar_region .handlediv':
      function (event) {
        var $openRegion = this.$el.find(
          '.mailpoet_sidebar_region:not(.closed)',
        );
        var $targetRegion = this.$el
          .find(event.target)
          .closest('.mailpoet_sidebar_region');

        $openRegion.find('.mailpoet_region_content').velocity('slideUp', {
          duration: 250,
          easing: 'easeOut',
          complete: function () {
            $openRegion.addClass('closed');
          },
        });

        if ($openRegion.get(0) !== $targetRegion.get(0)) {
          $targetRegion.find('.mailpoet_region_content').velocity('slideDown', {
            duration: 250,
            easing: 'easeIn',
            complete: function () {
              $targetRegion.removeClass('closed');
            },
          });
        }
      },
  },
  templateContext: function () {
    return {
      isWoocommerceTransactional: this.model.isWoocommerceTransactional(),
    };
  },
  initialize: function () {
    jQuery(window)
      .on('resize', this.updateHorizontalScroll.bind(this))
      .on('scroll', this.updateHorizontalScroll.bind(this));
  },
  onRender: function () {
    this.showChildView(
      'contentRegion',
      new Module.SidebarWidgetsView(App.getWidgets()),
    );
    this.showChildView(
      'layoutRegion',
      new Module.SidebarLayoutWidgetsView(App.getLayoutWidgets()),
    );
    this.showChildView(
      'stylesRegion',
      new Module.SidebarStylesView({
        model: App.getGlobalStyles(),
        availableStyles: App.getAvailableStyles(),
        isWoocommerceTransactional: this.model.isWoocommerceTransactional(),
      }),
    );
  },
  updateHorizontalScroll: function () {
    // Fixes the sidebar so that on narrower screens the horizontal
    // position of the sidebar would be scrollable and not fixed
    // partially out of visible screen
    this.$el.parent().each(function () {
      var calculatedLeft;
      var self = jQuery(this);

      if (self.css('position') === 'fixed') {
        calculatedLeft =
          self.parent().offset().left - jQuery(window).scrollLeft();
        self.css('left', calculatedLeft + 'px');
      } else {
        self.css('left', '');
      }
    });
  },
  onDomRefresh: function () {
    this.$el.parent().stick_in_parent({
      offset_top: 32,
    });
    this.$el
      .parent()
      .on('sticky_kit:stick', this.updateHorizontalScroll.bind(this));
    this.$el
      .parent()
      .on('sticky_kit:unstick', this.updateHorizontalScroll.bind(this));
    this.$el
      .parent()
      .on('sticky_kit:bottom', this.updateHorizontalScroll.bind(this));
    this.$el
      .parent()
      .on('sticky_kit:unbottom', this.updateHorizontalScroll.bind(this));
  },
});

/**
 * Draggable widget collection view
 */
Module.SidebarWidgetsCollectionView = Marionette.CollectionView.extend({
  childView: function (item) {
    return item.get('widgetView');
  },
});

/**
 * Responsible for rendering draggable content widgets
 */
Module.SidebarWidgetsView = Marionette.View.extend({
  getTemplate: function () {
    return window.templates.sidebarContent;
  },
  regions: {
    widgets: '.mailpoet_region_content',
  },

  initialize: function (widgets) {
    this.widgets = widgets;
  },

  onRender: function () {
    this.showChildView(
      'widgets',
      new Module.SidebarWidgetsCollectionView({
        collection: this.widgets,
      }),
    );
  },
});

/**
 * Responsible for rendering draggable layout widgets
 */
Module.SidebarLayoutWidgetsView = Module.SidebarWidgetsView.extend({
  getTemplate: function () {
    return window.templates.sidebarLayout;
  },
});

/**
 * Responsible for managing global styles
 */
Module.SidebarStylesView = Marionette.View.extend({
  getTemplate: function () {
    return window.templates.sidebarStyles;
  },
  behaviors: {
    ColorPickerBehavior: {},
    WooCommerceStylesBehavior: {},
  },
  events: function () {
    return {
      'change #mailpoet_text_font_color': _.partial(
        this.changeColorField,
        'text.fontColor',
      ),
      'change #mailpoet_text_font_family': function (event) {
        this.model.set('text.fontFamily', event.target.value);
      },
      'change #mailpoet_text_font_size': function (event) {
        this.model.set('text.fontSize', event.target.value);
      },
      'change #mailpoet_h1_font_color': _.partial(
        this.changeColorField,
        'h1.fontColor',
      ),
      'change #mailpoet_h1_font_family': function (event) {
        this.model.set('h1.fontFamily', event.target.value);
      },
      'change #mailpoet_h1_font_size': function (event) {
        this.model.set('h1.fontSize', event.target.value);
      },
      'change #mailpoet_h2_font_color': _.partial(
        this.changeColorField,
        'h2.fontColor',
      ),
      'change #mailpoet_h2_font_family': function (event) {
        this.model.set('h2.fontFamily', event.target.value);
      },
      'change #mailpoet_h2_font_size': function (event) {
        this.model.set('h2.fontSize', event.target.value);
      },
      'change #mailpoet_h3_font_color': _.partial(
        this.changeColorField,
        'h3.fontColor',
      ),
      'change #mailpoet_h3_font_family': function (event) {
        this.model.set('h3.fontFamily', event.target.value);
      },
      'change #mailpoet_h3_font_size': function (event) {
        this.model.set('h3.fontSize', event.target.value);
      },
      'change #mailpoet_a_font_color': _.partial(
        this.changeColorField,
        'link.fontColor',
      ),
      'change #mailpoet_a_font_underline': function (event) {
        this.model.set(
          'link.textDecoration',
          event.target.checked ? event.target.value : 'none',
        );
      },
      'change #mailpoet_text_line_height': function (event) {
        this.model.set('text.lineHeight', event.target.value);
      },
      'change #mailpoet_heading_line_height': function (event) {
        this.model.set('h1.lineHeight', event.target.value);
        this.model.set('h2.lineHeight', event.target.value);
        this.model.set('h3.lineHeight', event.target.value);
      },
      'change #mailpoet_newsletter_background_color': _.partial(
        this.changeColorField,
        'wrapper.backgroundColor',
      ),
      'change #mailpoet_background_color': _.partial(
        this.changeColorField,
        'body.backgroundColor',
      ),
    };
  },
  templateContext: function () {
    return {
      model: this.model.toJSON(),
      availableStyles: this.availableStyles.toJSON(),
      isWoocommerceTransactional: this.isWoocommerceTransactional,
    };
  },
  initialize: function (options) {
    this.availableStyles = options.availableStyles;
    this.isWoocommerceTransactional = options.isWoocommerceTransactional;
    App.getChannel().on('historyUpdate', this.render);
  },
  changeField: function (field, event) {
    this.model.set(field, jQuery(event.target).val());
  },
  changeColorField: function (field, event) {
    var value = jQuery(event.target).val();
    if (value === '') {
      value = 'transparent';
    }
    this.model.set(field, value);
  },
});

App.on('before:start', function (BeforeStartApp) {
  var Application = BeforeStartApp;
  Application.registerWidget = Module.registerWidget;
  Application.getWidgets = Module.getWidgets;
  Application.registerLayoutWidget = Module.registerLayoutWidget;
  Application.getLayoutWidgets = Module.getLayoutWidgets;
});

App.on('start', function (StartApp) {
  var sidebarView = new SidebarView({
    model: StartApp.getNewsletter(),
  });

  StartApp._appView.showChildView('sidebarRegion', sidebarView);
});

export default Module;
