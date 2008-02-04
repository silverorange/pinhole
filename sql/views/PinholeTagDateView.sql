create or replace view PinholeTagDateView as
select max(publish_date) as last_modified, min(publish_date) as first_modified,
	PinholePhotoTagBinding.tag
from PinholePhotoTagBinding
inner join PinholePhoto on PinholePhoto.id = PinholePhotoTagBinding.photo
group by PinholePhotoTagBinding.tag;
