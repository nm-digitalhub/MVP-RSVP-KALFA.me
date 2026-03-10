<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Guest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuestImportController extends Controller
{
    public function __invoke(Request $request, Event $event): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:text/csv,text/plain'],
        ]);

        $file = $request->file('file');

        if ($file === null) {
            return response()->json(['error' => 'No file uploaded'], 400);
        }

        if ($file->getError() !== null) {
            return response()->json(['error' => 'File upload failed: '.$file->getError()->getMessage()], 400);
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

        $headerMap = array_flip($headers);

        while (($data = fgetcsv($handle, 0)) !== false) {
            if (count($data) !== count($headers)) {
                $errors[] = 'Row '.($importedCount + 1).' has incorrect column count: '.count($data).' (expected '.count($headers).')';
                fclose($handle);

                return response()->json(['error' => 'Invalid CSV format', 'details' => $errors], 400);
            }

            $guestData = array_combine($headerMap, $data);

            $name = $guestData['name'] ?? trim($guestData['שם'] ?? '');
            $email = $guestData['email'] ?? trim($guestData['email'] ?? '');
            $phone = $guestData['phone'] ?? trim($guestData['phone'] ?? '');
            $notes = $guestData['notes'] ?? trim($guestData['notes'] ?? '');

            if (empty($name) && empty($email)) {
                $skippedCount++;

                continue;
            }

            $guest = Guest::create([
                'event_id' => $event->id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'notes' => $notes,
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
}
