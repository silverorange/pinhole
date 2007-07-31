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

CREATE INDEX PinholeDimension_instance_index ON PinholeDimension(instance);

INSERT INTO pinholedimension (id, shortname, title, max_width, max_height, crop_to_max, strip, publicly_accessible, instance) VALUES (2, 'thumb', 'Thumbnail', 100, 100, false, true, true, 1);
INSERT INTO pinholedimension (id, shortname, title, max_width, max_height, crop_to_max, strip, publicly_accessible, instance) VALUES (3, 'large', 'Large', 800, 800, false, true, false, 1);

