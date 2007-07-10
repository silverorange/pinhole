create table PinholeTag (
	id serial,
	name varchar(255),
	title varchar(255),
	createdate timestamp,
	primary key (id)
);

CREATE INDEX PinholeTag_name_index ON PinholeTag(shortname);
