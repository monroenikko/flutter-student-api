<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Post;
use App\Traits\ResponseApi;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class PostController extends Controller
{
    use ResponseApi;

    public function index()
    {
        try {
            $res = Post::orderBy('created_at', 'desc')->with('user:id,name,image')->withCount('comments', 'likes')
                ->with('likes', function($like){
                    return $like->where('user_id', auth()->user()->id)->select('id', 'user_id', 'post_id')->get();
                })
                ->get();
            return $this->success(
                'Data Successfully fetched.',
                Response::HTTP_OK,
                $res
            );
        } catch (Exception $e) {
            dd($e);
            return $this->error($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function show($id)
    {
        $res = Post::whereId($id)->withCount('comments', 'likes')->get();
        return $this->success(
            'Data Successfully fetched.',
            Response::HTTP_OK, $res
        ); 
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'body' => 'required|string'
        ]);

        $image = $this->saveImage($request->image, 'posts');

        $res = Post::create([
            'body' => $data['body'],
            'user_id' => auth()->user()->id,
            'image' => $image
        ]);

        return $this->success(
            'Data Successfully Stored.',
            Response::HTTP_OK, $res
        ); 
    }

    public function update(Request $request, $id)
    {
        try {

            $post = Post::findOrFail($id);

            $attrs = $request->validate([
                'body' => 'required|string'
            ]);
    
            $post->update([
                'body' => $attrs['body']
            ]);
    
            return $this->success(
                'Data Successfully Updated.',
                Response::HTTP_OK, $post
            ); 
        } catch (Exception $e) {
            return $this->error($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {

            $post = Post::findOrFail($id);

            if($post->user_id != auth()->user()->id)
            {
                return $this->error('Permission Denied', Response::HTTP_BAD_REQUEST);
            }
    
            $post->comments()->delete();
            $post->likes()->delete();
            $post->delete();
    
            DB::commit();
            return $this->success(
                'Data Successfully Deleted.',
                Response::HTTP_OK, []
            ); 

        } catch (Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
