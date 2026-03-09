# Rules for Using PDF Tools in Kalfa

Following the integration with SUMIT (OfficeGuy), the following rules apply when generating or managing PDF documents in this system.

## 1. Document-Centric Approach
PDFs are not generated directly. They are derived from **Accounting Documents** (Invoices, Receipts, Orders). 
Always use `App\Services\OfficeGuy\DocumentService` to interact with document-related PDF features.

## 2. Saloon-Based Communication
All communication with external PDF generators (SUMIT) MUST use **Saloon** requests.
- Use `OfficeGuy\LaravelSumitGateway\Http\Requests\Document\GetDocumentPdfRequest` to fetch PDF URLs.
- Do not use raw Guzzle or CURL.

## 3. Polymorphic Linking
Every PDF/Document MUST be linked to a host model via a polymorphic relationship (`order_id`, `order_type`).
- Example: `EventBilling` is the typical order type for event payments.

## 4. PDF Delivery
Prefer sending documents directly via the generator's email service if available.
- Use `DocumentService::sendByEmail()` to trigger SUMIT's email delivery.
- This ensures the PDF is the "Original" version and complies with accounting regulations.

## 5. Security & Persistence
- Do not store sensitive PDF data in local storage if it's already stored in the accounting gateway.
- SUMIT stores documents for 7 years. Use the `DocumentID` to retrieve the PDF URL on-demand.
- Fetching a PDF URL is fast and can be done during the Mail construction or UI rendering.

## 7. Local PDF Generation (DomPDF)
When generating documents that are NOT accounting-related (e.g., event schedules, internal reports, guest labels), use the locally installed `barryvdh/laravel-dompdf`.
- **Usage**: `PDF::loadView('pdf.template', $data)->download('file.pdf');`
- **Fonts**: Use UTF-8 compatible fonts for Hebrew support (e.g., DejaVu Sans).
- **Templates**: Store local PDF templates in `resources/views/pdf/`.

## 8. Selection Logic
- **Invoice/Receipt/Payment Confirmation**: ALWAYS use SUMIT (Rule #1).
- **Internal Reports/Manual Guest Lists/Non-Billing docs**: Use DomPDF (Rule #7).

---
*Based on DocumentService Analysis 2025-01-13*
