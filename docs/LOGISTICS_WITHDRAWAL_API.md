# Logistics Company Withdrawal API (with Paystack Integration)

This document describes the admin API endpoints for managing logistics company wallet withdrawals/payouts with automated Paystack Transfer API integration.

## Overview

The system now supports **automated bank transfers** via Paystack Transfer API:
1. View all logistics companies and their wallet balances
2. Setup Paystack transfer recipient (one-time per company)
3. Create withdrawal/payout requests that **automatically transfer money** via Paystack
4. Paystack webhooks automatically update withdrawal status
5. View all withdrawals with filtering options
6. Manual status updates still available as fallback

### How It Works

**First Time Setup (per company):**
1. Admin gets list of banks from Paystack
2. Admin creates Paystack transfer recipient with bank_code
3. System stores `paystack_recipient_code` in database

**Every Payout:**
1. Admin creates withdrawal → wallet debited immediately
2. **System automatically calls Paystack Transfer API** → money sent to bank
3. **Paystack webhook confirms transfer** → status auto-updated to "completed"
4. If transfer fails → webhook triggers auto-refund to wallet

## Authentication

All endpoints require:
- Valid authentication token (Sanctum)
- Admin role

Include token in headers:
```
Authorization: Bearer YOUR_TOKEN_HERE
```

---

## Setup Endpoints

### 1. Get List of Nigerian Banks

**GET** `/api/v1/admin/paystack/banks`

Returns all Nigerian banks supported by Paystack with their bank codes.

#### Response Example
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Access Bank",
      "slug": "access-bank",
      "code": "044",
      "longcode": "044150149",
      "gateway": "emandate",
      "pay_with_bank": false,
      "active": true,
      "is_deleted": false,
      "country": "Nigeria",
      "currency": "NGN",
      "type": "nuban"
    },
    {
      "id": 9,
      "name": "Guaranty Trust Bank",
      "slug": "guaranty-trust-bank",
      "code": "058",
      "longcode": "058152036",
      "gateway": "emandate",
      "pay_with_bank": false,
      "active": true,
      "is_deleted": false,
      "country": "Nigeria",
      "currency": "NGN",
      "type": "nuban"
    }
  ]
}
```

---

### 2. Create Paystack Transfer Recipient

**POST** `/api/v1/admin/logistics-companies/{companyId}/paystack-recipient`

Creates a Paystack transfer recipient for a logistics company. **Required before automated transfers can work.**

#### Path Parameters
- `companyId` (integer, required): The logistics company ID

#### Request Body
```json
{
  "bank_code": "058"
}
```

#### Fields
- `bank_code` (string, required): Paystack bank code (get from banks list endpoint)

#### Response Example (Success)
```json
{
  "success": true,
  "message": "Paystack recipient created successfully",
  "data": {
    "company": {
      "id": 1,
      "name": "FastShip Logistics",
      "code": "FASTSHIP",
      "bank_name": "GTBank",
      "bank_code": "058",
      "account_number": "0123456789",
      "account_name": "FastShip Logistics Ltd",
      "paystack_recipient_code": "RCP_xxxxxxxxxxx",
      "is_active": true
    },
    "recipient_code": "RCP_xxxxxxxxxxx"
  }
}
```

#### Error Responses

**Bank Details Not Configured (400)**
```json
{
  "success": false,
  "message": "Bank account details not configured"
}
```

**Paystack Error (400)**
```json
{
  "success": false,
  "message": "Account number could not be resolved"
}
```

---

## Withdrawal Endpoints

### 1. Get All Logistics Companies with Wallets

**GET** `/api/v1/admin/logistics-companies`

Returns all logistics companies with their wallet information.

#### Response Example
```json
{
  "success": true,
  "data": [
    {
      "company": {
        "id": 1,
        "name": "FastShip Logistics",
        "code": "FASTSHIP",
        "email": "ops@fastship.com",
        "phone": "+2348012345678",
        "delivery_fee": 2000.00,
        "commission_percentage": 10.00,
        "bank_name": "GTBank",
        "account_number": "0123456789",
        "account_name": "FastShip Logistics Ltd",
        "is_active": true,
        "created_at": "2026-01-10T10:20:21.000000Z",
        "updated_at": "2026-01-10T10:25:30.000000Z",
        "paystack_recipient_code": "RCP_xxxxxxxxxxx"
      },
      "wallet": {
        "id": 5,
        "user_id": null,
        "wallet_type": "logistics_fastship",
        "balance": 45000.00,
        "pending_balance": 12000.00,
        "created_at": "2026-01-10T10:20:21.000000Z",
        "updated_at": "2026-01-10T14:30:00.000000Z"
      }
    }
  ]
}
```

---

### 2. Create Logistics Company Payout

**POST** `/api/v1/admin/logistics-companies/{companyId}/payout`

Creates a withdrawal request and **automatically initiates Paystack transfer** if recipient is configured.

**Behavior:**
- If `paystack_recipient_code` exists: Automatic transfer via Paystack API
- If not configured: Manual process (pending status, admin must transfer manually)

#### Path Parameters
- `companyId` (integer, required): The logistics company ID

#### Request Body
```json
{
  "amount": 25000.00,
  "notes": "Monthly settlement - January 2026"
}
```

#### Fields
- `amount` (decimal, required): Amount to withdraw (must be > 0)
- `notes` (string, optional): Admin notes/reference (max 500 chars)

#### Response Example (Success - With Paystack)
```json
{
  "success": true,
  "message": "Payout initiated successfully",
  "data": {
    "withdrawal": {
      "id": 12,
      "wallet_id": 5,
      "recipient_type": "logistics_company",
      "recipient_id": 1,
      "amount": 25000.00,
      "bank_name": "GTBank",
      "account_number": "0123456789",
      "account_name": "FastShip Logistics Ltd",
      "reference": "WD-LOG-1-AbC123XyZ456",
      "paystack_transfer_code": "TRF_xxxxxxxxxxxxxx",
      "paystack_transfer_id": 123456789,
      "status": "processing",
      "notes": "Monthly settlement - January 2026",
      "metadata": {
        "initiated_by": 3,
        "initiated_by_name": "Admin User",
        "auto_transfer": true,
        "paystack_response": {
          "transfer_code": "TRF_xxxxxxxxxxxxxx",
          "id": 123456789,
          "status": "pending"
        }
      },
      "processed_at": null,
      "created_at": "2026-01-10T15:30:00.000000Z",
      "updated_at": "2026-01-10T15:30:00.000000Z"
    },
    "wallet_balance": 20000.00
  }
}
```

**Note:** Status will be "processing" if Paystack transfer initiated successfully, "pending" if manual transfer needed.

#### Error Responses

**Validation Error (422)**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "amount": ["The amount field is required."]
  }
}
```

