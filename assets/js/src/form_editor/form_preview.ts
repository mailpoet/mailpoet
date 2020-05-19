import jQuery from 'jquery';

jQuery(($) => {
  $(() => {
    const previewForm = $('div.mailpoet_form[data-is-preview="1"]');
    if (!previewForm.length) {
      return;
    }

    previewForm.submit((e) => { e.preventDefault(); return false; });

    const updateForm = (event) => {
      let width = null;
      if (!event.data) {
        return;
      }
      const formType = event.data.formType;
      if (formType === 'popup') {
        width = event.data.formSettings?.popupStyles.width;
      } else if (formType === 'fixed_bar') {
        width = event.data.formSettings?.fixedBarStyles.width;
      } else if (formType === 'slide_in') {
        width = event.data.formSettings?.slideInStyles.width;
      } else if (formType === 'below_post') {
        width = event.data.formSettings?.belowPostStyles.width;
      } else if (formType === 'others') {
        width = event.data.formSettings?.otherStyles.width;
      }

      if (width) {
        const unit = width.unit === 'pixel' ? 'px' : '%';
        previewForm.css('width', `${width.value}${unit}`);
        previewForm.css('max-width', `${width.value}${unit}`);
      }

      if (formType === 'slide_in') {
        if (previewForm.hasClass('mailpoet_form_position_left') && event.data.formSettings?.slideInFormPosition === 'right') {
          previewForm.removeClass('mailpoet_form_position_left');
          previewForm.addClass('mailpoet_form_position_right');
        } else if (previewForm.hasClass('mailpoet_form_position_right') && event.data.formSettings?.slideInFormPosition === 'left') {
          previewForm.removeClass('mailpoet_form_position_right');
          previewForm.addClass('mailpoet_form_position_left');
        }
      }

      if (formType === 'fixed_bar') {
        if (previewForm.hasClass('mailpoet_form_position_bottom') && event.data.formSettings?.fixedBarFormPosition === 'top') {
          previewForm.removeClass('mailpoet_form_position_bottom');
          previewForm.addClass('mailpoet_form_position_top');
        } else if (previewForm.hasClass('mailpoet_form_position_top') && event.data.formSettings?.fixedBarFormPosition === 'bottom') {
          previewForm.removeClass('mailpoet_form_position_top');
          previewForm.addClass('mailpoet_form_position_bottom');
        }
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
