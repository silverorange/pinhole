INSERT INTO AdminSection (id, displayorder, title, description, show)
	VALUES (101, 10, 'Gallery', NULL, true);

INSERT INTO AdminComponent (id, section, displayorder, shortname, title, description, enabled, show)
	VALUES (100, 101, 10, 'Photographer', 'Photographers', NULL, true, true);

SELECT setval('adminsection_id_seq', max(id)) FROM AdminSection;
SELECT setval('admincomponent_id_seq', max(id)) FROM AdminComponent;
SELECT setval('adminsubcomponent_id_seq', max(id)) FROM AdminSubComponent;

TRUNCATE TABLE AdminComponentAdminGroupBinding;

-- silverorange group
INSERT INTO AdminComponentAdminGroupBinding (component, groupnum) SELECT id, 1 FROM AdminComponent;

