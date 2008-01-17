create table PinholeTag (
	id serial,
	instance integer null references Instance(id),
	name varchar(255),
	title varchar(255),
	createdate timestamp,
	primary key (id)
);

CREATE INDEX PinholeTag_name_index ON PinholeTag(name);
CREATE INDEX PinholeTag_instance_index ON PinholeTag(instance);
