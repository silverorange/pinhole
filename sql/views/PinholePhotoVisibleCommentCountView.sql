create or replace view PinholePhotoVisibleCommentCountView as
	select
			PinholePhoto.id as photo,
			ImageSet.instance as instance,
			count(PinholeComment.id) as visible_comment_count,
			max(PinholeComment.createdate) as last_comment_date
		from PinholePhoto
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id
			left outer join PinholeComment on
				PinholeComment.photo = PinholePhoto.id and
				PinholeComment.spam = false and
				PinholeComment.status = 1 -- status 1 is published
		group by PinholePhoto.id, ImageSet.instance;
