create table PinholeDimension (
	id serial,
	instance integer not null references PinholeInstance(id),
	shortname varchar(255),
	title varchar(255),
	max_width int,
	max_height int,
	crop_to_max boolean default false,
	strip boolean default false,
	publicly_accessible boolean default false,
	primary key (id)
);
