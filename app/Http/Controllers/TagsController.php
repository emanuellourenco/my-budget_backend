<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;

class TagsController extends Controller
{
    public function getList($request, $fields)
    {
        $user = new User();
        $user_by_token = $user->getUserByToken($request->token);
        $tags = [];
        $total_count = 0;

        if ($user_by_token) {
            $offset = (bool) $request->offset ? $request->offset : 0;
            $limit = 100;
            if ((bool) $request->limit && $request->limit < 100) {
                $limit = $request->limit;
            }
            $order_by = (bool) $request->orderBy ? $request->orderBy : 'name';
            $sort_by = (bool) $request->sortBy ? $request->sortBy : 'asc';

            $tags = Tag::where('user_id', $user_by_token->id)
                ->select($fields)
                ->orderBy($order_by, $sort_by)
                ->skip($offset)
                ->take($limit)
                ->get();

            $total_count = Tag::where('user_id', $user_by_token->id)->count();
        }

        return ['tags' => $tags, 'total_count' => $total_count];
    }

    /**
     * Function to list tags and return an array with tags list and tags count.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $fields = ['name', 'color', 'id as key', 'rule'];
        $list = $this->getList($request, $fields);
        $tags = $list['tags'];
        $total_count = $list['total_count'];

        return ['tags' => $tags, 'total_count' => $total_count];
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function options(Request $request)
    {
        $fields = ['name as label', 'id as value'];
        $list = $this->getList($request, $fields);
        $tags = $list['tags'];
        $total_count = $list['total_count'];

        return ['tags' => $tags, 'total_count' => $total_count];
    }

    public function checkFields($request, $required)
    {
        $error = false;
        $data_error = [];

        foreach ($required as &$field) {
            $validateField = $request->input($field);

            if (!$validateField) {
                $error = true;

                array_push($data_error, [
                    'field' => $field,
                    'label' => 'This field is required',
                ]);
            }
        }

        return ['error' => $error, 'data_error' => $data_error];
    }

    /**
     * Store a newly created tag.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $requiredFields = ['name', 'color', 'rule'];
            $validateFields = $this->checkFields($request, $requiredFields);
            $data_error = $validateFields['data_error'];
            $error = $validateFields['error'];
            $tags = [];
            $total_count = 0;

            if (!$error) {
                $user = new User();
                $user_by_token = $user->getUserByToken($request->token);

                if ($user_by_token) {
                    $new_tag = new Tag();
                    $new_tag->name = $request->name;
                    $new_tag->color = $request->color;
                    $new_tag->rule = $request->rule;
                    $new_tag->user_id = $user_by_token->id;
                    $new_tag->save();

                    // Get new tags list based on offset and table limit
                    $fields = ['name', 'color', 'id as key', 'rule'];
                    $list = $this->getList($request, $fields);
                    $tags = $list['tags'];
                    $total_count = $list['total_count'];
                }
            }

            return ['tags' => $tags, 'total_count' => $total_count, 'error' => $data_error];
        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * Get the specified tag to update.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request)
    {
        try {
            $user = new User();
            $user_by_token = $user->getUserByToken($request->token);
            $tag = [];

            if ($user_by_token) {
                $tag = Tag::where('user_id', $user_by_token->id)
                    ->where('id', $id)
                    ->first();
            }

            return ['tag' => $tag];
        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
    }

    /**
     * Update the specified tag.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $requiredFields = ['name', 'color', 'rule'];
            $validateFields = $this->checkFields($request, $requiredFields);
            $data_error = $validateFields['data_error'];
            $error = $validateFields['error'];
            $tags = [];
            $total_count = 0;

            if (!$error) {
                $user = new User();
                $user_by_token = $user->getUserByToken($request->token);

                if ($user_by_token) {
                    $update_tag = Tag::where('user_id', $user_by_token->id)
                        ->where('id', $id)
                        ->first();

                    if ($update_tag) {
                        $update_tag->name = $request->name;
                        $update_tag->color = $request->color;
                        $update_tag->rule = $request->rule;
                        $update_tag->save();

                        // Get new tags list based on offset and table limit
                        $fields = ['name', 'color', 'id as key', 'rule'];
                        $list = $this->getList($request, $fields);
                        $tags = $list['tags'];
                        $total_count = $list['total_count'];
                    }
                }
            }

            return ['error' => $data_error, 'tags' => $tags, 'total_count' => $total_count];
        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * Remove the specified tag.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, Request $request)
    {
        try {
            $user = new User();
            $user_by_token = $user->getUserByToken($request->token);
            $tags = [];
            $total_count = 0;

            if ($user_by_token) {
                $delete_tag = Tag::where('user_id', $user_by_token->id)
                    ->where('id', $id)
                    ->first();

                if ($delete_tag) {
                    $delete_tag->delete();

                    // Get new tags list based on offset and table limit
                    $fields = ['name', 'color', 'id as key', 'rule'];
                    $list = $this->getList($request, $fields);
                    $tags = $list['tags'];
                    $total_count = $list['total_count'];
                }
            }

            return ['tags' => $tags, 'total_count' => $total_count];
        } catch (Exception $e) {
            return $e;
        }
    }
}
