<?php

declare(strict_types=1);

namespace App\Services\OfficeGuy;

use App\Models\EventBilling;
use App\Services\Sumit\EventBillingPayable;
use Illuminate\Support\Facades\Log;
use OfficeGuy\LaravelSumitGateway\Models\OfficeGuyDocument;
use OfficeGuy\LaravelSumitGateway\Services\DocumentService as VendorDocumentService;
use OfficeGuy\LaravelSumitGateway\Services\PaymentService;

/**
 * Host-level Document Service for SUMIT.
 * Wraps vendor service and enforces host-specific rules for PDF tools.
 */
class DocumentService
{
    /**
     * Get the PDF download URL for a document.
     */
    public function getPdfUrl(int $documentId): ?string
    {
        $result = VendorDocumentService::getDocumentPDF($documentId);

        if (! ($result['success'] ?? false)) {
            Log::warning('Failed to fetch PDF URL from SUMIT', [
                'document_id' => $documentId,
                'error' => $result['error'] ?? 'Unknown',
            ]);

            return null;
        }

        return $result['pdf_url'] ?? null;
    }

    /**
     * Create an order document for an event billing.
     */
    public function createInvoice(EventBilling $eventBilling): ?OfficeGuyDocument
    {
        $payable = new EventBillingPayable($eventBilling);
        $customer = PaymentService::getOrderCustomer($payable);

        $error = VendorDocumentService::createOrderDocument($payable, $customer);

        if ($error) {
            Log::error('Invoice creation failed', [
                'event_billing_id' => $eventBilling->id,
                'error' => $error,
            ]);

            return null;
        }

        return OfficeGuyDocument::where('order_id', $eventBilling->id)
            ->where('order_type', EventBilling::class)
            ->latest()
            ->first();
    }

    /**
     * Send a document by email directly via SUMIT.
     */
    public function sendByEmail(OfficeGuyDocument $document, ?string $email = null): bool
    {
        $result = VendorDocumentService::sendByEmail($document, $email);

        if (! ($result['success'] ?? false)) {
            Log::warning('Failed to send document by email via SUMIT', [
                'document_id' => $document->document_id,
                'error' => $result['error'] ?? 'Unknown',
            ]);

            return false;
        }

        return true;
    }
}
