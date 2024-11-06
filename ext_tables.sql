#
# Table structure for table 'tx_legaltext_domain_model_legal_text'
#
CREATE TABLE tx_itrklegaltextstypo3_domain_model_legal_text (
	user_account_id varchar(255) DEFAULT '' NOT NULL,
	type varchar(20) DEFAULT '' NOT NULL,
	text text NOT NULL,
	html text NOT NULL,
	pdf_url varchar(255) DEFAULT '' NOT NULL,
	country varchar(2) DEFAULT '' NOT NULL,
	language varchar(2) DEFAULT '' NOT NULL,
	root_page_id int(11) DEFAULT 0 NOT NULL
);
