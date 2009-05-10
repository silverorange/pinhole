create or replace view PinholePhotoCommentCountView as
	select
			PinholePhoto.id as photo,
			ImageSet.instance as instance,
			count(PinholeComment.id) as comment_count,
			max(PinholeComment.createdate) as last_comment_date
		from PinholePhoto
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id
			left outer join PinholeComment on
				PinholeComment.photo = PinholePhoto.id and
				PinholeComment.spam = false
		group by PinholePhoto.id, ImageSet.instance;
