create table PinholeInstance (
	id serial,
	shortname varchar(255),
	title varchar(255),
	enabled boolean default true,
	primary key (id));

CREATE INDEX PinholeInstance_shortname_index ON PinholeInstance(shortname);

insert into PinholeInstance (id, shortname, title) values (1, 'default', 'Default');
SELECT setval('pinholeinstance_id_seq', max(id)) FROM PinholeInstance;
