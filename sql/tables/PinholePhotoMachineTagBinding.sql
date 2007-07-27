create table PinholePhotoMachineTagBinding (
	photo integer not null references PinholePhoto(id) on delete cascade,
	tag integer not null references PinholeMachineTag(id) on delete cascade,
	primary key (photo, tag)
);
