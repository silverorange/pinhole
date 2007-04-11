create table PineholePhotoDimensionBinding (
	photo int not null references PinholePhoto(id) on delete cascade,
	dimension int not null references PinholeDimension(id) on delete cascade,
	width int not null,
	height int not null,
	primary key (photo, dimension)
);
