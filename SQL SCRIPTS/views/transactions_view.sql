CREATE OR REPLACE VIEW transactions_view AS
SELECT
	a.*,
    b.user_id,
    b.first_name,
    b.last_name,
    b.email,
    b.phone,
    b.account_type,
    c.category_name,
    c.color_code,
    d.first_name AS sender_first_name,
    d.last_name AS sender_last_name,
    d.phone AS sender_phone
FROM
	transactions a
LEFT JOIN account_view b
ON a.account_id = b.account_id
LEFT JOIN budget_categories c 
ON a.budget_category_id = c.category_id
LEFT JOIN account_view d
ON a.sender_account_id = d.account_id