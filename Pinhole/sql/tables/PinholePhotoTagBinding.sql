create table PinholePhotoTagBinding (
	photo integer not null references PinholePhoto(id) on delete cascade,
	tag integer not null references PinholeTag(id) on delete cascade,

	primary key (id)
);

