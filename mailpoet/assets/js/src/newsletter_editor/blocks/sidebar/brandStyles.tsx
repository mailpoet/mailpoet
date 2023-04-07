import { __ } from '@wordpress/i18n';

export function BrandStyles() {
  return (
    <>
      <h4>
        {__('Choose a style')} <i className="mailpoet_info" />
      </h4>
      <div id="mailpoet_style_types">
        <div className="mailpoet_form_field mailpoet_form_style_type_brand">
          <input
            type="radio"
            name="style_type"
            id="style_type_brand"
            value="brand"
            defaultChecked
          />
          <label htmlFor="style_type_brand">
            <i className="mailpoet_checkmark radio_checkmark" />
          </label>
        </div>
        <div className="mailpoet_form_field mailpoet_form_style_type_theme">
          <input
            type="radio"
            name="style_type"
            id="style_type_theme"
            value="theme"
          />
          <label htmlFor="style_type_theme">
            <i className="mailpoet_checkmark radio_checkmark" />
          </label>
        </div>
      </div>
    </>
  );
}