**Bank Details Not Configured (400)**
```json
{
  "success": false,
  "message": "Logistics company bank details not configured"
}
```

**Insufficient Balance (400)**
```json
{
  "success": false,
  "message": "Insufficient wallet balance",
  "data": {
    "available_balance": 15000.00,
    "requested_amount": 25000.00
  }
}
```

---

### 3. Get All Withdrawals

**GET** `/api/v1/admin/withdrawals`

Returns all withdrawal records with filtering and pagination.

#### Query Parameters
- `status` (string, optional): Filter by status (pending, processing, completed, failed)
- `recipient_type` (string, optional): Filter by recipient type (logistics_company, vendor)
- `start_date` (date, optional): Filter from date (YYYY-MM-DD)
- `end_date` (date, optional): Filter to date (YYYY-MM-DD)
- `per_page` (integer, optional): Items per page (default: 20)
- `page` (integer, optional): Page number

#### Request Example
```
GET /api/v1/admin/withdrawals?status=pending&per_page=10&page=1
```

#### Response Example
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 12,
        "wallet_id": 5,
        "recipient_type": "logistics_company",
        "recipient_id": 1,
        "amount": 25000.00,
        "bank_name": "GTBank",
        "account_number": "0123456789",
        "account_name": "FastShip Logistics Ltd",
        "reference": "WD-LOG-1-AbC123XyZ456",
        "status": "pending",
        "notes": "Monthly settlement - January 2026",
        "metadata": {
          "initiated_by": 3,
          "initiated_by_name": "Admin User"
        },
        "processed_at": null,
        "created_at": "2026-01-10T15:30:00.000000Z",
        "updated_at": "2026-01-10T15:30:00.000000Z",
        "wallet": {
          "id": 5,
          "wallet_type": "logistics_fastship",
          "balance": 20000.00
        },
        "recipient_details": {
          "id": 1,
          "name": "FastShip Logistics",
          "code": "FASTSHIP",
          "email": "ops@fastship.com"
        }
      }
    ],
    "first_page_url": "http://localhost/api/v1/admin/withdrawals?page=1",
    "from": 1,
    "last_page": 3,
    "last_page_url": "http://localhost/api/v1/admin/withdrawals?page=3",
    "links": [...],
    "next_page_url": "http://localhost/api/v1/admin/withdrawals?page=2",
    "path": "http://localhost/api/v1/admin/withdrawals",
    "per_page": 10,
    "prev_page_url": null,
    "to": 10,
    "total": 25
  }
}
```

---

### 4. Update Withdrawal Status

**PATCH** `/api/v1/admin/withdrawals/{withdrawalId}/status`

Updates the status of a withdrawal. Use this after processing the bank transfer.

#### Path Parameters
- `withdrawalId` (integer, required): The withdrawal ID

#### Request Body
```json
{
  "status": "completed",
  "notes": "Transfer successful - Reference: TXN123456789",
  "metadata": {
    "bank_reference": "TXN123456789",
    "processed_via": "GTBank Mobile App"
  }
}
```

#### Fields
- `status` (string, required): New status - must be one of:
  - `processing`: Transfer is being processed
  - `completed`: Transfer successful
  - `failed`: Transfer failed (wallet will be refunded automatically)
- `notes` (string, optional): Additional notes (max 500 chars)
- `metadata` (object, optional): Additional metadata (e.g., bank references, receipts)

#### Response Example (Success)
```json
{
  "success": true,
  "message": "Withdrawal status updated successfully",
  "data": {
    "id": 12,
    "wallet_id": 5,
    "recipient_type": "logistics_company",
    "recipient_id": 1,
    "amount": 25000.00,
    "bank_name": "GTBank",
    "account_number": "0123456789",
    "account_name": "FastShip Logistics Ltd",
    "reference": "WD-LOG-1-AbC123XyZ456",
    "status": "completed",
    "notes": "Transfer successful - Reference: TXN123456789",
    "metadata": {
      "initiated_by": 3,
      "initiated_by_name": "Admin User",
      "bank_reference": "TXN123456789",
      "processed_via": "GTBank Mobile App"
    },
    "processed_at": "2026-01-10T16:00:00.000000Z",
    "created_at": "2026-01-10T15:30:00.000000Z",
    "updated_at": "2026-01-10T16:00:00.000000Z"
  }
}
```

#### Error Response (Already Processed)
```json
{
  "success": false,
  "message": "Withdrawal already processed"
}
```

---

## Withdrawal Status Flow

### With Paystack Integration (Automated)
```
pending → [Paystack API called] → processing → [webhook received] → completed
                                              └→ failed (auto-refund via webhook)
