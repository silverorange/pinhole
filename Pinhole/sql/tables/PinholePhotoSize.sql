/*
 * Note:
 * This table is only for photo sizes that appear on the photo details page.
 * The thumbail size will be set elsewhere so we can treat the photos
 * differently in the file-loader security.
 */
create table PinholePhotoSize (
	id serial,
	title varchar(255) not null,
	shortname varchar(255) not null,
	width integer not null default 0,
	height integer not null default 0,
	primary key (id)
);

