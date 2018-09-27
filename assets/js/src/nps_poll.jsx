function displayPoll() {
  if (window.display_nps_poll && window.satismeter) {
    window.satismeter({
      writeKey: '6L479eVPXk7pBn6S',
      userId: window.current_wp_user.ID + window.site_url,
      traits: {
        name: window.current_wp_user.user_nicename,
        email: window.current_wp_user.user_email,
        createdAt: window.mailpoet_settings.installed_at,
      },
    });
  }
}

setImmediate(displayPoll);
