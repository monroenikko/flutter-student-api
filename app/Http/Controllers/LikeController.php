<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Post;
use App\Traits\ResponseApi;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\ResponseTrait;

class LikeController extends Controller
{
    use ResponseApi;
    
    public function likeOrUnlike($id)
    {
        $post = Post::find($id);

        if(!$post)
        {
            return $this->error('Post not Found', Response::HTTP_BAD_REQUEST);
        }

        $like = $post->likes()->firstWhere('user_id', auth()->user()->id);

        if(!$like)
        {
            Like::create([
                'post_id' => $id,
                'user_id' => auth()->user()->id
            ]);
            return $this->success('Liked.', Response::HTTP_OK, []);
        }

        $like->delete();
        return $this->success('Disliked.', Response::HTTP_OK, []);
    }
}
