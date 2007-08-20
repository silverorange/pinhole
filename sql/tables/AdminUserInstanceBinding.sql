create table AdminUserInstanceBinding (
	usernum int not null references AdminUser(id) on delete cascade,
	instance int not null references PinholeInstance(id) on delete cascade,
	primary key (usernum, instance)
);

insert into AdminUserInstanceBinding (usernum, instance) select id, 1 from AdminUser;
