<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\TransactionsTag;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class TransactionsController extends Controller
{
    /**
     *
     */
    public function getList($request)
    {
        $user = new User();
        $user_by_token = $user->getUserByToken($request->token);

        if ($user_by_token) {
            $offset = !!$request->offset ? $request->offset : 0;
            $limit = 100;
            if (!!$request->limit && $request->limit < 100) {
                $limit = $request->limit;
            }
            $order_by = !!$request->orderBy ? $request->orderBy : 'date';
            $sort_by = !!$request->sortBy ? $request->sortBy : 'desc';

            $transactions = Transaction::where('user_id', $user_by_token->id)
                ->select('description', 'date', 'value', 'type', 'id as key')
                ->orderBy($order_by, $sort_by)
                ->skip($offset)
                ->take($limit)
                ->get();

            // Get all tags from each transaction
            foreach ($transactions as &$transaction) {
                $tags = TransactionsTag::with('tag')
                    ->where('transaction_id', $transaction->key)
                    ->get();
                $transaction->tags = $tags;
                $transaction->date = Carbon::parse($transaction->date)->format(
                    'Y-m-d'
                );
            }

            $total_count = Transaction::where(
                'user_id',
                $user_by_token->id
            )->count();
        }

        return ['transactions' => $transactions, 'total_count' => $total_count];
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
     * Display a transactions and transactions tags lists paginated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $list = $this->getList($request);
        $transactions = $list['transactions'];
        $total_count = $list['total_count'];

        return ['transactions' => $transactions, 'total_count' => $total_count];
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
            $date = Carbon::parse($request->date)->format('Y/m/d');
            $requiredFields = ['description', 'value', 'type', 'date'];
            $validateFields = $this->checkFields($request, $requiredFields);
            $data_error = $validateFields['data_error'];

            if (!$validateFields['error']) {
                $user = new User();
                $user_by_token = $user->getUserByToken($request->token);
                $offset = !!$request->offset ? $request->offset : 0;
                $limit = 100;
                if (!!$request->limit && $request->limit < 100) {
                    $limit = $request->limit;
                }
                $order_by = !!$request->orderBy ? $request->orderBy : 'date';
                $sort_by = !!$request->sortBy ? $request->sortBy : 'desc';

                if ($user_by_token) {
                    // Add new transaction
                    $new_transaction = new Transaction();
                    $new_transaction->description = $description;
                    $new_transaction->value = $request->value;
                    $new_transaction->type = $request->type;
                    $new_transaction->date = $date;
                    $new_transaction->user_id = $user_by_token->id;
                    $new_transaction->save();

                    // Add tags to previously created transaction
                    $tags = $request->tags;
                    if (count($tags) > 0) {
                        foreach ($tags as &$tag) {
                            $new_transition_tag = new TransactionsTag();
                            $new_transition_tag->transaction_id =
                                $new_transaction->id;
                            $new_transition_tag->tag_id = $tag;
                            $new_transition_tag->save();
                        }
                    }

                    // Get new transactions list based on offset and table limit
                    $list = $this->getList($request);
                    $transactions = $list['transactions'];
                    $total_count = $list['total_count'];

                    return [
                        'status' => 201,
                        'transactions' => $transactions,
                        'total_count' => $total_count,
                    ];
                }
            }

            return [
                'status' => 401,
                'transactions' => [],
                'total_count' => 0,
                'error' => $data_error,
            ];
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

                // Get all tags from each transaction
                $tags = DB::table('transactions_tags as tt')
                    ->join('tags as t', 't.id', '=', 'tt.tag_id')
                    ->where('tt.transaction_id', $id)
                    ->pluck('t.id');

                $transaction->tags = $tags;

                return ['status' => 200, 'transaction' => $transaction];
            }

            return ['status' => 401, 'transaction' => []];
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
            $date = Carbon::parse($request->date)->format('Y/m/d');
            $requiredFields = ['description', 'value', 'type', 'date'];
            $validateFields = $this->checkFields($request, $requiredFields);
            $data_error = $validateFields['data_error'];

            if (!$validateFields['error']) {
                $user = new User();
                $user_by_token = $user->getUserByToken($request->token);
                $offset = !!$request->offset ? $request->offset : 0;
                $limit = 100;
                if (!!$request->limit && $request->limit < 100) {
                    $limit = $request->limit;
                }
                $order_by = !!$request->orderBy ? $request->orderBy : 'date';
                $sort_by = !!$request->sortBy ? $request->sortBy : 'desc';

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

                        $current_tags = TransactionsTag::where(
                            'transaction_id',
                            $id
                        )->pluck('tag_id');

                        $current_tags = $current_tags->toArray();

                        // Add tags to previously updated transaction
                        $add_tags = array_diff($tags, $current_tags);
                        foreach ($add_tags as &$add_tag) {
                            $new_Transaction_Tag = new TransactionsTag();
                            $new_Transaction_Tag->transaction_id = $id;
                            $new_Transaction_Tag->tag_id = $add_tag;
                            $new_Transaction_Tag->save();
                        }
                        // Delete tags to previously updated transaction
                        $delete_tags = array_diff($current_tags, $tags);
                        foreach ($delete_tags as &$delete_tag) {
                            $delete_transaction = TransactionsTag::where(
                                'tag_id',
                                $delete_tag
                            )
                                ->where('transaction_id', $id)
                                ->first();

                            if ($delete_transaction) {
                                $delete_transaction->delete();
                            }
                        }

                        // Get new transactions list based on offset and table limit
                        $list = $this->getList($request);
                        $transactions = $list['transactions'];
                        $total_count = $list['total_count'];

                        return [
                            'transactions' => $transactions,
                            'total_count' => $total_count,
                        ];
                    }
                }
            }

            return ['transactions' => [], 'total_count' => 0];
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
            $order_by = !!$request->orderBy ? $request->orderBy : 'date';
            $sort_by = !!$request->sortBy ? $request->sortBy : 'desc';

            if ($user_by_token) {
                $delete_transaction = Transaction::where(
                    'user_id',
                    $user_by_token->id
                )
                    ->where('id', $id)
                    ->first();

                if ($delete_transaction) {
                    // Get transaction tags list
                    $delete_transaction_tags = TransactionsTag::where(
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
                    $list = $this->getList($request);
                    $transactions = $list['transactions'];
                    $total_count = $list['total_count'];

                    return [
                        'transactions' => $transactions,
                        'total_count' => $total_count,
                    ];
                }
            }

            return ['transactions' => [], 'total_count' => 0];
        } catch (Exception $e) {
            return $e;
        }
    }
}
