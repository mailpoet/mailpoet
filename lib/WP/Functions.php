<?php
namespace MailPoet\WP;

use WP_Error;

class Functions {

  static private $instance;

  /**
   * @return Functions
   */
  static function get() {
    if (self::$instance === null) {
      self::$instance = new Functions;
    }
    return self::$instance;
  }

  static function set(Functions $instance) {
    self::$instance = $instance;
  }

  /**
   * @param string $tag
   * @param mixed ...$args
   * @return mixed
   */
  function doAction($tag, ...$args) {
    return call_user_func_array('do_action', func_get_args());
  }

  /**
   * @param string $tag
   * @param mixed ...$args
   * @return mixed
   */
  function applyFilters($tag, ...$args) {
    return call_user_func_array('apply_filters', func_get_args());
  }

  /**
   * @param string $tag
   * @param callable $function_to_add
   * @param int $priority
   * @param int $accepted_args
   * @return boolean
   */
  function addAction($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
    return call_user_func_array('add_action', func_get_args());
  }

  function __($text, $domain = 'default') {
    return __($text, $domain);
  }

  function _e($text, $domain = 'default') {
    return _e($text, $domain);
  }

  function _n($single, $plural, $number, $domain = 'default') {
    return _n($single, $plural, $number, $domain);
  }

  function _x($text, $context, $domain = 'default') {
    return _x($text, $context, $domain);
  }

  function addCommentMeta($comment_id, $meta_key, $meta_value, $unique = false) {
    return add_comment_meta($comment_id, $meta_key, $meta_value, $unique);
  }

  function addFilter($tag, callable $function_to_add, $priority = 10, $accepted_args = 1) {
    return add_filter($tag, $function_to_add, $priority, $accepted_args);
  }

  /**
   * @param bool|array $crop
   */
  function addImageSize($name, $width = 0, $height = 0, $crop = false) {
    return add_image_size($name, $width, $height, $crop);
  }

