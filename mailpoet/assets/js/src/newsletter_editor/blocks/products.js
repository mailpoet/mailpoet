/**
 * Products block.
 *
 * This block works slightly differently compared to any other block.
 * The difference is that once the user changes settings of this block,
 * it will be removed and replaced with other blocks. So this block will
 * not appear in the final JSON structure, it serves only as a placeholder
 * for products, that will be comprised of container, image, button and text blocks
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
var ProductsDisplayOptionsSettingsView;
var SingleProductSelectionSettingsView;
var EmptyProductSelectionSettingsView;
var ProductSelectionSettingsView;
var ProductSelectionCollectionView;

Module.ProductsBlockModel = base.BlockModel.extend({
  stale: ['_selectedProducts', '_availableProducts', '_transformedProducts'],
  defaults: function productsModelDefaults() {
    return this._getDefaults(
      {
        type: 'products',
        withLayout: true,
        amount: '10',
        offset: 0,
        contentType: 'product',
        postStatus: 'publish', // 'draft'|'pending'|'publish'
        terms: [], // List of category and tag objects
        search: '', // Search keyword term
        inclusionType: 'include', // 'include'|'exclude'
        displayType: 'excerpt', // 'excerpt'|'full'|'titleOnly'
        titleFormat: 'h1', // 'h1'|'h2'|'h3'
        titleAlignment: 'left', // 'left'|'center'|'right'
        titleIsLink: false, // false|true
        imageFullWidth: false, // true|false
        titlePosition: 'abovePost', // 'abovePost'|'aboveExcerpt'
        featuredImagePosition: 'alternate', // 'centered'|'right'|'left'|'alternate'|'none'
        pricePosition: 'below', // 'hidden'|'above'|'below'
        readMoreType: 'link', // 'link'|'button'
        readMoreText: 'Buy now', // 'link'|'button'
        readMoreButton: {
          text: 'Buy now',
          url: '[postLink]',
        },
        sortBy: 'newest', // 'newest'|'oldest',
        showDivider: true, // true|false
        divider: {},
        _selectedProducts: [],
        _availableProducts: [],
        _transformedProducts: new (App.getBlockTypeModel('container'))(),
      },
      App.getConfig().get('blockDefaults.products'),
    );
  },
  relations: function relations() {
    return {
      readMoreButton: App.getBlockTypeModel('button'),
      divider: App.getBlockTypeModel('divider'),
      _selectedProducts: Backbone.Collection,
      _availableProducts: Backbone.Collection,
      _transformedProducts: App.getBlockTypeModel('container'),
    };
  },
  initialize: function initialize() {
    var PRODUCT_REFRESH_DELAY_MS = 500;
    var refreshAvailableProducts = _.debounce(
      this.fetchAvailableProducts.bind(this),
      PRODUCT_REFRESH_DELAY_MS,
    );
    var refreshTransformedProducts = _.debounce(
      this._refreshTransformedProducts.bind(this),
      PRODUCT_REFRESH_DELAY_MS,
    );

    // Attach Radio.Requests API primarily for highlighting
    _.extend(this, Radio.Requests);

    this.fetchAvailableProducts();
    this.on('change', this._updateDefaults, this);
    this.on(
      'change:terms change:postStatus change:search',
      refreshAvailableProducts,
    );
    this.on('loadMoreProducts', this._loadMoreProducts, this);

    this.listenTo(
      this.get('_selectedProducts'),
      'add remove reset',
      refreshTransformedProducts,
    );
    this.on(
      'change:displayType change:titleFormat change:featuredImagePosition change:titleAlignment change:titleIsLink change:imageFullWidth change:pricePosition change:readMoreType change:readMoreText change:showDivider change:titlePosition',
      refreshTransformedProducts,
    );
    this.listenTo(
      this.get('readMoreButton'),
      'change',
      refreshTransformedProducts,
    );
    this.listenTo(this.get('divider'), 'change', refreshTransformedProducts);
    this.listenTo(App.getChannel(), 'hideSettings', this.destroy);

    this.on('insertSelectedProducts', this._insertSelectedProducts, this);
  },
  fetchAvailableProducts: function fetchAvailableProducts() {
    var that = this;
    this.set('offset', 0);
    CommunicationComponent.getPosts(this.toJSON())
      .done(function getPostsDone(products) {
        that.get('_availableProducts').reset(products);
        that.get('_selectedProducts').reset(); // Empty out the collection
        that.trigger('change:_availableProducts');
      })
      .fail(function getProductsFail() {
        MailPoet.Notice.error(MailPoet.I18n.t('failedToFetchAvailablePosts'));
      });
  },
  _loadMoreProducts: function loadMoreProducts() {
    var that = this;
    var productsCount = this.get('_availableProducts').length;
    var nextOffset = this.get('offset') + Number(this.get('amount'));

    if (productsCount === 0 || productsCount < nextOffset) {
      // No more posts to load
      return false;
    }
    this.set('offset', nextOffset);
    this.trigger('loadingMoreProducts');

    CommunicationComponent.getPosts(this.toJSON())
      .done(function getPostsDone(products) {
        that.get('_availableProducts').add(products);
        that.trigger('change:_availableProducts');
      })
      .fail(function getProductsFail() {
        MailPoet.Notice.error(MailPoet.I18n.t('failedToFetchAvailablePosts'));
      })
      .always(function getProductsAlways() {
        that.trigger('moreProductsLoaded');
      });
    return true;
  },
  _refreshTransformedProducts: function refreshTransformedProducts() {
    var that = this;
    var data = this.toJSON();

    data.posts = this.get('_selectedProducts').pluck('ID');

    if (data.posts.length === 0) {
      this.get('_transformedProducts').get('blocks').reset();
      return;
    }

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
  _insertSelectedProducts: function insertSelectedProducts() {
    var data = this.toJSON();
    var index = this.collection.indexOf(this);
    var collection = this.collection;

    data.posts = this.get('_selectedProducts').pluck('ID');

    if (data.posts.length === 0) return;

    CommunicationComponent.getTransformedPosts(data)
      .done(function getTransformedPostsDone(proucts) {
        collection.add(JSON.parse(JSON.stringify(proucts)), { at: index });
      })
      .fail(function getTransformedProductsFail() {
        MailPoet.Notice.error(MailPoet.I18n.t('failedToFetchRenderedPosts'));
      });
  },
});

Module.ProductsBlockView = base.BlockView.extend({
  className: 'mailpoet_block mailpoet_products_block mailpoet_droppable_block',
  getTemplate: function getTemplate() {
    return window.templates.productsBlock;
  },
  modelEvents: {}, // Forcefully disable all events
  regions: _.extend(
    {
      productsRegion: '.mailpoet_products_container',
    },
    base.BlockView.prototype.regions,
  ),
  onDragSubstituteBy: function onDragSubstituteBy() {
    return Module.ProductsWidgetView;
  },
  initialize: function initialize() {
    base.BlockView.prototype.initialize.apply(this, arguments);

    this.toolsView = new Module.ProductsBlockToolsView({ model: this.model });
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
    this.showChildView(
      'productsRegion',
      new ContainerView({
        model: this.model.get('_transformedProducts'),
        renderOptions: renderOptions,
      }),
    );
  },
  notifyAboutSelf: function notifyAboutSelf() {
    return this;
  },
  onBeforeDestroy: function onBeforeDestroy() {
    this.model.stopReplying('blockView', this.notifyAboutSelf, this);
  },
});

Module.ProductsBlockToolsView = base.BlockToolsView.extend({
  getSettingsView: function getSettingsView() {
    return Module.ProductsBlockSettingsView;
  },
});

Module.ProductsBlockSettingsView = base.BlockSettingsView.extend({
  getTemplate: function getTemplate() {
    return window.templates.productsBlockSettings;
  },
  regions: {
    selectionRegion: '.mailpoet_settings_products_selection',
    displayOptionsRegion: '.mailpoet_settings_products_display_options',
  },
  events: {
    'click .mailpoet_settings_products_show_display_options':
      'switchToDisplayOptions',
    'click .mailpoet_settings_products_show_product_selection':
      'switchToProductSelection',
    'click .mailpoet_settings_products_insert_selected': 'insertProducts',
  },
  templateContext: function templateContext() {
    return {
      model: this.model.toJSON(),
    };
  },
  initialize: function initialize() {
    this.model.trigger('startEditing');
    this.selectionView = new ProductSelectionSettingsView({
      model: this.model,
    });
    this.displayOptionsView = new ProductsDisplayOptionsSettingsView({
      model: this.model,
    });
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
      overlayRender: false,
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
    this.$('.mailpoet_settings_products_selection').addClass('mailpoet_closed');
    this.$('.mailpoet_settings_products_display_options').removeClass(
      'mailpoet_closed',
    );

    // Switch controls
    this.$('.mailpoet_settings_products_show_display_options').addClass(
      'mailpoet_hidden',
    );
    this.$('.mailpoet_settings_products_show_product_selection').removeClass(
      'mailpoet_hidden',
    );
  },
  switchToProductSelection: function switchToProductSelection() {
    // Switch content view
    this.$('.mailpoet_settings_products_display_options').addClass(
      'mailpoet_closed',
    );
    this.$('.mailpoet_settings_products_selection').removeClass(
      'mailpoet_closed',
    );

    // Switch controls
    this.$('.mailpoet_settings_products_show_product_selection').addClass(
      'mailpoet_hidden',
    );
    this.$('.mailpoet_settings_products_show_display_options').removeClass(
      'mailpoet_hidden',
    );
  },
  insertProducts: function insertProducts() {
    this.model.trigger('insertSelectedProducts');
    this.model.destroy();
    this.close();
  },
});

ProductSelectionCollectionView = Marionette.CollectionView.extend({
  className: 'mailpoet_products_scroll_container',
  childView: function childView() {
    return SingleProductSelectionSettingsView;
  },
  emptyView: function emptyView() {
    return EmptyProductSelectionSettingsView;
  },
  childViewOptions: function childViewOptions() {
    return {
      blockModel: this.blockModel,
    };
  },
  initialize: function initialize(options) {
    this.blockModel = options.blockModel;
  },
  events: {
    scroll: 'onProductsScroll',
  },
  onProductsScroll: function onProductsScroll(event) {
    var $productsBox = jQuery(event.target);
    if (
      $productsBox.scrollTop() + $productsBox.innerHeight() >=
      $productsBox[0].scrollHeight
    ) {
      // Load more posts if scrolled to bottom
      this.blockModel.trigger('loadMoreProducts');
    }
  },
});

ProductSelectionSettingsView = Marionette.View.extend({
  getTemplate: function getTemplate() {
    return window.templates.postSelectionProductsBlockSettings;
  },
  regions: {
    posts: '.mailpoet_product_selection_container',
  },
  events: function events() {
    return {
      'change .mailpoet_products_post_status': _.partial(
        this.changeField,
        'postStatus',
      ),
      'input .mailpoet_products_search_term': _.partial(
        this.changeField,
        'search',
      ),
    };
  },
  modelEvents: {
    'change:offset': function changeOffset(model, value) {
      // Scroll posts view to top if settings are changed
      if (value === 0) {
        this.$('.mailpoet_products_scroll_container').scrollTop(0);
      }
    },
    loadingMoreProducts: function loadingMoreProducts() {
      this.$('.mailpoet_product_selection_loading').css(
        'visibility',
        'visible',
      );
    },
    moreProductsLoaded: function moreProductsLoaded() {
      this.$('.mailpoet_product_selection_loading').css('visibility', 'hidden');
    },
  },
  templateContext: function templateContext() {
    return {
      model: this.model.toJSON(),
    };
  },
  onRender: function onRender() {
    var productsView;
    // Dynamically update available post types
    productsView = new ProductSelectionCollectionView({
      collection: this.model.get('_availableProducts'),
      blockModel: this.model,
    });

    this.showChildView('posts', productsView);
  },
  onAttach: function onAttach() {
    var that = this;

    this.$('.mailpoet_products_categories_and_tags')
      .select2({
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
              that.model.get('contentType'),
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
              results: _.map(data.terms, function results(item) {
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
      })
      .trigger('change');
  },
  changeField: function changeField(field, event) {
    this.model.set(field, jQuery(event.target).val());
  },
});

EmptyProductSelectionSettingsView = Marionette.View.extend({
  getTemplate: function getTemplate() {
    return window.templates.emptyPostProductsBlockSettings;
  },
});

SingleProductSelectionSettingsView = Marionette.View.extend({
  getTemplate: function getTemplate() {
    return window.templates.singlePostProductsBlockSettings;
  },
  events: function events() {
    return {
      'change .mailpoet_select_product_checkbox': 'productSelectionChange',
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
  productSelectionChange: function productSelectionChange(event) {
    var checkBox = jQuery(event.target);
    var selectedProductsCollection = this.blockModel.get('_selectedProducts');
    if (checkBox.prop('checked')) {
      selectedProductsCollection.add(this.model);
    } else {
      selectedProductsCollection.remove(this.model);
    }
  },
});

ProductsDisplayOptionsSettingsView = base.BlockSettingsView.extend({
  getTemplate: function getTemplate() {
    return window.templates.displayOptionsProductsBlockSettings;
  },
  events: function events() {
    return {
      'click .mailpoet_products_select_button': 'showButtonSettings',
      'click .mailpoet_products_select_divider': 'showDividerSettings',
      'change .mailpoet_products_read_more_type': 'changeReadMoreType',
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
      'input .mailpoet_products_read_more_text': _.partial(
        this.changeField,
        'readMoreText',
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
  showButtonSettings: function showButtonSettings() {
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
  changeReadMoreType: function changeReadMoreType(event) {
    var value = jQuery(event.target).val();
    if (value === 'link') {
      this.$('.mailpoet_products_read_more_text').removeClass(
        'mailpoet_hidden',
      );
      this.$('.mailpoet_products_select_button').addClass('mailpoet_hidden');
    } else if (value === 'button') {
      this.$('.mailpoet_products_read_more_text').addClass('mailpoet_hidden');
      this.$('.mailpoet_products_select_button').removeClass('mailpoet_hidden');
    }
    this.changeField('readMoreType', event);
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

Module.ProductsWidgetView = base.WidgetView.extend({
  className:
    base.WidgetView.prototype.className + ' mailpoet_droppable_layout_block',
  id: 'automation_editor_block_products',
  getTemplate: function getTemplate() {
    return window.templates.productsInsertion;
  },
  behaviors: {
    DraggableBehavior: {
      cloneOriginal: true,
      drop: function drop() {
        return new Module.ProductsBlockModel({}, { parse: true });
      },
    },
  },
});

App.on('before:start', function beforeStartApp(BeforeStartApp) {
  if (!window.mailpoet_woocommerce_active) {
    return;
  }
  BeforeStartApp.registerBlockType('products', {
    blockModel: Module.ProductsBlockModel,
    blockView: Module.ProductsBlockView,
  });

  BeforeStartApp.registerWidget({
    name: 'products',
    widgetView: Module.ProductsWidgetView,
    priority: 98,
  });
});

export default Module;