```

### Without Paystack (Manual)
```
pending → [admin manual transfer] → processing → [admin confirms] → completed
                                                               └→ failed (manual refund)
```

1. **pending**: Withdrawal created, wallet debited
   - Waiting for Paystack transfer OR manual admin action
2. **processing**: Transfer initiated
   - Paystack transfer in progress OR admin manually transferring
3. **completed**: Transfer successful (final state)
   - Set by webhook OR manual admin confirmation
4. **failed**: Transfer failed, wallet refunded automatically (final state)
   - Refund by webhook OR manual admin action

---

## Webhook Integration

Paystack sends webhooks to: `POST /api/v1/webhooks/paystack/transfer`

### Events Handled:
- `transfer.success` → Auto-marks withdrawal as completed
- `transfer.failed` → Auto-refunds wallet and marks as failed
- `transfer.reversed` → Auto-refunds wallet and marks as failed

**Webhook Security:**
- Signature verification using `x-paystack-signature` header
- Only valid Paystack webhooks are processed

**No admin action required** - webhooks automatically update status and handle refunds.

---

## Workflow Example (with Paystack)

### Step 0: One-Time Setup
```bash
# Get list of banks
GET /api/v1/admin/paystack/banks

# Create Paystack recipient (find GTBank code = 058)
POST /api/v1/admin/logistics-companies/1/paystack-recipient
{
  "bank_code": "058"
}
```

Response confirms `paystack_recipient_code` is now stored.

### Step 1: Check Logistics Company Wallet
```bash
GET /api/v1/admin/logistics-companies
```

Response shows FastShip has ₦45,000 available.

### Step 2: Create Withdrawal
```bash
POST /api/v1/admin/logistics-companies/1/payout
Content-Type: application/json

