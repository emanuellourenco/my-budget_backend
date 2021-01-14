<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tag;
use App\Models\User;

class TagsController extends Controller
{
    /**
     * Function to list tags and return an array with tags list and tags count.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = new User;
        $user_by_token = $user->getUserByToken($request->token);
        $offset = !!$request->offset ? $request->offset : 0;
        $limit = 100;
        if(!!$request->limit && $request->limit < 100){
            $limit = $request->limit;
        }
        $order_by = !!$request->orderBy ? $request->orderBy : "name";
        $sort_by = !!$request->sortBy ? $request->sortBy : "asc";

        $tags = !!$user_by_token ? 
                    Tag::where("user_id", $user_by_token->id)
                        ->select("name", "color", "id as key", "rule")
                        ->orderBy($order_by, $sort_by)
                        ->skip($offset)
                        ->take($limit)
                        ->get() 
                : 
                    [];

        $total_count = !!$user_by_token ? 
                    Tag::where("user_id", $user_by_token->id)->count() : 0;            

        return ["tags" => $tags, "total_count" => $total_count];
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {        
        try {
            $data_error = [];
            $error = false;
            $name = $request->name;
            $color = $request->color;
            $rule = $request->rule;

            if(!$name){ 
                array_push($data_error, ["field"=> "name", "label" => "This field is required"]);
            }
            if(!$color){ 
                array_push($data_error, ["field"=> "color", "label" => "This field is required"]);
            }
            if(!$rule){ 
                array_push($data_error, ["field"=> "rule", "label" => "This field is required"]);
            }
            if(!$name || !$color || !$rule){ 
                $error = true;
            };

            if(!$error){
                $user = new User;
                $user_by_token = $user->getUserByToken($request->token);
                $offset = !!$request->offset ? $request->offset : 0;
                $limit = 100;
                if(!!$request->limit && $request->limit < 100){
                    $limit = $request->limit;
                }
                $order_by = !!$request->orderBy ? $request->orderBy : "name";
                $sort_by = !!$request->sortBy ? $request->sortBy : "asc";

                if($user_by_token){
                    $new_tag = new Tag;
                    $new_tag->name = $request->name;
                    $new_tag->color = $request->color;
                    $new_tag->rule = $request->rule;
                    $new_tag->user_id = $user_by_token->id;
                    $new_tag->save();
        
                    $tags = Tag::where("user_id", $user_by_token->id)
                                ->select("name", "color", "id as key", "rule")
                                ->orderBy($order_by, $sort_by)
                                ->skip($offset)
                                ->take($limit)
                                ->get();

                    $total_count = Tag::where("user_id", $user_by_token->id)->count(); 
                    
                    return ["status"=> "success", "tags" => $tags, "total_count" => $total_count];
                }
            } else {
                return ["status"=> "error", "data_error" => $data_error];
            }


        } catch (Exception $e) {
    
            return $e;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function show($id,Request $request)
    {
        try {
            $user = new User;
            $user_by_token = $user->getUserByToken($request->token);

            if($user_by_token){
                $tag = Tag::where("user_id", $user_by_token->id)->where("id", $id)->first();

                return ["status"=> "success", "tag" => $tag];
            }
            
            return ["status"=> "error", "tag" => [], "user_by_token" => $user_by_token, $id, $request];

        } catch (Exception $e) {
    
            return $e;
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        try {
            $user = new User;
            $user_by_token = $user->getUserByToken($request->token);
            $offset = !!$request->offset ? $request->offset : 0;
            $limit = 100;
            if(!!$request->limit && $request->limit < 100){
                $limit = $request->limit;
            }
            $order_by = !!$request->orderBy ? $request->orderBy : "name";
            $sort_by = !!$request->sortBy ? $request->sortBy : "asc";

            if($user_by_token){
                $update_tag = Tag::where("user_id", $user_by_token->id)->where("id",$id)->first();

                if($update_tag){
                    $update_tag->name = $request->name;
                    $update_tag->color = $request->color;
                    $update_tag->rule = $request->rule;
                    $update_tag->save();

                    $tags = Tag::where("user_id", $user_by_token->id)
                                ->select("name", "color", "id as key", "rule")
                                ->orderBy($order_by, $sort_by)
                                ->skip($offset)
                                ->take($limit)
                                ->get();

                    $total_count = Tag::where("user_id", $user_by_token->id)->count(); 

                    return ["status"=> "success", "tags" => $tags, "total_count" => $total_count];
                } else {
                    return "tag not found";
                }
            }

        } catch (Exception $e) {
    
            return $e;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy($id,Request $request)
    {
        try {
            $user = new User;
            $user_by_token = $user->getUserByToken($request->token);
            $offset = !!$request->offset ? $request->offset : 0;
            $limit = 100;
            if(!!$request->limit && $request->limit < 100){
                $limit = $request->limit;
            }
            $order_by = !!$request->orderBy ? $request->orderBy : "name";
            $sort_by = !!$request->sortBy ? $request->sortBy : "asc";

            if($user_by_token){
                
                $delete_tag = Tag::where("user_id", $user_by_token->id)->where("id", $id)->first();

                if($delete_tag){
                    $delete_tag->delete();

                    $tags = Tag::where("user_id", $user_by_token->id)
                                    ->select("name", "color", "id as key", "rule")
                                    ->orderBy($order_by, $sort_by)
                                    ->skip($offset)
                                    ->take($limit)
                                    ->get();

                    $total_count = Tag::where("user_id", $user_by_token->id)->count(); 

                    return ["status"=> "success", "tags" => $tags, "total_count" => $total_count];
                } else {
                    return "tag not found";
                }
            }

        } catch (Exception $e) {
    
            return $e;
        }
    }
}