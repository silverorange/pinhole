create table PinholeComment (
	id serial, 
	photo serial references PinholePhoto (id),
	fullname varchar(255),
	bodytext varchar(255),
	email varchar(255),
	webaddress varchar(255),
	rating integer default null,
	create_date timestamp,
	primary key (id)
);
