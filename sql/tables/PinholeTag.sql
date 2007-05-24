create table PinholeTag (
	id serial,
	parent int,
	shortname varchar(255),
	title varchar(255),
	status int not null default 0,
	createdate timestamp,
	name_space varchar(50),
	primary key (id)
);

ALTER TABLE PinholeTag ADD CONSTRAINT Tagfk FOREIGN KEY (parent) REFERENCES PinholeTag(id) MATCH FULL on delete cascade;

CREATE INDEX PinholeTag_parent_index ON PinholeTag(parent);
CREATE INDEX PinholeTag_shortname_index ON PinholeTag(shortname);
CREATE INDEX PinholeTag_name_space_index ON PinholeTag(name_space);
