create table PinholePhotographer (
	id serial,
	fullname varchar(255),
	description text,
	status int not null default 0,
	primary key (id)
);
