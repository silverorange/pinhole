create table PinholeDimension (
	id serial,
	shortname varchar(255),
	title varchar(255),
	max_width int,
	max_height int,
	crop_to_max boolean default false,
	primary key (id)
);
