import asNum from './server_value_as_num';
import * as defaults from './defaults';

export default function mapFormDataAfterLoading(data) {
  const mapped = {
    ...data,
    settings: {
      ...data.settings,
      placeFormBellowAllPages: data.settings.place_form_bellow_all_pages === '1',
      placeFormBellowAllPosts: data.settings.place_form_bellow_all_posts === '1',
      placePopupFormOnAllPages: data.settings.place_popup_form_on_all_pages === '1',
      placePopupFormOnAllPosts: data.settings.place_popup_form_on_all_posts === '1',
      popupFormDelay: data.settings.popup_form_delay !== undefined
        ? asNum(data.settings.popup_form_delay) : defaults.popupForm.formDelay,
      placeFixedBarFormOnAllPages: data.settings.place_fixed_bar_form_on_all_pages === '1',
      placeFixedBarFormOnAllPosts: data.settings.place_fixed_bar_form_on_all_posts === '1',
      fixedBarFormDelay: data.settings.fixed_bar_form_delay !== undefined
        ? asNum(data.settings.fixed_bar_form_delay)
        : defaults.fixedBarForm.formDelay,
      fixedBarFormPosition: data.settings.fixed_bar_form_position ?? defaults.fixedBarForm.position,
      placeSlideInFormOnAllPages: data.settings.place_slide_in_form_on_all_pages === '1',
      placeSlideInFormOnAllPosts: data.settings.place_slide_in_form_on_all_posts === '1',
      slideInFormDelay: data.settings.slide_in_form_delay !== undefined
        ? asNum(data.settings.slide_in_form_delay)
        : defaults.slideInForm.formDelay,
      slideInFormPosition: data.settings.slide_in_form_position ?? defaults.slideInForm.position,
      alignment: data.settings.alignment ?? defaults.formStyles.alignment,
      borderRadius: data.settings.border_radius !== undefined
        ? asNum(data.settings.border_radius)
        : defaults.formStyles.borderRadius,
      borderSize: data.settings.border_size !== undefined
        ? asNum(data.settings.border_size)
        : defaults.formStyles.borderSize,
      formPadding: data.settings.form_padding !== undefined
        ? asNum(data.settings.form_padding)
        : defaults.formStyles.formPadding,
      inputPadding: data.settings.input_padding !== undefined
        ? asNum(data.settings.input_padding)
        : defaults.formStyles.inputPadding,
      borderColor: data.settings.border_color,
      fontFamily: data.settings.font_family,
      successValidationColor: data.settings.success_validation_color,
      errorValidationColor: data.settings.error_validation_color,
      backgroundImageUrl: data.settings.background_image_url,
      backgroundImageDisplay: data.settings.background_image_display,
      closeButton: data.settings.close_button,
      belowPostStyles: { ...defaults.belowPostForm.styles, ...data.settings.below_post_styles },
      slideInStyles: { ...defaults.slideInForm.styles, ...data.settings.slide_in_styles },
      fixedBarStyles: { ...defaults.fixedBarForm.styles, ...data.settings.fixed_bar_styles },
      popupStyles: { ...defaults.popupForm.styles, ...data.settings.popup_styles },
      otherStyles: { ...defaults.otherForm.styles, ...data.settings.other_styles },
    },
  };

  mapped.settings.belowPostStyles.width.value = asNum(mapped.settings.belowPostStyles.width.value);
  mapped.settings.slideInStyles.width.value = asNum(mapped.settings.slideInStyles.width.value);
  mapped.settings.fixedBarStyles.width.value = asNum(mapped.settings.fixedBarStyles.width.value);
  mapped.settings.popupStyles.width.value = asNum(mapped.settings.popupStyles.width.value);
  mapped.settings.otherStyles.width.value = asNum(mapped.settings.otherStyles.width.value);

  return mapped;
}
