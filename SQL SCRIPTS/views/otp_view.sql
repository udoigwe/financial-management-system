CREATE OR REPLACE VIEW otp_view AS
SELECT
	a.*,
    b.user_id,
    b.first_name,
    b.last_name,
    b.email,
    b.phone
FROM
	otp a
LEFT JOIN account_view b
ON a.account_id = b.account_id