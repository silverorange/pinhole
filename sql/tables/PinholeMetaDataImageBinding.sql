create table PinholeMetaDataImageBinding (
	image int not null references Image(id) on delete cascade,
	meta_data int not null references PinholeMetaData(id) on delete cascade,
	value varchar(255) not null,
	primary key (image, meta_data)
);

CREATE INDEX PinholeMetaDataImageBinding_value_index ON PinholeMetaDataImageBinding(value);
