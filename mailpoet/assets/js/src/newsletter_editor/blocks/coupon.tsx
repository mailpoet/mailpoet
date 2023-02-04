/* eslint-disable func-names */
/**
 * Coupon content block
 */
import { App } from 'newsletter_editor/App';
import { BaseBlock } from 'newsletter_editor/blocks/base';
import ReactDOM from 'react-dom';
import _ from 'underscore';
import jQuery from 'jquery';
import 'backbone.marionette';
import { MailPoet } from 'mailpoet';
import { Settings } from './coupon/settings';

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
        productCategoryIds: [], // selected categories id
        excludedProductCategoryIds: [],
        type: 'coupon',
        amount: 10,
        amountMax: 100,
        discountType: 'percent',
        expiryDay: 10,
        styles: {
          block: {
            backgroundColor: '#ffffff',
            borderColor: '#000000',
            borderRadius: '5px',
            borderStyle: 'solid',
            borderWidth: '1px',
            fontColor: '#000000',
            fontFamily: 'Verdan',
            fontSize: '18px',
            fontWeight: 'normal',
            lineHeight: '40px',
            textAlign: 'center',
            width: '200px',
          },
        },
        source: 'createNew',
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
  onRender() {
    ReactDOM.render(
      <Settings
        availableDiscountTypes={App.getConfig()
          .get('coupon.discount_types')
          .toJSON()}
        priceDecimalSeparator={App.getConfig().get(
          'coupon.price_decimal_separator',
        )}
        setValueCallback={(name, value) => this.model.set(name, value)}
        getValueCallback={(name) => this.model.get(name)}
      />,
      document.getElementById('mailpoet_coupon_block_settings'),
    );

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
