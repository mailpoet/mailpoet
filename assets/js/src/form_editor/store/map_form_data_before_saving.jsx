export default function mapFormDataBeforeSaving(data) {
  const mappedData = {
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
      fixed_bar_form_position: data.settings.fixedBarFormPosition,
      place_slide_in_form_on_all_pages: data.settings.placeSlideInFormOnAllPages === true ? '1' : '',
      place_slide_in_form_on_all_posts: data.settings.placeSlideInFormOnAllPosts === true ? '1' : '',
      slide_in_form_delay: data.settings.slideInFormDelay,
      slide_in_form_position: data.settings.slideInFormPosition,
      border_radius: data.settings.borderRadius,
      border_size: data.settings.borderSize,
      form_padding: data.settings.formPadding,
      input_padding: data.settings.inputPadding,
      border_color: data.settings.borderColor,
    },
  };

  delete mappedData.settings.placeFormBellowAllPages;
  delete mappedData.settings.placeFormBellowAllPosts;
  delete mappedData.settings.placePopupFormOnAllPages;
  delete mappedData.settings.placePopupFormOnAllPosts;
  delete mappedData.settings.popupFormDelay;
  delete mappedData.settings.placeFixedBarFormOnAllPages;
  delete mappedData.settings.placeFixedBarFormOnAllPosts;
  delete mappedData.settings.fixedBarFormDelay;
  delete mappedData.settings.fixedBarFormPosition;
  delete mappedData.settings.placeSlideInFormOnAllPages;
  delete mappedData.settings.placeSlideInFormOnAllPosts;
  delete mappedData.settings.slideInFormDelay;
  delete mappedData.settings.slideInFormPosition;
  delete mappedData.settings.borderRadius;
  delete mappedData.settings.borderSize;
  delete mappedData.settings.formPadding;
  delete mappedData.settings.inputPadding;
  delete mappedData.settings.borderColor;

  return mappedData;
}
