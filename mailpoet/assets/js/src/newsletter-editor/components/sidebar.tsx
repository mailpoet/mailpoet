/* eslint-disable func-names */
import Backbone, { Collection } from 'backbone';
import Marionette from 'backbone.marionette';
import SuperModel from 'backbone.supermodel';
import _ from 'underscore';
import jQuery from 'jquery';
import { createRoot } from 'react-dom/client';

import { App } from 'newsletter-editor/app';
import { MailPoet } from 'mailpoet';
import { BrandStyles } from '../blocks/sidebar/brand-styles';
import { getBrandStylesSettings } from '../utils';

type ModuleType = Record<string, (...args: unknown[]) => void> & {
  _contentWidgets: Collection;
  _layoutWidgets: Collection;
  SidebarWidgetsView: typeof Marionette.View;
};
type WidgetType = {
  name: string;
  widgetView: typeof Marionette.View;
  priority: null;
};
const Module: ModuleType = {} as ModuleType;
// Widget handlers for use to create new content blocks via drag&drop
// eslint-disable-next-line no-underscore-dangle
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
Module.registerWidget = (widget: WidgetType) => {
  const hiddenWidgets = App.getConfig().get('hiddenWidgets');
  if (hiddenWidgets && hiddenWidgets.includes(widget.name)) {
    return false;
  }
  // eslint-disable-next-line no-underscore-dangle,@typescript-eslint/no-unsafe-return
  return Module._contentWidgets.add(widget) as Collection;
};
Module.getWidgets = function () {
  // eslint-disable-next-line no-underscore-dangle,@typescript-eslint/no-unsafe-return
  return Module._contentWidgets;
};

// Layout widget handlers for use to create new layout blocks via drag&drop
// eslint-disable-next-line no-underscore-dangle
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
  // eslint-disable-next-line no-underscore-dangle,@typescript-eslint/no-unsafe-return
  return Module._layoutWidgets.add(widget);
};
Module.getLayoutWidgets = function () {
  // eslint-disable-next-line no-underscore-dangle,@typescript-eslint/no-unsafe-return
  return Module._layoutWidgets;
};

const SidebarView = Marionette.View.extend({
  getTemplate: () => window.templates.sidebar,
  regions: {
    contentRegion: '.mailpoet_content_region',
    layoutRegion: '.mailpoet_layout_region',
    stylesRegion: '.mailpoet_styles_region',
    previewRegion: '.mailpoet_preview_region',
  },
  events: {
    'click .mailpoet_sidebar_region h3, .mailpoet_sidebar_region .handlediv':
      function (event) {
        const $openRegion: JQuery = this.$el.find(
          '.mailpoet_sidebar_region:not(.closed)',
        );
        const $targetRegion: JQuery = this.$el
          .find(event.target)
          .closest('.mailpoet_sidebar_region');

        $openRegion.find('.mailpoet_region_content').velocity('slideUp', {
          duration: 250,
          easing: 'easeOut',
          complete: () => {
            $openRegion.addClass('closed');
          },
        });

        if ($openRegion.get(0) !== $targetRegion.get(0)) {
          $targetRegion.find('.mailpoet_region_content').velocity('slideDown', {
            duration: 250,
            easing: 'easeIn',
            complete: () => {
              $targetRegion.removeClass('closed');
            },
          });
        }
      },
  },
  templateContext() {
    return {
      isWoocommerceTransactional: this.model.isWoocommerceTransactional(),
    };
  },
  initialize(): void {
    jQuery(window)
      // a workaround since the views are not correctly typed
      .on('resize', this.updateHorizontalScroll.bind(this) as () => void)
      .on('scroll', this.updateHorizontalScroll.bind(this) as () => void);
  },
  onRender(): void {
    this.showChildView(
      'contentRegion',
      new Module.SidebarWidgetsView(
        App.getWidgets() as Marionette.ViewOptions<Backbone.Model>,
      ),
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
  updateHorizontalScroll() {
    // Fixes the sidebar so that on narrower screens the horizontal
    // position of the sidebar would be scrollable and not fixed
    // partially out of visible screen
    this.$el.parent().each(function () {
      const self = jQuery(this);

      if (self.css('position') === 'fixed') {
        const calculatedLeft =
          self.parent().offset().left - jQuery(window).scrollLeft();
        self.css('left', `${calculatedLeft}px`);
      } else {
        self.css('left', '');
      }
    });
  },
  onDomRefresh() {
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
}) as typeof Marionette.View & {
  updateHorizontalScroll: () => void;
};

/**
 * Draggable widget collection view
 */
Module.SidebarWidgetsCollectionView = Marionette.CollectionView.extend({
  childView(item): typeof Marionette.View {
    return item.get('widgetView') as typeof Marionette.View;
  },
});

/**
 * Responsible for rendering draggable content widgets
 */
Module.SidebarWidgetsView = Marionette.View.extend({
  getTemplate: () => window.templates.sidebarContent,
  regions: {
    widgets: '.mailpoet_region_content',
  },

  initialize(widgets: WidgetType[]) {
    this.widgets = widgets;
  },

  onRender() {
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
  getTemplate: () => window.templates.sidebarLayout,
});

/**
 * Responsible for managing global styles
 */
Module.SidebarStylesView = Marionette.View.extend({
  brandStylesRoot: null,
  getTemplate: () => window.templates.sidebarStyles,
  behaviors: {
    ColorPickerBehavior: {},
    WooCommerceStylesBehavior: {},
  },
  events() {
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
  templateContext() {
    return {
      model: this.model.toJSON(),
      availableStyles: this.availableStyles.toJSON(),
      isWoocommerceTransactional: this.isWoocommerceTransactional,
    };
  },
  initialize(options) {
    this.availableStyles = options.availableStyles;
    this.isWoocommerceTransactional = options.isWoocommerceTransactional;
    App.getChannel().on('historyUpdate', this.render);
  },
  changeField(field, event) {
    this.model.set(field, jQuery(event.target).val());
  },
  changeColorField(field, event) {
    const value = jQuery(event.target).val() || 'transparent';
    this.model.set(field, value);
  },
  onRender() {
    const container: HTMLDivElement = this.$el.find(
      '#mailpoet_brand_styles',
    )[0];
    const isBrandTemplatesEnabled = MailPoet.FeaturesController.isSupported(
      MailPoet.FeaturesController.FEATURE_BRAND_TEMPLATES,
    );
    if (
      !container ||
      !isBrandTemplatesEnabled ||
      !getBrandStylesSettings().available
    ) {
      return;
    }
    this.brandStylesRoot = createRoot(container);
    this.brandStylesRoot.render(<BrandStyles />);
  },
  onDestroy() {
    if (this.brandStylesRoot) {
      this.brandStylesRoot.unmount();
    }
  },
});

App.on('before:start', (BeforeStartApp) => {
  const Application = BeforeStartApp;
  Application.registerWidget = Module.registerWidget;
  Application.getWidgets = Module.getWidgets;
  Application.registerLayoutWidget = Module.registerLayoutWidget;
  Application.getLayoutWidgets = Module.getLayoutWidgets;
});

App.on('start', (StartApp) => {
  const sidebarView = new SidebarView({
    model: StartApp.getNewsletter(),
  });

  // eslint-disable-next-line no-underscore-dangle
  StartApp._appView.showChildView('sidebarRegion', sidebarView);
});

export { Module as SidebarComponent };
