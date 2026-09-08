<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateEnquiryRequest;
use App\Http\Resources\EnquiryResource;
use App\Models\ActivityLog;
use App\Models\Enquiry;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EnquiryController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);

        $enquiries = Enquiry::query()
            ->with(['property', 'assignee'])
            ->filter($request->only(['status', 'property_id', 'search']))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return $this->success(
            EnquiryResource::collection($enquiries->items()),
            'Enquiries retrieved successfully.',
            200,
            [
                'current_page' => $enquiries->currentPage(),
                'per_page' => $enquiries->perPage(),
                'total' => $enquiries->total(),
                'last_page' => $enquiries->lastPage(),
            ]
        );
    }

    public function show(Enquiry $enquiry)
    {
        $enquiry->load(['property', 'assignee', 'user']);

        return $this->success(new EnquiryResource($enquiry), 'Enquiry retrieved successfully.');
    }

    public function update(UpdateEnquiryRequest $request, Enquiry $enquiry)
    {
        $enquiry->update($request->validated());

        ActivityLog::record('enquiry.updated', 'Enquiry', $enquiry->id, $request->validated());

        return $this->success(new EnquiryResource($enquiry->fresh(['property', 'assignee'])), 'Enquiry updated successfully.');
    }

    public function destroy(Enquiry $enquiry)
    {
        $enquiry->delete();

        ActivityLog::record('enquiry.deleted', 'Enquiry', $enquiry->id);

        return $this->success(null, 'Enquiry deleted successfully.');
    }

    public function export(Request $request): StreamedResponse
    {
        $enquiries = Enquiry::query()
            ->with('property')
            ->filter($request->only(['status', 'property_id', 'search']))
            ->orderByDesc('created_at')
            ->get();

        $filename = 'enquiries-'.now()->format('Y-m-d-His').'.csv';

        $callback = function () use ($enquiries) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Name', 'Email', 'Phone', 'Property', 'Subject', 'Status', 'Created At']);

            foreach ($enquiries as $enquiry) {
                fputcsv($handle, [
                    $enquiry->id,
                    self::csvSafe($enquiry->name),
                    self::csvSafe($enquiry->email),
                    self::csvSafe($enquiry->phone),
                    self::csvSafe($enquiry->property?->title),
                    self::csvSafe($enquiry->subject),
                    $enquiry->status->value,
                    $enquiry->created_at,
                ]);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Neutralize CSV/spreadsheet formula injection by prefixing a leading
     * =, +, -, or @ with a single quote, per OWASP guidance.
     */
    private static function csvSafe(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
    }
}
