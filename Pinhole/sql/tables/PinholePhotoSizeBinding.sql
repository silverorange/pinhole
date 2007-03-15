create table PinholePhotoSizeBinding (
	photo integer not null references PinholePhoto(id) on delete cascade,
	photo_size integer not null references PinholePhotoSize(id) on delete cascade,
	width integer not null default 0,
	height integer not null default 0,

	primary key (photo, photo_size)
);

