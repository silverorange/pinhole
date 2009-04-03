create table PinholePhotoMetaDataBinding (
	photo int not null references PinholePhoto(id) on delete cascade,
	meta_data int not null references PinholeMetaData(id) on delete cascade,
	value varchar(255) not null,
	primary key (photo, meta_data)
);

CREATE INDEX PinholePhotoMetaDataBinding_photo_index ON PinholePhotoMetaDataBinding(photo);
CREATE INDEX PinholePhotoMetaDataBinding_meta_data_index ON PinholePhotoMetaDataBinding(meta_data);
CREATE INDEX PinholePhotoMetaDataBinding_value_index ON PinholePhotoMetaDataBinding(value);
