alter table ImageSet add instance integer null references Instance(id) on delete cascade;
CREATE INDEX ImageSet_instance_index ON ImageSet(instance);
