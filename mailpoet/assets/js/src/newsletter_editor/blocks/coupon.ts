/* eslint-disable func-names */
/**
 * Coupon content block
 */
import { App } from 'newsletter_editor/App';
import { BaseBlock } from 'newsletter_editor/blocks/base';
import _ from 'underscore';
import jQuery from 'jquery';
import 'backbone.marionette';
import { MailPoet } from '../../mailpoet';
import 'select2';

export const FEATURE_COUPON_BLOCK = 'Coupon block';

const Module: Record<string, (...args: unknown[]) => void> = {};
const base = BaseBlock;

Module.CouponBlockModel = base.BlockModel.extend({
  defaults() {
    // eslint-disable-next-line no-underscore-dangle
    return this._getDefaults({}, App.getConfig().get('blockDefaults.coupon'));
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
        availableCoupons: App.getConfig().get('coupon.available_coupons'),
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
          const coupon = event.params.data.text;
          model.set('existingCoupon', coupon);
          model.set('code', coupon);
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
          .val(),
      );
    }
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
