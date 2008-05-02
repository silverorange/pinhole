create table PinholePhotoTagBinding (
	photo int not null references PinholePhoto(id) on delete cascade,
	tag int not null references PinholeTag(id) on delete cascade,
	displayorder int,
	primary key (photo, tag)
);
