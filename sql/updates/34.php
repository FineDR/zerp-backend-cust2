<?php
CreateTable('api_supplier_invoice_draft', "CREATE TABLE IF NOT EXISTS api_supplier_invoice_draft (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	draft_uuid CHAR(36) NOT NULL,
	created_by VARCHAR(20) NOT NULL,
	supplierid VARCHAR(10) NOT NULL,
	tran_date DATE NULL,
	due_date DATE NULL,
	supp_reference VARCHAR(20) NULL,
	ex_rate DECIMAL(18,10) NOT NULL DEFAULT 1.0000000000,
	comments TEXT NULL,
	tax_mode ENUM('AUTO','MANUAL') NOT NULL DEFAULT 'AUTO',
	currency CHAR(3) NOT NULL,
	curr_decimal_places INT NOT NULL DEFAULT 2,
	taxgroupid INT NOT NULL,
	local_tax_province INT NOT NULL,
	status ENUM('DRAFT','POSTED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
	posted_transno INT NULL,
	posted_supptrans_id BIGINT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
	UNIQUE KEY uq_draft_uuid (draft_uuid),
	KEY idx_created_by_status (created_by, status),
	KEY idx_supplierid_status (supplierid, status)
	) ENGINE=InnoDB)");

	CreateTable('api_supplier_invoice_lines',"CREATE TABLE IF NOT EXISTS api_supplier_invoice_lines (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	draft_uuid CHAR(36) NOT NULL,
	line_type ENUM('GRN','GL','SHIPMENT','CONTRACT','ASSET') NOT NULL,
	ref1 VARCHAR(50) NULL,
	ref2 VARCHAR(50) NULL,
	ref3 VARCHAR(50) NULL,
	description VARCHAR(255) NULL,
	qty DECIMAL(18,4) NULL,
	unit_price DECIMAL(18,4) NULL,
	amount DECIMAL(18,4) NULL,
	meta JSON NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY idx_draft_uuid (draft_uuid),
	CONSTRAINT fk_lines_draft FOREIGN KEY (draft_uuid)
		REFERENCES api_supplier_invoice_draft (draft_uuid)
		ON DELETE CASCADE
	) ENGINE=InnoDB");

	CreateTable('api_supplier_invoice_taxes',"CREATE TABLE IF NOT EXISTS api_supplier_invoice_taxes (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	draft_uuid CHAR(36) NOT NULL,
	taxauthid INT NOT NULL,
	taxglcode INT NOT NULL,
	tax_rate DECIMAL(18,8) NOT NULL DEFAULT 0,
	tax_on_tax TINYINT NOT NULL DEFAULT 0,
	calc_order INT NOT NULL DEFAULT 1,
	amount_supplier DECIMAL(18,4) NOT NULL DEFAULT 0,
	PRIMARY KEY (id),
	UNIQUE KEY uq_draft_tax (draft_uuid, taxauthid),
	KEY idx_draft_uuid (draft_uuid),
	CONSTRAINT fk_taxes_draft FOREIGN KEY (draft_uuid)
		REFERENCES api_supplier_invoice_draft (draft_uuid)
		ON DELETE CASCADE
	) ENGINE=InnoDB");

	CreateTable('api_idempotency_keys',"CREATE TABLE IF NOT EXISTS api_idempotency_keys (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	idem_key VARCHAR(80) NOT NULL,
	created_by VARCHAR(20) NOT NULL,
	action VARCHAR(40) NOT NULL,
	response_json JSON NOT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY(id),
	UNIQUE KEY uq_idem (idem_key, created_by, action)
	) ENGINE=InnoDB");

if ($_SESSION['Updates']['Errors'] == 0) {
	UpdateDBNo(basename(__FILE__, '.php'), __('Add temporary tables for supplier invoice'));
}