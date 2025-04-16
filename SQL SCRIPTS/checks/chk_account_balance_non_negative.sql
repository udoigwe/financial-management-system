ALTER TABLE account
ADD CONSTRAINT chk_account_balance_non_negative
CHECK (balance >= 0);