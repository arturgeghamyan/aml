<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class BlogController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('auth:sanctum', except: ['index', 'show'])
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Blog::with('user')->latest()->paginate(request()->paginate ?? 9);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'title' => 'required|max:255',
            'body' => 'required',
            'image' => 'required'
        ]);

        $fields['image'] = $this->uploadImage($request);

        $blog = $request->user()->Blogs()->create($fields);

        return ['blog' => $blog, 'user' => $blog->user];
        // return $blog;
    }

    /**
     * Display the specified resource.
     */
    public function show(Blog $blog)
    {
        return ['blog' => $blog, 'user' => $blog->user];
        // return $blog;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Blog $blog)
    {
        Gate::authorize('modify', $blog);
        $fields = $request->validate([
            'title' => 'required|max:255',
            'body' => 'required',
            'image' => 'required'
        ]);

        $fields['image'] = $this->uploadImage($request);

        $blog->update($fields);

        return ['blog' => $blog, 'user' => $blog->user];
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Blog $blog)
    {
        Gate::authorize('modify', $blog);

        $blog->delete();

        return ['message' => 'The blog was deleted'];
    }

    private function uploadImage($request)
    {
        $data = $request->input('image');
        $base64Image = preg_replace('#^data:image/\w+;base64,#i', '', $data);
        $imageData = base64_decode($base64Image);
        $fileName = 'image_' . time() . '.png';
        Storage::put('public/images/' . $fileName, $imageData);

        return Storage::url('images/' . $fileName);
    }
}
