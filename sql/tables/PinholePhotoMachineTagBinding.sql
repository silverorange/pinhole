create table PinholePhotoMachineTagBinding (
	photo int not null references PinholePhoto(id) on delete cascade,
	tag int not null references PinholeMachineTag(id) on delete cascade,
	primary key (photo, tag)
);
