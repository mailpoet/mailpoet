import { __ } from '@wordpress/i18n';
import { useState } from 'react';

export const STYLE_TYPES = {
  brand: 'brand',
  theme: 'theme',
};

const templateColors = {
  selectedStyle: 'brand',
  brand: {
    // Todo: get from settings
    fontFamily: 'SF Pro Text',
    fontWeight: 'normal',
    background: '#ffffff',
    foreground: '#dfa8bb',
  },
  theme: {
    // Todo: get from settings
    fontFamily: 'SF Pro Text',
    fontWeight: '800',
    background: '#ffffff',
    foreground: '#ad86e9',
  },
};

export function BrandStyles() {
  const { brand, theme, selectedStyle } = templateColors;
  const [selected, setSelected] = useState(selectedStyle);
  const selectStyle = (style: string) => () => setSelected(style);

  return (
    <>
      <h4>
        {__('Choose a style', 'mailpoet')} <i className="mailpoet_info" />
      </h4>
      <div id="mailpoet_style_types">
        <div className="mailpoet_form_field mailpoet_form_style_type_brand">
          <input
            type="radio"
            name="style_type"
            id="style_type_brand"
            value={STYLE_TYPES.brand}
            checked={selected === STYLE_TYPES.brand}
            onClick={selectStyle(STYLE_TYPES.brand)}
          />
          <label htmlFor="style_type_brand">
            <h1
              className="style_type_typography"
              style={{
                fontFamily: brand.fontFamily,
                fontWeight: brand.fontWeight,
              }}
            >
              Aa
            </h1>
            <span className="style_type_colors">
              <span
                style={{
                  background: brand.foreground,
                }}
              />
              <span
                style={{
                  background: brand.background,
                }}
              />
            </span>
            <i className="mailpoet_checkmark radio_checkmark" />
          </label>
        </div>
        <div className="mailpoet_form_field mailpoet_form_style_type_theme">
          <input
            type="radio"
            name="style_type"
            id="style_type_theme"
            value={STYLE_TYPES.theme}
            checked={selected === STYLE_TYPES.theme}
            onClick={selectStyle(STYLE_TYPES.theme)}
          />
          <label htmlFor="style_type_theme">
            <h1
              className="style_type_typography"
              style={{
                fontFamily: theme.fontFamily,
                fontWeight: theme.fontWeight,
              }}
            >
              Aa
            </h1>
            <span className="style_type_colors">
              <span
                style={{
                  background: theme.foreground,
                }}
              />
              <span
                style={{
                  background: theme.background,
                }}
              />
            </span>
            <i className="mailpoet_checkmark radio_checkmark" />
          </label>
        </div>
      </div>
    </>
  );
}
