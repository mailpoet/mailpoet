function displayPoll() {
  if (
    window.mailpoet_display_nps_poll
    && window.satismeter
    && window.mailpoet_installed_at_isoFormat
  ) {
    // New users poll
    window.satismeter({
      writeKey: '6L479eVPXk7pBn6S',
      userId: window.mailpoet_current_wp_user.ID + window.mailpoet_site_url,
      traits: {
        name: window.mailpoet_current_wp_user.user_nicename,
        email: window.mailpoet_current_wp_user.user_email,
        createdAt: window.mailpoet_installed_at_isoFormat,
      },
    });
    // Old users poll
    satismeter({
      writeKey: "k0aJAsQAWI2ERyGv",
      userId: window.mailpoet_current_wp_user.ID + window.mailpoet_site_url,
      traits: {
        name: window.mailpoet_current_wp_user.user_nicename,
        email: window.mailpoet_current_wp_user.user_email,
        createdAt: window.mailpoet_installed_at_isoFormat,
      },
    });
  }
}

setImmediate(displayPoll);
