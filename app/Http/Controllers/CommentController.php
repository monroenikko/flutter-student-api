<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Post;
use App\Models\Comment;
use App\Traits\ResponseApi;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\ResponseTrait;

class CommentController extends Controller
{
    use ResponseApi;
    
    public function index($id)
    {
        try {
            $post = Post::find($id);
            if(!$post)
            {
                return $this->error('Post not Found', Response::HTTP_BAD_REQUEST);
            }
            return $this->success(
                'Data Successfully Fetched.',
                Response::HTTP_OK, $post->comments()->with('user:id,name,image')->get()
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function store(Request $request, $id)
    {
        try {
            $post = Post::find($id);

            if(!$post)
            {
                return $this->error('Post not Found', Response::HTTP_BAD_REQUEST);
            }

            $data = $request->validate([
                'comment' => 'required|string'
            ]);

            $res = Comment::create([
                'comment' => $data['comment'],
                'post_id' => $id,
                'user_id' => auth()->user()->id
            ]);

            return $this->success(
                'Data Successfully Store.',
                Response::HTTP_OK, $res
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function update(Request $request, $id)
    {
        try {

            $comment = Comment::find($id);

            if(!$comment)
            {
                return $this->error('Comment not Found', Response::HTTP_BAD_REQUEST);
            }

            if($comment->user_id != auth()->user()->id)
            {
                return $this->error('Permission Denied', Response::HTTP_BAD_REQUEST);
            }

            $data = $request->validate([
                'comment' => 'required|string'
            ]);

            $res = $comment->update([
                'comment' => $data['comment']
            ]);

            return $this->success(
                'Data Successfully Updated.',
                Response::HTTP_OK, $res
            );

        } catch (Exception $e) {
            return $this->error($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function destroy($id)
    {
        try {

            $comment = Comment::find($id);

            if(!$comment)
            {
                return $this->error('Comment not Found', Response::HTTP_BAD_REQUEST);
            }

            if($comment->user_id != auth()->user()->id)
            {
                return $this->error('Permission Denied', Response::HTTP_BAD_REQUEST);
            }

            $comment->delete();

            return $this->success(
                'Data Successfully Deleted.',
                Response::HTTP_OK, []
            );

        } catch (Exception $e) {
            return $this->error($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
