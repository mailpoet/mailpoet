/* eslint-disable func-names */
/* eslint-disable no-underscore-dangle */
/**
 * Dynamic products block.
 */
import { App } from 'newsletter-editor/app';
import { BaseBlock } from 'newsletter-editor/blocks/base';
import { ButtonBlock } from 'newsletter-editor/blocks/button';
import { DividerBlock } from 'newsletter-editor/blocks/divider';
import { CommunicationComponent } from 'newsletter-editor/components/communication';
import SuperModel from 'backbone.supermodel';
import _ from 'underscore';
import jQuery from 'jquery';
import { __ } from '@wordpress/i18n';

const Module: Record<string, (...args: unknown[]) => void> = {};
const base = BaseBlock;

Module.DynamicProductsSupervisor = SuperModel.extend({
  initialize() {
    const DELAY_REFRESH_FOR_MS = 500;
    this.listenTo(
      App.getChannel(),
      'dynamicProductsRefresh',
      _.debounce(this.refresh, DELAY_REFRESH_FOR_MS),
    );
  },
  refresh() {
    const models =
      App.findModels((model) => model.get('type') === 'dynamicProducts') || [];

    if (models.length === 0) return;
    // eslint-disable-next-line @typescript-eslint/no-unsafe-return
    const blocks = _.map(models, (model) => model.toJSON());

    void CommunicationComponent.getBulkTransformedProducts({
      blocks,
      // eslint-disable-next-line @typescript-eslint/no-unsafe-argument
    }).then(_.partial(this.refreshBlocks, models));
  },
  refreshBlocks(models, renderedBlocks) {
    _.each(_.zip(models, renderedBlocks), (args) => {
      const model = args[0];
      const contents = args[1];
      model.trigger('refreshPosts', contents);
    });
  },
});

Module.DynamicProductsBlockModel = base.BlockModel.extend({
  stale: ['_container', '_displayOptionsHidden', '_featuredImagePosition'],
  defaults() {
    // eslint-disable-next-line @typescript-eslint/no-unsafe-return
    return this._getDefaults(
      {
        type: 'dynamicProducts',
        withLayout: true,
        amount: '10',
        contentType: 'product',
        terms: [], // List of category and tag objects
        inclusionType: 'include', // 'include'|'exclude'
        displayType: 'excerpt', // 'excerpt'|'full'|'titleOnly'
        titleFormat: 'h1', // 'h1'|'h2'|'h3'
        titleAlignment: 'left', // 'left'|'center'|'right'
        titleIsLink: false, // false|true
        imageFullWidth: false, // true|false
        titlePosition: 'abovePost', // 'abovePost'|'aboveExcerpt'
        featuredImagePosition: 'left', // 'centered'|'right'|'left'|'alternate'|'none'
        pricePosition: 'below', // 'hidden'|'above'|'below'
        readMoreType: 'link', // 'link'|'button'
        readMoreText: 'Buy now', // 'link'|'button'
        readMoreButton: {
          text: 'Buy now',
          url: '[postLink]',
        },
        sortBy: 'newest', // 'newest'|'oldest',
        showDivider: true, // true|false
        showCrossSells: false, // true|false
        divider: {},
        _container: new (App.getBlockTypeModel('container'))(),
        _displayOptionsHidden: true, // true|false
        _featuredImagePosition: 'none', // 'centered'|'left'|'right'|'alternate'|'none'
      },
      App.getConfig().get('blockDefaults.dynamicProducts'),
    );
  },
  relations() {
    return {
      readMoreButton: App.getBlockTypeModel('button'),
      divider: App.getBlockTypeModel('divider'),
      _container: App.getBlockTypeModel('container'),
    };
  },
  initialize(...args) {
    base.BlockView.prototype.initialize.apply(this, args);

    this.on(
      'change:amount change:contentType change:terms change:inclusionType change:displayType change:titleFormat change:featuredImagePosition change:titleAlignment change:titleIsLink change:imageFullWidth change:pricePosition change:readMoreType change:readMoreText change:sortBy change:showDivider change:showCrossSells change:titlePosition',
      this._handleChanges,
      this,
    );
    this.listenTo(this.get('readMoreButton'), 'change', this._handleChanges);
    this.listenTo(this.get('divider'), 'change', this._handleChanges);
    this.on('add remove update reset', this._handleChanges);
    this.on('refreshPosts', this.updatePosts, this);
    this.set('_featuredImagePosition', this.get('featuredImagePosition'));
  },
  updatePosts(posts) {
    this.get('_container.blocks').reset(posts, { parse: true });
  },
  /**
   * Batch more changes during a specific time, instead of fetching
   * ALC posts on each model change
   */
  _handleChanges() {
    this._updateDefaults();
    App.getChannel().trigger('dynamicProductsRefresh');
  },
});

