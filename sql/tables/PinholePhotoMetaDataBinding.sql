create table PinholePhotoMetaDataBinding (
	photo int not null references PinholePhoto(id) on delete cascade,
	metadata int not null references PinholeMetaData(id) on delete cascade,
	value varchar(255) not null,
	primary key (photo, metadata)
);

CREATE INDEX PinholePhotoMetaDataBinding_value_index ON PinholePhotoMetaDataBinding(value);
