CREATE OR REPLACE VIEW notifications_view AS
SELECT
	a.*,
    b.first_name,
    b.last_name,
    b.email,
    b.phone,
    b.role
FROM
	notifications a
LEFT JOIN users b
ON a.user_id = b.user_id