/* eslint-disable func-names */
/**
 * Coupon content block
 */
import { App } from 'newsletter-editor/app';
import { BaseBlock } from 'newsletter-editor/blocks/base';
import { createRoot } from 'react-dom/client';
import _ from 'underscore';
import jQuery from 'jquery';
import 'backbone.marionette';
import { Settings } from './coupon/settings';

const Module: Record<string, (...args: unknown[]) => void> = {};
const base = BaseBlock;

Module.CouponBlockModel = base.BlockModel.extend({
  defaults() {
    // eslint-disable-next-line no-underscore-dangle
    return this._getDefaults(
      {
        isStandardEmail: App.getNewsletter().isStandardEmail(),
        productIds: [], // selected product ids,
        excludedProductIds: [],
        productCategoryIds: [], // selected categories id
        excludedProductCategoryIds: [],
        type: 'coupon',
        amount: 10,
        amountMax: 100,
        discountType: 'percent',
        expiryDay: 10,
        usageLimit: '',
        usageLimitPerUser: '',
        minimumAmount: '',
        maximumAmount: '',
        emailRestrictions: '',
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
        code: App.getConfig().get('coupon.code_placeholder'),
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
      'click .mailpoet_settings_coupon_show_coupon_configuration':
        'switchToCouponConfiguration',
      'click .mailpoet_settings_coupon_show_display_options':
        'switchToDisplayOptions',
    };
  },
  templateContext(...args) {
    return _.extend(
      {},
      base.BlockView.prototype.templateContext.apply(this, args),
      {
        availableStyles: App.getAvailableStyles().toJSON(),
        renderOptions: this.renderOptions,
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
  switchToDisplayOptions(): void {
    // Switch content view
    jQuery('.mailpoet_coupon_block_settings').addClass('mailpoet_closed');
    jQuery('.mailpoet_coupon_block_display_options').removeClass(
      'mailpoet_closed',
    );

    // Switch controls
    jQuery('.mailpoet_settings_coupon_show_display_options').addClass(
      'mailpoet_hidden',
    );
    jQuery('.mailpoet_settings_coupon_show_coupon_configuration').removeClass(
      'mailpoet_hidden',
    );
  },
  switchToCouponConfiguration(): void {
    // Switch content view
    jQuery('.mailpoet_coupon_block_display_options').addClass(
      'mailpoet_closed',
    );
    jQuery('.mailpoet_coupon_block_settings').removeClass('mailpoet_closed');

    // Switch controls
    jQuery('.mailpoet_settings_coupon_show_coupon_configuration').addClass(
      'mailpoet_hidden',
    );
    jQuery('.mailpoet_settings_coupon_show_display_options').removeClass(
      'mailpoet_hidden',
    );
  },
  onRender() {
    const container = document.getElementById('mailpoet_coupon_block_settings');
    const root = createRoot(container);
    root.render(
      <Settings
        availableDiscountTypes={App.getConfig()
          .get('coupon.discount_types')
          .toJSON()}
        codePlaceholder={App.getConfig().get('coupon.code_placeholder')}
        priceDecimalSeparator={App.getConfig().get(
          'coupon.price_decimal_separator',
        )}
        setValueCallback={(name, value) => this.model.set(name, value)}
        getValueCallback={(name) => this.model.get(name)}
      />,
    );
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
  if (!window.MailPoet.isWoocommerceActive) {
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
