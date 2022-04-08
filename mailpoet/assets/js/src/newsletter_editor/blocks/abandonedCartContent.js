/**
 * Abandoned cart content block.
 *
 * This block depends on blocks.divider for block model and
 * block settings view.
 */
import Backbone from 'backbone';
import Radio from 'backbone.radio';
import _ from 'underscore';
import jQuery from 'jquery';
import MailPoet from 'mailpoet';
import App from 'newsletter_editor/App';
import CommunicationComponent from 'newsletter_editor/components/communication';
import BaseBlock from 'newsletter_editor/blocks/base';
import DividerBlock from 'newsletter_editor/blocks/divider';
import 'select2';

var Module = {};
var base = BaseBlock;
var isAbandonedCartContentBlockActive = true;
var ProductsDisplayOptionsSettingsView;

Module.AbandonedCartContentBlockModel = base.BlockModel.extend({
  stale: ['_selectedProducts', '_availableProducts', '_transformedProducts'],
  defaults: function productsModelDefaults() {
    return this._getDefaults(
      {
        type: 'abandonedCartContent',
        withLayout: true,
        amount: '2',
        contentType: 'product',
        postStatus: 'publish', // 'draft'|'pending'|'publish'
        inclusionType: 'include', // 'include'|'exclude'
        displayType: 'excerpt', // 'excerpt'|'full'|'titleOnly'
        titleFormat: 'h1', // 'h1'|'h2'|'h3'
        titleAlignment: 'left', // 'left'|'center'|'right'
        titleIsLink: false, // false|true
        imageFullWidth: false, // true|false
        titlePosition: 'abovePost', // 'abovePost'|'aboveExcerpt'
        featuredImagePosition: 'alternate', // 'centered'|'right'|'left'|'alternate'|'none'
        pricePosition: 'below', // 'hidden'|'above'|'below'
        readMoreType: 'none', // 'link'|'button'|'none'
        readMoreText: '', // 'link'|'button'
        readMoreButton: {},
        sortBy: 'newest', // 'newest'|'oldest',
        showDivider: true, // true|false
        divider: {},
        _selectedProducts: [],
        _availableProducts: [],
        _transformedProducts: new (App.getBlockTypeModel('container'))(),
      },
      App.getConfig().get('blockDefaults.abandonedCartContent'),
    );
  },
  relations: function relations() {
    return {
      divider: App.getBlockTypeModel('divider'),
      _selectedProducts: Backbone.Collection,
      _availableProducts: Backbone.Collection,
      _transformedProducts: App.getBlockTypeModel('container'),
    };
  },
  initialize: function initialize() {
    var PRODUCT_REFRESH_DELAY_MS = 500;
    var refreshTransformedProducts = _.debounce(
      this._refreshTransformedProducts.bind(this),
      PRODUCT_REFRESH_DELAY_MS,
    );

    // Attach Radio.Requests API primarily for highlighting
    _.extend(this, Radio.Requests);

    this._refreshTransformedProducts();
    this.on('change', this._updateDefaults, this);

    this.listenTo(
      this.get('_selectedProducts'),
      'add remove reset',
      refreshTransformedProducts,
    );
    this.on(
      'change:displayType change:titleFormat change:featuredImagePosition change:titleAlignment change:titleIsLink change:imageFullWidth change:pricePosition change:showDivider change:titlePosition',
      refreshTransformedProducts,
    );
    this.listenTo(this.get('divider'), 'change', refreshTransformedProducts);
  },
  _refreshTransformedProducts: function refreshTransformedProducts() {
    var that = this;
    var data = this.toJSON();

    CommunicationComponent.getTransformedPosts(data)
      .done(function getTransformedPostsDone(products) {
        that
          .get('_transformedProducts')
          .get('blocks')
          .reset(products, { parse: true });
      })
      .fail(function getTransformedProductsFail() {
        MailPoet.Notice.error(MailPoet.I18n.t('failedToFetchRenderedPosts'));
      });
  },
});

Module.AbandonedCartContentBlockView = base.BlockView.extend({
  className:
    'mailpoet_block mailpoet_abandoned_cart_content_block mailpoet_droppable_block',
  getTemplate: function getTemplate() {
    return window.templates.abandonedCartContentBlock;
  },
  modelEvents: _.omit(base.BlockView.prototype.modelEvents, 'change'),
  regions: _.extend(
    {
      productsRegion: '.mailpoet_abandoned_cart_content_container',
    },
    base.BlockView.prototype.regions,
  ),
  onDragSubstituteBy: function onDragSubstituteBy() {
    return Module.AbandonedCartContentBlockWidgetView;
  },
  initialize: function initialize() {
    base.BlockView.prototype.initialize.apply(this, arguments);

    this.toolsView = new Module.AbandonedCartContentBlockToolsView({
      model: this.model,
    });
  },
  events: {
    'click .mailpoet_abandoned_cart_content_block_overlay': 'showSettings',
  },
  onRender: function onRender() {
    var ContainerView;
    var renderOptions;
    if (!isAbandonedCartContentBlockActive) {
      // Hide block if it's not in an abandoned cart email
      return;
    }
    if (!this.getRegion('toolsRegion').hasView()) {
      this.showChildView('toolsRegion', this.toolsView);
    }

    ContainerView = App.getBlockTypeView('container');
    renderOptions = {
      disableTextEditor: true,
      disableDragAndDrop: true,
      emptyContainerMessage: MailPoet.I18n.t('noPostsToDisplay'),
    };
    this.showChildView(
      'productsRegion',
      new ContainerView({
        model: this.model.get('_transformedProducts'),
        renderOptions: renderOptions,
      }),
    );
  },
});

