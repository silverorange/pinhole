create table PinholePhotoDimensionBinding (
	photo integer not null references PinholePhoto(id) on delete cascade,
	dimension integer not null references ImageDimension(id) on delete cascade,
	image_type integer not null references ImageType(id),
	width integer not null,
	height integer not null,
	primary key(photo, dimension)
);
