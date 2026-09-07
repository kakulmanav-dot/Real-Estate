<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Testimonial\StoreTestimonialRequest;
use App\Http\Requests\Testimonial\UpdateTestimonialRequest;
use App\Http\Resources\TestimonialResource;
use App\Models\ActivityLog;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TestimonialController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $testimonials = Testimonial::query()
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return $this->success(
            TestimonialResource::collection($testimonials->items()),
            'Testimonials retrieved successfully.',
            200,
            [
                'current_page' => $testimonials->currentPage(),
                'per_page' => $testimonials->perPage(),
                'total' => $testimonials->total(),
                'last_page' => $testimonials->lastPage(),
            ]
        );
    }

    public function store(StoreTestimonialRequest $request)
    {
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->storeImage($request);
        }

        $testimonial = Testimonial::create($data);

        ActivityLog::record('testimonial.created', 'Testimonial', $testimonial->id);

        return $this->success(new TestimonialResource($testimonial), 'Testimonial created successfully.', 201);
    }

    public function update(UpdateTestimonialRequest $request, Testimonial $testimonial)
    {
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            if ($testimonial->image_path) {
                Storage::disk('public')->delete($testimonial->image_path);
            }
            $data['image_path'] = $this->storeImage($request);
        }

        $testimonial->update($data);

        ActivityLog::record('testimonial.updated', 'Testimonial', $testimonial->id);

        return $this->success(new TestimonialResource($testimonial), 'Testimonial updated successfully.');
    }

    public function destroy(Testimonial $testimonial)
    {
        if ($testimonial->image_path) {
            Storage::disk('public')->delete($testimonial->image_path);
        }

        $testimonial->delete();

        ActivityLog::record('testimonial.deleted', 'Testimonial', $testimonial->id);

        return $this->success(null, 'Testimonial deleted successfully.');
    }

    public function toggleApproval(Testimonial $testimonial)
    {
        $testimonial->update(['is_approved' => ! $testimonial->is_approved]);

        ActivityLog::record('testimonial.approval_toggled', 'Testimonial', $testimonial->id, ['is_approved' => $testimonial->is_approved]);

        return $this->success(new TestimonialResource($testimonial), 'Testimonial approval status updated.');
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['required', 'integer', 'distinct'],
        ]);

        foreach ($request->input('order') as $index => $id) {
            Testimonial::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        return $this->success(null, 'Testimonials reordered successfully.');
    }

    private function storeImage(Request $request): string
    {
        $filename = Str::uuid().'.'.$request->file('image')->getClientOriginalExtension();

        return $request->file('image')->storeAs('testimonials', $filename, 'public');
    }
}
