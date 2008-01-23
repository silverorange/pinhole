/*
Convert PinholePhoto to make use of SiteImage tables
*/

truncate PinholePhotoDimensionBinding;
delete from PinholePhoto;
delete from ImageDimension;
delete from ImageSet;

-- sets

insert into ImageSet (id, shortname, obfuscate_filename, instance)
	select id, 'photos', true, id from Instance;

SELECT setval('imageset_id_seq', max(id)) FROM ImageSet;

-- dimensions

insert into ImageDimension (id, image_set, default_type, shortname, title,
	max_width, max_height, crop, strip, publicly_accessible)
select id, instance, 1, shortname, title,
    max_width, max_height, crop_to_max, strip, publicly_accessible
from PinholeDimension;

SELECT setval('imagedimension_id_seq', max(id)) FROM ImageDimension;

-- images

insert into PinholePhoto (id, image_set, title, filename, original_filename, description,
	upload_date, serialized_exif, photographer, photo_date, photo_date_parts,
	photo_time_zone, publish_date, status)
select id, instance, title, filename, original_filename, description,
	upload_date, serialized_exif, photographer, photo_date, photo_date_parts,
	photo_time_zone, publish_date, status
from PinholePhotoOld;

SELECT setval('pinholephoto_id_seq', max(id)) FROM PinholePhoto;

-- image dimensions

insert into PinholePhotoDimensionBinding (photo, dimension, image_type, width, height)
select photo, dimension, 1, width, height
from PinholePhotoDimensionBindingOld;

