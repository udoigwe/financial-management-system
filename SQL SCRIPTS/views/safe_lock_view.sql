CREATE OR REPLACE VIEW safe_lock_view AS
SELECT
	a.*,
    b.user_id,
    b.first_name,
    b.last_name,
    b.email,
    b.phone,
    b.account_type,
    b.pin
FROM
	safe_lock a
LEFT JOIN account_view b
ON a.account_id = b.account_id