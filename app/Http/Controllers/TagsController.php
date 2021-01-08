<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tag;

class TagsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $tags = Tag::where("user_id", $request->user_id)->get();

        return ["tags" => $tags];
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
            $new_tag = new Tag;
            $new_tag->name = $request->name;
            $new_tag->color = $request->color;
            $new_tag->rule = $request->rule;
            $new_tag->user_id = $request->user_id;
            $new_tag->save();

            return "tag created";

        } catch (Exception $e) {
    
            return $e;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
            $update_tag = Tag::findOrFail($id);

            if($update_tag){
                $update_tag->name = $request->name;
                $update_tag->color = $request->color;
                $update_tag->rule = $request->rule;
                $update_tag->save();

                return "tag updated";

            } else {
                return "tag not found";
            }

        } catch (Exception $e) {
    
            return $e;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $delete_tag = Tag::findOrFail($id);

            if($delete_tag){
                $delete_tag->delete();

                return "tag deleted";

            } else {
                return "tag not found";
            }

        } catch (Exception $e) {
    
            return $e;
        }
    }
}
