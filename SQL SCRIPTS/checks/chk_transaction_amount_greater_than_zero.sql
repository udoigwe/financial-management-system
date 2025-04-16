ALTER TABLE transactions
ADD CONSTRAINT chk_transaction_amount_greater_than_zero
CHECK (amount > 0);