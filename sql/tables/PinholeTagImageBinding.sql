create table PinholeTagImageBinding (
	image int not null references Image(id) on delete cascade,
	tag int not null references PinholeTag(id) on delete cascade,
	primary key (image, tag)
);
