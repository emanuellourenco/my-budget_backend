<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Transaction_Tag;
use App\Models\User;

class TransactionsController extends Controller
{
    /**
     * Display a transactions and transactions tags lists paginated.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = new User();
        $user_by_token = $user->getUserByToken($request->token);

        if ($user_by_token) {
            $offset = !!$request->offset ? $request->offset : 0;
            $limit = 100;
            if (!!$request->limit && $request->limit < 100) {
                $limit = $request->limit;
            }
            $order_by = !!$request->orderBy ? $request->orderBy : 'name';
            $sort_by = !!$request->sortBy ? $request->sortBy : 'asc';

            $transactions = Transaction::where('user_id', $user_by_token->id)
                ->select('description', 'date', 'value', 'id as key')
                ->orderBy($order_by, $sort_by)
                ->skip($offset)
                ->take($limit)
                ->get();

            $total_count = Transaction::where(
                'user_id',
                $user_by_token->id
            )->count();

            $transaction_tags = Transaction_Tag::where(
                'user_id',
                $user_by_token->id
            )->get();

            return [
                'status' => 200,
                'transactions' => $transactions,
                'transaction_tags' => $transaction_tags,
                'total_count' => $total_count,
            ];
        }

        return [
            'status' => 401,
            'transactions' => [],
            'transaction_tags' => [],
            'total_count' => 0,
        ];
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
     * Store a newly created transaction and transactions tags and returns 
     * transactions list updated based on table limit and offset.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $data_error = [];
            $error = false;
            $description = $request->description;
            $value = $request->value;
            $date = $request->date;

            if (!$description) {
                array_push($data_error, [
                    'field' => 'description',
                    'label' => 'This field is required',
                ]);
            }
            if (!$value) {
                array_push($data_error, [
                    'field' => 'value',
                    'label' => 'This field is required',
                ]);
            }
            if (!$date) {
                array_push($data_error, [
                    'field' => 'date',
                    'label' => 'This field is required',
                ]);
            }
            if (!$description || !$value || !$date) {
                $error = true;
            }

            if (!$error) {
                $user = new User();
                $user_by_token = $user->getUserByToken($request->token);
                $offset = !!$request->offset ? $request->offset : 0;
                $limit = 100;
                if (!!$request->limit && $request->limit < 100) {
                    $limit = $request->limit;
                }
                $order_by = !!$request->orderBy ? $request->orderBy : 'name';
                $sort_by = !!$request->sortBy ? $request->sortBy : 'asc';

                if ($user_by_token) {
                    // Add new transaction
                    $new_transaction = new Transaction();
                    $new_transaction->description = $description;
                    $new_transaction->value = $value;
                    $new_transaction->date = $date;
                    $new_transaction->user_id = $user_by_token->id;
                    $new_transaction->save();

                    // Add tags to previously created transaction
                    $request->tags->each(function ($item, $key) {
                        $new_transition_tag = new Transition_Tag();
                        $new_transition_tag->transition_id =
                            $new_transaction->id;
                        $new_transition_tag->tag_id = $item;
                        $new_transition_tag->save();
                    });

                    // Get new transactions list based on offset and table limit
                    $transactions = Transaction::where(
                        'user_id',
                        $user_by_token->id
                    )
                        ->select('description', 'date', 'value', 'id as key')
                        ->orderBy($order_by, $sort_by)
                        ->skip($offset)
                        ->take($limit)
                        ->get();

                    $total_count = Transaction::where(
                        'user_id',
                        $user_by_token->id
                    )->count();

                    $transaction_tags = Transaction_Tag::where(
                        'user_id',
                        $user_by_token->id
                    )->get();

                    return [
                        'status' => 201,
                        'transactions' => $transactions,
                        'transaction_tags' => $transaction_tags,
                        'total_count' => $total_count,
                    ];
                }

                return [
                    'status' => 401,
                    'transactions' => [],
                    'transaction_tags' => [],
                    'total_count' => 0,
                ];
            }
        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * Get the specified transactions and their tags to update.
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request)
    {
        try {
            $user = new User();
            $user_by_token = $user->getUserByToken($request->token);

            if ($user_by_token) {
                $transaction = Transaction::where('user_id', $user_by_token->id)
                    ->where('id', $id)
                    ->first();

                $tags = Transaction_Tag::where(
                    'transaction_id',
                    $id->id
                )->get();

                return [
                    'status' => 200,
                    'transaction' => $transaction,
                    'tags' => $tags,
                ];
            }

            return ['status' => 401, 'transaction' => [], 'tags' => []];
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
     * Update the specified transaction and transactions tags and returns 
     * transactions list updated based on table limit and offset.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $data_error = [];
            $error = false;
            $description = $request->description;
            $value = $request->value;
            $date = $request->date;

            if (!$description) {
                array_push($data_error, [
                    'field' => 'description',
                    'label' => 'This field is required',
                ]);
            }
            if (!$value) {
                array_push($data_error, [
                    'field' => 'value',
                    'label' => 'This field is required',
                ]);
            }
            if (!$date) {
                array_push($data_error, [
                    'field' => 'date',
                    'label' => 'This field is required',
                ]);
            }
            if (!$description || !$value || !$date) {
                $error = true;
            }

            if (!$error) {
                $user = new User();
                $user_by_token = $user->getUserByToken($request->token);
                $offset = !!$request->offset ? $request->offset : 0;
                $limit = 100;
                if (!!$request->limit && $request->limit < 100) {
                    $limit = $request->limit;
                }
                $order_by = !!$request->orderBy ? $request->orderBy : 'name';
                $sort_by = !!$request->sortBy ? $request->sortBy : 'asc';

                if ($user_by_token) {
                    $update_transaction = Transaction::where(
                        'user_id',
                        $user_by_token->id
                    )
                        ->where('id', $id)
                        ->first();

                    if ($update_transaction) {
                        $update_transaction->description = $description;
                        $update_transaction->value = $value;
                        $update_transaction->date = $date;
                        $update_transaction->save();

                        $tags = $request->tags;
                        $current_tags = Transaction_Tag::where(
                            'transaction_id',
                            $id->id
                        )->get();

                        // Add tags to previously updated transaction
                        $add_tags = array_diff($tags, $current_tags);
                        foreach ($add_tags as &$add_tag) {
                            $new_Transaction_Tag = new Transaction_Tag();
                            $new_Transaction_Tag->transition_id = $id;
                            $new_Transaction_Tag->tag_id = $add_tag;
                            $new_Transaction_Tag->save();
                        }
                        // Delete tags to previously updated transaction
                        $delete_tags = array_diff($tags, $current_tags);
                        foreach ($delete_tags as &$delete_tag) {
                            $delete_transaction = Transaction::where(
                                'user_id',
                                $user_by_token->id
                            )
                                ->where('id', $id)
                                ->first();
                            $delete_transaction->delete;
                        }

                        // Get new transactions list based on offset and table limit
                        $transactions = Transaction::where(
                            'user_id',
                            $user_by_token->id
                        )
                            ->select(
                                'description',
                                'date',
                                'value',
                                'id as key'
                            )
                            ->orderBy($order_by, $sort_by)
                            ->skip($offset)
                            ->take($limit)
                            ->get();

                        $total_count = Transaction::where(
                            'user_id',
                            $user_by_token->id
                        )->count();

                        $transaction_tags = Transaction_Tag::where(
                            'user_id',
                            $user_by_token->id
                        )->get();

                        return [
                            'status' => 201,
                            'transactions' => $transactions,
                            'transaction_tags' => $transaction_tags,
                            'total_count' => $total_count,
                        ];
                    }
                }

                return ['status' => 401, 'tags' => [], 'total_count' => 0];
            }

            return [
                'status' => 404,
                'transactions' => [],
                'transaction_tags' => [],
                'total_count' => 0,
            ];
        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * Remove the specified transactions and their tags.
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, Request $request)
    {
        try {
            $user = new User();
            $user_by_token = $user->getUserByToken($request->token);
            $offset = !!$request->offset ? $request->offset : 0;
            $limit = 100;
            if (!!$request->limit && $request->limit < 100) {
                $limit = $request->limit;
            }
            $order_by = !!$request->orderBy ? $request->orderBy : 'name';
            $sort_by = !!$request->sortBy ? $request->sortBy : 'asc';

            if ($user_by_token) {
                $delete_transaction = Transaction::where(
                    'user_id',
                    $user_by_token->id
                )
                    ->where('id', $id)
                    ->first();

                if ($delete_transaction) {
                    // Get transaction tags list
                    $delete_transaction_tags = Transaction_Tag::where(
                        'transaction_id',
                        $id
                    )->get();
                    // Delete all transaction tags before delete transaction
                    foreach ($delete_transaction_tags as &$transaction_tag) {
                        $transaction_tag->delete();
                    }
                    // Delete transaction
                    $delete_transaction->delete();

                    // Get new transactions list based on offset and table limit
                    $transactions = Transaction::where(
                        'user_id',
                        $user_by_token->id
                    )
                        ->select('description', 'date', 'value', 'id as key')
                        ->orderBy($order_by, $sort_by)
                        ->skip($offset)
                        ->take($limit)
                        ->get();

                    $total_count = Transaction::where(
                        'user_id',
                        $user_by_token->id
                    )->count();

                    $transaction_tags = Transaction_Tag::where(
                        'user_id',
                        $user_by_token->id
                    )->get();

                    return [
                        'status' => 201,
                        'transactions' => $transactions,
                        'transaction_tags' => $transaction_tags,
                        'total_count' => $total_count,
                    ];
                }
            }

            return [
                'status' => 404,
                'transactions' => [],
                'transaction_tags' => [],
                'total_count' => 0,
            ];
        } catch (Exception $e) {
            return $e;
        }
    }
}
