<?php
namespace App\Services;

use Exception;
use App\Models\Article;
use App\Traits\ResponseApi;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\RfidListsResource;
use App\Http\Resources\ArticleListsResource;

class ArticleService
{
    use ResponseApi;

    protected $model;
    public function __construct(Article $model)
    {
        $this->model = $model;
    }

    public function index($request)
    {
        $data = new ArticleListsResource($this->model->where('status', '1')
            ->paginate(isset($request->limit) ? $request->limit : 10));

        return $this->success('Data successfully listed.', Response::HTTP_OK, $data);
    }

    public function show($data, int $id)
    {
        try {
            $data = $this->model->find($id);
            if(!$data)
            {
                return $this->error('Article not Found', Response::HTTP_BAD_REQUEST);
            }
            return $this->success(
                'Data Successfully Fetched.',
                Response::HTTP_OK,
                $data->comments()->with('user:id,name,image')->get()
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

}