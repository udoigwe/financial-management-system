ALTER TABLE cards
ADD CONSTRAINT chk_card_expiry_date_after_issuance_date
CHECK (expiry_date > issue_date);