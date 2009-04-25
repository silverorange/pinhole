create table PinholePhoto (
	-- Image table columns
	id serial,
	image_set integer not null references ImageSet(id) on delete cascade,
	upload_set integer references PinholePhotoUploadSet(id) on delete cascade,

	title varchar(255),
	filename varchar(255),
	original_filename varchar(255),
	description text,

	-- Pinhole specific columns
	upload_date timestamp,
	serialized_exif text,
	photographer integer references PinholePhotographer(id) on delete set null,

	photo_date timestamp,
	photo_date_parts integer not null default 0,
		-- The time in the photo_date field above is always in UTC.
		-- The photo_time_zone field below specifies how to convert the time
		-- in photo_date for output. It is the timezone the photo was taken in.
	photo_time_zone varchar(50) not null default 'UTC',

	publish_date timestamp,
	status integer not null default 0,
	private boolean not null default false,
	for_sale boolean not null default false,

	-- import settings
	auto_publish boolean not null default false,
	set_content_by_meta_data boolean not null default false,
	set_tags_by_meta_data boolean not null default false,

	primary key(id)
);

CREATE INDEX PinholePhoto_image_set_index ON PinholePhoto(image_set);
CREATE INDEX PinholePhoto_title_index ON PinholePhoto(title);
CREATE INDEX PinholePhoto_photo_date_index ON PinholePhoto(photo_date);
CREATE INDEX PinholePhoto_status_index ON PinholePhoto(status);
CREATE INDEX PinholePhoto_private_index ON PinholePhoto(private);
CREATE INDEX PinholePhoto_for_sale_index ON PinholePhoto(for_sale);
