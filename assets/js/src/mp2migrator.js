(function ($) {
  'use strict';

  var that;

  var mailpoet_import = {
    fatal_error: '',
    is_logging: false,
    
    /**
     * Start the logger
     */
    start_logger: function () {
      that.is_logging = true;
      clearTimeout(that.display_logs_timeout);
      clearTimeout(that.update_progressbar_timeout);
      clearTimeout(that.update_wordpress_info_timeout);
      that.update_display();
    },
    
    /**
     * Stop the logger
     */
    stop_logger: function () {
      that.is_logging = false;
    },
    
    /**
     * Update the display
     */
    update_display: function () {
      that.display_logs();
      that.update_progressbar();
    },
    
    /**
     * Display the logs
     */
    display_logs: function () {
      $.ajax({
        url: objectPlugin.log_file_url,
        cache: false
      }).done(function (result) {
        $('#action_message').html(''); // Clear the action message
        $("#logger").html('');
        result.split("\n").forEach(function (row) {
          if(row.substr(0, 7) === '[ERROR]' || row.substr(0, 9) === '[WARNING]' || row === 'IMPORT STOPPED BY USER') {
            row = '<span class="error_msg">' + row + '</span>'; // Mark the errors in red
          }
          // Test if the import is complete
          else if(row === 'IMPORT COMPLETE') {
            row = '<span class="complete_msg">' + row + '</span>'; // Mark the complete message in green
            $('#action_message').html(MailPoet.I18n.t('import_complete'))
                    .removeClass('failure').addClass('success');
          }
          $("#logger").append(row + "<br />\n");

        });
        $("#logger").append('<span class="error_msg">' + that.fatal_error + '</span>' + "<br />\n");
      }).always(function () {
        if(that.is_logging) {
          that.display_logs_timeout = setTimeout(that.display_logs, 1000);
        }
      });
    },
    
    /**
     * Update the progressbar
     */
    update_progressbar: function () {
      $.ajax({
        url: objectPlugin.progress_url,
        cache: false,
        dataType: 'json'
      }).always(function (result) {
        // Move the progress bar
        var progress = Math.round(Number(result.current) / Number(result.total) * 100);
        $('#progressbar').progressbar('option', 'value', progress);
        $('#progresslabel').html(progress + '%');
        if(that.is_logging) {
          that.update_progressbar_timeout = setTimeout(that.update_progressbar, 1000);
        }
      });
    },
    
    /**
     * Start the import
     *
     * @returns {Boolean}
     */
    start_import: function () {
      that.fatal_error = '';
      // Start displaying the logs
      that.start_logger();

      // Disable the import button
      that.import_button_label = $('#import').val();
      $('#import').val(MailPoet.I18n.t('importing')).attr('disabled', 'disabled');
      // Show the stop button
      $('#stop-import').show();
      // Clear the action message
      $('#action_message').html('');

      // Run the import
      MailPoet.Ajax.post({
        endpoint: 'MP2Migrator',
        action: 'import',
        data: {
        }
      }).always(function () {
        that.stop_logger();
        that.update_display(); // Get the latest information after the import was stopped
        that.reactivate_import_button();
      }).done(function (response) {
        if(response) {
          that.fatal_error = response.data;
        }
      }).fail(function (response) {
        if(response.errors.length > 0) {
          MailPoet.Notice.error(
            response.errors.map(function (error) {
              return error.message;
            }),
            {scroll: true}
          );
        }
      });
      return false;
    },
    
    /**
     * Reactivate the import button
     *
     */
    reactivate_import_button: function () {
      $('#import').val(that.import_button_label).removeAttr('disabled');
      $('#stop-import').hide();
    },
    
    /**
     * Stop the import
     *
     * @returns {Boolean}
     */
    stop_import: function () {
      $('#stop-import').attr('disabled', 'disabled');
      // Stop the import
      MailPoet.Ajax.post({
        endpoint: 'MP2Migrator',
        action: 'stopImport',
        data: {
        }
      }).always(function () {
        $('#stop-import').removeAttr('disabled'); // Enable the button
        that.reactivate_import_button();
        that.update_display(); // Get the latest information after the import was stopped
      });
      that.stop_logger();
      return false;
    },

    /**
     * Skip the import
     *
     * @returns {Boolean}
     */
    skip_import: function () {
      MailPoet.Ajax.post({
        endpoint: 'MP2Migrator',
        action: 'skipImport',
        data: {
        }
      }).done(function () {
        window.location.reload();
      });
      return false;
    }

  };

  /**
   * Actions to run when the DOM is ready
   */
  $(function () {
    that = mailpoet_import;

    $('#progressbar').progressbar({value: 0});

    // Import button
    $('#import').click(that.start_import);

    // Stop import button
    $('#stop-import').click(that.stop_import);

    // Skip import link
    $('#skip-import').click(that.skip_import);
  });

})(jQuery);
