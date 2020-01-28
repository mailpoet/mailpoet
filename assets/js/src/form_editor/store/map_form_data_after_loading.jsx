export default function mapFormDataAfterLoading(data) {
  return {
    ...data,
    settings: {
      ...data.settings,
      placeFormBellowAllPages: data.settings.placeFormBellowAllPages === '1',
      placeFormBellowAllPosts: data.settings.placeFormBellowAllPosts === '1',
    },
  };
}
