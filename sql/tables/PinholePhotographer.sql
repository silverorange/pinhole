create table PinholePhotographer (
	id serial,
	fullname varchar(255),
	description text,
	enabled boolean not null default true,
	archived boolean not null default false,
	primary key (id)
);
