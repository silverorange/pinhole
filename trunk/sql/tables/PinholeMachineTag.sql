create table PinholeMachineTag (
	id serial,

	instance integer not null references PinholeInstance(id),

	namespace varchar(255),
	name varchar(255),
	value varchar(255),
	createdate timestamp,

	primary key (id)
);

CREATE INDEX PinholeMachineTag_instance_index ON PinholeMachineTag(instance);
