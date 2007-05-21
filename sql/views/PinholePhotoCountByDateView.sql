create or replace view PinholePhotoCountByDateView as
	select count(PinholePhoto.id) as photo_count,
		cast(PinholePhoto.photo_date as date) as photo_date,
		PinholePhoto.status
	from PinholePhoto
	group by
		cast(PinholePhoto.photo_date as date),
		PinholePhoto.status
