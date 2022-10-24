<?php
namespace App\Services;

use Exception;
use App\Models\Article;
use App\Traits\{SchoolYear, ResponseApi};
use Illuminate\Http\Response;
use App\Http\Resources\{ArticleResource, ArticleListsResource};

class ArticleService
{
    use ResponseApi, SchoolYear;

    protected $model, $class_record;

    public function __construct(Article $model, ClassRecordService $class_record)
    {
        $this->model = $model;
        $this->class_record = $class_record;
    }

    public function index($request)
    {
        $school_year_id = $this->activeSchoolYear()->id;
        $class_detail =  $this->class_record->hasClassDetail($school_year_id, $sem = null);

        $request['grade_level'] = $class_detail->classDetail->grade_level;

        $data = $this->model
            ->filter($request)
            ->paginate(isset($request->limit) ? $request->limit : 10);

        $data = new ArticleListsResource($data);

        return $this->success('Data successfully listed.', Response::HTTP_OK, $data);
    }

    public function show(int $id)
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
                new ArticleResource($data)
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

}