export default function mapFormDataBeforeSaving(data) {
  return {
    ...data,
    settings: {
      ...data.settings,
      place_form_bellow_all_pages: data.settings.placeFormBellowAllPages === true ? '1' : '',
      place_form_bellow_all_posts: data.settings.placeFormBellowAllPosts === true ? '1' : '',
    },
  };
}
