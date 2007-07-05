create table PinholePhoto (
	id serial,
	title varchar(255),
	description text,

	upload_date timestamp,
	filename varchar(50),
	original_filename varchar(255),
	serialized_exif text,
	photographer integer references PinholePhotographer(id),

	photo_date timestamp,
	photo_date_parts integer not null default 0,

	publish_date timestamp,
	status integer not null default 0,
	
	primary key (id)
);

CREATE INDEX PinholePhoto_title_index ON PinholePhoto(title);
CREATE INDEX PinholePhoto_photo_date_index ON PinholePhoto(photo_date);
CREATE INDEX PinholePhoto_status_index ON PinholePhoto(status);
