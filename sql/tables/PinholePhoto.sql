create table PinholePhoto (
	id serial,

	instance integer not null references PinholeInstance(id),

	title varchar(255),
	description text,

	upload_date timestamp,
	filename varchar(50),
	original_filename varchar(255),
	serialized_exif text,
	photographer integer references PinholePhotographer(id),

	photo_date timestamp,
	photo_date_parts integer not null default 0,
	-- The time in the photo_date field above is always in UTC.
	-- The photo_time_zone field below specifies how to convert the time
	-- in photo_date for output. It is the timezone the photo was taken in.
	photo_time_zone varchar(50) not null default 'UTC',

	publish_date timestamp,
	status integer not null default 0,

	primary key (id)
);

CREATE INDEX PinholePhoto_title_index ON PinholePhoto(title);
CREATE INDEX PinholePhoto_photo_date_index ON PinholePhoto(photo_date);
CREATE INDEX PinholePhoto_status_index ON PinholePhoto(status);
CREATE INDEX PinholePhoto_instance_index ON PinholePhoto(instance);
