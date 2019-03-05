<?php
namespace MailPoet\WP;

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

  function wpRemotePost() {
    return call_user_func_array('wp_remote_post', func_get_args());
  }

  function wpRemoteGet() {
    return call_user_func_array('wp_remote_get', func_get_args());
  }

  function wpRemoteRetrieveBody() {
    return call_user_func_array('wp_remote_retrieve_body', func_get_args());
  }

  function wpRemoteRetrieveResponseCode() {
    return call_user_func_array('wp_remote_retrieve_response_code', func_get_args());
  }

  function wpRemoteRetrieveResponseMessage() {
    return call_user_func_array('wp_remote_retrieve_response_message', func_get_args());
  }

  function addFilter() {
    return call_user_func_array('add_filter', func_get_args());
  }

  function applyFilters() {
    return call_user_func_array('apply_filters', func_get_args());
  }

  function removeFilter() {
    return call_user_func_array('remove_filter', func_get_args());
  }

  function addAction() {
    return call_user_func_array('add_action', func_get_args());
  }

  function doAction() {
    return call_user_func_array('do_action', func_get_args());
  }

  function removeAction() {
    return call_user_func_array('remove_action', func_get_args());
  }

  function removeAllFilters() {
    return call_user_func_array('remove_all_filters', func_get_args());
  }

  function currentFilter() {
    return call_user_func_array('current_filter', func_get_args());
  }

  function currentTime() {
    return call_user_func_array('current_time', func_get_args());
  }

  function homeUrl() {
    return call_user_func_array('home_url', func_get_args());
  }

  function isUserLoggedIn() {
    return call_user_func_array('is_user_logged_in', func_get_args());
  }

  function getOption() {
    return call_user_func_array('get_option', func_get_args());
  }

  function getUserdata() {
    return call_user_func_array('get_userdata', func_get_args());
  }

  function getPost() {
    return call_user_func_array('get_post', func_get_args());
  }

  function wpEncodeEmoji() {
    return call_user_func_array('wp_encode_emoji', func_get_args());
  }

  function __() {
    return call_user_func_array('__', func_get_args());
  }

  function stripslashesDeep() {
    return call_user_func_array('stripslashes_deep', func_get_args());
  }

  function wpVerifyNonce() {
    return call_user_func_array('wp_verify_nonce', func_get_args());
  }

  function statusHeader() {
    return call_user_func_array('status_header', func_get_args());
  }

  function wpJsonEncode() {
    return call_user_func_array('wp_json_encode', func_get_args());
  }

  function getObjectTaxonomies() {
    return call_user_func_array('get_object_taxonomies', func_get_args());
  }

  function doShortcode() {
    return call_user_func_array('do_shortcode', func_get_args());
  }

  function adminUrl() {
    return call_user_func_array('admin_url', func_get_args());
  }

  function siteUrl() {
    return call_user_func_array('site_url', func_get_args());
  }

  function getPermalink() {
    return call_user_func_array('get_permalink', func_get_args());
  }

  function n() {
    return call_user_func_array('_n', func_get_args());
  }

  function isMultisite() {
    return call_user_func_array('is_multisite', func_get_args());
  }

  function isPluginActive() {
    return call_user_func_array('is_plugin_active', func_get_args());
  }

  function isRtl() {
    return call_user_func_array('is_rtl', func_get_args());
  }

  function currentUserCan() {
    return call_user_func_array('current_user_can', func_get_args());
  }
  
  /**
   * @return \WP_Role|null
   */
  function getRole() {
    return call_user_func_array('get_role', func_get_args());
  }

  function wpEnqueueStyle() {
    return call_user_func_array('wp_enqueue_style', func_get_args());
  }

  function getCurrentScreen() {
    return call_user_func_array('get_current_screen', func_get_args());
  }

  function deleteOption() {
    return call_user_func_array('delete_option', func_get_args());
  }

  function updateOption() {
    return call_user_func_array('update_option', func_get_args());
  }

  function pluginsUrl() {
    return call_user_func_array('plugins_url', func_get_args());
  }

  function wpUploadDir() {
    return call_user_func_array('wp_upload_dir', func_get_args());
  }

  function addImageSize() {
    return call_user_func_array('add_image_size', func_get_args());
  }

  function getLocale() {
    return call_user_func_array('get_locale', func_get_args());
  }

  function registerActivationHook() {
    return call_user_func_array('register_activation_hook', func_get_args());
  }

  function registerWidget() {
    return call_user_func_array('register_widget', func_get_args());
  }

  function unloadTextdomain() {
    return call_user_func_array('unload_textdomain', func_get_args());
  }

  function addQueryArg() {
    return call_user_func_array('add_query_arg', func_get_args());
  }

  function getPlugins() {
    return call_user_func_array('get_plugins', func_get_args());
  }

  function selfAdminUrl() {
    return call_user_func_array('self_admin_url', func_get_args());
  }

  function wpCreateNonce() {
    return call_user_func_array('wp_create_nonce', func_get_args());
  }

  function getUserLocale() {
    return call_user_func_array('get_user_locale', func_get_args());
  }

  function loadPluginTextdomain() {
    return call_user_func_array('load_plugin_textdomain', func_get_args());
  }

  function loadTextdomain() {
    return call_user_func_array('load_textdomain', func_get_args());
  }

  function wpEnqueueScript() {
    return call_user_func_array('wp_enqueue_script', func_get_args());
  }

  function x() {
    return call_user_func_array('_x', func_get_args());
  }

  function addMenuPage() {
    return call_user_func_array('add_menu_page', func_get_args());
  }

  function addScreenOption() {
    return call_user_func_array('add_screen_option', func_get_args());
  }

  function addSubmenuPage() {
    return call_user_func_array('add_submenu_page', func_get_args());
  }

  function getCurrentUserId() {
    return call_user_func_array('get_current_user_id', func_get_args());
  }

  function getPluginPageHook() {
    return call_user_func_array('get_plugin_page_hook', func_get_args());
  }

  function getSiteOption() {
    return call_user_func_array('get_site_option', func_get_args());
  }

  function getUserMeta() {
    return call_user_func_array('get_user_meta', func_get_args());
  }

  function includesUrl() {
    return call_user_func_array('includes_url', func_get_args());
  }

  function wpEnqueueMedia() {
    return call_user_func_array('wp_enqueue_media', func_get_args());
  }

  function wpGetCurrentUser() {
    return call_user_func_array('wp_get_current_user', func_get_args());
  }

  function wpGetReferer() {
    return call_user_func_array('wp_get_referer', func_get_args());
  }

  function pluginBasename() {
    return call_user_func_array('plugin_basename', func_get_args());
  }

  function getPosts() {
    return call_user_func_array('get_posts', func_get_args());
  }

  function addShortcode() {
    return call_user_func_array('add_shortcode', func_get_args());
  }

  function dateI18n() {
    return call_user_func_array('date_i18n', func_get_args());
  }

  function numberFormatI18n() {
    return call_user_func_array('number_format_i18n', func_get_args());
  }

  function removeShortcode() {
    return call_user_func_array('remove_shortcode', func_get_args());
  }

  function getSiteUrl() {
    return call_user_func_array('get_site_url', func_get_args());
  }

  function escAttr() {
    return call_user_func_array('esc_attr', func_get_args());
  }

  function e() {
    return call_user_func_array('_e', func_get_args());
  }

  function escHtml() {
    return call_user_func_array('esc_html', func_get_args());
  }

  function getBloginfo() {
    return call_user_func_array('get_bloginfo', func_get_args());
  }

  function wpAddInlineScript() {
    return call_user_func_array('wp_add_inline_script', func_get_args());
  }

  function wpLocalizeScript() {
    return call_user_func_array('wp_localize_script', func_get_args());
  }

  function wpParseArgs() {
    return call_user_func_array('wp_parse_args', func_get_args());
  }

  function wpPrintScripts() {
    return call_user_func_array('wp_print_scripts', func_get_args());
  }

  function wpGetTheme() {
    return call_user_func_array('wp_get_theme', func_get_args());
  }

  function isSerialized() {
    return call_user_func_array('is_serialized', func_get_args());
  }

  function isEmail() {
    return call_user_func_array('is_email', func_get_args());
  }

  function getTheAuthorMeta() {
    return call_user_func_array('get_the_author_meta', func_get_args());
  }

  function wpGetPostTerms() {
    return call_user_func_array('wp_get_post_terms', func_get_args());
  }

  function wpTrimWords() {
    return call_user_func_array('wp_trim_words', func_get_args());
  }

  function wpautop() {
    return call_user_func_array('wpautop', func_get_args());
  }

  function getPostThumbnailId() {
    return call_user_func_array('get_post_thumbnail_id', func_get_args());
  }

  function getTemplatePart() {
    return call_user_func_array('get_template_part', func_get_args());
  }

  function getPages() {
    return call_user_func_array('get_pages', func_get_args());
  }

  function registerPostType() {
    return call_user_func_array('register_post_type', func_get_args());
  }

  function removeAllActions() {
    return call_user_func_array('remove_all_actions', func_get_args());
  }

  function wpInsertPost() {
    return call_user_func_array('wp_insert_post', func_get_args());
  }

  function arrayReplaceRecursive() {
    return call_user_func_array('array_replace_recursive', func_get_args());
  }

  function addCommentMeta() {
    return call_user_func_array('add_comment_meta', func_get_args());
  }

  function deleteCommentMeta() {
    return call_user_func_array('delete_comment_meta', func_get_args());
  }

  function getComment() {
    return call_user_func_array('get_comment', func_get_args());
  }

  function getCommentMeta() {
    return call_user_func_array('get_comment_meta', func_get_args());
  }

  function getEditProfileUrl() {
    return call_user_func_array('get_edit_profile_url', func_get_args());
  }

  function singlePostTitle() {
    return call_user_func_array('single_post_title', func_get_args());
  }

  function wpLoginUrl() {
    return call_user_func_array('wp_login_url', func_get_args());
  }

  function getDateFromGmt() {
    return call_user_func_array('get_date_from_gmt', func_get_args());
  }

  function translate() {
    return call_user_func_array('translate', func_get_args());
  }

  function wpDequeueScript() {
    return call_user_func_array('wp_dequeue_script', func_get_args());
  }

  function wpDequeueStyle() {
    return call_user_func_array('wp_dequeue_style', func_get_args());
  }

  function setTransient() {
    return call_user_func_array('set_transient', func_get_args());
  }

  function isAdmin() {
    return call_user_func_array('is_admin', func_get_args());
  }

  function wpSafeRedirect() {
    return call_user_func_array('wp_safe_redirect', func_get_args());
  }

  function wpStaticizeEmoji() {
    return call_user_func_array('wp_staticize_emoji', func_get_args());
  }

  function wpGetAttachmentImageSrc() {
    return call_user_func_array('wp_get_attachment_image_src', func_get_args());
  }

  function getPostTypes() {
    return call_user_func_array('get_post_types', func_get_args());
  }

  function getTerms() {
    return call_user_func_array('get_terms', func_get_args());
  }

  function getEditableRoles() {
    return call_user_func_array('get_editable_roles', func_get_args());
  }

  function getCategories() {
    return call_user_func_array('get_categories', func_get_args());
  }

  function hasFilter() {
    return call_user_func_array('has_filter', func_get_args());
  }

  function wcPrice() {
    return call_user_func_array('wc_price', func_get_args());
  }

  function wcGetOrder() {
    return call_user_func_array('wc_get_order', func_get_args());
  }
  function wcGetCustomerOrderCount() {
    return call_user_func_array('wc_get_customer_order_count', func_get_args());
  }

}
