create table PinholeMachineTagImageBinding (
	image integer not null references Image(id) on delete cascade,
	tag integer not null references PinholeMachineTag(id) on delete cascade,
	primary key (image, tag)
);
