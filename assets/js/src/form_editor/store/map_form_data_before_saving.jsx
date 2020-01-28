export default function mapFormDataBeforeSaving(data) {
  return {
    ...data,
    settings: {
      ...data.settings,
      placeFormBellowAllPages: data.settings.placeFormBellowAllPages === true ? '1' : '',
      placeFormBellowAllPosts: data.settings.placeFormBellowAllPosts === true ? '1' : '',
    },
  };
}
