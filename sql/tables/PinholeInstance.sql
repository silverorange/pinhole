create table PinholeInstance (
	id serial,
	shortname varchar(255),
	title varchar(255),
	enabled boolean default true,
	primary key (id));

CREATE INDEX PinholeInstance_shortname_index ON PinholeInstance(shortname);

