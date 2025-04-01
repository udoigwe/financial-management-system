CREATE OR REPLACE VIEW cards_view AS
SELECT
	a.*,
    b.user_id,
    b.first_name,
    b.last_name,
    b.email,
    b.phone
FROM
	cards a
LEFT JOIN account_view b
ON a.account_id = b.account_id