import jQuery from 'jquery';
import Parsley from 'parsleyjs';

jQuery(($) => {
  Parsley.addValidator('scheduledAt', {
    requirementType: 'string',
    validateString: (value, error) => {
      const maxYears = 5;
      const hoursInYear = 8760;
      const daysInYear = 365;
      const weeksInYear = 52;

      const selectType = $(
        'select[name="afterTimeType"],select#scheduling_time_interval',
      );
      const afterTimeType = selectType.val();
      let isValid = true;
      if (afterTimeType === 'hours' && hoursInYear * maxYears < value) {
        isValid = false;
      }
      if (afterTimeType === 'days' && daysInYear * maxYears < value) {
        isValid = false;
      }
      if (afterTimeType === 'weeks' && weeksInYear * maxYears < value) {
        isValid = false;
      }

      if (!isValid) {
        return $.Deferred().reject(error);
      }
      return true;
    },
    messages: {
      en: 'An email can only be scheduled up to 5 years in the future. Please choose a shorter period.',
    },
  });

  Parsley.addValidator('segmentsWithSubscribers', {
    requirementType: 'string',
    validateMultiple: (values, noSegmentWithSubscribersError) => {
      const segments = window.mailpoet_segments || [];

      let isValid = true;
      values.forEach((segmentId) => {
        const segment = segments.find((s) => s.id === segmentId);
        if (segment && segment.subscribers === 0) {
          isValid = false;
        }
      });

      if (!isValid) {
        return $.Deferred().reject(noSegmentWithSubscribersError);
      }
      return true;
    },
    messages: {
      en: 'Please select a list with subscribers',
    },
  });
});
