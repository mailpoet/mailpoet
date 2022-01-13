/**
   * Creates WC Lookup Tables database schema.
   * Copied from WC-Admin version 2.9.2-plugin
 */
CREATE TABLE IF NOT EXISTS `wp_wc_order_stats` (
  order_id bigint(20) unsigned NOT NULL,
  parent_id bigint(20) unsigned DEFAULT 0 NOT NULL,
  date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
  date_created_gmt datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
  num_items_sold int(11) DEFAULT 0 NOT NULL,
  total_sales double DEFAULT 0 NOT NULL,
  tax_total double DEFAULT 0 NOT NULL,
  shipping_total double DEFAULT 0 NOT NULL,
  net_total double DEFAULT 0 NOT NULL,
  returning_customer boolean DEFAULT NULL,
  status varchar(200) NOT NULL,
  customer_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (order_id),
  KEY date_created (date_created),
  KEY customer_id (customer_id),
  KEY status (status(191))
);

CREATE TABLE IF NOT EXISTS `wp_wc_order_product_lookup` (
  order_item_id BIGINT UNSIGNED NOT NULL,
  order_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  variation_id BIGINT UNSIGNED NOT NULL,
  customer_id BIGINT UNSIGNED NULL,
  date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
  product_qty INT NOT NULL,
  product_net_revenue double DEFAULT 0 NOT NULL,
  product_gross_revenue double DEFAULT 0 NOT NULL,
  coupon_amount double DEFAULT 0 NOT NULL,
  tax_amount double DEFAULT 0 NOT NULL,
  shipping_amount double DEFAULT 0 NOT NULL,
  shipping_tax_amount double DEFAULT 0 NOT NULL,
  PRIMARY KEY  (order_item_id),
  KEY order_id (order_id),
  KEY product_id (product_id),
  KEY customer_id (customer_id),
  KEY date_created (date_created)
);

CREATE TABLE IF NOT EXISTS `wp_wc_order_tax_lookup` (
  order_id BIGINT UNSIGNED NOT NULL,
  tax_rate_id BIGINT UNSIGNED NOT NULL,
  date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
  shipping_tax double DEFAULT 0 NOT NULL,
  order_tax double DEFAULT 0 NOT NULL,
  total_tax double DEFAULT 0 NOT NULL,
  PRIMARY KEY (order_id, tax_rate_id),
  KEY tax_rate_id (tax_rate_id),
  KEY date_created (date_created)
);

CREATE TABLE IF NOT EXISTS `wp_wc_order_coupon_lookup` (
  order_id BIGINT UNSIGNED NOT NULL,
  coupon_id BIGINT NOT NULL,
  date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
  discount_amount double DEFAULT 0 NOT NULL,
  PRIMARY KEY (order_id, coupon_id),
  KEY coupon_id (coupon_id),
  KEY date_created (date_created)
);

CREATE TABLE IF NOT EXISTS `wp_wc_admin_notes` (
  note_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL,
  type varchar(20) NOT NULL,
  locale varchar(20) NOT NULL,
  title longtext NOT NULL,
  content longtext NOT NULL,
  content_data longtext NULL default null,
  status varchar(200) NOT NULL,
  source varchar(200) NOT NULL,
  date_created datetime NOT NULL default '0000-00-00 00:00:00',
  date_reminder datetime NULL default null,
  is_snoozable boolean DEFAULT 0 NOT NULL,
  layout varchar(20) DEFAULT '' NOT NULL,
  image varchar(200) NULL DEFAULT NULL,
  is_deleted boolean DEFAULT 0 NOT NULL,
  is_read boolean DEFAULT 0 NOT NULL,
  icon varchar(200) NOT NULL default 'info',
  PRIMARY KEY (note_id)
);

CREATE TABLE IF NOT EXISTS `wp_wc_admin_note_actions` (
  action_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  note_id BIGINT UNSIGNED NOT NULL,
  name varchar(255) NOT NULL,
  label varchar(255) NOT NULL,
  query longtext NOT NULL,
  status varchar(255) NOT NULL,
  is_primary boolean DEFAULT 0 NOT NULL,
  actioned_text varchar(255) NOT NULL,
  nonce_action varchar(255) NULL DEFAULT NULL,
  nonce_name varchar(255) NULL DEFAULT NULL,
  PRIMARY KEY (action_id),
  KEY note_id (note_id)
);

CREATE TABLE IF NOT EXISTS `wp_wc_customer_lookup` (
  customer_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED DEFAULT NULL,
  username varchar(60) DEFAULT '' NOT NULL,
  first_name varchar(255) NOT NULL,
  last_name varchar(255) NOT NULL,
  email varchar(100) NULL default NULL,
  date_last_active timestamp NULL default null,
  date_registered timestamp NULL default null,
  country char(2) DEFAULT '' NOT NULL,
  postcode varchar(20) DEFAULT '' NOT NULL,
  city varchar(100) DEFAULT '' NOT NULL,
  state varchar(100) DEFAULT '' NOT NULL,
  PRIMARY KEY (customer_id),
  UNIQUE KEY user_id (user_id),
  KEY email (email)
);

CREATE TABLE IF NOT EXISTS `wp_wc_category_lookup` (
  category_tree_id BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (category_tree_id,category_id)
);
