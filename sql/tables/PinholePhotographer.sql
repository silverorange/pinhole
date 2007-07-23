create table PinholePhotographer (
	id serial,
	instance integer not null references PinholeInstance(id),
	fullname varchar(255),
	description text,
	status int not null default 0,
	primary key (id)
);
