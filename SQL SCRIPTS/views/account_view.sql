CREATE OR REPLACE VIEW account_view AS
SELECT
	a.*,
    b.first_name,
    b.last_name,
    b.dob,
    b.email,
    b.password,
    b.gender,
    b.identification,
    b.identification_number,
    b.phone,
    b.role,
    b.last_seen,
    b.created_at AS joined_at,
    b.account_status,
    c.first_name AS account_officer_first_name,
    c.last_name AS account_officer_last_name,
    c.phone AS account_officer_phone,
    c.email AS account_officer_email
FROM 
	account a
LEFT JOIN users b
ON a.user_id = b.user_id
LEFT JOIN users c
ON a.account_officer_id = c.user_id
WHERE b.role = 'Customer'