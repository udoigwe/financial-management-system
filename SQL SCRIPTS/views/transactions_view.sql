CREATE OR REPLACE VIEW transactions_view AS
SELECT
	a.*,
    b.user_id,
    b.first_name,
    b.last_name,
    b.email,
    b.phone,
    b.account_type,
    c.category_name
FROM
	transactions a
LEFT JOIN account_view b
ON a.account_id = b.account_id
LEFT JOIN budget_categories c 
ON a.budget_category_id = c.category_id