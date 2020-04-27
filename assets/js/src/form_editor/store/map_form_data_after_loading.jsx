function asNum(num) {
  const numI = parseInt(num, 10);
  if (Number.isNaN(numI)) {
    return undefined;
  }
  return numI;
}


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
      borderRadius: asNum(data.settings.borderRadius),
      borderSize: asNum(data.settings.borderSize),
      formPadding: asNum(data.settings.formPadding),
      inputPadding: asNum(data.settings.inputPadding),
    },
  };
}