Module.DynamicProductsBlockView = base.BlockView.extend({
  className:
    'mailpoet_block mailpoet_dynamic_products_block mailpoet_droppable_block',
  initialize() {
    function replaceButtonStylesHandler(data) {
      this.model.set({ readMoreButton: data });
    }
    App.getChannel().on(
      'replaceAllButtonStyles',
      replaceButtonStylesHandler.bind(this),
    );
  },
  getTemplate() {
    return window.templates.dynamicProductsBlock;
  },
  regions: {
    toolsRegion: '.mailpoet_tools',
    postsRegion: '.mailpoet_dynamic_products_block_posts',
  },
  modelEvents: _.extend(
    _.omit(base.BlockView.prototype.modelEvents, 'change'),
    {
      postsChanged: 'render',
    },
  ),
  events: {
    'click .mailpoet_dynamic_products_block_overlay': 'showSettings',
  },
  onDragSubstituteBy() {
    return Module.DynamicProductsWidgetView;
  },
  onRender() {
    const ContainerView = App.getBlockTypeView('container');
    const renderOptions = {
      disableTextEditor: true,
      disableDragAndDrop: true,
      emptyContainerMessage: __('There is no content to display.', 'mailpoet'),
    };
    this.toolsView = new Module.DynamicProductsBlockToolsView({
      model: this.model,
    });
    this.showChildView('toolsRegion', this.toolsView);
    this.showChildView(
      'postsRegion',
      new ContainerView({
        model: this.model.get('_container'),
        renderOptions,
      }),
    );
  },
  duplicateBlock() {
    const originalData = this.model.toJSON();
    const newModel = new Module.DynamicProductsBlockModel(originalData);
    this.model.collection.add(newModel, {
      at: this.model.collection.findIndex(this.model),
    });
  },
});

Module.DynamicProductsBlockToolsView = base.BlockToolsView.extend({
  getSettingsView() {
    return Module.DynamicProductsBlockSettingsView;
  },
});

