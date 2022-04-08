export default function mapFormDataBeforeSaving(data) {
  const mappedData = {
    ...data,
    settings: {
      ...data.settings,
      form_placement: {
        popup: {
          enabled:
            data.settings.formPlacement?.popup?.enabled === true ? '1' : '',
          exit_intent_enabled: data.settings.formPlacement?.popup
            ?.exitIntentEnabled
            ? '1'
            : '',
          delay: data.settings.formPlacement?.popup?.delay,
          cookieExpiration:
            data.settings.formPlacement?.popup?.cookieExpiration,
          styles: data.settings.formPlacement?.popup?.styles,
          animation: data.settings.formPlacement?.popup?.animation,
          categories: data.settings.formPlacement?.popup?.categories ?? [],
          tags: data.settings.formPlacement?.popup?.tags ?? [],
          posts: {
            all:
              data.settings.formPlacement?.popup?.posts?.all === true
                ? '1'
                : '',
            selected: data.settings.formPlacement?.popup?.posts?.selected,
          },
          pages: {
            all:
              data.settings.formPlacement?.popup?.pages?.all === true
                ? '1'
                : '',
            selected: data.settings.formPlacement?.popup?.pages?.selected,
          },
        },
        fixed_bar: {
          enabled:
            data.settings.formPlacement?.fixedBar?.enabled === true ? '1' : '',
          delay: data.settings.formPlacement?.fixedBar?.delay,
          cookieExpiration:
            data.settings.formPlacement?.fixedBar?.cookieExpiration,
          styles: data.settings.formPlacement?.fixedBar?.styles,
          position: data.settings.formPlacement?.fixedBar?.position,
          animation: data.settings.formPlacement?.fixedBar?.animation,
          categories: data.settings.formPlacement?.fixedBar?.categories ?? [],
          tags: data.settings.formPlacement?.fixedBar?.tags ?? [],
          posts: {
            all:
              data.settings.formPlacement?.fixedBar?.posts?.all === true
                ? '1'
                : '',
            selected: data.settings.formPlacement?.fixedBar?.posts?.selected,
          },
          pages: {
            all:
              data.settings.formPlacement?.fixedBar?.pages?.all === true
                ? '1'
                : '',
            selected: data.settings.formPlacement?.fixedBar?.pages?.selected,
          },
        },
        below_posts: {
          enabled:
            data.settings.formPlacement?.belowPosts?.enabled === true
              ? '1'
              : '',
          styles: data.settings.formPlacement?.belowPosts?.styles,
          categories: data.settings.formPlacement?.belowPosts?.categories ?? [],
          tags: data.settings.formPlacement?.belowPosts?.tags ?? [],
          posts: {
            all:
              data.settings.formPlacement?.belowPosts?.posts?.all === true
                ? '1'
                : '',
            selected: data.settings.formPlacement?.belowPosts?.posts?.selected,
          },
          pages: {
            all:
              data.settings.formPlacement?.belowPosts?.pages?.all === true
                ? '1'
                : '',
            selected: data.settings.formPlacement?.belowPosts?.pages?.selected,
          },
        },
        slide_in: {
          enabled:
            data.settings.formPlacement?.slideIn?.enabled === true ? '1' : '',
          delay: data.settings.formPlacement?.slideIn?.delay,
          cookieExpiration:
            data.settings.formPlacement?.slideIn?.cookieExpiration,
          position: data.settings.formPlacement?.slideIn?.position,
          animation: data.settings.formPlacement?.slideIn?.animation,
          styles: data.settings.formPlacement?.slideIn?.styles,
          categories: data.settings.formPlacement?.slideIn?.categories ?? [],
          tags: data.settings.formPlacement?.slideIn?.tags ?? [],
          posts: {
            all:
              data.settings.formPlacement?.slideIn?.posts?.all === true
                ? '1'
                : '',
            selected: data.settings.formPlacement?.slideIn?.posts?.selected,
          },
          pages: {
            all:
              data.settings.formPlacement?.slideIn?.pages?.all === true
                ? '1'
                : '',
            selected: data.settings.formPlacement?.slideIn?.pages?.selected,
          },
        },
        others: {
          styles: data.settings.formPlacement?.others?.styles,
        },
      },

      border_radius: data.settings.borderRadius,
      border_size: data.settings.borderSize,
      form_padding: data.settings.formPadding,
      input_padding: data.settings.inputPadding,
      border_color: data.settings.borderColor,
      font_family: data.settings.fontFamily,
      success_validation_color: data.settings.successValidationColor,
      error_validation_color: data.settings.errorValidationColor,
      background_image_url: data.settings.backgroundImageUrl,
      background_image_display: data.settings.backgroundImageDisplay,
      close_button: data.settings.closeButton,
    },
  };

  if (mappedData.settings.font_family === '') {
    delete mappedData.settings.font_family;
  }

  delete mappedData.settings.formPlacement;
  delete mappedData.settings.successValidationColor;
  delete mappedData.settings.errorValidationColor;
  delete mappedData.settings.borderRadius;
  delete mappedData.settings.borderSize;
  delete mappedData.settings.formPadding;
  delete mappedData.settings.inputPadding;
  delete mappedData.settings.borderColor;
  delete mappedData.settings.backgroundImageUrl;
  delete mappedData.settings.backgroundImageDisplay;
  delete mappedData.settings.fontFamily;
  delete mappedData.settings.closeButton;

  return mappedData;
}
