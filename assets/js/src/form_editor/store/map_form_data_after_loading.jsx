export default function mapFormDataAfterLoading(data) {
  return {
    ...data,
    settings: {
      ...data.settings,
      placeFormBellowAllPages: data.settings.place_form_bellow_all_pages === '1',
      placeFormBellowAllPosts: data.settings.place_form_bellow_all_posts === '1',
      placePopupFormOnAllPages: data.settings.place_popup_form_on_all_pages === '1',
      placePopupFormOnAllPosts: data.settings.place_popup_form_on_all_posts === '1',
    },
  };
}
