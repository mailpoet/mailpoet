import jQuery from 'jquery';
import { camelCase } from 'lodash';

jQuery(($) => {
  $(() => {
    const $previewForm = $('div.mailpoet_form[data-is-preview="1"]');
    if (!$previewForm.length) {
      return;
    }

    $previewForm.on('submit', (e) => {
      e.preventDefault();
      return false;
    });

    const toggleClass = (form, from, to): void => {
      form.removeClass(from);
      setTimeout(() => form.addClass(to));
    };

    let originalFormSettings;
    const updateForm = (event): void => {
      if (!event.data) {
        return;
      }
      // Allow message processing only when send from editor's origin
      const editorUrl = new URL($previewForm.data('editor-url') as string);
      if (editorUrl.origin !== event.origin) {
        return;
      }

      const formType = event.data.formType as string;
      const placementName = camelCase(formType);
      // Get width settings based on type
      const width =
        event.data.formSettings?.formPlacement?.[placementName]?.styles?.width;

      if (!width) {
        return;
      }

      // Apply width settings
      const unit = width.unit === 'pixel' ? 'px' : '%';
      const newWidth = `${width.value}${unit}`;
      if (formType === 'fixed_bar') {
        const formElement = $previewForm.find('form.mailpoet_form');
        formElement.css('width', newWidth);
      } else {
        $previewForm.css('width', newWidth);
        if (unit === 'px') {
          // Update others container width to render full pixel size
          $('#mailpoet_widget_preview #sidebar').css('width', newWidth);
        } else {
          // Reset container size to default render percent size
          $('#mailpoet_widget_preview #sidebar').css('width', null);
        }
      }

      // When some settings are changed we want to replay animation
      const newFormSettings =
        event.data.formSettings?.formPlacement?.[placementName];
      const allowAnimation =
        newFormSettings?.position !== originalFormSettings?.position ||
        newFormSettings?.animation !== originalFormSettings?.animation;
      originalFormSettings = newFormSettings;

      const animation =
        event.data.formSettings?.formPlacement?.[placementName]?.animation;
      if (animation !== '' && allowAnimation) {
        $previewForm.removeClass((_, className) =>
          (className.match(/(^|\s)mailpoet_form_animation\S+/g) || []).join(
            ' ',
          ),
        );
        setTimeout(() =>
          $previewForm.addClass(`mailpoet_form_animation_${animation}`),
        );
        toggleClass(
          $previewForm.prev('.mailpoet_form_popup_overlay'),
          'mailpoet_form_overlay_animation',
          'mailpoet_form_overlay_animation',
        );
      }

      const position =
        event.data.formSettings?.formPlacement?.[placementName]?.position;

      // Ajdust others (widget) container
      if (formType === 'others') {
        if (unit === 'px') {
          // Update others container width so that we can render full pixel size
          $('#mailpoet_widget_preview #sidebar').css(
            'width',
            `${width.value}${unit}`,
          );
        } else {
          // Reset container size to default render percent size
          $('#mailpoet_widget_preview #sidebar').css('width', null);
        }
      }

      if (formType === 'slide_in') {
        if (
          $previewForm.hasClass('mailpoet_form_position_left') &&
          position === 'right'
        ) {
          toggleClass(
            $previewForm,
            'mailpoet_form_position_left',
            'mailpoet_form_position_right',
          );
        } else if (
          $previewForm.hasClass('mailpoet_form_position_right') &&
          position === 'left'
        ) {
          toggleClass(
            $previewForm,
            'mailpoet_form_position_right',
            'mailpoet_form_position_left',
          );
        }
      }

      if (formType === 'fixed_bar') {
        if (
          $previewForm.hasClass('mailpoet_form_position_bottom') &&
          position === 'top'
        ) {
          toggleClass(
            $previewForm,
            'mailpoet_form_position_bottom',
            'mailpoet_form_position_top',
          );
        } else if (
          $previewForm.hasClass('mailpoet_form_position_top') &&
          position === 'bottom'
        ) {
          toggleClass(
            $previewForm,
            'mailpoet_form_position_top',
            'mailpoet_form_position_bottom',
          );
        }
      }

      // Detect tight container
      $previewForm.removeClass('mailpoet_form_tight_container');
      if ($previewForm.width() < 400) {
        $previewForm.addClass('mailpoet_form_tight_container');
      }
    };
    window.addEventListener('message', updateForm, false);

    // Display only form on widget preview page
    // This should keep element visible and still placed within the content but hide everything else
    const widgetPreview = $('#mailpoet_widget_preview');
    if (widgetPreview.length) {
      $('#mailpoet_widget_preview').siblings().hide();
      $('#mailpoet_widget_preview').parents().siblings().hide();
    }
  });
});
