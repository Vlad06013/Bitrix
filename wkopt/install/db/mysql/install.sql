
CREATE TABLE wk_spec 
(
	ID int NOT NULL AUTO_INCREMENT,
	ID_1C varchar(255) NOT NULL,
	USER_ID_1C varchar(255) NOT NULL,
	CAREER varchar(255) NOT NULL,
	DELIVERY_TO varchar(255) NOT NULL,
	PICKUP varchar(1) NOT NULL,
	PRIMARY KEY(ID)
);
CREATE TABLE wk_spec_products 
(
	ID int NOT NULL AUTO_INCREMENT,
	ID_1C varchar(255) NOT NULL,
	MAX_QUANTITY int NOT NULL,
	AVAILABLE_QUANTITY int NOT NULL,
	SPEC_ID varchar(255) NOT NULL,
	PRICE double NOT NULL,
	PRIMARY KEY(ID)
);
CREATE TABLE wk_new_order_info 
(
	ID int NOT NULL AUTO_INCREMENT,
	TIME_DELIVERY varchar(255),
	DATE_DELIVERY date,
	DATE_CREATE date,
	SPEC_ID int,
	USER_GETTER_NAME varchar(255),
	USER_GETTER_PHONE varchar(255),
	STATUS_DELIVERY varchar(2) NOT NULL,
	USER_ID int NOT NULL,
	PRIMARY KEY(ID)
);
CREATE TABLE wk_new_order_products
(
	ID int NOT NULL AUTO_INCREMENT,
	QUANTITY int NOT NULL,
	ORDER_ID int NOT NULL,
	PRIMARY KEY(ID)
);
-- default 'N',