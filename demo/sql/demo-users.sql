insert into AdminUser (id, email, name, password, password_salt, enabled, force_change_password)
values (1, 'demo@silverorange.com', 'Demo User', 'fe01ce2a7fbac8fafaed7c982a04e229', '', true, false);

SELECT setval('adminuser_id_seq', max(id)) FROM AdminUser;

-- default AdminUserAdminGroupBinding bindings
insert into AdminUserAdminGroupBinding (usernum, groupnum)
	select AdminUser.id, AdminGroup.id from AdminUser, AdminGroup;
