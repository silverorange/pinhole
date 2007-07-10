create table PinholeMachineTag (
	id serial,
	namespace varchar(255),
	name varchar(255),
	value varchar(255),
	createdate timestamp,
	primary key (id)
);
