CREATE OR REPLACE VIEW budget_categories_view AS
SELECT
	a.*,
    b.user_id,
    b.first_name,
    b.last_name,
    b.email,
    b.phone
FROM
	budget_categories a
LEFT JOIN account_view b
ON a.account_id = b.account_id