Module.AbandonedCartContentBlockToolsView = base.BlockToolsView.extend({
  getSettingsView: function getSettingsView() {
    return Module.AbandonedCartContentBlockSettingsView;
  },
});

Module.AbandonedCartContentBlockSettingsView = base.BlockSettingsView.extend({
  getTemplate: function getTemplate() {
    return window.templates.abandonedCartContentBlockSettings;
  },
  regions: {
    displayOptionsRegion:
      '.mailpoet_settings_abandoned_cart_content_display_options',
  },
  events: {
    'click .mailpoet_done_editing': 'close',
  },
  templateContext: function templateContext() {
    return {
      model: this.model.toJSON(),
    };
  },
  initialize: function initialize() {
    this.model.trigger('startEditing');
    this.displayOptionsView = new ProductsDisplayOptionsSettingsView({
      model: this.model,
    });
  },
  onRender: function onRender() {
    this.model.request('blockView');

    this.showChildView('displayOptionsRegion', this.displayOptionsView);

    MailPoet.Modal.panel({
      element: this.$el,
      template: '',
      position: 'right',
      overlayRender: false,
      width: App.getConfig().get('sidepanelWidth'),
    });

    // Inform child views that they have been attached to document
    this.displayOptionsView.triggerMethod('attach');
  },
});

ProductsDisplayOptionsSettingsView = base.BlockSettingsView.extend({
  getTemplate: function getTemplate() {
    return window.templates.displayOptionsAbandonedCartContentBlockSettings;
  },
  events: function events() {
    return {
      'click .mailpoet_products_select_divider': 'showDividerSettings',
      'change .mailpoet_products_display_type': 'changeDisplayType',
      'change .mailpoet_products_title_format': 'changeTitleFormat',
      'change .mailpoet_products_title_as_links': _.partial(
        this.changeBoolField,
        'titleIsLink',
      ),
      'change .mailpoet_products_show_divider': _.partial(
        this.changeBoolField,
        'showDivider',
      ),
      'change .mailpoet_products_title_alignment': _.partial(
        this.changeField,
        'titleAlignment',
      ),
      'change .mailpoet_products_image_full_width': _.partial(
        this.changeBoolField,
        'imageFullWidth',
      ),
      'change .mailpoet_products_featured_image_position': _.partial(
        this.changeField,
        'featuredImagePosition',
      ),
      'change .mailpoet_products_price_position': _.partial(
        this.changeField,
        'pricePosition',
      ),
      'change .mailpoet_products_title_position': _.partial(
        this.changeField,
        'titlePosition',
      ),
    };
  },
  templateContext: function templateContext() {
    return {
      model: this.model.toJSON(),
    };
  },
  showDividerSettings: function showDividerSettings() {
    var dividerModule = DividerBlock;
    new dividerModule.DividerBlockSettingsView({
      model: this.model.get('divider'),
      renderOptions: {
        displayFormat: 'subpanel',
        hideApplyToAll: true,
      },
    }).render();
  },
  changeDisplayType: function changeDisplayType(event) {
    var value = jQuery(event.target).val();
    if (value !== 'titleOnly') {
      this.$('.mailpoet_products_title_position').removeClass(
        'mailpoet_hidden',
      );
      this.$('.mailpoet_products_title_position_separator').removeClass(
        'mailpoet_hidden',
      );
    } else {
      this.$('.mailpoet_products_title_position').addClass('mailpoet_hidden');
      this.$('.mailpoet_products_title_position_separator').addClass(
        'mailpoet_hidden',
      );
    }

    this.changeField('displayType', event);
  },
  changeTitleFormat: function changeTitleFormat(event) {
    this.changeField('titleFormat', event);
  },
});

Module.AbandonedCartContentBlockWidgetView = base.WidgetView.extend({
  className:
    base.WidgetView.prototype.className + ' mailpoet_droppable_layout_block',
  id: 'automation_editor_block_abandoned_cart_content',
  getTemplate: function getTemplate() {
    return window.templates.abandonedCartContentInsertion;
  },
  behaviors: {
    DraggableBehavior: {
      cloneOriginal: true,
      drop: function drop() {
        return new Module.AbandonedCartContentBlockModel({}, { parse: true });
      },
    },
  },
});

App.on('before:start', function beforeStartApp(BeforeStartApp, options) {
  if (!window.mailpoet_woocommerce_active) {
    return;
  }
  BeforeStartApp.registerBlockType('abandonedCartContent', {
    blockModel: Module.AbandonedCartContentBlockModel,
    blockView: Module.AbandonedCartContentBlockView,
  });

  if (
    options.newsletter.options.group !== 'woocommerce' ||
    options.newsletter.options.event !== 'woocommerce_abandoned_shopping_cart'
  ) {
    isAbandonedCartContentBlockActive = false;
    return;
  }

  BeforeStartApp.registerWidget({
    name: 'abandonedCartContent',
    widgetView: Module.AbandonedCartContentBlockWidgetView,
    priority: 99,
  });
});

export default Module;
