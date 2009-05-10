create table PinholeComment (
	id serial,
	photo integer not null references PinholePhoto(id) on delete cascade,
	photographer integer references PinholePhotographer(id),
	fullname varchar(255),
	link varchar(255),
	email varchar(255),
	bodytext text not null,
	status integer not null default 0,
	spam boolean not null default false,
	ip_address varchar(15),
	user_agent varchar(255),
	createdate timestamp not null,
	primary key (id)
);

create index PinholeComment_spam_index on PinholeComment(spam);
create index PinholeComment_photo_index on PinholeComment(photo);
create index PinholeComment_status_index on PinholeComment(status);
