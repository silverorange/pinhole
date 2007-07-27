create table PinholeComment (
	id serial, 
	instance integer not null references PinholeInstance(id),
	photo integer references PinholePhoto (id) not null,
	name varchar(255),
	bodytext varchar(255),
	email varchar(255),
	url varchar(255),
	remote_ip varchar(15),
	show boolean not null default true,
	rating integer default null,
	createdate timestamp,
	primary key (id)
);
