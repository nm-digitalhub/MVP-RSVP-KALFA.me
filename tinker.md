[
    {
        "key": "customer_model_class",
        "class": "App\\Models\\Organization",
        "class_exists": true,
        "is_eloquent_model": true,
        "implements_has_sumit_customer": false,
        "implements_payable": false,
        "missing_customer_methods": [
            "getSumitCustomerId",
            "getSumitCustomerEmail",
            "getSumitCustomerName",
            "getSumitCustomerPhone",
            "getSumitCustomerBusinessId"
        ],
        "missing_payable_methods": [
            "getPayableId",
            "getPayableAmount",
            "getPayableCurrency",
            "getCustomerId",
            "getLineItems",
            "getPayableType"
        ],
        "table": "organizations",
        "has_sumit_customer_id_column": false
    },
    {
        "key": "models.customer",
        "class": "App\\Models\\Organization",
        "class_exists": true,
        "is_eloquent_model": true,
        "implements_has_sumit_customer": false,
        "implements_payable": false,
        "missing_customer_methods": [
            "getSumitCustomerId",
            "getSumitCustomerEmail",
            "getSumitCustomerName",
            "getSumitCustomerPhone",
            "getSumitCustomerBusinessId"
        ],
        "missing_payable_methods": [
            "getPayableId",
            "getPayableAmount",
            "getPayableCurrency",
            "getCustomerId",
            "getLineItems",
            "getPayableType"
        ],
        "table": "organizations",
        "has_sumit_customer_id_column": false
    },
    {
        "key": "models.order",
        "class": "App\\Models\\EventBilling",
        "class_exists": true,
        "is_eloquent_model": true,
        "implements_has_sumit_customer": false,
        "implements_payable": false,
        "missing_customer_methods": [
            "getSumitCustomerId",
            "getSumitCustomerEmail",
            "getSumitCustomerName",
            "getSumitCustomerPhone",
            "getSumitCustomerBusinessId"
        ],
        "missing_payable_methods": [
            "getPayableId",
            "getPayableAmount",
            "getPayableCurrency",
            "getCustomerId",
            "getLineItems",
            "getPayableType"
        ],
        "table": "events_billing",
        "has_sumit_customer_id_column": false
    },
    {
        "key": "order.model",
        "class": "App\\Models\\EventBilling",
        "class_exists": true,
        "is_eloquent_model": true,
        "implements_has_sumit_customer": false,
        "implements_payable": false,
        "missing_customer_methods": [
            "getSumitCustomerId",
            "getSumitCustomerEmail",
            "getSumitCustomerName",
            "getSumitCustomerPhone",
            "getSumitCustomerBusinessId"
        ],
        "missing_payable_methods": [
            "getPayableId",
            "getPayableAmount",
            "getPayableCurrency",
            "getCustomerId",
            "getLineItems",
            "getPayableType"
        ],
        "table": "events_billing",
        "has_sumit_customer_id_column": false
    }
]

---

## Runtime config check (officeguy)

```json
{
    "runtime.customer_model_class": "App\\Models\\Organization",
    "runtime.models.customer": "App\\Models\\Organization",
    "runtime.models.order": "App\\Models\\EventBilling",
    "runtime.order.model": "App\\Models\\EventBilling",
    "runtime.staff_model": null,
    "consistent.customer": true,
    "consistent.order": true
}
```