  function addMenuPage($page_title, $menu_title, $capability, $menu_slug, callable $function = null, $icon_url = '', $position = null) {
    return add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position);
  }

  function addQueryArg($key, $value = false, $url = false) {
    return add_query_arg($key, $value, $url);
  }

  function addScreenOption($option, $args = []) {
    return add_screen_option($option, $args);
  }

  function addShortcode($tag, callable $callback) {
    return add_shortcode($tag, $callback);
  }

  function addSubmenuPage($parent_slug, $page_title, $menu_title, $capability, $menu_slug, callable $function = null) {
    return add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);
  }

  function adminUrl($path = '', $scheme = 'admin') {
    return admin_url($path, $scheme);
  }

  function currentFilter() {
    return current_filter();
  }

  function currentTime($type, $gmt = false) {
    return current_time($type, $gmt);
  }

  function currentUserCan($capability) {
    return current_user_can($capability);
  }

  function dateI18n($dateformatstring, $timestamp_with_offset = false, $gmt = false) {
    return date_i18n($dateformatstring, $timestamp_with_offset, $gmt);
  }

  function deleteCommentMeta($comment_id, $meta_key, $meta_value = '') {
    return delete_comment_meta($comment_id, $meta_key, $meta_value);
  }

  function deleteOption($option) {
    return delete_option($option);
  }

  function doShortcode($content, $ignore_html = false) {
    return do_shortcode($content, $ignore_html);
  }

  function escAttr($text) {
    return esc_attr($text);
  }

  function escHtml($text) {
    return esc_html($text);
  }

  function escSql($sql) {
    return esc_sql($sql);
  }

  function getBloginfo($show = '', $filter = 'raw') {
    return get_bloginfo($show, $filter);
  }

  function getCategories($args = '') {
    return get_categories($args);
  }

  function getComment($comment = null, $output = OBJECT) {
    return get_comment($comment, $output);
  }

  function getCommentMeta($comment_id, $key = '', $single = false) {
    return get_comment_meta($comment_id, $key, $single);
  }

  function getCurrentScreen() {
    return get_current_screen();
  }

  function getCurrentUserId() {
    return get_current_user_id();
  }

  function getDateFromGmt($string, $format = 'Y-m-d H:i:s') {
    return get_date_from_gmt($string, $format);
  }

  function getEditProfileUrl($user_id = 0, $scheme = 'admin') {
    return get_edit_profile_url($user_id, $scheme);
  }

  function getEditableRoles() {
    return get_editable_roles();
  }

  function getLocale() {
    return get_locale();
  }

  function getObjectTaxonomies($object, $output = 'names') {
    return get_object_taxonomies($object, $output);
  }

  function getOption($option, $default = false) {
    return get_option($option, $default);
  }

  function getPages($args = []) {
    return get_pages($args);
  }

  function getPermalink($post, $leavename = false) {
    return get_permalink($post, $leavename);
  }

  function getPluginPageHook($plugin_page, $parent_page) {
    return get_plugin_page_hook($plugin_page, $parent_page);
  }

  function getPluginUpdates() {
    return get_plugin_updates();
  }

  function getPlugins($plugin_folder = '') {
    return get_plugins($plugin_folder);
  }

  function getPost($post = null, $output = OBJECT, $filter = 'raw') {
    return get_post($post, $output, $filter);
  }

  function getPostThumbnailId($post = null) {
    return get_post_thumbnail_id($post);
  }

  function getPostTypes($args = [], $output = 'names', $operator = 'and') {
    return get_post_types($args, $output, $operator);
  }

  function getPosts(array $args = null) {
    return get_posts($args);
  }

  function getRole($role) {
    return get_role($role);
  }

  function getSiteOption($option, $default = false, $deprecated = true) {
    return get_site_option($option, $default, $deprecated);
  }

  function getSiteUrl($blog_id = null, $path = '', $scheme = null) {
    return get_site_url($blog_id, $path, $scheme);
  }

  function getTemplatePart($slug, $name = null) {
    return get_template_part($slug, $name);
  }

  /**
   * @param string|array $args
   * @param string|array $deprecated
   * @return array|int|WP_Error
   */
  function getTerms($args = [], $deprecated = '') {
    return get_terms($args, $deprecated);
  }

  /**
   * @param int|boolean $user_id
   */
  function getTheAuthorMeta($field = '', $user_id = false) {
    return get_the_author_meta($field, $user_id);
  }

  /**
   * @param  int|\WP_User $user_id
   */
  function getUserLocale($user_id = 0) {
    return get_user_locale($user_id);
  }

  function getUserMeta($user_id, $key = '', $single = false) {
    return get_user_meta($user_id, $key, $single);
  }

  function getUserdata($user_id) {
    return get_userdata($user_id);
  }

  function getUserBy($field, $value) {
    return get_user_by($field, $value);
  }

  function hasFilter($tag, $function_to_check = false) {
    return has_filter($tag, $function_to_check);
  }

  function homeUrl($path = '', $scheme = null) {
    return home_url($path, $scheme);
  }

  function includesUrl($path = '', $scheme = null) {
    return includes_url($path, $scheme);
  }

  function isAdmin() {
    return is_admin();
  }

  function isEmail($email, $deprecated = false) {
    return is_email($email, $deprecated);
  }

  function isMultisite() {
    return is_multisite();
  }

  function isPluginActive($plugin) {
    return is_plugin_active($plugin);
  }

  function isRtl() {
    return is_rtl();
  }

  function isSerialized($data, $strict = true) {
    return is_serialized($data, $strict);
  }

  function isUserLoggedIn() {
    return is_user_logged_in();
  }

  /**
   * @param  string|boolean $deprecated
   * @param  string|boolean $plugin_rel_path
   */
  function loadPluginTextdomain($domain, $deprecated = false, $plugin_rel_path = false) {
    return load_plugin_textdomain($domain, $deprecated, $plugin_rel_path);
  }

  function loadTextdomain($domain, $mofile) {
    return load_textdomain($domain, $mofile);
  }

  function numberFormatI18n($number, $decimals = 0) {
    return number_format_i18n($number, $decimals);
  }

  function pluginBasename($file) {
    return plugin_basename($file);
  }

  function pluginsUrl($path = '', $plugin = '') {
    return plugins_url($path, $plugin);
  }

  function registerActivationHook($file, callable $function) {
    return register_activation_hook($file, $function);
  }

  function registerPostType($post_type, $args = []) {
    return register_post_type($post_type, $args);
  }

  function registerWidget($widget) {
    return register_widget($widget);
  }

  function removeAction($tag, callable $function_to_remove, $priority = 10) {
    return remove_action($tag, $function_to_remove, $priority);
  }

  function removeAllActions($tag, $priority = false) {
    return remove_all_actions($tag, $priority);
  }

  function removeAllFilters($tag, $priority = false) {
    return remove_all_filters($tag, $priority);
  }

  function removeFilter($tag, callable $function_to_remove, $priority = 10) {
    return remove_filter($tag, $function_to_remove, $priority);
  }

  function removeShortcode($tag) {
    return remove_shortcode($tag);
  }

  function selfAdminUrl($path = '', $scheme = 'admin') {
    return self_admin_url($path, $scheme);
  }

  function setTransient($transient, $value, $expiration) {
    return set_transient($transient, $value, $expiration);
  }

  function getTransient($transient) {
    return get_transient($transient);
  }

  function deleteTransient($transient) {
    return delete_transient($transient);
  }

  function singlePostTitle($prefix = '', $display = true) {
    return single_post_title($prefix, $display);
  }

  function siteUrl($path = '', $scheme = null) {
    return site_url($path, $scheme);
  }

  function statusHeader($code, $description = '') {
    return status_header($code, $description);
  }

  function stripslashesDeep($value) {
    return stripslashes_deep($value);
  }

  function translate($text, $domain = 'default') {
    return translate($text, $domain);
  }

  function unloadTextdomain($domain) {
    return unload_textdomain($domain);
  }

  function updateOption($option, $value, $autoload = null) {
    return update_option($option, $value, $autoload);
  }

  function wpAddInlineScript($handle, $data, $position = 'after') {
    return wp_add_inline_script($handle, $data, $position);
  }

  function wpCreateNonce($action = -1) {
    return wp_create_nonce($action);
  }

  function wpDequeueScript($handle) {
    return wp_dequeue_script($handle);
  }

  function wpDequeueStyle($handle) {
    return wp_dequeue_style($handle);
  }

  function wpEncodeEmoji($content) {
    return wp_encode_emoji($content);
  }

  function wpEnqueueMedia(array $args = []) {
    return wp_enqueue_media($args);
  }

  function wpEnqueueScript($handle, $src = '', array $deps = [], $ver = false, $in_footer = false) {
    return wp_enqueue_script($handle, $src, $deps, $ver, $in_footer);
  }

  function wpEnqueueStyle($handle, $src = '', array $deps = [], $ver = false, $media = 'all') {
    return wp_enqueue_style($handle, $src, $deps, $ver, $media);
  }

  function wpGetAttachmentImageSrc($attachment_id, $size = 'thumbnail', $icon = false) {
    return wp_get_attachment_image_src($attachment_id, $size, $icon);
  }

  function wpGetCurrentUser() {
    return wp_get_current_user();
  }

  function wpGetPostTerms($post_id, $taxonomy = 'post_tag', array $args = []) {
    return wp_get_post_terms($post_id, $taxonomy, $args);
  }

  function wpGetReferer() {
    return wp_get_referer();
  }

  function wpGetTheme($stylesheet = null, $theme_root = null) {
    return wp_get_theme($stylesheet, $theme_root);
  }

  function wpInsertPost(array $postarr, $wp_error = false) {
    return wp_insert_post($postarr, $wp_error);
  }

  function wpJsonEncode($data, $options = 0, $depth = 512) {
    return wp_json_encode($data, $options, $depth);
  }

  function wpLocalizeScript($handle, $object_name, array $l10n) {
    return wp_localize_script($handle, $object_name, $l10n);
  }

  function wpLoginUrl($redirect = '', $force_reauth = false) {
    return wp_login_url($redirect, $force_reauth);
  }

  function wpParseArgs($args, $defaults = '') {
    return wp_parse_args($args, $defaults);
  }

  function wpPrintScripts($handles = false) {
    return wp_print_scripts($handles);
  }

  function wpRemoteGet($url, array $args = []) {
    return wp_remote_get($url, $args);
  }

  function wpRemotePost($url, array $args = []) {
    return wp_remote_post($url, $args);
  }

  function wpRemoteRetrieveBody($response) {
    return wp_remote_retrieve_body($response);
  }

  function wpRemoteRetrieveResponseCode($response) {
    return wp_remote_retrieve_response_code($response);
  }

  function wpRemoteRetrieveResponseMessage($response) {
    return wp_remote_retrieve_response_message($response);
  }

  function wpSafeRedirect($location, $status = 302) {
    return wp_safe_redirect($location, $status);
  }

  function wpSetCurrentUser($id, $name = '') {
    return wp_set_current_user($id, $name);
  }

  function wpStaticizeEmoji($text) {
    return wp_staticize_emoji($text);
  }

  function wpTrimWords($text, $num_words = 55, $more = null) {
    return wp_trim_words($text, $num_words, $more);
  }

  function wpUploadDir($time = null, $create_dir = true, $refresh_cache = false) {
    return wp_upload_dir($time, $create_dir, $refresh_cache);
  }

  function wpVerifyNonce($nonce, $action = -1) {
    return wp_verify_nonce($nonce, $action);
  }

  function wpautop($pee, $br = true) {
    return wpautop($pee, $br);
  }

  /**
   * @param string $host
   * @return array|bool
   */
  function parseDbHost($host) {
    global $wpdb;
    if (method_exists($wpdb, 'parse_db_host')) {
      return $wpdb->parse_db_host($host);
    } else {
      // Backward compatibility for WP 4.7 and 4.8
      $port = 3306;
      $socket = null;
      // Peel off the port parameter
      if (preg_match('/(?=:\d+$)/', $host)) {
        list($host, $port) = explode(':', $host);
      }
      // Peel off the socket parameter
      if (preg_match('/:\//', $host)) {
        list($host, $socket) = explode(':', $host);
      }
      return [$host, $port, $socket, false];
    }
  }

  /**
   * @param string|null $type
   * @param string|null $permission
   * @return object
   */
  function wpCountPosts($type = null, $permission = null) {
    return wp_count_posts($type, $permission);
  }
}
