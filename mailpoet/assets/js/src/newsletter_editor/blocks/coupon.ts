/* eslint-disable func-names */
/**
 * Coupon content block
 */
import { App } from 'newsletter_editor/App';
import { BaseBlock } from 'newsletter_editor/blocks/base';
import _ from 'underscore';
import jQuery from 'jquery';
import 'backbone.marionette';
import { MailPoet } from 'mailpoet';
import { CommunicationComponent } from 'newsletter_editor/components/communication';

export const FEATURE_COUPON_BLOCK = 'Coupon block';

const Module: Record<string, (...args: unknown[]) => void> = {};
const base = BaseBlock;

Module.CouponBlockModel = base.BlockModel.extend({
  defaults() {
    // eslint-disable-next-line no-underscore-dangle
    return this._getDefaults(
      {
        productIds: [], // selected product ids,
        excludedProductIds: [],
        productCategories: [], // selected categories id
        excludedProductCategories: [],
        emailRestrictions: [],
      },
      App.getConfig().get('blockDefaults.coupon'),
    );
  },
});

Module.CouponBlockView = base.BlockView.extend({
  className: 'mailpoet_block mailpoet_coupon_block mailpoet_droppable_block',
  getTemplate: () => window.templates.couponBlock,
  onDragSubstituteBy: () => Module.CouponWidgetView,
  behaviors: _.extend({}, base.BlockView.prototype.behaviors, {
    ShowSettingsBehavior: {},
  }),
  initialize(...args) {
    base.BlockView.prototype.initialize.apply(this, args);

    // Listen for attempts to change all coupon blocks in one go
    this.replaceCouponStylesHandler = (data) => {
      this.model.set(data);
    };

    App.getChannel().on(
      'replaceAllCouponStyles',
      this.replaceCouponStylesHandler,
    );
  },
  onRender() {
    this.toolsView = new Module.CouponBlockToolsView({ model: this.model });
    this.showChildView('toolsRegion', this.toolsView);
  },
});

Module.CouponBlockToolsView = base.BlockToolsView.extend({
  getSettingsView: () => Module.CouponBlockSettingsView,
});

