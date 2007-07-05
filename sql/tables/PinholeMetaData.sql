create table PinholeMetaData (
	id serial,
	shortname varchar(255),
	title varchar(255),
	show boolean default false,
	primary key (id)
);

CREATE INDEX PinholeMetaData_shortname_index ON PinholeMetaData(shortname);
