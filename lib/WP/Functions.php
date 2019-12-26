<?php

namespace MailPoet\WP;

use WP_Error;

class Functions {

  static private $instance;

  /**
   * @return Functions
   */
  public static function get() {
    if (self::$instance === null) {
      self::$instance = new Functions;
    }
    return self::$instance;
  }

  public static function set(Functions $instance) {
    self::$instance = $instance;
  }

  /**
   * @param string $tag
   * @param mixed ...$args
   * @return mixed
   */
  public function doAction($tag, ...$args) {
    return call_user_func_array('do_action', func_get_args());
  }

  /**
   * @param string $tag
   * @param mixed ...$args
   * @return mixed
   */
  public function applyFilters($tag, ...$args) {
    return call_user_func_array('apply_filters', func_get_args());
  }

  /**
   * @param string $tag
   * @param callable $function_to_add
   * @param int $priority
   * @param int $accepted_args
   * @return boolean
   */
  public function addAction($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
    return call_user_func_array('add_action', func_get_args());
  }

  public function __($text, $domain = 'default') {
    return __($text, $domain);
  }

  public function _e($text, $domain = 'default') {
    return _e($text, $domain);
  }

  public function _n($single, $plural, $number, $domain = 'default') {
    return _n($single, $plural, $number, $domain);
  }

  public function _x($text, $context, $domain = 'default') {
    return _x($text, $context, $domain);
  }

  public function addCommentMeta($comment_id, $meta_key, $meta_value, $unique = false) {
    return add_comment_meta($comment_id, $meta_key, $meta_value, $unique);
  }

  public function addFilter($tag, callable $function_to_add, $priority = 10, $accepted_args = 1) {
    return add_filter($tag, $function_to_add, $priority, $accepted_args);
  }

  /**
   * @param bool|array $crop
   */
  public function addImageSize($name, $width = 0, $height = 0, $crop = false) {
    return add_image_size($name, $width, $height, $crop);
  }

