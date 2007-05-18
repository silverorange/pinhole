create table PinholePhoto (
	id serial,
	title varchar(255),
	description text,

	upload_date timestamp,
	filename varchar(50),
	original_filename varchar(255),
	exif text,
	photographer integer references PinholePhotographer(id),

	photo_date timestamp,
	photo_date_parts integer not null default 0,

	publish_date timestamp,
	status integer not null default 0,
	
	primary key (id)
);
