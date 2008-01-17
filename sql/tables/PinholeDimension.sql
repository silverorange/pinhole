create table PinholeDimension (
	id serial,
	instance integer null references Instance(id),
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

INSERT INTO PinholeDimension (id, shortname, title, max_width, max_height, crop_to_max, strip, publicly_accessible, instance)
VALUES (1, 'thumb', 'Thumbnail', 100, 100, false, true, true, 1);

INSERT INTO PinholeDimension (id, shortname, title, max_width, max_height, crop_to_max, strip, publicly_accessible, instance)
VALUES (2, 'large', 'Large', 800, 800, false, true, false, 1);

INSERT INTO PinholeDimension (id, shortname, title, max_width, max_height, crop_to_max, strip, publicly_accessible, instance)
VALUES (3, 'original', 'Original', null, null, false, false, false, 1);

SELECT setval('pinholedimension_id_seq', max(id)) FROM PinholeDimension;
