export default function mapFormDataBeforeSaving(data) {
  return {
    ...data,
    settings: {
      ...data.settings,
      place_form_bellow_all_pages: data.settings.placeFormBellowAllPages === true ? '1' : '',
      place_form_bellow_all_posts: data.settings.placeFormBellowAllPosts === true ? '1' : '',
      place_popup_form_on_all_pages: data.settings.placePopupFormOnAllPages === true ? '1' : '',
      place_popup_form_on_all_posts: data.settings.placePopupFormOnAllPosts === true ? '1' : '',
      popup_form_delay: data.settings.popupFormDelay,
      place_fixed_bar_form_on_all_pages: data.settings.placeFixedBarFormOnAllPages === true ? '1' : '',
      place_fixed_bar_form_on_all_posts: data.settings.placeFixedBarFormOnAllPosts === true ? '1' : '',
      fixed_bar_form_delay: data.settings.fixedBarFormDelay,
    },
  };
}
