create table PinholePhoto (
	id serial,
	title varchar(255),
	bodytext text,
	enabled boolean not null default true,
	published boolean not null default false,
	reply_mode integer not null default 0, -- set with constants for (open/closed/locked)
	filename varchar(255),
	original_filename varchar(255),
	photographer integer references PinholePhotographer(id),
	exif text,

	photo_date datetime,
	photo_date_parts integer not null default 0,
	publish_date datetime,
	upload_date datetime,
	
	primary key (id)
);

