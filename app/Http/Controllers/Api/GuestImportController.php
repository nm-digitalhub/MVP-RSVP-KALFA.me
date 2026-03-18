<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

class GuestImportController extends Controller
{
    /**
     * Import guests from a CSV file.
     *
     * The CSV must have a header row. Supported columns: `name`, `email`, `phone`, `notes`.
     * Hebrew column names are also supported: `שם` for name.
     * Rows with no name and no email are silently skipped.
     *
     * Returns count of imported and skipped rows.
     */
    public function __invoke(Request $request, Organization $organization, Event $event): JsonResponse
    {
        $user = $request->user();

        abort_if($event->organization_id !== $organization->id, 403);
        abort_unless(
            $user !== null && $user->organizations()->where('organizations.id', $organization->id)->exists(),
            403
        );

        $file = $request->file('file');

        if (! $file instanceof UploadedFile) {
            return response()->json(['error' => 'No file uploaded'], 400);
        }

        $uploadErrorMessage = $this->uploadErrorMessage($file);

        if ($uploadErrorMessage !== null) {
            return response()->json(['error' => 'File upload failed: '.$uploadErrorMessage], 400);
        }

        $validator = Validator::make($request->all(), [
            'file' => ['mimes:csv,txt'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first('file')], 400);
        }

        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return response()->json(['error' => 'Could not open file'], 400);
        }

        $guests = [];
        $importedCount = 0;
        $skippedCount = 0;
        $errors = [];

        $headers = fgetcsv($handle, 0);

        if ($headers === false) {
            fclose($handle);

            return response()->json(['error' => 'Could not read CSV headers'], 400);
        }

        while (($data = fgetcsv($handle, 0)) !== false) {
            $normalizedData = array_map(
                static fn ($value): string => trim((string) ($value ?? '')),
                $data
            );

            if ($normalizedData === [] || count(array_filter($normalizedData, static fn (string $value): bool => $value !== '')) === 0) {
                $skippedCount++;

                continue;
            }

            if (count($data) !== count($headers)) {
                $errors[] = 'Row '.($importedCount + 1).' has incorrect column count: '.count($data).' (expected '.count($headers).')';
                fclose($handle);

                return response()->json(['error' => 'Invalid CSV format', 'details' => $errors], 400);
            }

            $guestData = array_combine($headers, $data);

            if ($guestData === false) {
                fclose($handle);

                return response()->json(['error' => 'Invalid CSV format'], 400);
            }

            $name = trim((string) ($guestData['name'] ?? $guestData['שם'] ?? ''));
            $email = trim((string) ($guestData['email'] ?? ''));
            $phone = trim((string) ($guestData['phone'] ?? ''));
            $notes = trim((string) ($guestData['notes'] ?? ''));

            if ($name === '' && $email === '') {
                $skippedCount++;

                continue;
            }

            $guest = $event->guests()->create([
                'name' => $name !== '' ? $name : $email,
                'email' => $email !== '' ? $email : null,
                'phone' => $phone !== '' ? $phone : null,
                'notes' => $notes !== '' ? $notes : null,
                'sort_order' => $importedCount,
            ]);

            $guests[] = $guest;
            $importedCount++;
        }

        fclose($handle);

        return response()->json([
            'message' => 'Import completed',
            'imported' => $importedCount,
            'skipped' => $skippedCount,
            'guests' => $guests,
        ], 201);
    }

    private function uploadErrorMessage(UploadedFile $file): ?string
    {
        $dynamicError = get_object_vars($file)['error'] ?? null;

        if (is_string($dynamicError) && $dynamicError !== '') {
            return $dynamicError;
        }

        if ($file->isValid()) {
            return null;
        }

        $error = $file->getError();

        if (is_string($error) && $error !== '') {
            return $error;
        }

        return $file->getErrorMessage();
    }
}