  public function addMenuPage($page_title, $menu_title, $capability, $menu_slug, callable $function = null, $icon_url = '', $position = null) {
    if (is_null($function)) {
      $function = function () {
      };
    }
    return add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position);
  }

  public function addQueryArg($key, $value = false, $url = false) {
    return add_query_arg($key, $value, $url);
  }

  public function addScreenOption($option, $args = []) {
    return add_screen_option($option, $args);
  }

  public function addShortcode($tag, callable $callback) {
    return add_shortcode($tag, $callback);
  }

  public function addSubmenuPage($parent_slug, $page_title, $menu_title, $capability, $menu_slug, callable $function) {
    return add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);
  }

  public function adminUrl($path = '', $scheme = 'admin') {
    return admin_url($path, $scheme);
  }

  public function currentFilter() {
    return current_filter();
  }

  public function currentTime($type, $gmt = false) {
    return current_time($type, $gmt);
  }

  public function currentUserCan($capability) {
    return current_user_can($capability);
  }

  public function dateI18n($dateformatstring, $timestamp_with_offset = false, $gmt = false) {
    return date_i18n($dateformatstring, $timestamp_with_offset, $gmt);
  }

  public function deleteCommentMeta($comment_id, $meta_key, $meta_value = '') {
    return delete_comment_meta($comment_id, $meta_key, $meta_value);
  }

  public function deleteOption($option) {
    return delete_option($option);
  }

  public function doShortcode($content, $ignore_html = false) {
    return do_shortcode($content, $ignore_html);
  }

  public function escAttr($text) {
    return esc_attr($text);
  }

  public function escHtml($text) {
    return esc_html($text);
  }

  public function escSql($sql) {
    return esc_sql($sql);
  }

  public function getBloginfo($show = '', $filter = 'raw') {
    return get_bloginfo($show, $filter);
  }

  public function getCategories($args = '') {
    return get_categories($args);
  }

  public function getComment($comment = null, $output = OBJECT) {
    return get_comment($comment, $output);
  }

  public function getCommentMeta($comment_id, $key = '', $single = false) {
    return get_comment_meta($comment_id, $key, $single);
  }

  public function getCurrentScreen() {
    return get_current_screen();
  }

  public function getCurrentUserId() {
    return get_current_user_id();
  }

  public function getDateFromGmt($string, $format = 'Y-m-d H:i:s') {
    return get_date_from_gmt($string, $format);
  }

  public function getEditProfileUrl($user_id = 0, $scheme = 'admin') {
    return get_edit_profile_url($user_id, $scheme);
  }

  public function getEditableRoles() {
    return get_editable_roles();
  }

  public function getLocale() {
    return get_locale();
  }

  public function getObjectTaxonomies($object, $output = 'names') {
    return get_object_taxonomies($object, $output);
  }

  public function getOption($option, $default = false) {
    return get_option($option, $default);
  }

  public function getPages($args = []) {
    return get_pages($args);
  }

  public function getPermalink($post, $leavename = false) {
    return get_permalink($post, $leavename);
  }

  public function getPluginPageHook($plugin_page, $parent_page) {
    return get_plugin_page_hook($plugin_page, $parent_page);
  }

  public function getPluginUpdates() {
    return get_plugin_updates();
  }

  public function getPlugins($plugin_folder = '') {
    return get_plugins($plugin_folder);
  }

  public function getPost($post = null, $output = OBJECT, $filter = 'raw') {
    return get_post($post, $output, $filter);
  }

  public function getPostThumbnailId($post = null) {
    return get_post_thumbnail_id($post);
  }

  public function getPostTypes($args = [], $output = 'names', $operator = 'and') {
    return get_post_types($args, $output, $operator);
  }

  public function getPosts(array $args = null) {
    return get_posts($args);
  }

  public function getRole($role) {
    return get_role($role);
  }

  public function getSiteOption($option, $default = false, $deprecated = true) {
    return get_site_option($option, $default, $deprecated);
  }

  public function getSiteUrl($blog_id = null, $path = '', $scheme = null) {
    return get_site_url($blog_id, $path, $scheme);
  }

  public function getTemplatePart($slug, $name = null) {
    return get_template_part($slug, $name);
  }

  /**
   * @param string|array $args
   * @param string|array $deprecated
   * @return array|int|WP_Error
   */
  public function getTerms($args = [], $deprecated = '') {
    return get_terms($args, $deprecated);
  }

  /**
   * @param int|false $user_id
   */
  public function getTheAuthorMeta($field = '', $user_id = false) {
    return get_the_author_meta($field, $user_id);
  }

  /**
   * @param  int|\WP_User $user_id
   */
  public function getUserLocale($user_id = 0) {
    return get_user_locale($user_id);
  }

  public function getUserMeta($user_id, $key = '', $single = false) {
    return get_user_meta($user_id, $key, $single);
  }

  public function getUserdata($user_id) {
    return get_userdata($user_id);
  }

  public function getUserBy($field, $value) {
    return get_user_by($field, $value);
  }

  public function hasFilter($tag, $function_to_check = false) {
    return has_filter($tag, $function_to_check);
  }

  public function homeUrl($path = '', $scheme = null) {
    return home_url($path, $scheme);
  }

  public function includesUrl($path = '', $scheme = null) {
    return includes_url($path, $scheme);
  }

  public function isAdmin() {
    return is_admin();
  }

  public function isEmail($email, $deprecated = false) {
    return is_email($email, $deprecated);
  }

  public function isMultisite() {
    return is_multisite();
  }

  public function isPluginActive($plugin) {
    return is_plugin_active($plugin);
  }

  public function isRtl() {
    return is_rtl();
  }

  public function isSerialized($data, $strict = true) {
    return is_serialized($data, $strict);
  }

  public function isUserLoggedIn() {
    return is_user_logged_in();
  }

  /**
   * @param  string|false $deprecated
   * @param  string|false $plugin_rel_path
   */
  public function loadPluginTextdomain($domain, $deprecated = false, $plugin_rel_path = false) {
    return load_plugin_textdomain($domain, $deprecated, $plugin_rel_path);
  }

  public function loadTextdomain($domain, $mofile) {
    return load_textdomain($domain, $mofile);
  }

  public function numberFormatI18n($number, $decimals = 0) {
    return number_format_i18n($number, $decimals);
  }

  public function pluginBasename($file) {
    return plugin_basename($file);
  }

  public function pluginsUrl($path = '', $plugin = '') {
    return plugins_url($path, $plugin);
  }

  public function registerActivationHook($file, $function) {
    return register_activation_hook($file, $function);
  }

  public function registerPostType($post_type, $args = []) {
    return register_post_type($post_type, $args);
  }

  public function registerWidget($widget) {
    return register_widget($widget);
  }

    /**
   * @param string $tag
   * @param callable $function_to_remove
   * @param int $priority
   */
  public function removeAction($tag, $function_to_remove, $priority = 10) {
    return remove_action($tag, $function_to_remove, $priority);
  }

  public function removeAllActions($tag, $priority = false) {
    return remove_all_actions($tag, $priority);
  }

  public function removeAllFilters($tag, $priority = false) {
    return remove_all_filters($tag, $priority);
  }

  public function removeFilter($tag, callable $function_to_remove, $priority = 10) {
    return remove_filter($tag, $function_to_remove, $priority);
  }

  public function removeShortcode($tag) {
    return remove_shortcode($tag);
  }

  public function selfAdminUrl($path = '', $scheme = 'admin') {
    return self_admin_url($path, $scheme);
  }

  public function setTransient($transient, $value, $expiration) {
    return set_transient($transient, $value, $expiration);
  }

  public function getTransient($transient) {
    return get_transient($transient);
  }

  public function deleteTransient($transient) {
    return delete_transient($transient);
  }

  public function singlePostTitle($prefix = '', $display = true) {
    return single_post_title($prefix, $display);
  }

  public function siteUrl($path = '', $scheme = null) {
    return site_url($path, $scheme);
  }

  public function statusHeader($code, $description = '') {
    return status_header($code, $description);
  }

  public function stripslashesDeep($value) {
    return stripslashes_deep($value);
  }

  public function translate($text, $domain = 'default') {
    return translate($text, $domain);
  }

  public function unloadTextdomain($domain) {
    return unload_textdomain($domain);
  }

  public function updateOption($option, $value, $autoload = null) {
    return update_option($option, $value, $autoload);
  }

  public function wpAddInlineScript($handle, $data, $position = 'after') {
    return wp_add_inline_script($handle, $data, $position);
  }

  public function wpCreateNonce($action = -1) {
    return wp_create_nonce($action);
  }

  public function wpDequeueScript($handle) {
    return wp_dequeue_script($handle);
  }

  public function wpDequeueStyle($handle) {
    return wp_dequeue_style($handle);
  }

  public function wpEncodeEmoji($content) {
    return wp_encode_emoji($content);
  }

  public function wpEnqueueMedia(array $args = []) {
    return wp_enqueue_media($args);
  }

  public function wpEnqueueScript($handle, $src = '', array $deps = [], $ver = false, $in_footer = false) {
    return wp_enqueue_script($handle, $src, $deps, $ver, $in_footer);
  }

  public function wpEnqueueStyle($handle, $src = '', array $deps = [], $ver = false, $media = 'all') {
    return wp_enqueue_style($handle, $src, $deps, $ver, $media);
  }

  public function wpGetAttachmentImageSrc($attachment_id, $size = 'thumbnail', $icon = false) {
    return wp_get_attachment_image_src($attachment_id, $size, $icon);
  }

  public function wpGetCurrentUser() {
    return wp_get_current_user();
  }

  public function wpGetPostTerms($post_id, $taxonomy = 'post_tag', array $args = []) {
    return wp_get_post_terms($post_id, $taxonomy, $args);
  }

  public function wpGetReferer() {
    return wp_get_referer();
  }

  public function wpGetTheme($stylesheet = null, $theme_root = null) {
    return wp_get_theme($stylesheet, $theme_root);
  }

  public function wpInsertPost(array $postarr, $wp_error = false) {
    return wp_insert_post($postarr, $wp_error);
  }

  public function wpJsonEncode($data, $options = 0, $depth = 512) {
    return wp_json_encode($data, $options, $depth);
  }

  public function wpLocalizeScript($handle, $object_name, array $l10n) {
    return wp_localize_script($handle, $object_name, $l10n);
  }

  public function wpLoginUrl($redirect = '', $force_reauth = false) {
    return wp_login_url($redirect, $force_reauth);
  }

  public function wpParseArgs($args, $defaults = '') {
    return wp_parse_args($args, $defaults);
  }

  public function wpParseUrl($url, $component = -1) {
    return wp_parse_url($url, $component);
  }

  public function wpSpecialcharsDecode($string, $quote_style = ENT_NOQUOTES ) {
    return wp_specialchars_decode($string, $quote_style);
  }

  public function wpPrintScripts($handles = false) {
    return wp_print_scripts($handles);
  }

  public function wpRemoteGet($url, array $args = []) {
    return wp_remote_get($url, $args);
  }

  public function wpRemotePost($url, array $args = []) {
    return wp_remote_post($url, $args);
  }

  public function wpRemoteRetrieveBody($response) {
    return wp_remote_retrieve_body($response);
  }

  public function wpRemoteRetrieveResponseCode($response) {
    return wp_remote_retrieve_response_code($response);
  }

  public function wpRemoteRetrieveResponseMessage($response) {
    return wp_remote_retrieve_response_message($response);
  }

  public function wpSafeRedirect($location, $status = 302) {
    return wp_safe_redirect($location, $status);
  }

  public function wpSetCurrentUser($id, $name = '') {
    return wp_set_current_user($id, $name);
  }

  public function wpStaticizeEmoji($text) {
    return wp_staticize_emoji($text);
  }

  public function wpTrimWords($text, $num_words = 55, $more = null) {
    return wp_trim_words($text, $num_words, $more);
  }

  public function wpUploadDir($time = null, $create_dir = true, $refresh_cache = false) {
    return wp_upload_dir($time, $create_dir, $refresh_cache);
  }

  public function wpVerifyNonce($nonce, $action = -1) {
    return wp_verify_nonce($nonce, $action);
  }

  public function wpautop($pee, $br = true) {
    return wpautop($pee, $br);
  }

  /**
   * @param string $host
   * @return array|bool
   */
  public function parseDbHost($host) {
    global $wpdb;
    if (method_exists($wpdb, 'parse_db_host')) {
      return $wpdb->parse_db_host($host);
    } else {
      // Backward compatibility for WP 4.7 and 4.8
      $port = null;
      $socket = null;
      $is_ipv6 = false;

      // First peel off the socket parameter from the right, if it exists.
      $socket_pos = strpos( $host, ':/' );
      if ($socket_pos !== false) {
        $socket = substr($host, $socket_pos + 1);
        $host = substr($host, 0, $socket_pos);
      }

      // We need to check for an IPv6 address first.
      // An IPv6 address will always contain at least two colons.
      if (substr_count( $host, ':' ) > 1) {
        $pattern = '#^(?:\[)?(?P<host>[0-9a-fA-F:]+)(?:\]:(?P<port>[\d]+))?#';
        $is_ipv6 = true;
      } else {
        // We seem to be dealing with an IPv4 address.
        $pattern = '#^(?P<host>[^:/]*)(?::(?P<port>[\d]+))?#';
      }

      $matches = [];
      $result = preg_match($pattern, $host, $matches);
      if (1 !== $result) {
        // Couldn't parse the address, bail.
        return false;
      }

      $host = '';
      foreach (['host', 'port'] as $component) {
        if (!empty($matches[$component])) {
          $$component = $matches[$component];
        }
      }
      return [$host, $port, $socket, $is_ipv6];
    }
  }

}
