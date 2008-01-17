New Note 88

ALTER TABLE pinholedimension ALTER column instance drop not null;
ALTER TABLE pinholemachinetag ALTER column instance drop not null;
ALTER TABLE pinholemetadata ALTER column instance drop not null;
ALTER TABLE pinholephoto ALTER column instance drop not null;
ALTER TABLE pinholephotographer ALTER column instance drop not null;
ALTER TABLE pinholetag ALTER column instance drop not null;

ALTER TABLE pinholedimension drop constraint pinholedimension_instance_fkey;
ALTER TABLE pinholemachinetag drop constraint pinholemachinetag_instance_fkey;
ALTER TABLE pinholemetadata drop constraint pinholemetadata_instance_fkey;
ALTER TABLE pinholephoto drop constraint pinholephoto_instance_fkey;
ALTER TABLE pinholephotographer drop constraint pinholephotographer_instance_fkey;
ALTER TABLE pinholetag drop constraint pinholetag_instance_fkey;
ALTER TABLE adminuserinstancebinding drop constraint adminuserinstancebinding_instance_fkey;

ALTER TABLE pinholedimension add constraint pinholedimension_instance_fkey FOREIGN KEY (instance) REFERENCES instance(id);
ALTER TABLE pinholemachinetag add constraint pinholemachinetag_instance_fkey FOREIGN KEY (instance) REFERENCES instance(id);
ALTER TABLE pinholemetadata add constraint pinholemetadata_instance_fkey FOREIGN KEY (instance) REFERENCES instance(id);
ALTER TABLE pinholephoto add constraint pinholephoto_instance_fkey FOREIGN KEY (instance) REFERENCES instance(id);
ALTER TABLE pinholephotographer add constraint pinholephotographer_instance_fkey FOREIGN KEY (instance) REFERENCES instance(id);
ALTER TABLE pinholetag add constraint pinholetag_instance_fkey FOREIGN KEY (instance) REFERENCES instance(id);
ALTER TABLE adminuserinstancebinding add constraint adminuserinstancebinding_instance_fkey FOREIGN KEY (instance) REFERENCES instance(id);

INSERT into instanceconfigsetting (instance, name, value) select id,'site.title', title from pinholeinstance;
INSERT into instanceconfigsetting (instance, name, value) select id,'site.enabled', cast(enabled as integer) from pinholeinstance;

drop TABLE pinholeinstance ;