Module.DynamicProductsBlockSettingsView = base.BlockSettingsView.extend({
  getTemplate() {
    return window.templates.dynamicProductsBlockSettings;
  },
  events() {
    return {
      'click .mailpoet_dynamic_products_hide_display_options':
        'toggleDisplayOptions',
      'click .mailpoet_dynamic_products_show_display_options':
        'toggleDisplayOptions',
      'click .mailpoet_dynamic_products_select_button': 'showButtonSettings',
      'click .mailpoet_dynamic_products_select_divider': 'showDividerSettings',
      'change .mailpoet_dynamic_products_read_more_type': 'changeReadMoreType',
      'change .mailpoet_dynamic_products_display_type': 'changeDisplayType',
      'change .mailpoet_dynamic_products_title_format': 'changeTitleFormat',
      'change .mailpoet_dynamic_products_title_as_links': _.partial(
        this.changeBoolField,
        'titleIsLink',
      ),
      'change .mailpoet_dynamic_products_show_divider': _.partial(
        this.changeBoolField,
        'showDivider',
      ),
      'change .mailpoet_dynamic_products_show_cross_sells': _.partial(
        this.changeBoolField,
        'showCrossSells',
      ),
      'input .mailpoet_dynamic_products_show_amount': _.partial(
        this.changeField,
        'amount',
      ),
      'change .mailpoet_dynamic_products_content_type': _.partial(
        this.changeField,
        'contentType',
      ),
      'change .mailpoet_dynamic_products_include_or_exclude': _.partial(
        this.changeField,
        'inclusionType',
      ),
      'change .mailpoet_dynamic_products_title_alignment': _.partial(
        this.changeField,
        'titleAlignment',
      ),
      'change .mailpoet_dynamic_products_image_full_width': _.partial(
        this.changeBoolField,
        'imageFullWidth',
      ),
      'change .mailpoet_dynamic_products_price_position': _.partial(
        this.changeField,
        'pricePosition',
      ),
      'change .mailpoet_dynamic_products_featured_image_position':
        'changeFeaturedImagePosition',
      'input .mailpoet_dynamic_products_read_more_text': _.partial(
        this.changeField,
        'readMoreText',
      ),
      'change .mailpoet_dynamic_products_sort_by': _.partial(
        this.changeField,
        'sortBy',
      ),
      'change .mailpoet_dynamic_products_title_position': _.partial(
        this.changeField,
        'titlePosition',
      ),
      'click .mailpoet_done_editing': 'close',
    };
  },
  onRender() {
    // eslint-disable-next-line @typescript-eslint/no-this-alias
    const that = this;

    this.$('.mailpoet_dynamic_products_categories_and_tags')
      .select2({
        multiple: true,
        allowClear: true,
        placeholder: __('Categories & tags', 'mailpoet'),
        ajax: {
          data(params) {
            return {
              term: params.term,
              page: params.page || 1,
            };
          },
          transport(options, success, failure) {
            let taxonomies;
            let termsPromise;
            const promise = CommunicationComponent.getProductTaxonomies(
              that.model.get('contentType'),
            ).then((tax) => {
              taxonomies = tax;
              // Fetch available terms based on the list of taxonomies already fetched
              termsPromise = CommunicationComponent.getProductTerms({
                search: options.data.term,
                page: options.data.page,
                taxonomies: _.keys(taxonomies),
              }).then((terms) => ({
                taxonomies,
                terms,
              }));
              // eslint-disable-next-line @typescript-eslint/no-unsafe-return
              return termsPromise;
            });

            promise.then(success);
            promise.fail(failure);
            // eslint-disable-next-line @typescript-eslint/no-unsafe-return
            return promise;
          },
          processResults(data) {
            // Transform taxonomies and terms into select2 compatible format
            return {
              results: _.map(data.terms, (item) =>
                // eslint-disable-next-line @typescript-eslint/no-unsafe-return
                _.defaults(
                  {
                    text: `${
                      // eslint-disable-next-line @typescript-eslint/restrict-template-expressions
                      data.taxonomies[item.taxonomy].labels.singular_name
                    }: ${
                      // eslint-disable-next-line @typescript-eslint/restrict-template-expressions
                      item.name
                    }`,
                    id: item.term_id,
                  },
                  item,
                ),
              ),
              pagination: {
                more: data.terms.length === 100,
              },
            };
          },
        },
      })
      .on({
        'select2:select': (event) => {
          const terms = that.model.get('terms');
          terms.add(event.params.data);
          // Reset whole model in order for change events to propagate properly
          that.model.set('terms', terms.toJSON());
        },
        'select2:unselect': (event) => {
          const terms = that.model.get('terms');
          terms.remove(event.params.data);
          // Reset whole model in order for change events to propagate properly
          that.model.set('terms', terms.toJSON());
        },
      })
      .trigger('change');
  },
  toggleDisplayOptions() {
    this.model.set(
      '_displayOptionsHidden',
      !this.model.get('_displayOptionsHidden'),
    );
    this.render();
  },
  showButtonSettings() {
    const buttonModule = ButtonBlock;
    new buttonModule.ButtonBlockSettingsView({
      model: this.model.get('readMoreButton'),
      renderOptions: {
        displayFormat: 'subpanel',
        hideLink: true,
        hideApplyToAll: true,
      },
    }).render();
  },
  showDividerSettings() {
    const dividerModule = DividerBlock;
    new dividerModule.DividerBlockSettingsView({
      model: this.model.get('divider'),
      renderOptions: {
        displayFormat: 'subpanel',
        hideApplyToAll: true,
      },
    }).render();
  },
  changeReadMoreType(event: Event) {
    const value = jQuery(event.target).val();
    if (value === 'link') {
      this.$('.mailpoet_dynamic_products_read_more_text').removeClass(
        'mailpoet_hidden',
      );
      this.$('.mailpoet_dynamic_products_select_button').addClass(
        'mailpoet_hidden',
      );
    } else if (value === 'button') {
      this.$('.mailpoet_dynamic_products_read_more_text').addClass(
        'mailpoet_hidden',
      );
      this.$('.mailpoet_dynamic_products_select_button').removeClass(
        'mailpoet_hidden',
      );
    }
    this.changeField('readMoreType', event);
  },
  changeDisplayType(event: Event) {
    const value = jQuery(event.target).val();

    // Reset titleFormat if it was set to List when switching away from displayType=titleOnly
    if (value !== 'titleOnly' && this.model.get('titleFormat') === 'ul') {
      this.model.set('titleFormat', 'h1');
      this.$('.mailpoet_dynamic_products_title_format').val(['h1']);
      this.$('.mailpoet_dynamic_products_title_as_link').removeClass(
        'mailpoet_hidden',
      );
    }
    this.changeField('displayType', event);
    this.model.set(
      '_featuredImagePosition',
      this.model.get('featuredImagePosition'),
    );
    this.render();
  },
  changeTitleFormat(event: Event) {
    const value = jQuery(event.target).val();
    if (value === 'ul') {
      this.$('.mailpoet_dynamic_products_non_title_list_options').addClass(
        'mailpoet_hidden',
      );

      this.model.set('titleIsLink', true);
      this.$('.mailpoet_dynamic_products_title_as_link').addClass(
        'mailpoet_hidden',
      );
      this.$('.mailpoet_dynamic_products_title_as_links').val(['true']);
    } else {
      this.$('.mailpoet_dynamic_products_non_title_list_options').removeClass(
        'mailpoet_hidden',
      );
      this.$('.mailpoet_dynamic_products_title_as_link').removeClass(
        'mailpoet_hidden',
      );
    }
    this.changeField('titleFormat', event);
  },
  changeFeaturedImagePosition(event: Event) {
    this.changeField('featuredImagePosition', event);
    this.changeField('_featuredImagePosition', event);
  },
});