Module.CouponBlockSettingsView = base.BlockSettingsView.extend({
  getTemplate: () => window.templates.couponBlockSettings,
  events() {
    return {
      'input .mailpoet_field_coupon_code': _.partial(this.changeField, 'code'),
      'change .mailpoet_field_coupon_source': 'changeSource',
      'change .mailpoet_field_coupon_discount_type': 'changeDiscountType',
      'input .mailpoet_field_coupon_amount': _.partial(
        this.changeField,
        'amount',
      ),
      'input .mailpoet_field_coupon_expiry_day': _.partial(
        this.changeField,
        'expiryDay',
      ),
      'change .mailpoet_field_coupon_free_shipping': _.partial(
        this.changeBoolCheckboxField,
        'freeShipping',
      ),
      'input .mailpoet_field_coupon_minimum_amount': _.partial(
        this.validateMinAndMaxAmountFields,
        'minimumAmount',
      ),
      'input .mailpoet_field_coupon_maximum_amount': _.partial(
        this.validateMinAndMaxAmountFields,
        'maximumAmount',
      ),
      'change .mailpoet_field_coupon_individual_use': _.partial(
        this.changeBoolCheckboxField,
        'individualUse',
      ),
      'change .mailpoet_field_coupon_exclude_sale_items': _.partial(
        this.changeBoolCheckboxField,
        'excludeSaleItems',
      ),
      'input .mailpoet_field_coupon_email_restrictions': _.partial(
        this.validateEmailRestrictionsField,
        'emailRestrictions',
      ),
      'input .mailpoet_field_coupon_usage_limit': _.partial(
        this.changeField,
        'usageLimit',
      ),
      'input .mailpoet_field_coupon_usage_limit_per_user': _.partial(
        this.changeField,
        'usageLimitPerUser',
      ),
      'change .mailpoet_field_coupon_alignment': _.partial(
        this.changeField,
        'styles.block.textAlign',
      ),
      'change .mailpoet_field_coupon_font_color': _.partial(
        this.changeColorField,
        'styles.block.fontColor',
      ),
      'change .mailpoet_field_coupon_font_family': _.partial(
        this.changeField,
        'styles.block.fontFamily',
      ),
      'change .mailpoet_field_coupon_font_size': _.partial(
        this.changeField,
        'styles.block.fontSize',
      ),
      'change .mailpoet_field_coupon_background_color': _.partial(
        this.changeColorField,
        'styles.block.backgroundColor',
      ),
      'change .mailpoet_field_coupon_border_color': _.partial(
        this.changeColorField,
        'styles.block.borderColor',
      ),
      'change .mailpoet_field_coupon_font_weight': 'changeFontWeight',

      'input .mailpoet_field_coupon_border_width': _.partial(
        this.updateValueAndCall,
        '.mailpoet_field_coupon_border_width_input',
        _.partial(this.changePixelField, 'styles.block.borderWidth').bind(this),
      ),
      'change .mailpoet_field_coupon_border_width': _.partial(
        this.updateValueAndCall,
        '.mailpoet_field_coupon_border_width_input',
        _.partial(this.changePixelField, 'styles.block.borderWidth').bind(this),
      ),
      'input .mailpoet_field_coupon_border_width_input': _.partial(
        this.updateValueAndCall,
        '.mailpoet_field_coupon_border_width',
        _.partial(this.changePixelField, 'styles.block.borderWidth').bind(this),
      ),

      'input .mailpoet_field_coupon_border_radius': _.partial(
        this.updateValueAndCall,
        '.mailpoet_field_coupon_border_radius_input',
        _.partial(this.changePixelField, 'styles.block.borderRadius').bind(
          this,
        ),
      ),
      'change .mailpoet_field_coupon_border_radius': _.partial(
        this.updateValueAndCall,
        '.mailpoet_field_coupon_border_radius_input',
        _.partial(this.changePixelField, 'styles.block.borderRadius').bind(
          this,
        ),
      ),
      'input .mailpoet_field_coupon_border_radius_input': _.partial(
        this.updateValueAndCall,
        '.mailpoet_field_coupon_border_radius',
        _.partial(this.changePixelField, 'styles.block.borderRadius').bind(
          this,
        ),
      ),

      'input .mailpoet_field_coupon_width': _.partial(
        this.updateValueAndCall,
        '.mailpoet_field_coupon_width_input',
        _.partial(this.changePixelField, 'styles.block.width').bind(this),
      ),
      'change .mailpoet_field_coupon_width': _.partial(
        this.updateValueAndCall,
        '.mailpoet_field_coupon_width_input',
        _.partial(this.changePixelField, 'styles.block.width').bind(this),
      ),
      'input .mailpoet_field_coupon_width_input': _.partial(
        this.updateValueAndCall,
        '.mailpoet_field_coupon_width',
        _.partial(this.changePixelField, 'styles.block.width').bind(this),
      ),

      'input .mailpoet_field_coupon_line_height': _.partial(
        this.updateValueAndCall,
        '.mailpoet_field_coupon_line_height_input',
        _.partial(this.changePixelField, 'styles.block.lineHeight').bind(this),
      ),
      'change .mailpoet_field_coupon_line_height': _.partial(
        this.updateValueAndCall,
        '.mailpoet_field_coupon_line_height_input',
        _.partial(this.changePixelField, 'styles.block.lineHeight').bind(this),
      ),
      'input .mailpoet_field_coupon_line_height_input': _.partial(
        this.updateValueAndCall,
        '.mailpoet_field_coupon_line_height',
        _.partial(this.changePixelField, 'styles.block.lineHeight').bind(this),
      ),

      'click .mailpoet_field_coupon_replace_all_styles': 'applyToAll',
      'click .mailpoet_done_editing': 'close',
    };
  },
  templateContext(...args) {
    return _.extend(
      {},
      base.BlockView.prototype.templateContext.apply(this, args),
      {
        availableStyles: App.getAvailableStyles().toJSON(),
        renderOptions: this.renderOptions,
        availableDiscountTypes: App.getConfig()
          .get('coupon.discount_types')
          .toJSON(),
        availableCoupons: App.getConfig()
          .get('coupon.available_coupons')
          .toJSON(),
        minAndMaxAmountFieldsErrorMessage: MailPoet.I18n.t(
          'couponMinAndMaxAmountFieldsErrorMessage',
        ).replace(
          '%s',
          String(App.getConfig().get('coupon.price_decimal_separator')),
        ),
      },
    );
  },
  applyToAll() {
    App.getChannel().trigger(
      'replaceAllCouponStyles',
      _.pick(this.model.toJSON(), 'styles', 'type'),
    );
  },
  updateValueAndCall(fieldToUpdate, callable, event) {
    this.$(fieldToUpdate).val(jQuery(event.target).val());
    callable(event);
  },
  changeFontWeight(event) {
    const checked = !!jQuery(event.target).prop('checked');
    this.model.set(
      'styles.block.fontWeight',
      checked ? jQuery(event.target).val() : 'normal',
    );
  },
  changeDiscountType(event) {
    const amountMax = this.model.get('amountMax');
    const $input = this.$('.mailpoet_field_coupon_amount');
    let newAmountMax;
    if (event.target.value && event.target.value.includes('percent')) {
      newAmountMax = 100;
    } else {
      newAmountMax = null;
    }

    this.$('.mailpoet_field_coupon_amount').parsley().destroy();
    $input.prop('data-parsley-maxlength', newAmountMax);

    this.changeField('discountType', event);
    this.model.set('amountMax', newAmountMax);

    if (amountMax !== this.model.get('amountMax')) {
      this.render();
    }

    // It's a new element after the re-render
    this.$('.mailpoet_field_coupon_amount').parsley().validate();
  },
  onRender() {
    this.$('[data-parsley-validate]')
      .parsley()
      .forEach((instance) => {
        if (instance.element.value) {
          instance.validate();
        }
      });

    const model = this.model;
    this.$('.mailpoet_field_coupon_existing_coupon')
      .select2({
        multiple: false,
        allowClear: false,
      })
      .on({
        'select2:select': function (event) {
          const couponId = event.params.data.id;
          const couponCode = event.params.data.text;
          model.set('couponId', couponId);
          model.set('code', couponCode);
        },
      })
      .trigger('change');

    const fieldKeys = {
      productIds: 'productIds',
      excludedProductIds: 'excludedProductIds',
      productCategories: 'productCategories',
      excludedProductCategories: 'excludedProductCategories',
    };

    this.$('#mailpoet_field_coupon_product_ids')
      .select2(this.productSelect2Options())
      .on(this.select2OnEventOptions(fieldKeys.productIds))
      .trigger('change');

    this.$('#mailpoet_field_coupon_excluded_product_ids')
      .select2(this.productSelect2Options())
      .on(this.select2OnEventOptions(fieldKeys.excludedProductIds))
      .trigger('change');

    this.$('#mailpoet_field_coupon_product_categories')
      .select2(this.categoriesSelect2Options())
      .on(this.select2OnEventOptions(fieldKeys.productCategories))
      .trigger('change');

    this.$('#mailpoet_field_coupon_excluded_product_categories')
      .select2(this.categoriesSelect2Options())
      .on(this.select2OnEventOptions(fieldKeys.excludedProductCategories))
      .trigger('change');
  },
  changeSource(event) {
    const value = jQuery(event.target).val();
    this.model.set('source', value);

    if (value === 'createNew') {
      this.$('.mailpoet_field_coupon_source_use_existing').addClass(
        'mailpoet_hidden',
      );
      this.$('.mailpoet_field_coupon_source_create_new').removeClass(
        'mailpoet_hidden',
      );
      // reset code placeholder
      this.model.set('code', App.getConfig().get('coupon.code_placeholder'));
      this.model.set('couponId', null);
    } else if (value === 'useExisting') {
      this.$('.mailpoet_field_coupon_source_create_new').addClass(
        'mailpoet_hidden',
      );
      this.$('.mailpoet_field_coupon_source_use_existing').removeClass(
        'mailpoet_hidden',
      );
      // set selected code from available
      this.model.set(
        'code',
        this.$('.mailpoet_field_coupon_existing_coupon')
          .find(':selected')
          .text(),
      );
      this.model.set(
        'couponId',
        this.$('.mailpoet_field_coupon_existing_coupon')
          .find(':selected')
          .val(),
      );
    }
  },
  productSelect2Options() {
    const productOptions = {
      type: 'products',
      amount: '10', // number of fetched products
      offset: 0,
      contentType: 'product',
      postStatus: 'publish', // 'draft'|'pending'|'publish'
      search: '', // Search keyword term

      sortBy: 'newest', // 'newest'|'oldest',
    };

    return {
      multiple: true,
      allowClear: true,
      ajax: {
        delay: 250, // wait 250 milliseconds before triggering the request

        data: (params) => {
          const currentPage = params.page || 1;
          return {
            search: params.term,
            page: currentPage,
            // page starts from 1, offset starts from 0. we need to perform some calc to make sure it retrieve the right number of results
            // 'query:append' is added during pagination
            offset:
              params._type === 'query:append' // eslint-disable-line no-underscore-dangle
                ? (currentPage - 1) * Number(productOptions.amount)
                : 0,
          };
        },
        transport: (options, success, failure) => {
          // Fetch available products
          const productPromise = CommunicationComponent.getPosts({
            ...productOptions,
            search: options.data.search,
            offset: options.data.offset,
          });

          productPromise.then(success);
          productPromise.fail(failure);
          return productPromise;
        },
        processResults: (data) => ({
          results: _.map(data, (item) =>
            _.defaults(
              {
                text: item.post_title,
                id: item.ID,
              },
              item,
            ),
          ),
          pagination: {
            more: data.length >= Number(productOptions.amount),
          },
        }),
      },
    };
  },
  categoriesSelect2Options() {
    return {
      multiple: true,
      allowClear: true,
      ajax: {
        delay: 250, // wait 250 milliseconds before triggering the request
        data: (params) => ({
          search: params.term,
          page: params.page || 1,
        }),
        transport: (options, success, failure) => {
          // Fetch available product categories
          const termsPromise = CommunicationComponent.getTerms({
            search: options.data.search,
            page: options.data.page,
            taxonomies: ['product_cat'],
          });
          termsPromise.then(success);
          termsPromise.fail(failure);
          return termsPromise;
        },
        processResults: (data) => ({
          results: _.map(data, (item) =>
            _.defaults(
              {
                text: item.name,
                id: item.term_id,
              },
              item,
            ),
          ),
          pagination: {
            more: data.length === 100,
          },
        }),
      },
    };
  },
  select2OnEventOptions(fieldName) {
    return {
      'select2:select': (event) => {
        const modelItem = this.model.get(fieldName);
        modelItem.add(event.params.data);
        // Reset whole model in order for change events to propagate properly
        this.model.set(fieldName, modelItem.toJSON());
      },
      'select2:unselect': (event) => {
        const modelItem = this.model.get(fieldName);
        modelItem.remove(event.params.data);
        // Reset whole model in order for change events to propagate properly
        this.model.set(fieldName, modelItem.toJSON());
      },
    };
  },
  validateMinAndMaxAmountFields(field, event) {
    const element = event.target;
    const errorElem = element.nextElementSibling;

    // this validation code was gotten from https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/client/legacy/js/admin/woocommerce_admin.js#L150-L212
    // used by /wp-admin/edit.php?post_type=shop_coupon :- when adding/editing a coupon

    const priceDecimalSeparator = String(
      App.getConfig().get('coupon.price_decimal_separator'),
    );

    const regex = new RegExp(`[^-0-9%\\${priceDecimalSeparator}]+`, 'gi');
    const decimalRegex = new RegExp(`[^\\${priceDecimalSeparator}]`, 'gi');

    const value = element.value;
    let newvalue = value.replace(regex, '');

    // Check if newvalue have more than one decimal point.
    if (newvalue.replace(decimalRegex, '').length > 1) {
      newvalue = newvalue.replace(decimalRegex, '');
    }

    if (value !== newvalue) {
      // show error message
      if (errorElem && errorElem.classList.contains('mailpoet_hidden')) {
        errorElem.classList.remove('mailpoet_hidden');
      }
      return;
    }

    if (errorElem && !errorElem.classList.contains('mailpoet_hidden')) {
      errorElem.classList.add('mailpoet_hidden'); // hide error message
    }

    this.model.set(field, value);
  },
  validateEmailRestrictionsField(field, event) {
    const element = event.target;
    const errorElem = element.nextElementSibling;

    const isValid = element.checkValidity();

    if (!isValid) {
      errorElem.textContent = element.validationMessage;

      if (errorElem.classList.contains('mailpoet_hidden')) {
        errorElem.classList.remove('mailpoet_hidden');
      }

      return;
    }

    if (errorElem && !errorElem.classList.contains('mailpoet_hidden')) {
      errorElem.classList.add('mailpoet_hidden');
    }

    this.model.set(field, element.value);
  },
});

Module.CouponWidgetView = base.WidgetView.extend({
  id: 'automation_editor_block_coupon',
  getTemplate: () => window.templates.couponInsertion,
  behaviors: {
    DraggableBehavior: {
      cloneOriginal: true,
      drop: () => new Module.CouponBlockModel(),
    },
  },
});

App.on('before:start', (BeforeStartApp) => {
  if (
    !MailPoet.FeaturesController.isSupported(FEATURE_COUPON_BLOCK) ||
    !window.MailPoet.isWoocommerceActive
  ) {
    return;
  }
  BeforeStartApp.registerBlockType('coupon', {
    blockModel: Module.CouponBlockModel,
    blockView: Module.CouponBlockView,
  });

  BeforeStartApp.registerWidget({
    name: 'coupon',
    widgetView: Module.CouponWidgetView,
    priority: 92,
  });
});

export { Module as CouponBlock };
