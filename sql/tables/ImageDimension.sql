alter table ImageDimension add publicly_accessible boolean default false;

/*
INSERT INTO PinholeDimension (id, shortname, title, max_width, max_height, crop_to_max, strip, publicly_accessible, instance)
VALUES (1, 'thumb', 'Thumbnail', 100, 100, false, true, true, 1);

INSERT INTO PinholeDimension (id, shortname, title, max_width, max_height, crop_to_max, strip, publicly_accessible, instance)
VALUES (2, 'large', 'Large', 800, 800, false, true, false, 1);

INSERT INTO PinholeDimension (id, shortname, title, max_width, max_height, crop_to_max, strip, publicly_accessible, instance)
VALUES (3, 'original', 'Original', null, null, false, false, false, 1);

SELECT setval('pinholedimension_id_seq', max(id)) FROM PinholeDimension;
*/