Module.DynamicProductsWidgetView = base.WidgetView.extend({
  className:
    // eslint-disable-next-line @typescript-eslint/restrict-template-expressions
    `${base.WidgetView.prototype.className} mailpoet_droppable_layout_block`,
  getTemplate() {
    return window.templates.dynamicProductsInsertion;
  },
  behaviors: {
    DraggableBehavior: {
      cloneOriginal: true,
      drop() {
        // eslint-disable-next-line @typescript-eslint/no-unsafe-return
        return new Module.DynamicProductsBlockModel({}, { parse: true });
      },
      onDrop(options) {
        options.droppedView.triggerMethod('showSettings');
      },
    },
  },
});

App.on('before:start', (BeforeStartApp) => {
  if (!window.mailpoet_woocommerce_active) {
    return false;
  }

  BeforeStartApp.registerBlockType('dynamicProducts', {
    blockModel: Module.DynamicProductsBlockModel,
    blockView: Module.DynamicProductsBlockView,
  });

  BeforeStartApp.registerWidget({
    name: 'dynamicProducts',
    widgetView: Module.DynamicProductsWidgetView,
    priority: 99,
  });

  return undefined;
});

App.on('start', (StartApp) => {
  if (!window.mailpoet_woocommerce_active) {
    return false;
  }

  const Application = StartApp;
  Application._DynamicProductsSupervisor =
    new Module.DynamicProductsSupervisor();
  Application._DynamicProductsSupervisor.refresh();

  return undefined;
});

export { Module as DynamicProductsBlock };
