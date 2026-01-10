# Paystack Transfer Integration - Implementation Summary

## What Was Implemented

Complete **automated bank transfer system** for logistics company payouts using Paystack Transfer API.

## New Files Created

1. **app/Services/PaystackService.php**
   - `createTransferRecipient()` - Register bank account with Paystack
   - `initiateTransfer()` - Send money to bank account
   - `verifyTransfer()` - Check transfer status
   - `finalizeTransfer()` - Complete OTP verification (if needed)
   - `listBanks()` - Get all Nigerian banks
   - `resolveAccountNumber()` - Verify account number

2. **app/Http/Controllers/Api/PaystackWebhookController.php**
   - Handles `transfer.success` - Auto-marks withdrawal as completed
   - Handles `transfer.failed` - Auto-refunds wallet
   - Handles `transfer.reversed` - Auto-refunds wallet
   - Signature verification for security

## Modified Files

### Migrations (4 new)
1. `add_paystack_recipient_code_to_logistics_companies_table.php`
2. `add_paystack_fields_to_wallet_withdrawals_table.php` 
3. `add_bank_code_to_logistics_companies_table.php`

### Models
- **LogisticsCompany**: Added `bank_code`, `paystack_recipient_code`
- **WalletWithdrawal**: Added `paystack_transfer_code`, `paystack_transfer_id`

### Controllers
- **AdminController**: 
  - Updated `createLogisticsPayout()` - Now calls Paystack API automatically
  - Added `createPaystackRecipient()` - Setup transfer recipient
  - Added `getPaystackBanks()` - List available banks

### Routes (api.php)
- `POST /api/v1/webhooks/paystack/transfer` - Webhook endpoint (public)
- `GET /api/v1/admin/paystack/banks` - Get banks list
- `POST /api/v1/admin/logistics-companies/{id}/paystack-recipient` - Create recipient

### Documentation
- Updated **LOGISTICS_WITHDRAWAL_API.md** with full Paystack integration guide

## Database Schema Changes

```sql
-- logistics_companies table
ALTER TABLE logistics_companies 
  ADD bank_code VARCHAR(255) NULL,
  ADD paystack_recipient_code VARCHAR(255) NULL;

-- wallet_withdrawals table  
ALTER TABLE wallet_withdrawals
  ADD paystack_transfer_code VARCHAR(255) NULL,
  ADD paystack_transfer_id BIGINT NULL;
```

## How It Works

### One-Time Setup (per logistics company)
1. Admin gets list of banks: `GET /admin/paystack/banks`
2. Find bank code (e.g., GTBank = "058")
3. Create recipient: `POST /admin/logistics-companies/1/paystack-recipient`
4. System stores `paystack_recipient_code` in database

### Every Payout (automated)
1. Admin creates withdrawal → wallet debited
2. **System automatically calls Paystack Transfer API**
3. Money sent to logistics company's bank account
4. **Paystack webhook confirms success** → status updated to "completed"
5. Or **webhook reports failure** → wallet auto-refunded

## Flow Diagram

```
┌─────────────┐
│ Admin       │
│ Creates     │
│ Payout      │
└──────┬──────┘
       │
       ▼
┌─────────────────────────┐
│ 1. Debit Wallet         │
│ 2. Call Paystack API    │◄─────┐
│ 3. Status: processing   │      │
└──────┬──────────────────┘      │
       │                         │
       │                         │
       ▼                         │
┌──────────────────┐             │
│ Paystack         │             │
│ Processes        │             │
│ Transfer         │             │
└──────┬───────────┘             │
       │                         │
       │ (Success OR Failure)    │
       │                         │
       ▼                         │
┌──────────────────┐             │
│ Paystack sends   │             │
│ Webhook          │─────────────┘
└──────┬───────────┘
       │
       ▼
┌──────────────────────────┐
│ SUCCESS: Mark completed  │
│ FAILED: Refund wallet    │
└──────────────────────────┘
```

## Testing Checklist

### Setup Phase
- [ ] Get Paystack secret key from dashboard
- [ ] Add to `.env`: `PAYSTACK_SECRET_KEY=sk_test_xxxxx`
- [ ] Get list of banks: `GET /admin/paystack/banks`
- [ ] Create test logistics company with bank details
- [ ] Create Paystack recipient: `POST /admin/logistics-companies/1/paystack-recipient`

### Payout Phase
- [ ] Create wallet transaction for logistics company
- [ ] Create payout: `POST /admin/logistics-companies/1/payout`
- [ ] Verify Paystack API was called (check logs)
- [ ] Verify wallet was debited
- [ ] Verify status is "processing"

### Webhook Phase
- [ ] Configure webhook URL in Paystack dashboard: `https://yourdomain.com/api/v1/webhooks/paystack/transfer`
- [ ] Wait for Paystack to send webhook
- [ ] Verify status changed to "completed" or "failed"
- [ ] If failed, verify wallet was refunded

## Environment Variables Required

```env
PAYSTACK_SECRET_KEY=sk_test_xxxxxxxxxxxxx
```

## Manual Fallback

System still works if Paystack is not configured:
- Payouts created with status "pending"
- Admin manually transfers money via bank
- Admin manually updates status to "completed"

## Security Features

✅ Webhook signature verification (HMAC SHA512)
✅ Immediate wallet debit (prevents double-spend)
✅ Automatic refund on failures
✅ Full audit trail in metadata
✅ Unique references for idempotency

## Next Steps

1. **Test in Paystack Test Mode**
   - Use test API keys
   - Use Paystack test bank accounts
   - Verify webhooks work

2. **Configure Webhook URL in Paystack Dashboard**
   - Go to Settings → Webhooks
   - Add: `https://yourdomain.com/api/v1/webhooks/paystack/transfer`
   - Events: `transfer.success`, `transfer.failed`, `transfer.reversed`

3. **Go Live**
   - Switch to live API keys
   - Update webhook URL to production domain
   - Test with real (small) transfers first

## Support

- Paystack API Docs: https://paystack.com/docs/api/transfer/
- Paystack Transfer Guide: https://paystack.com/docs/transfers/single-transfers/
- Implementation Doc: `docs/LOGISTICS_WITHDRAWAL_API.md`
