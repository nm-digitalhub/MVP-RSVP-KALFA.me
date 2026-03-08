
Feature: Public Customer ID for Account

Context

Based on system analysis and runtime verification via Tinker, the domain model structure is:

User
  ↓
Organization
  ↓
Account

The Account model represents the financial customer entity in the system.

Currently the system uses:

Field	Purpose
id	Internal database primary key
sumit_customer_id	External identifier used by the SUMIT payment provider

This implementation introduces a third identifier:

public_id

This will serve as the public-facing customer identifier.

⸻

Objective

Implement a human-readable, non-sequential, non-guessable public customer identifier for the Account model.

This identifier must:
	•	Not replace the database primary key.
	•	Not replace sumit_customer_id.
	•	Be safe for public exposure (URLs, API, documents).
	•	Be automatically generated when creating an Account.

⸻

Target Architecture

After implementation the Account model will contain:

Account
 ├─ id (internal PK)
 ├─ public_id (public identifier)
 └─ sumit_customer_id (external provider ID)

Identifier responsibilities:

Field	Responsibility
id	Internal relational integrity
public_id	Public customer reference
sumit_customer_id	Payment provider mapping


⸻

Public ID Format

The identifier format must follow this structure:

PREFIX + RANDOM + CHECKSUM

Example:

ACC-7K4D-92F8
ACC-K4DJ-29FA7
ACC-A9DK-4F2L3

Components:

Component	Purpose
PREFIX	Entity identifier (ACC)
RANDOM	Random alphanumeric sequence
CHECKSUM	Digit used to detect typing errors


⸻

Benefits

This approach is commonly used by large SaaS platforms such as
Stripe and Shopify.

Advantages:
	•	Non-guessable identifiers
	•	Human readable
	•	Typo detection via checksum
	•	Safe for public APIs
	•	Does not expose database primary keys

⸻

Implementation Steps

1. Database Migration

Add a new column to the accounts table.

Schema::table('accounts', function (Blueprint $table) {
    $table->string('public_id', 20)
        ->unique()
        ->nullable()
        ->after('id');
});

After full deployment and backfilling, the column may be made non-nullable.

⸻

2. Identifier Generator

Create a helper or service responsible for generating customer IDs.

Example implementation:

use Illuminate\Support\Str;

function generateCustomerCode(): string
{
    $random = strtoupper(Str::random(8));

    $sum = 0;

    foreach (str_split($random) as $char) {
        $sum += ord($char);
    }

    $checksum = $sum % 10;

    return "ACC-" . substr($random, 0, 4) . "-" . substr($random, 4, 4) . $checksum;
}

Requirements:
	•	Must generate uppercase characters
	•	Must produce consistent formatting
	•	Must be unique across accounts

⸻

3. Attach Generator to Account Model

Modify the Account model boot logic.

protected static function booted()
{
    static::creating(function ($account) {

        if (!$account->public_id) {
            $account->public_id = generateCustomerCode();
        }

    });
}

This ensures:
	•	Every new account automatically receives a public_id
	•	Manual override remains possible

⸻

4. System Usage

Use public_id for all public references.

Examples:

API

/api/accounts/ACC-K4DJ-29FA7

Documents

Customer ID: ACC-K4DJ-29FA7

Links

/billing/account/ACC-K4DJ-29FA7

Never expose the internal id in public interfaces.

⸻

5. Domain Relationships (unchanged)

No domain changes are required.

The current relationships remain:

User
   ↓
Organization
   ↓ account_id
Account

This implementation does not modify existing relations.

⸻

6. SUMIT Integration Compatibility

The SUMIT integration continues using:

Account.sumit_customer_id

Mapping becomes:

public_id → internal system reference
sumit_customer_id → SUMIT reference

This keeps internal, public, and external identifiers fully separated.

⸻

7. Additional Recommended Uses

The public identifier may also be used for:
	•	Customer numbers in invoices
	•	Order identifiers
	•	Checkout references
	•	External support references

⸻

Final Expected Model Structure

User
  ↓
Organization
  ↓
Account
   ├─ id
   ├─ public_id
   └─ sumit_customer_id

Three identifiers with clear responsibilities:

Identifier	Scope
id	Internal database key
public_id	Public system identifier
sumit_customer_id	External payment provider identifier


⸻

Constraints

The implementation must not:
	•	Replace existing primary keys
	•	Break existing relationships
	•	Modify SUMIT integration behavior
	•	Introduce sequential public identifiers

⸻

End of Instruction