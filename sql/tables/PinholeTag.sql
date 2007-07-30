create table PinholeTag (
	id serial,
	instance integer not null references PinholeInstance(id),
	name varchar(255),
	title varchar(255),
	createdate timestamp,
	primary key (id)
);

CREATE INDEX PinholeTag_name_index ON PinholeTag(shortname);
CREATE INDEX PinholeTag_instance_index ON PinholeTag(instance);
