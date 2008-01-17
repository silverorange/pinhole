create table PinholePhotographer (
	id serial,
	instance integer null references Instance(id),
	fullname varchar(255),
	description text,
	status int not null default 0,
	primary key (id)
);

CREATE INDEX PinholePhotographer_instance_index ON PinholePhotographer(instance);
