create table PinholeTag (
	id serial,
	title varchar(255),
	shortname varchar(255),
	bodytext text,
	enabled boolean not null default true,
	displayorder integer not null default 0,
	archived boolean not null default false,

	thumb_width integer,
	thumb_height integer,
	large_width integer,
	large_height integer,

	createdate datetime,

	primary key (id)
);

