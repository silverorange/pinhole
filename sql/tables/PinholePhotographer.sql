create table PinholePhotographer (
	id serial,
	fullname varchar(255),
	bodytext text,
	enabled boolean not null default true,
	archived boolean not null default false,
	createdate timestamp,
	primary key (id)
);

