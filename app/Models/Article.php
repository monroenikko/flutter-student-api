<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    const ARTICLE_TYPE = [
        1 => 'News',
        2 => 'Event',
        3 => 'Announcement'
    ];

    const ARTICLE_TYPE_DESIGN = [
        1 => 'bg-red color-palette',
        2 => 'bg-green-active color-palette'
    ];

    const ARTICLE_STATUS = [
        0 => 'Deleted',
        1 => 'Published',
        2 => 'Draft',
        3 => 'Archived'
    ];
    const ARTICLE_STATUS_DESIGN = [
        0 => 'badge-danger',
        1 => 'badge-success',
        2 => 'badge-warning',
        3 => 'badge-primary'
    ];

    const LEVEL = [
        1 => 'Junior High',
        2 => 'Senior Hig',
    ];

    const LEVEL_DESIGN = [
        1 => 'badge-primary',
        2 => 'badge-success',
    ];

    //const ARTICLE_TABLE_HEAD = ['Posting Date','Title','Article Status','Level'];
    const ARTICLE_TABLE_HEAD_SORT = [
        ['label'=>'Posting Date', 'column_name' => 'posting_date'],
        ['label'=>'Title', 'column_name' => 'title'],
        ['label'=>'Article Status', 'column_name' => 'status'],
        ['label'=>'Level', 'column_name' => 'level']
    ];

    protected $table="articles";

    protected $fillable = [
        'article_type',
        'featured_image',
        'posting_date',
        'event_date_start',
        'event_date_end',
        'school_year',
        'title',
        'content',
        'level',
        'link_to_article',
        'user_id',
        'slug',
        'status'
    ];

    public function scopeFilter($query, $params)
    {
        return  $query->with('articleLevels')
                ->when(isset($params['grade_level']), function($item) use ($params){
                    $item->whereHas('articleLevels', function($q) use ($params)
                    {
                        if((int)$params['grade_level'] > 10){
                            $q->where('student_level', 2);
                        }

                        if((int)$params['grade_level'] < 11){
                            $q->where('student_level', 1);
                        }
                    });
                })->where('status', 1)
                // ->when(isset($params), function($q) use ($params) {
                //     $q->where('title', 'like', '%'.$params.'%')
                //         ->orWhere('content', 'like', '%'.$params.'%')
                //         ->orWhere('status', 'like', '%'.$params.'%');
                // })
                // ->whereHas('articleLevels', function($q) {
                //     $q->where('student_level', 2);
                // })
                ->orderBy('id','desc');
    }

    public function articleLevels()
    {
        return $this->hasOne(ArticleStudentLevel::class);
    }
}
