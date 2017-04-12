<?php

namespace App\Http\Controllers\Api;

use Auth;
use Validator;
use App\Topic;
use App\Question;
use Illuminate\Http\Request;

class QuestionsController extends ApiController
{
    public function __construct()
    {
        $this->middleware('jwt.auth', [
            'only' => ['store', 'update', 'destroy']
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $questions = Question::with([
            'user' => function ($query) {
            $query->select('id','name','avatar');
            },
            'topics' => function ($query) {
                $query->select('name');
            },
            ])->get();
        return $this->responseSuccess('查询成功', $questions->toArray());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|min:4|max:196',
            'tags' => 'required',
            'body' => 'required|min:10',
            'article_image' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->responseError(trans('validation.failed'), $validator->errors()->toArray());
        }

        $data = [
            'title' => $request->get('title'),
            'body' => $request->get('body'),
            'user_id' => Auth::id(),
            'close_comment' => $request->get('close_comment'),
            'image_url' => $request->get('article_image')
        ];

        $question = Question::create($data);

        $topics = explode(',', $request->get('tags'));
        foreach ($topics as $topic) {
            $topic = trim($topic);
            if ($topic) {
                $key = Topic::where('name', $topic)->first();
                if ($key) {
                    $question->topics()->save($key);
                } else {
                    $key = $question->topics()->create([
                        'name' => $topic,
                        'questions_count' => 1,
                        'bio' => $topic,
                    ]);
                }
                $topicCount = \DB::table('question_topic')->where('topic_id', $key->id)->count();
                $key->questions_count = $topicCount;
                $key->save();
            }
        }

        return $this->responseSuccess('话题创建成功', $question->toArray());
    }

    public function changeArticleImage(Request $request)
    {
        $file = $request->file('img');
        $filename = md5(time()).'.'.$file->getClientOriginalExtension();
        $file->move(public_path('articleImage'), $filename);

        $article_image = 'http://localhost/laravue-backend/public/articleImage/'.$filename;

        return ['url' => $article_image];
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $question = Question::where('id', $id)->with([
            'user' => function ($query) {
                $query->select('id','name','avatar','created_at');
            },
            'topics' => function ($query) {
                $query->select('name');
            },
        ])->get();
        return $this->responseSuccess('查询成功', $question->toArray());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function hotArticles()
    {
        $articles = Question::with([
            'user' => function ($query) {
                $query->select('id','avatar');
            }])->latest()->get();

        return $this->responseSuccess('查询成功', $articles->toArray());
    }
}