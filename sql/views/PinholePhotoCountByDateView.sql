create or replace view PinholePhotoCountByDateView as
	select  count(PinholePhoto.id) as photo_count,
		max(PinholePhoto.photo_date) as photo_date,
		PinholePhoto.status
	from PinholePhoto
	group by date_part('year', PinholePhoto.photo_date),
		 date_part('month', PinholePhoto.photo_date),
		 date_part('day', PinholePhoto.photo_date),
		 PinholePhoto.status
