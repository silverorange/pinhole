alter table Image add upload_date timestamp;
alter table Image add serialized_exif text;
alter table Image add photographer integer references PinholePhotographer(id);

alter table Image add photo_date timestamp;
alter table Image add photo_date_parts integer not null default 0;
	-- The time in the photo_date field above is always in UTC.
	-- The photo_time_zone field below specifies how to convert the time
	-- in photo_date for output. It is the timezone the photo was taken in.
alter table Image add photo_time_zone varchar(50) not null default 'UTC';

alter table Image add publish_date timestamp;
alter table Image add status integer not null default 0;

CREATE INDEX Image_title_index ON Image(title);
CREATE INDEX Image_photo_date_index ON Image(photo_date);
CREATE INDEX Image_status_index ON Image(status);
