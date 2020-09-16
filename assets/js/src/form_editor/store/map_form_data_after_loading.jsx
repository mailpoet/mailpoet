/* eslint-disable camelcase */
import asNum from './server_value_as_num';
import * as defaults from './defaults';

export default function mapFormDataAfterLoading(data) {
  const mapped = {
    ...data,
    settings: {
      ...data.settings,
      formPlacement: {
        popup: {
          enabled: data.settings.form_placement?.popup?.enabled === '1',
          exitIntentEnabled: data.settings.form_placement?.popup?.exit_intent_enabled === '1',
          delay: data.settings.form_placement?.popup?.delay !== undefined
            ? asNum(data.settings.form_placement?.popup?.delay)
            : defaults.popupForm.formDelay,
          animation: data.settings.form_placement?.popup?.animation !== undefined
            ? data.settings.form_placement?.popup?.animation
            : defaults.popupForm.animation,
          styles: { ...defaults.popupForm.styles, ...data.settings.form_placement?.popup?.styles },
          posts: {
            all: data.settings.form_placement?.popup?.posts?.all === '1',
          },
          pages: {
            all: data.settings.form_placement?.popup?.pages?.all === '1',
          },
        },
        fixedBar: {
          enabled: data.settings.form_placement?.fixed_bar?.enabled === '1',
          delay: data.settings.form_placement?.fixed_bar?.delay !== undefined
            ? asNum(data.settings.form_placement?.fixed_bar?.delay)
            : defaults.fixedBarForm.formDelay,
          animation: data.settings.form_placement?.fixed_bar?.animation
            ?? defaults.fixedBarForm.animation,
          styles: {
            ...defaults.fixedBarForm.styles,
            ...data.settings.form_placement?.fixed_bar?.styles,
          },
          position: data.settings.form_placement?.fixed_bar?.position
            ?? defaults.fixedBarForm.position,
          posts: {
            all: data.settings.form_placement?.fixed_bar?.posts?.all === '1',
          },
          pages: {
            all: data.settings.form_placement?.fixed_bar?.pages?.all === '1',
          },
        },
        belowPosts: {
          enabled: data.settings.form_placement?.below_posts?.enabled === '1',
          styles: {
            ...defaults.belowPostForm.styles,
            ...data.settings.form_placement?.below_posts?.styles,
          },
          posts: {
            all: data.settings.form_placement?.below_posts?.posts?.all === '1',
          },
          pages: {
            all: data.settings.form_placement?.below_posts?.pages?.all === '1',
          },
        },
        slideIn: {
          enabled: data.settings.form_placement?.slide_in?.enabled === '1',
          delay: data.settings.form_placement?.slide_in?.delay !== undefined
            ? asNum(data.settings.form_placement?.slide_in?.delay)
            : defaults.slideInForm.formDelay,
          position: data.settings.form_placement?.slide_in?.position
            ?? defaults.slideInForm.position,
          animation: data.settings.form_placement?.slide_in?.position
            ?? defaults.slideInForm.animation,
          styles: {
            ...defaults.slideInForm.styles,
            ...data.settings.form_placement?.slide_in?.styles,
          },
          posts: {
            all: data.settings.form_placement?.slide_in?.posts?.all === '1',
          },
          pages: {
            all: data.settings.form_placement?.slide_in?.pages?.all === '1',
          },
        },
        others: {
          styles: { ...defaults.otherForm.styles, ...data.settings.form_placement?.others?.styles },
        },
      },

      alignment: data.settings.alignment ?? defaults.formStyles.alignment,
      borderRadius: data.settings.border_radius !== undefined
        ? asNum(data.settings.border_radius)
        : defaults.formStyles.borderRadius,
      borderSize: data.settings.border_size !== undefined
        ? asNum(data.settings.border_size)
        : defaults.formStyles.borderSize,
      formPadding: data.settings.form_padding !== undefined
        ? asNum(data.settings.form_padding)
        : defaults.formStyles.formPadding,
      inputPadding: data.settings.input_padding !== undefined
        ? asNum(data.settings.input_padding)
        : defaults.formStyles.inputPadding,
      borderColor: data.settings.border_color,
      fontFamily: data.settings.font_family,
      successValidationColor: data.settings.success_validation_color,
      errorValidationColor: data.settings.error_validation_color,
      backgroundImageUrl: data.settings.background_image_url,
      backgroundImageDisplay: data.settings.background_image_display,
      closeButton: data.settings.close_button ?? defaults.formStyles.closeButton,

    },
  };

  mapped.settings.formPlacement.belowPosts.styles.width.value = asNum(
    mapped.settings.formPlacement.belowPosts.styles.width.value
  );
  mapped.settings.formPlacement.slideIn.styles.width.value = asNum(
    mapped.settings.formPlacement.slideIn.styles.width.value
  );
  mapped.settings.formPlacement.fixedBar.styles.width.value = asNum(
    mapped.settings.formPlacement.fixedBar.styles.width.value
  );
  mapped.settings.formPlacement.popup.styles.width.value = asNum(
    mapped.settings.formPlacement.popup.styles.width.value
  );
  mapped.settings.formPlacement.others.styles.width.value = asNum(
    mapped.settings.formPlacement.others.styles.width.value
  );

  return mapped;
}
