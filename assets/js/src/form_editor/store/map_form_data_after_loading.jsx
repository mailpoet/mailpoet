export default function mapFormDataAfterLoading(data) {
  let popupFormDelay = parseInt(data.settings.popup_form_delay, 10);
  if (Number.isNaN(popupFormDelay)) {
    popupFormDelay = undefined;
  }
  let fixedBarFormDelay = parseInt(data.settings.fixed_bar_form_delay, 10);
  if (Number.isNaN(fixedBarFormDelay)) {
    fixedBarFormDelay = undefined;
  }

  return {
    ...data,
    settings: {
      ...data.settings,
      placeFormBellowAllPages: data.settings.place_form_bellow_all_pages === '1',
      placeFormBellowAllPosts: data.settings.place_form_bellow_all_posts === '1',
      placePopupFormOnAllPages: data.settings.place_popup_form_on_all_pages === '1',
      placePopupFormOnAllPosts: data.settings.place_popup_form_on_all_posts === '1',
      popupFormDelay,
      placeFixedBarFormOnAllPages: data.settings.place_fixed_bar_form_on_all_pages === '1',
      placeFixedBarFormOnAllPosts: data.settings.place_fixed_bar_form_on_all_posts === '1',
      fixedBarFormDelay,
    },
  };
}
