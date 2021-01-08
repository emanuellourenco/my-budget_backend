<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Transaction_Tag;

class TransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $transactions = Transaction::where("user_id", $request->user_id)->get();
        $transaction_tags = Transaction_Tag::where("user_id", $request->user_id)->get();
        
        return ["transactions" => $transactions, "transaction_tags" => $transaction_tags];
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
            $new_transaction = new Transaction;
            $new_transaction->description = $request->description;
            $new_transaction->value = $request->value;
            $new_transaction->date = $request->date;
            $new_transaction->user_id = $request->user_id;
            $new_transaction->save();

            $request->tags->each(function ($item, $key) {
                $new_transition_tag = new Transition_Tag;
                $new_transition_tag->transition_id = $new_transaction->id;
                $new_transition_tag->tag_id = $item;
                $new_transition_tag->save();
            });

            return "transition created";

        } catch(Exception $e){
            return ($e);
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
}
