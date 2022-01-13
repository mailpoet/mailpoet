-- phpMyAdmin SQL Dump
-- version 4.4.10
-- http://www.phpmyadmin.net
--
-- Client :  localhost:3306
-- Généré le :  Mer 26 Avril 2017 à 17:52
-- Version du serveur :  5.5.42
-- Version de PHP :  7.0.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Base de données :  `mailpoet`
--

-- --------------------------------------------------------

--
-- Structure de la table `wp_wysija_campaign`
--

DROP TABLE IF EXISTS `wp_wysija_campaign`;
CREATE TABLE `wp_wysija_campaign` (
  `campaign_id` int(10) unsigned NOT NULL,
  `name` varchar(250) DEFAULT NULL,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `wp_wysija_campaign_list`
--

DROP TABLE IF EXISTS `wp_wysija_campaign_list`;
CREATE TABLE `wp_wysija_campaign_list` (
  `list_id` int(10) unsigned NOT NULL,
  `campaign_id` int(10) unsigned NOT NULL,
  `filter` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `wp_wysija_custom_field`
--

DROP TABLE IF EXISTS `wp_wysija_custom_field`;
CREATE TABLE `wp_wysija_custom_field` (
  `id` mediumint(9) NOT NULL,
  `name` tinytext NOT NULL,
  `type` tinytext NOT NULL,
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `settings` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `wp_wysija_email`
--

DROP TABLE IF EXISTS `wp_wysija_email`;
CREATE TABLE `wp_wysija_email` (
  `email_id` int(10) unsigned NOT NULL,
  `campaign_id` int(10) unsigned NOT NULL DEFAULT '0',
  `subject` varchar(250) NOT NULL DEFAULT '',
  `body` longtext,
  `created_at` int(10) unsigned DEFAULT NULL,
  `modified_at` int(10) unsigned DEFAULT NULL,
  `sent_at` int(10) unsigned DEFAULT NULL,
  `from_email` varchar(250) DEFAULT NULL,
  `from_name` varchar(250) DEFAULT NULL,
  `replyto_email` varchar(250) DEFAULT NULL,
  `replyto_name` varchar(250) DEFAULT NULL,
  `attachments` text,
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `type` tinyint(4) NOT NULL DEFAULT '1',
  `number_sent` int(10) unsigned NOT NULL DEFAULT '0',
  `number_opened` int(10) unsigned NOT NULL DEFAULT '0',
  `number_clicked` int(10) unsigned NOT NULL DEFAULT '0',
  `number_unsub` int(10) unsigned NOT NULL DEFAULT '0',
  `number_bounce` int(10) unsigned NOT NULL DEFAULT '0',
  `number_forward` int(10) unsigned NOT NULL DEFAULT '0',
  `params` text,
  `wj_data` longtext,
  `wj_styles` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `wp_wysija_email_user_stat`
--

DROP TABLE IF EXISTS `wp_wysija_email_user_stat`;
CREATE TABLE `wp_wysija_email_user_stat` (
  `user_id` int(10) unsigned NOT NULL,
  `email_id` int(10) unsigned NOT NULL,
  `sent_at` int(10) unsigned NOT NULL,
  `opened_at` int(10) unsigned DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `wp_wysija_email_user_url`
--

DROP TABLE IF EXISTS `wp_wysija_email_user_url`;
CREATE TABLE `wp_wysija_email_user_url` (
  `email_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `url_id` int(10) unsigned NOT NULL,
  `clicked_at` int(10) unsigned DEFAULT NULL,
  `number_clicked` int(10) unsigned NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `wp_wysija_form`
--

DROP TABLE IF EXISTS `wp_wysija_form`;
CREATE TABLE `wp_wysija_form` (
  `form_id` int(10) unsigned NOT NULL,
  `name` tinytext CHARACTER SET utf8 COLLATE utf8_bin,
  `data` longtext CHARACTER SET utf8 COLLATE utf8_bin,
  `styles` longtext CHARACTER SET utf8 COLLATE utf8_bin,
  `subscribed` int(10) unsigned NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `wp_wysija_list`
--

DROP TABLE IF EXISTS `wp_wysija_list`;
CREATE TABLE `wp_wysija_list` (
  `list_id` int(10) unsigned NOT NULL,
  `name` varchar(250) DEFAULT NULL,
  `namekey` varchar(255) DEFAULT NULL,
  `description` text,
  `unsub_mail_id` int(10) unsigned NOT NULL DEFAULT '0',
  `welcome_mail_id` int(10) unsigned NOT NULL DEFAULT '0',
  `is_enabled` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `is_public` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `created_at` int(10) unsigned DEFAULT NULL,
  `ordering` int(10) unsigned NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `wp_wysija_queue`
--

DROP TABLE IF EXISTS `wp_wysija_queue`;
CREATE TABLE `wp_wysija_queue` (
  `user_id` int(10) unsigned NOT NULL,
  `email_id` int(10) unsigned NOT NULL,
  `send_at` int(10) unsigned NOT NULL DEFAULT '0',
  `priority` tinyint(4) NOT NULL DEFAULT '0',
  `number_try` tinyint(3) unsigned NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `wp_wysija_url`
--

DROP TABLE IF EXISTS `wp_wysija_url`;
CREATE TABLE `wp_wysija_url` (
  `url_id` int(10) unsigned NOT NULL,
  `name` varchar(250) DEFAULT NULL,
  `url` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `wp_wysija_url_mail`
--

DROP TABLE IF EXISTS `wp_wysija_url_mail`;
CREATE TABLE `wp_wysija_url_mail` (
  `email_id` int(11) NOT NULL,
  `url_id` int(10) unsigned NOT NULL,
  `unique_clicked` int(10) unsigned NOT NULL DEFAULT '0',
  `total_clicked` int(10) unsigned NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `wp_wysija_user`
--

DROP TABLE IF EXISTS `wp_wysija_user`;
CREATE TABLE `wp_wysija_user` (
  `user_id` int(10) unsigned NOT NULL,
  `wpuser_id` int(10) unsigned NOT NULL DEFAULT '0',
  `email` varchar(255) NOT NULL,
  `firstname` varchar(255) NOT NULL DEFAULT '',
  `lastname` varchar(255) NOT NULL DEFAULT '',
  `ip` varchar(100) NOT NULL,
  `confirmed_ip` varchar(100) NOT NULL DEFAULT '0',
  `confirmed_at` int(10) unsigned DEFAULT NULL,
  `last_opened` int(10) unsigned DEFAULT NULL,
  `last_clicked` int(10) unsigned DEFAULT NULL,
  `keyuser` varchar(255) NOT NULL DEFAULT '',
  `created_at` int(10) unsigned DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `domain` varchar(255) DEFAULT '',
  `cf_1` varchar(100) DEFAULT NULL,
  `cf_3` varchar(255) DEFAULT NULL,
  `cf_4` varchar(255) DEFAULT NULL,
  `cf_5` tinyint(1) DEFAULT NULL,
  `cf_6` varchar(255) DEFAULT NULL,
  `cf_7` int(20) DEFAULT NULL,
  `cf_8` varchar(100) DEFAULT NULL,
  `cf_9` varchar(100) DEFAULT NULL,
  `cf_10` varchar(100) DEFAULT NULL,
  `cf_11` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `wp_wysija_user_field`
--

DROP TABLE IF EXISTS `wp_wysija_user_field`;
CREATE TABLE `wp_wysija_user_field` (
  `field_id` int(10) unsigned NOT NULL,
  `name` varchar(250) DEFAULT NULL,
  `column_name` varchar(250) NOT NULL DEFAULT '',
  `type` tinyint(3) unsigned DEFAULT '0',
  `values` text,
  `default` varchar(250) NOT NULL DEFAULT '',
  `is_required` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `error_message` varchar(250) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `wp_wysija_user_history`
--

DROP TABLE IF EXISTS `wp_wysija_user_history`;
CREATE TABLE `wp_wysija_user_history` (
  `history_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `email_id` int(10) unsigned DEFAULT '0',
  `type` varchar(250) NOT NULL DEFAULT '',
  `details` text,
  `executed_at` int(10) unsigned DEFAULT NULL,
  `executed_by` int(10) unsigned DEFAULT NULL,
  `source` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `wp_wysija_user_list`
--

DROP TABLE IF EXISTS `wp_wysija_user_list`;
CREATE TABLE `wp_wysija_user_list` (
  `list_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `sub_date` int(10) unsigned DEFAULT '0',
  `unsub_date` int(10) unsigned DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Index pour les tables exportées
--

--
-- Index pour la table `wp_wysija_campaign`
--
ALTER TABLE `wp_wysija_campaign`
  ADD PRIMARY KEY (`campaign_id`);

--
-- Index pour la table `wp_wysija_campaign_list`
--
ALTER TABLE `wp_wysija_campaign_list`
  ADD PRIMARY KEY (`list_id`,`campaign_id`);

--
-- Index pour la table `wp_wysija_custom_field`
--
ALTER TABLE `wp_wysija_custom_field`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `wp_wysija_email`
--
ALTER TABLE `wp_wysija_email`
  ADD PRIMARY KEY (`email_id`);

--
-- Index pour la table `wp_wysija_email_user_stat`
--
ALTER TABLE `wp_wysija_email_user_stat`
  ADD PRIMARY KEY (`user_id`,`email_id`);

--
-- Index pour la table `wp_wysija_email_user_url`
--
ALTER TABLE `wp_wysija_email_user_url`
  ADD PRIMARY KEY (`user_id`,`email_id`,`url_id`);

--
-- Index pour la table `wp_wysija_form`
--
ALTER TABLE `wp_wysija_form`
  ADD PRIMARY KEY (`form_id`);

--
-- Index pour la table `wp_wysija_list`
--
ALTER TABLE `wp_wysija_list`
  ADD PRIMARY KEY (`list_id`);

--
-- Index pour la table `wp_wysija_queue`
--
ALTER TABLE `wp_wysija_queue`
  ADD PRIMARY KEY (`user_id`,`email_id`),
  ADD KEY `SENT_AT_INDEX` (`send_at`);

--
-- Index pour la table `wp_wysija_url`
--
ALTER TABLE `wp_wysija_url`
  ADD PRIMARY KEY (`url_id`);

--
-- Index pour la table `wp_wysija_url_mail`
--
ALTER TABLE `wp_wysija_url_mail`
  ADD PRIMARY KEY (`email_id`,`url_id`);

--
-- Index pour la table `wp_wysija_user`
--
ALTER TABLE `wp_wysija_user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `EMAIL_UNIQUE` (`email`);

--
-- Index pour la table `wp_wysija_user_field`
--
ALTER TABLE `wp_wysija_user_field`
  ADD PRIMARY KEY (`field_id`);

--
-- Index pour la table `wp_wysija_user_history`
--
ALTER TABLE `wp_wysija_user_history`
  ADD PRIMARY KEY (`history_id`);

--
-- Index pour la table `wp_wysija_user_list`
--
ALTER TABLE `wp_wysija_user_list`
  ADD PRIMARY KEY (`list_id`,`user_id`);

--
-- AUTO_INCREMENT pour les tables exportées
--

--
-- AUTO_INCREMENT pour la table `wp_wysija_campaign`
--
ALTER TABLE `wp_wysija_campaign`
  MODIFY `campaign_id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `wp_wysija_custom_field`
--
ALTER TABLE `wp_wysija_custom_field`
  MODIFY `id` mediumint(9) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `wp_wysija_email`
--
ALTER TABLE `wp_wysija_email`
  MODIFY `email_id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `wp_wysija_form`
--
ALTER TABLE `wp_wysija_form`
  MODIFY `form_id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `wp_wysija_list`
--
ALTER TABLE `wp_wysija_list`
  MODIFY `list_id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `wp_wysija_url`
--
ALTER TABLE `wp_wysija_url`
  MODIFY `url_id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `wp_wysija_url_mail`
--
ALTER TABLE `wp_wysija_url_mail`
  MODIFY `email_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `wp_wysija_user`
--
ALTER TABLE `wp_wysija_user`
  MODIFY `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `wp_wysija_user_field`
--
ALTER TABLE `wp_wysija_user_field`
  MODIFY `field_id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `wp_wysija_user_history`
--
ALTER TABLE `wp_wysija_user_history`
  MODIFY `history_id` int(10) unsigned NOT NULL AUTO_INCREMENT;