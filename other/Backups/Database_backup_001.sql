-- --------------------------------------------------------

--
-- Estrutura da tabela `account`
--

CREATE TABLE IF NOT EXISTS `account` (
  `id_account` int(11) NOT NULL AUTO_INCREMENT,
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  `last_update` timestamp NULL DEFAULT NULL,
  `insert_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_account`),
  KEY `hidden` (`hidden`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Estrutura da tabela `account_user`
--

CREATE TABLE IF NOT EXISTS `account_user` (
  `id_account` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `role` varchar(300) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'member',
  PRIMARY KEY (`id_account`,`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `barcode`
--

CREATE TABLE IF NOT EXISTS `barcode` (
  `id_barcode` int(11) NOT NULL AUTO_INCREMENT,
  `id_product` int(11) NOT NULL,
  `barcode` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `remarks` varchar(300) COLLATE utf8_unicode_ci NOT NULL,
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  `last_update` timestamp NULL DEFAULT NULL,
  `insert_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_barcode`),
  KEY `hidden` (`hidden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Estrutura da tabela `budget`
--

CREATE TABLE IF NOT EXISTS `budget` (
  `id_budget` int(11) NOT NULL AUTO_INCREMENT,
  `id_account` int(11) NOT NULL,
  `table_type` tinyint(1) NOT NULL COMMENT '1 - TYPE_CATEGORY, 2 - TYPE_ENTITY',
  `id_field` int(11) NOT NULL,
  `amount` double NOT NULL,
  `timespan` tinyint(1) NOT NULL COMMENT '1 - TIMESPAN_DAY, 2, - TIMESPAN_WEEK, 3 - TIMESPAN_MONTH, 4 - TIMESPAN_YEAR',
  `sort` int(3) NOT NULL,
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  `last_update` timestamp NULL DEFAULT NULL,
  `insert_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_budget`),
  KEY `hidden` (`hidden`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Estrutura da tabela `category`
--

CREATE TABLE IF NOT EXISTS `category` (
  `id_category` int(11) NOT NULL AUTO_INCREMENT,
  `id_account` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `remarks` varchar(300) COLLATE utf8_unicode_ci NOT NULL,
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  `last_update` timestamp NULL DEFAULT NULL,
  `insert_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_category`),
  UNIQUE KEY `name` (`name`),
  KEY `hidden` (`hidden`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Estrutura da tabela `entity`
--

CREATE TABLE IF NOT EXISTS `entity` (
  `id_entity` int(11) NOT NULL AUTO_INCREMENT,
  `id_account` int(11) NOT NULL,
  `name` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `remarks` varchar(300) COLLATE utf8_unicode_ci NOT NULL,
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  `last_update` timestamp NULL DEFAULT NULL,
  `insert_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_entity`),
  UNIQUE KEY `name` (`name`),
  KEY `hidden` (`hidden`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Estrutura da tabela `product`
--

CREATE TABLE IF NOT EXISTS `product` (
  `id_product` int(11) NOT NULL AUTO_INCREMENT,
  `id_entity` int(11) NOT NULL,
  `reference` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(300) COLLATE utf8_unicode_ci DEFAULT NULL,
  `remarks` varchar(300) COLLATE utf8_unicode_ci NOT NULL,
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  `last_update` timestamp NULL DEFAULT NULL,
  `insert_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_product`),
  KEY `hidden` (`hidden`),
  KEY `reference` (`reference`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Estrutura da tabela `similar_entity`
--

CREATE TABLE IF NOT EXISTS `similar_entity` (
  `id_entity` int(11) NOT NULL,
  `id_similar_entity` int(11) NOT NULL,
  PRIMARY KEY (`id_entity`,`id_similar_entity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `similar_product`
--

CREATE TABLE IF NOT EXISTS `similar_product` (
  `id_product` int(11) NOT NULL,
  `id_similar_product` int(11) NOT NULL,
  PRIMARY KEY (`id_product`,`id_similar_product`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `transaction`
--

CREATE TABLE IF NOT EXISTS `transaction` (
  `id_transaction` int(11) NOT NULL AUTO_INCREMENT,
  `type` tinyint(1) NOT NULL COMMENT '1 - Credit, 2 - Debit',
  `id_entity` int(11) NOT NULL,
  `id_category` int(11) NOT NULL,
  `amount` double NOT NULL,
  `transaction_date` datetime NOT NULL,
  `remarks` varchar(300) COLLATE utf8_unicode_ci NOT NULL,
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  `last_update` timestamp NULL DEFAULT NULL,
  `insert_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_transaction`),
  KEY `id_category` (`id_category`),
  KEY `hidden` (`hidden`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Estrutura da tabela `transaction_product`
--

CREATE TABLE IF NOT EXISTS `transaction_product` (
  `id_transaction` int(11) NOT NULL,
  `id_product` int(11) NOT NULL,
  `counter` int(11) NOT NULL,
  `price` double NOT NULL,
  `quantity` double NOT NULL DEFAULT '1',
  `sort` int(3) NOT NULL,
  PRIMARY KEY (`id_transaction`,`id_product`,`counter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `_lang_copy`
--

CREATE TABLE IF NOT EXISTS `_lang_copy` (
  `lang` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `copy` text COLLATE utf8_unicode_ci NOT NULL,
  `last_update` timestamp NULL DEFAULT NULL,
  `insert_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`lang`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `_logs`
--

CREATE TABLE IF NOT EXISTS `_logs` (
  `id_log` int(11) NOT NULL AUTO_INCREMENT,
  `log_data` text COLLATE utf8_unicode_ci NOT NULL,
  `last_update` timestamp NULL DEFAULT NULL,
  `insert_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Estrutura da tabela `_pending_emails`
--

CREATE TABLE IF NOT EXISTS `_pending_emails` (
  `id_pending_email` int(11) NOT NULL AUTO_INCREMENT,
  `email_subject` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email_from` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email_to` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email_body` text COLLATE utf8_unicode_ci NOT NULL,
  `email_cc` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email_bcc` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email_replyTo` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `contentType` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'text/html',
  `charset` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'utf-8',
  `valid_until` datetime DEFAULT NULL,
  `sent_successfully` bit(1) NOT NULL DEFAULT b'0',
  `last_update` timestamp NULL DEFAULT NULL,
  `insert_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pending_email`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Estrutura da tabela `_statistics`
--

CREATE TABLE IF NOT EXISTS `_statistics` (
  `id_statistics` int(11) NOT NULL AUTO_INCREMENT,
  `hashed_columns` char(64) COLLATE utf8_unicode_ci NOT NULL,
  `ip_address` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `request_url` varchar(1024) COLLATE utf8_unicode_ci DEFAULT NULL,
  `statistics_data` text COLLATE utf8_unicode_ci NOT NULL,
  `num_pageviews` int(11) NOT NULL DEFAULT '1',
  `last_update` timestamp NULL DEFAULT NULL,
  `insert_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_statistics`),
  UNIQUE KEY `hashed_columns` (`hashed_columns`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Estrutura da tabela `_token`
--

CREATE TABLE IF NOT EXISTS `_token` (
  `id_user` int(11) NOT NULL,
  `objective` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `token` char(64) COLLATE utf8_unicode_ci NOT NULL,
  `valid_until` datetime DEFAULT NULL,
  `last_update` timestamp NULL DEFAULT NULL,
  `insert_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`,`objective`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `_user`
--

CREATE TABLE IF NOT EXISTS `_user` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `super_admin` tinyint(1) NOT NULL DEFAULT '0',
  `username` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `password` char(64) COLLATE utf8_unicode_ci NOT NULL,
  `force_change_password` tinyint(1) NOT NULL DEFAULT '0',
  `validated` datetime DEFAULT NULL,
  `profile_image` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  `last_update` timestamp NULL DEFAULT NULL,
  `insert_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
