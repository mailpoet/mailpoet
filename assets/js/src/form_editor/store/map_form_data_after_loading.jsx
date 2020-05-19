import asNum from './server_value_as_num';

export default function mapFormDataAfterLoading(data) {
  return {
    ...data,
    settings: {
      ...data.settings,
      placeFormBellowAllPages: data.settings.place_form_bellow_all_pages === '1',
      placeFormBellowAllPosts: data.settings.place_form_bellow_all_posts === '1',
      placePopupFormOnAllPages: data.settings.place_popup_form_on_all_pages === '1',
      placePopupFormOnAllPosts: data.settings.place_popup_form_on_all_posts === '1',
      popupFormDelay: asNum(data.settings.popup_form_delay),
      placeFixedBarFormOnAllPages: data.settings.place_fixed_bar_form_on_all_pages === '1',
      placeFixedBarFormOnAllPosts: data.settings.place_fixed_bar_form_on_all_posts === '1',
      fixedBarFormDelay: asNum(data.settings.fixed_bar_form_delay),
      fixedBarFormPosition: data.settings.fixed_bar_form_position,
      placeSlideInFormOnAllPages: data.settings.place_slide_in_form_on_all_pages === '1',
      placeSlideInFormOnAllPosts: data.settings.place_slide_in_form_on_all_posts === '1',
      slideInFormDelay: asNum(data.settings.slide_in_form_delay),
      slideInFormPosition: data.settings.slide_in_form_position,
      borderRadius: asNum(data.settings.border_radius),
      borderSize: asNum(data.settings.border_size),
      formPadding: data.settings.form_padding ? asNum(data.settings.form_padding) : 10,
      inputPadding: data.settings.input_padding ? asNum(data.settings.input_padding) : 5,
      borderColor: data.settings.border_color,
    },
  };
}
