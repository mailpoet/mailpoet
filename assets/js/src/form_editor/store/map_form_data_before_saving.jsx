export default function mapFormDataBeforeSaving(data) {
  const mappedData = {
    ...data,
    settings: {
      ...data.settings,
      form_placement: {
        popup: {
          enabled: data.settings.formPlacement?.popup?.enabled === true ? '1' : '',
          exit_intent_enabled: data.settings.formPlacement?.popup?.exitIntentEnabled ? '1' : '',
          delay: data.settings.formPlacement?.popup?.delay,
          styles: data.settings.formPlacement?.popup?.styles,
          posts: {
            all: data.settings.formPlacement?.popup?.posts?.all === true ? '1' : '',
          },
          pages: {
            all: data.settings.formPlacement?.popup?.pages?.all === true ? '1' : '',
          },
        },
        fixed_bar: {
          enabled: data.settings.formPlacement?.fixedBar?.enabled === true ? '1' : '',
          delay: data.settings.formPlacement?.fixedBar?.delay,
          styles: data.settings.formPlacement?.fixedBar?.styles,
          position: data.settings.formPlacement?.fixedBar?.position,
          posts: {
            all: data.settings.formPlacement?.fixedBar?.posts?.all === true ? '1' : '',
          },
          pages: {
            all: data.settings.formPlacement?.fixedBar?.pages?.all === true ? '1' : '',
          },
        },
        below_posts: {
          enabled: data.settings.formPlacement?.belowPosts?.enabled === true ? '1' : '',
          styles: data.settings.formPlacement?.belowPosts?.styles,
          posts: {
            all: data.settings.formPlacement?.belowPosts?.posts?.all === true ? '1' : '',
          },
          pages: {
            all: data.settings.formPlacement?.belowPosts?.pages?.all === true ? '1' : '',
          },
        },
        slide_in: {
          enabled: data.settings.formPlacement?.slideIn?.enabled === true ? '1' : '',
          delay: data.settings.formPlacement?.slideIn?.delay,
          position: data.settings.formPlacement?.slideIn?.position,
          styles: data.settings.formPlacement?.slideIn?.styles,
          posts: {
            all: data.settings.formPlacement?.slideIn?.posts?.all === true ? '1' : '',
          },
          pages: {
            all: data.settings.formPlacement?.slideIn?.pages?.all === true ? '1' : '',
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
