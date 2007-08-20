/*
 * Returns navbar information from a tag.
 *
 * @param_parent INTEGER: the id of the tag to search from.
 *
 * @returned_row type_tag_navbar: a row containing id, shortname and title
 *
 * Returns a set of returned_rows ordered correctly to go in the navbar.
 * If the tag is not found, returns an empty recordset
 *
 * This procedure uses recursion to output entries in the correct order for
 * applications.
 */
CREATE TYPE type_tag_navbar AS (id INTEGER, parent INTEGER, shortname VARCHAR(255), title VARCHAR(255));

CREATE OR REPLACE FUNCTION getPinholeTagNavBar(INTEGER) RETURNS SETOF type_tag_navbar AS $$
	DECLARE
		param_id ALIAS FOR $1;
		local_found BOOLEAN;
		returned_row type_tag_navbar%ROWTYPE;
		parent_row type_tag_navbar%ROWTYPE;
	BEGIN
		-- get current tag results
		SELECT INTO returned_row id, parent, shortname, title
		FROM PinholeTag
		WHERE id = param_id;

		local_found = FOUND;

		-- get parent tag results
		IF returned_row.parent IS NOT NULL THEN
		FOR parent_row IN SELECT * FROM getPinholeTagNavBar(returned_row.parent) LOOP
			RETURN NEXT parent_row;
		END LOOP;
		END IF;

		IF local_found THEN
			RETURN NEXT returned_row;
		END IF;

		RETURN;
	END;
$$ LANGUAGE 'plpgsql';