{
  "amount": 25000,
  "notes": "Monthly settlement"
}
```

**What happens automatically:**
1. Wallet debited to ₦20,000
2. Paystack Transfer API called
3. Status set to "processing"
4. Money sent to FastShip's bank account

### Step 3: Paystack Processes Transfer
**No admin action needed.** Paystack sends webhook within seconds/minutes.

### Step 4: Webhook Auto-Updates Status
**Automatic when transfer succeeds:**
- Webhook: `transfer.success`
- Status changed to "completed"
- `processed_at` timestamp set

**Automatic if transfer fails:**
- Webhook: `transfer.failed`
- Wallet refunded to ₦45,000
- Status changed to "failed"

### Manual Override (Optional)
If needed, admin can still manually update status:
```bash
PATCH /api/v1/admin/withdrawals/12/status
Content-Type: application/json

{
  "status": "processing",
  "notes": "Initiating bank transfer"
}
```

### Step 5: Confirm Completion
```bash
PATCH /api/v1/admin/withdrawals/12/status
Content-Type: application/json

{
  "status": "completed",
  "notes": "Transfer successful",
  "metadata": {
    "bank_reference": "GTB-TXN-123456789"
  }
}
```

### If Transfer Fails
```bash
PATCH /api/v1/admin/withdrawals/12/status
Content-Type: application/json

{
  "status": "failed",
  "notes": "Transfer rejected - invalid account number",
  "metadata": {
    "error_code": "INVALID_ACCOUNT"
  }
}
```

The system automatically refunds ₦25,000 back to the wallet.

---

## Database Schema

### logistics_companies Table (Updated)
```sql
- id
- name
- code (unique)
- email
- phone
- delivery_fee (decimal)
- commission_percentage (decimal)
- bank_name (nullable)
- bank_code (nullable) -- NEW: Paystack bank code (e.g., "058" for GTBank)
- account_number (nullable)
- account_name (nullable)
- paystack_recipient_code (nullable) -- NEW: Paystack recipient ID for transfers
- is_active (boolean)
- timestamps
```

### wallet_withdrawals Table (Updated)
```sql
- id
- wallet_id (foreign key)
- recipient_type (logistics_company, vendor)
- recipient_id (polymorphic)
- amount (decimal)
- bank_name
- account_number
- account_name
- reference (unique)
- paystack_transfer_code (nullable) -- NEW: Paystack transfer identifier
- paystack_transfer_id (nullable) -- NEW: Paystack transfer numeric ID
- status (enum: pending, processing, completed, failed)
- notes (text, nullable)
- metadata (json, nullable)
- processed_at (timestamp, nullable)
- timestamps
```

---

## Security Notes

1. **Admin Only**: All endpoints require admin role
2. **Immediate Debit**: Wallets are debited when withdrawal is created (prevents double withdrawals)
3. **Auto-Refund**: Failed withdrawals automatically refund the wallet (via webhook or manual action)
4. **Audit Trail**: All actions tracked with metadata (who initiated, Paystack responses, timestamps)
5. **Idempotent**: Each withdrawal has unique reference to prevent duplicates
6. **Webhook Security**: Paystack webhooks verified using HMAC SHA512 signature
7. **Automatic Processing**: No manual intervention needed when Paystack integration is active

---

## Implementation Status

✅ **Completed:**
- PaystackService with all transfer methods
- Automatic transfer initiation when recipient exists
- Webhook handler for transfer events (success/failed/reversed)
- Auto-refund on failed transfers
- Bank list endpoint
- Recipient creation endpoint
- Full audit trail in metadata

⚠️ **Manual Fallback:**
- If `paystack_recipient_code` is not set, system works in manual mode
- Admin can still update status manually as needed

🔄 **Future Enhancements:**
- OTP finalization for first-time transfers (if Paystack requires)
- Email notifications to logistics companies when payouts complete
